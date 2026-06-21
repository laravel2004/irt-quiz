<?php

namespace App\Services;

use App\Repositories\ExamSessionRepository;
use App\Models\QuestionBank;
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
        return DB::transaction(function () use ($data) {
            $session = $this->repository->create($data);
            
            foreach ($data['categories'] as $cat) {
                $sessionCat = \App\Models\ExamSessionCategory::create([
                    'exam_session_id' => $session->id,
                    'category_id' => $cat['id'],
                    'duration' => $cat['duration'],
                    'total_questions' => $cat['total_questions'],
                    'max_score_raw' => $cat['max_score_raw'] ?? 100,
                    'max_score_irt' => $cat['max_score_irt'] ?? 1000
                ]);

                if (isset($cat['sub_categories'])) {
                    foreach ($cat['sub_categories'] as $sub) {
                        \App\Models\ExamSessionSubCategory::create([
                            'exam_session_category_id' => $sessionCat->id,
                            'sub_category_id' => $sub['id'],
                            'percentage' => $sub['percentage']
                        ]);
                    }
                }
            }
            $this->generateSessionQuestions($session->id);
            
            return $session;
        });
    }

    public function updateWithCategories(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $session = $this->repository->update($id, $data);
            
            \App\Models\ExamSessionCategory::where('exam_session_id', $id)->delete();
            
            foreach ($data['categories'] as $cat) {
                $sessionCat = \App\Models\ExamSessionCategory::create([
                    'exam_session_id' => $id,
                    'category_id' => $cat['id'],
                    'duration' => $cat['duration'],
                    'total_questions' => $cat['total_questions'],
                    'max_score_raw' => $cat['max_score_raw'] ?? 100,
                    'max_score_irt' => $cat['max_score_irt'] ?? 1000
                ]);

                if (isset($cat['sub_categories'])) {
                    foreach ($cat['sub_categories'] as $sub) {
                        \App\Models\ExamSessionSubCategory::create([
                            'exam_session_category_id' => $sessionCat->id,
                            'sub_category_id' => $sub['id'],
                            'percentage' => $sub['percentage']
                        ]);
                    }
                }
            }
            $this->generateSessionQuestions($id);
            
            return $session;
        });
    }

    public function enrollQuestions(int $sessionId, array $questionIds)
    {
        $session = $this->repository->find($sessionId);
        if (!$session) return false;
        
        $session->questions()->sync($questionIds);
        return true;
    }

    public function generateSessionQuestions(int $sessionId)
    {
        $session = $this->repository->find($sessionId);
        if (!$session) return;
        
        $session->load('sessionCategories.subCategories');

        $allSelectedIds = [];

        foreach ($session->sessionCategories as $sc) {
            $catQuestionIds = [];

            foreach ($sc->subCategories as $subCat) {
                $count = (int) round(($subCat->percentage / 100) * $sc->total_questions);
                if ($count <= 0) {
                    continue;
                }

                $questionIds = $this->pickQuestionsForSubCategory(
                    $sc->category_id,
                    $subCat->sub_category_id,
                    $count,
                    $catQuestionIds
                );

                $catQuestionIds = array_merge($catQuestionIds, $questionIds);
            }

            if (count($catQuestionIds) < $sc->total_questions) {
                $missingCount = $sc->total_questions - count($catQuestionIds);

                $missingQuestionIds = QuestionBank::where('category_id', $sc->category_id)
                    ->whereNotIn('id', $catQuestionIds)
                    ->inRandomOrder()
                    ->limit($missingCount)
                    ->pluck('id')
                    ->toArray();

                $catQuestionIds = array_merge($catQuestionIds, $missingQuestionIds);
            }

            if (count($catQuestionIds) < $sc->total_questions) {
                $missingCount = $sc->total_questions - count($catQuestionIds);
                $allCatQuestions = QuestionBank::where('category_id', $sc->category_id)
                    ->pluck('id')
                    ->toArray();

                if (!empty($allCatQuestions)) {
                    for ($i = 0; $i < $missingCount; $i++) {
                        $catQuestionIds[] = $allCatQuestions[array_rand($allCatQuestions)];
                    }
                }
            }

            $allSelectedIds = array_merge($allSelectedIds, $catQuestionIds);
        }

        DB::table('session_questions')->where('exam_session_id', $session->id)->delete();

        $insertData = [];
        $now = now();
        foreach ($allSelectedIds as $qId) {
            $insertData[] = [
                'exam_session_id' => $session->id,
                'question_bank_id' => $qId,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        
        if (!empty($insertData)) {
            foreach (array_chunk($insertData, 500) as $chunk) {
                DB::table('session_questions')->insert($chunk);
            }
        }
    }

    private function pickQuestionsForSubCategory(int $categoryId, int $subCategoryId, int $requiredCount, array $excludedIds = []): array
    {
        $questions = QuestionBank::where('category_id', $categoryId)
            ->where('sub_category_id', $subCategoryId)
            ->whereNotIn('id', $excludedIds)
            ->get();

        if ($questions->isEmpty()) {
            return [];
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

        if (count($selectedIds) < $requiredCount) {
            $allQuestionIds = $questions->pluck('id')->values()->all();
            while (count($selectedIds) < $requiredCount && !empty($allQuestionIds)) {
                $selectedIds[] = $allQuestionIds[array_rand($allQuestionIds)];
            }
        }

        return array_values($selectedIds);
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

            if (!$pickedInThisRound) {
                break;
            }
        }

        return array_values(array_unique($selectedIds));
    }
}
