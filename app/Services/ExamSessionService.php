<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionSubCategory;
use App\Models\QuestionBank;
use App\Models\SubCategory;
use App\Repositories\ExamSessionRepository;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamSessionService extends BaseService
{
    public function __construct(ExamSessionRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createWithCategories(array $data)
    {
        $data['is_lock_quiz'] ??= false;
        $data = $this->withScoringDefaults($data);
        $this->validateCategoryData($data['categories']);
        $this->validateScoringData($data);

        return DB::transaction(function () use ($data) {
            $session = $this->repository->create($data);
            $this->createCategories($session->id, $data['categories']);
            $this->generateSessionQuestions($session->id);

            return $session;
        }, 3);
    }

    public function updateWithCategories(int $id, array $data)
    {
        $data['is_lock_quiz'] ??= false;
        $data = $this->withScoringDefaults($data);
        $this->validateCategoryData($data['categories']);
        $this->validateScoringData($data);

        return DB::transaction(function () use ($id, $data) {
            $session = ExamSession::with('sessionCategories.subCategories')
                ->lockForUpdate()
                ->findOrFail($id);
            $allocationChanged = $this->allocationSignature($session) !== $this->allocationSignature($data);
            $scoringChanged = $this->scoringSignature($session) !== $this->scoringSignature($data);
            $mustRegenerate = $allocationChanged || (! $session->is_lock_quiz && $data['is_lock_quiz']);

            if ($mustRegenerate && $session->participants()->whereNotNull('started_at')->exists()) {
                throw new DomainException('Sesi yang sudah mulai dikerjakan tidak dapat mengubah kumpulan soal atau mengunci soal.');
            }

            if ($scoringChanged && $session->participants()->whereNotNull('started_at')->exists()) {
                throw new DomainException('Konfigurasi nilai tidak dapat diubah karena sesi sudah mulai dikerjakan.');
            }

            $wasLocked = $session->is_lock_quiz;
            $session->update(collect($data)->except('categories')->all());

            if ($allocationChanged) {
                ExamSessionCategory::where('exam_session_id', $id)->delete();
                $this->createCategories($id, $data['categories']);
            } else {
                $this->updateCategoryMetadata($session, $data['categories']);
            }
            if ($mustRegenerate) {
                $this->generateSessionQuestions($session->id);
            } elseif ($wasLocked && ! $session->is_lock_quiz) {
                QuestionBank::where('locked_by_exam_session_id', $session->id)
                    ->update(['locked_by_exam_session_id' => null]);
            }

            return $session;
        }, 3);
    }

    public function generateSessionQuestions(int $sessionId)
    {
        return DB::transaction(function () use ($sessionId) {
            $session = ExamSession::query()
                ->with('sessionCategories.category', 'sessionCategories.subCategories.subCategory')
                ->lockForUpdate()
                ->findOrFail($sessionId);

            $this->validateSessionConfiguration($session);

            if ($session->participants()->whereNotNull('started_at')->exists()) {
                throw new DomainException('Sesi yang sudah mulai dikerjakan tidak dapat generate ulang soal.');
            }

            $selectedIds = [];

            foreach ($session->sessionCategories->sortBy('category_id') as $sessionCategory) {
                $subCategories = $sessionCategory->subCategories->values();

                if ($subCategories->isEmpty()) {
                    $selectedIds = array_merge($selectedIds, $this->pickQuestions(
                        $this->availableQuestions($session, $sessionCategory->category_id, null, $selectedIds),
                        $sessionCategory->total_questions,
                        $sessionCategory->category->name ?? 'mata pelajaran'
                    ));

                    continue;
                }

                foreach ($this->allocateQuestionCounts($subCategories, $sessionCategory->total_questions) as $index => $count) {
                    if ($count === 0) {
                        continue;
                    }

                    $subCategory = $subCategories[$index];
                    $selectedIds = array_merge($selectedIds, $this->pickQuestions(
                        $this->availableQuestions($session, $sessionCategory->category_id, $subCategory->sub_category_id, $selectedIds),
                        $count,
                        $subCategory->subCategory->name ?? 'sub mata pelajaran'
                    ));
                }
            }

            $selectedIds = array_values(array_unique($selectedIds));
            $this->syncGeneratedQuestions($session, $selectedIds);
        }, 3);
    }

    private function availableQuestions(ExamSession $session, int $categoryId, ?int $subCategoryId, array $excludedIds): Collection
    {
        $query = QuestionBank::query()
            ->availableForSession($session->id)
            ->where('category_id', $categoryId)
            ->whereNotIn('id', $excludedIds);

        if ($subCategoryId !== null) {
            $query->where('sub_category_id', $subCategoryId);
        } else {
            $query->whereNull('sub_category_id');
        }

        // ponytail: locks all eligible candidates in a category; use narrower reservations if banks become very large.
        return $query->orderBy('id')->lockForUpdate()->get();
    }

    private function allocateQuestionCounts(Collection $subCategories, int $totalQuestions): array
    {
        $allocations = $subCategories->values()->map(function ($subCategory, $index) use ($totalQuestions) {
            $rawCount = ($subCategory->percentage / 100) * $totalQuestions;
            $baseCount = (int) floor($rawCount);

            return [
                'index' => $index,
                'count' => $baseCount,
                'remainder' => $rawCount - $baseCount,
            ];
        })->all();

        $remaining = $totalQuestions - array_sum(array_column($allocations, 'count'));
        usort($allocations, fn ($left, $right) => $right['remainder'] <=> $left['remainder'] ?: $left['index'] <=> $right['index']);

        for ($index = 0; $index < $remaining; $index++) {
            $allocations[$index]['count']++;
        }

        usort($allocations, fn ($left, $right) => $left['index'] <=> $right['index']);

        return array_column($allocations, 'count');
    }

    private function pickQuestions(Collection $questions, int $requiredCount, string $label): array
    {
        if ($questions->count() < $requiredCount) {
            throw new DomainException("Soal tersedia untuk {$label} hanya {$questions->count()}, sedangkan sesi membutuhkan {$requiredCount} soal.");
        }

        $codedQuestions = $questions->filter(fn ($question) => filled($question->kode_soal));
        $ungroupedQuestions = $questions->filter(fn ($question) => blank($question->kode_soal));

        $selectedIds = [];

        if ($codedQuestions->isNotEmpty()) {
            $groupedByKode = $codedQuestions
                ->groupBy('kode_soal')
                ->map(function (Collection $items) {
                    return $items->shuffle()->values()->pluck('id')->values()->all();
                })
                ->all();

            $selectedIds = $this->pickFromKodeGroups($groupedByKode, $requiredCount);
        }

        if (count($selectedIds) < $requiredCount && $ungroupedQuestions->isNotEmpty()) {
            $remainingNeeded = $requiredCount - count($selectedIds);
            $ungroupedIds = $ungroupedQuestions
                ->whereNotIn('id', $selectedIds)
                ->shuffle()
                ->pluck('id')
                ->take($remainingNeeded)
                ->values()
                ->toArray();

            $selectedIds = array_merge($selectedIds, $ungroupedIds);
        }

        if (count($selectedIds) < $requiredCount) {
            $remainingNeeded = $requiredCount - count($selectedIds);
            $remainingUniqueIds = $questions
                ->whereNotIn('id', $selectedIds)
                ->shuffle()
                ->pluck('id')
                ->take($remainingNeeded)
                ->values()
                ->toArray();

            $selectedIds = array_merge($selectedIds, $remainingUniqueIds);
        }

        return array_values($selectedIds);
    }

    private function syncGeneratedQuestions(ExamSession $session, array $questionIds): void
    {
        if ($session->is_lock_quiz) {
            QuestionBank::where('locked_by_exam_session_id', $session->id)
                ->when($questionIds, fn ($query) => $query->whereNotIn('id', $questionIds))
                ->update(['locked_by_exam_session_id' => null]);

            QuestionBank::whereIn('id', $questionIds)
                ->whereNull('locked_by_exam_session_id')
                ->update(['locked_by_exam_session_id' => $session->id]);

            if (QuestionBank::whereIn('id', $questionIds)
                ->where('locked_by_exam_session_id', $session->id)
                ->count() !== count($questionIds)) {
                throw new DomainException('Sebagian soal baru saja dikunci oleh sesi lain. Silakan coba generate ulang.');
            }
        } else {
            QuestionBank::where('locked_by_exam_session_id', $session->id)
                ->update(['locked_by_exam_session_id' => null]);
        }

        DB::table('session_questions')->where('exam_session_id', $session->id)->delete();

        $now = now();
        $rows = array_map(fn ($questionId) => [
            'exam_session_id' => $session->id,
            'question_bank_id' => $questionId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $questionIds);

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('session_questions')->insert($chunk);
        }
    }

    private function allocationSignature(ExamSession|array $source): string
    {
        if ($source instanceof ExamSession) {
            $categories = $source->sessionCategories->sortBy('category_id')->map(fn ($category) => [
                'id' => $category->category_id,
                'total_questions' => $category->total_questions,
                'sub_categories' => $category->subCategories->map(fn ($subCategory) => [
                    'id' => $subCategory->sub_category_id,
                    'percentage' => $subCategory->percentage,
                ])->values()->all(),
            ])->values()->all();
        } else {
            $categories = collect($source['categories'])->sortBy('id')->map(fn ($category) => [
                'id' => (int) $category['id'],
                'total_questions' => (int) $category['total_questions'],
                'sub_categories' => collect($category['sub_categories'] ?? [])->map(fn ($subCategory) => [
                    'id' => (int) $subCategory['id'],
                    'percentage' => (int) $subCategory['percentage'],
                ])->values()->all(),
            ])->values()->all();
        }

        return json_encode($categories, JSON_THROW_ON_ERROR);
    }

    private function createCategories(int $sessionId, array $categories): void
    {
        foreach ($categories as $category) {
            $sessionCategory = ExamSessionCategory::create([
                'exam_session_id' => $sessionId,
                'category_id' => $category['id'],
                'duration' => $category['duration'],
                'total_questions' => $category['total_questions'],
                'max_score_raw' => $category['max_score_raw'] ?? 100,
                'min_score_irt' => $category['min_score_irt'] ?? 0,
                'max_score_irt' => $category['max_score_irt'] ?? 1000,
            ]);

            foreach ($category['sub_categories'] ?? [] as $subCategory) {
                ExamSessionSubCategory::create([
                    'exam_session_category_id' => $sessionCategory->id,
                    'sub_category_id' => $subCategory['id'],
                    'percentage' => $subCategory['percentage'],
                ]);
            }
        }
    }

    private function updateCategoryMetadata(ExamSession $session, array $categories): void
    {
        $sessionCategories = $session->sessionCategories->keyBy('category_id');

        foreach ($categories as $category) {
            $sessionCategories[$category['id']]->update([
                'duration' => $category['duration'],
                'max_score_raw' => $category['max_score_raw'] ?? 100,
                'min_score_irt' => $category['min_score_irt'] ?? 0,
                'max_score_irt' => $category['max_score_irt'] ?? 1000,
            ]);
        }
    }

    private function withScoringDefaults(array $data): array
    {
        $data['participant_score_display'] ??= 'irt';

        foreach ($data['categories'] as &$category) {
            $category['min_score_irt'] ??= 0;
        }
        unset($category);

        $totalMin = (float) collect($data['categories'])->sum('min_score_irt');
        $totalMax = (float) collect($data['categories'])->sum('max_score_irt');
        $range = $totalMax - $totalMin;

        $data['predicate_kurang_min'] ??= $totalMin;
        $data['predicate_memadai_min'] ??= round($totalMin + ($range * 0.50), 2);
        $data['predicate_baik_min'] ??= round($totalMin + ($range * 0.70), 2);
        $data['predicate_istimewa_min'] ??= round($totalMin + ($range * 0.85), 2);

        $totalRawMax = (float) collect($data['categories'])->sum('max_score_raw');
        $data['predicate_raw_kurang_min'] ??= 0;
        $data['predicate_raw_memadai_min'] ??= round($totalRawMax * 0.50, 2);
        $data['predicate_raw_baik_min'] ??= round($totalRawMax * 0.70, 2);
        $data['predicate_raw_istimewa_min'] ??= round($totalRawMax * 0.85, 2);

        return $data;
    }

    private function validateScoringData(array $data): void
    {
        foreach ($data['categories'] as $category) {
            if (! is_numeric($category['min_score_irt'])
                || ! is_numeric($category['max_score_irt'])
                || (float) $category['min_score_irt'] < 0
                || (float) $category['max_score_irt'] <= (float) $category['min_score_irt']) {
                throw new DomainException('Batas atas IRT harus lebih besar dari batas bawah IRT.');
            }
        }

        $totalMin = round((float) collect($data['categories'])->sum('min_score_irt'), 2);
        $totalMax = round((float) collect($data['categories'])->sum('max_score_irt'), 2);
        $thresholds = collect([
            $data['predicate_kurang_min'],
            $data['predicate_memadai_min'],
            $data['predicate_baik_min'],
            $data['predicate_istimewa_min'],
        ])->map(fn ($value) => round((float) $value, 2))->all();

        if ($thresholds[0] !== $totalMin) {
            throw new DomainException("Nilai minimum predikat Kurang harus sama dengan total batas bawah IRT, yaitu {$totalMin}.");
        }

        if (! ($thresholds[0] < $thresholds[1]
            && $thresholds[1] < $thresholds[2]
            && $thresholds[2] < $thresholds[3])) {
            throw new DomainException('Urutan ambang predikat harus Kurang < Memadai < Baik < Istimewa.');
        }

        if ($thresholds[3] > $totalMax) {
            throw new DomainException("Ambang Istimewa tidak boleh melebihi total batas atas IRT, yaitu {$totalMax}.");
        }

        if (! in_array($data['participant_score_display'], ['raw', 'irt', 'both'], true)) {
            throw new DomainException('Pilihan tampilan skor peserta tidak valid.');
        }

        $totalRawMax = round((float) collect($data['categories'])->sum('max_score_raw'), 2);
        $rawThresholds = collect([
            $data['predicate_raw_kurang_min'],
            $data['predicate_raw_memadai_min'],
            $data['predicate_raw_baik_min'],
            $data['predicate_raw_istimewa_min'],
        ])->map(fn ($value) => round((float) $value, 2))->all();

        if ($rawThresholds[0] !== 0.0
            || ! ($rawThresholds[0] < $rawThresholds[1]
                && $rawThresholds[1] < $rawThresholds[2]
                && $rawThresholds[2] < $rawThresholds[3])
            || $rawThresholds[3] > $totalRawMax) {
            throw new DomainException('Konfigurasi Predikat Raw tidak valid.');
        }
    }

    private function scoringSignature(ExamSession|array $source): string
    {
        if ($source instanceof ExamSession) {
            $categories = $source->sessionCategories->sortBy('category_id')->map(fn ($category) => [
                'id' => $category->category_id,
                'raw_max' => number_format((float) $category->max_score_raw, 2, '.', ''),
                'min' => number_format((float) $category->min_score_irt, 2, '.', ''),
                'max' => number_format((float) $category->max_score_irt, 2, '.', ''),
            ])->values()->all();
            $thresholds = collect([
                $source->predicate_kurang_min,
                $source->predicate_memadai_min,
                $source->predicate_baik_min,
                $source->predicate_istimewa_min,
            ]);
            $rawThresholds = collect([
                $source->predicate_raw_kurang_min,
                $source->predicate_raw_memadai_min,
                $source->predicate_raw_baik_min,
                $source->predicate_raw_istimewa_min,
            ]);
            $display = $source->participant_score_display;
        } else {
            $categories = collect($source['categories'])->sortBy('id')->map(fn ($category) => [
                'id' => (int) $category['id'],
                'raw_max' => number_format((float) $category['max_score_raw'], 2, '.', ''),
                'min' => number_format((float) $category['min_score_irt'], 2, '.', ''),
                'max' => number_format((float) $category['max_score_irt'], 2, '.', ''),
            ])->values()->all();
            $thresholds = collect([
                $source['predicate_kurang_min'],
                $source['predicate_memadai_min'],
                $source['predicate_baik_min'],
                $source['predicate_istimewa_min'],
            ]);
            $rawThresholds = collect([
                $source['predicate_raw_kurang_min'],
                $source['predicate_raw_memadai_min'],
                $source['predicate_raw_baik_min'],
                $source['predicate_raw_istimewa_min'],
            ]);
            $display = $source['participant_score_display'];
        }

        return json_encode([
            'categories' => $categories,
            'thresholds' => $thresholds->map(fn ($value) => number_format((float) $value, 2, '.', ''))->all(),
            'raw_thresholds' => $rawThresholds->map(fn ($value) => number_format((float) $value, 2, '.', ''))->all(),
            'participant_score_display' => $display,
        ], JSON_THROW_ON_ERROR);
    }

    private function validateCategoryData(array $categories): void
    {
        if (count($categories) !== collect($categories)->pluck('id')->unique()->count()) {
            throw new DomainException('Mata pelajaran tidak boleh dipilih lebih dari satu kali.');
        }

        foreach ($categories as $category) {
            $subCategories = $category['sub_categories'] ?? [];
            $subCategoryIds = collect($subCategories)->pluck('id');

            if ($subCategoryIds->isEmpty()) {
                if (SubCategory::where('category_id', $category['id'])->exists()) {
                    throw new DomainException('Pilih sub mata pelajaran untuk setiap mata pelajaran yang memilikinya.');
                }

                continue;
            }

            if ($subCategoryIds->count() !== $subCategoryIds->unique()->count()
                || collect($subCategories)->sum('percentage') !== 100
                || SubCategory::whereIn('id', $subCategoryIds)->where('category_id', $category['id'])->count() !== $subCategoryIds->count()) {
                throw new DomainException('Konfigurasi sub mata pelajaran tidak valid.');
            }
        }
    }

    private function validateSessionConfiguration(ExamSession $session): void
    {
        $categories = $session->sessionCategories->map(fn ($category) => [
            'id' => $category->category_id,
            'sub_categories' => $category->subCategories->map(fn ($subCategory) => [
                'id' => $subCategory->sub_category_id,
                'percentage' => $subCategory->percentage,
            ])->all(),
        ])->all();

        $this->validateCategoryData($categories);
    }

    private function pickFromKodeGroups(array $kodeGroups, int $requiredCount): array
    {
        $kodeKeys = array_keys($kodeGroups);
        shuffle($kodeKeys);

        $selectedIds = [];

        while (count($selectedIds) < $requiredCount) {
            $pickedInThisRound = false;

            foreach ($kodeKeys as $kode) {
                if (count($selectedIds) >= $requiredCount) {
                    break;
                }

                if (empty($kodeGroups[$kode])) {
                    continue;
                }

                $selectedIds[] = array_shift($kodeGroups[$kode]);
                $pickedInThisRound = true;
            }

            if (! $pickedInThisRound) {
                break;
            }
        }

        return array_values(array_unique($selectedIds));
    }
}
