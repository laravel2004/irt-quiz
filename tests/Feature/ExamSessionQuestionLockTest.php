<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamSessionParticipant;
use App\Models\ParticipantCategoryStatus;
use App\Models\QuestionBank;
use App\Models\SubCategory;
use App\Services\ExamSessionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamSessionQuestionLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_session_reserves_questions_for_future_sessions(): void
    {
        [$category, $subCategory] = $this->makeCategoryWithSubCategory('Aljabar');
        $this->makeQuestions($category->id, $subCategory->id, 2);

        $service = app(ExamSessionService::class);
        $first = $service->createWithCategories($this->sessionData($category->id, $subCategory->id, 2, true));

        $this->assertSame(2, $first->questions()->count());
        $this->assertSame(2, QuestionBank::where('locked_by_exam_session_id', $first->id)->count());

        $this->expectException(DomainException::class);
        $service->createWithCategories($this->sessionData($category->id, $subCategory->id, 1, true));
    }

    public function test_percentages_use_largest_remainder_without_cross_subcategory_fallback(): void
    {
        $category = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);
        $subCategories = collect(['Aljabar', 'Geometri', 'Statistika'])->map(function ($name) use ($category) {
            $subCategory = SubCategory::create([
                'category_id' => $category->id,
                'name' => $name,
                'slug' => strtolower($name),
            ]);

            $this->makeQuestions($category->id, $subCategory->id, 10);

            return $subCategory;
        });

        $session = app(ExamSessionService::class)->createWithCategories([
            'name' => 'Pembagian Persentase',
            'code' => 'PERSEN01',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_lock_quiz' => false,
            'categories' => [[
                'id' => $category->id,
                'duration' => 60,
                'total_questions' => 10,
                'max_score_raw' => 100,
                'max_score_irt' => 1000,
                'sub_categories' => $subCategories->values()->map(fn ($subCategory, $index) => [
                    'id' => $subCategory->id,
                    'percentage' => [33, 33, 34][$index],
                ])->all(),
            ]],
        ]);

        $counts = DB::table('session_questions')
            ->join('question_banks', 'question_banks.id', '=', 'session_questions.question_bank_id')
            ->where('session_questions.exam_session_id', $session->id)
            ->selectRaw('question_banks.sub_category_id, COUNT(*) as total')
            ->groupBy('question_banks.sub_category_id')
            ->pluck('total', 'sub_category_id');

        $this->assertSame(3, $counts[$subCategories[0]->id]);
        $this->assertSame(3, $counts[$subCategories[1]->id]);
        $this->assertSame(4, $counts[$subCategories[2]->id]);
    }

    public function test_unlock_releases_only_questions_owned_by_that_session(): void
    {
        [$category, $subCategory] = $this->makeCategoryWithSubCategory('Geometri');
        $this->makeQuestions($category->id, $subCategory->id, 4);

        $service = app(ExamSessionService::class);
        $first = $service->createWithCategories($this->sessionData($category->id, $subCategory->id, 2, true));
        $second = $service->createWithCategories($this->sessionData($category->id, $subCategory->id, 2, true));

        $service->updateWithCategories($first->id, $this->sessionData($category->id, $subCategory->id, 2, false));

        $this->assertSame(0, QuestionBank::where('locked_by_exam_session_id', $first->id)->count());
        $this->assertSame(2, QuestionBank::where('locked_by_exam_session_id', $second->id)->count());
    }

    public function test_locked_session_can_regenerate_its_own_questions(): void
    {
        [$category, $subCategory] = $this->makeCategoryWithSubCategory('Peluang');
        $this->makeQuestions($category->id, $subCategory->id, 2);

        $service = app(ExamSessionService::class);
        $session = $service->createWithCategories($this->sessionData($category->id, $subCategory->id, 2, true));

        $service->generateSessionQuestions($session->id);

        $this->assertSame(2, $session->questions()->count());
        $this->assertSame(2, QuestionBank::where('locked_by_exam_session_id', $session->id)->count());
    }

    public function test_started_session_cannot_change_its_question_allocation(): void
    {
        [$category, $subCategory] = $this->makeCategoryWithSubCategory('Statistika');
        $this->makeQuestions($category->id, $subCategory->id, 3);

        $service = app(ExamSessionService::class);
        $session = $service->createWithCategories($this->sessionData($category->id, $subCategory->id, 2, false));
        ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'name' => 'Peserta Ujian',
            'whatsapp' => '081234567890',
            'access_code' => 'START1',
            'started_at' => now(),
        ]);

        $this->expectException(DomainException::class);
        $service->updateWithCategories($session->id, $this->sessionData($category->id, $subCategory->id, 3, false));
    }

    public function test_metadata_edit_preserves_category_statuses_for_started_participants(): void
    {
        [$category, $subCategory] = $this->makeCategoryWithSubCategory('Kalkulus');
        $this->makeQuestions($category->id, $subCategory->id, 2);

        $service = app(ExamSessionService::class);
        $session = $service->createWithCategories($this->sessionData($category->id, $subCategory->id, 2, false));
        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'name' => 'Peserta Aktif',
            'whatsapp' => '081234567891',
            'access_code' => 'START2',
            'started_at' => now(),
        ]);
        $categoryId = $session->sessionCategories()->value('id');
        $status = ParticipantCategoryStatus::create([
            'exam_session_participant_id' => $participant->id,
            'exam_session_category_id' => $categoryId,
            'started_at' => now(),
        ]);

        $data = $this->sessionData($category->id, $subCategory->id, 2, false);
        $data['name'] = 'Nama Sesi Diperbarui';
        $service->updateWithCategories($session->id, $data);

        $this->assertDatabaseHas('exam_session_categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('participant_category_statuses', ['id' => $status->id]);
    }

    public function test_service_rejects_invalid_subcategory_percentages(): void
    {
        [$category, $subCategory] = $this->makeCategoryWithSubCategory('Trigonometri');
        $this->makeQuestions($category->id, $subCategory->id, 2);

        $data = $this->sessionData($category->id, $subCategory->id, 2, false);
        $data['categories'][0]['sub_categories'][0]['percentage'] = 50;

        $this->expectException(DomainException::class);
        app(ExamSessionService::class)->createWithCategories($data);
    }

    public function test_service_requires_subcategory_selection_when_category_has_subcategories(): void
    {
        [$category, $subCategory] = $this->makeCategoryWithSubCategory('Logika');
        $this->makeQuestions($category->id, $subCategory->id, 2);

        $data = $this->sessionData($category->id, $subCategory->id, 2, false);
        $data['categories'][0]['sub_categories'] = [];

        $this->expectException(DomainException::class);
        app(ExamSessionService::class)->createWithCategories($data);
    }

    private function makeCategoryWithSubCategory(string $name): array
    {
        $category = Category::create(['name' => $name, 'slug' => strtolower($name)]);
        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => strtolower($name),
        ]);

        return [$category, $subCategory];
    }

    private function makeQuestions(int $categoryId, int $subCategoryId, int $count): void
    {
        foreach (range(1, $count) as $number) {
            QuestionBank::create([
                'category_id' => $categoryId,
                'sub_category_id' => $subCategoryId,
                'type' => 'pilihan_ganda',
                'question_text' => "Soal {$number}",
                'options' => ['A' => 'Jawaban'],
                'correct_answer' => ['A'],
            ]);
        }
    }

    private function sessionData(int $categoryId, int $subCategoryId, int $questionCount, bool $isLocked): array
    {
        return [
            'name' => 'Sesi Lock',
            'code' => strtoupper(uniqid('LOCK')),
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_lock_quiz' => $isLocked,
            'categories' => [[
                'id' => $categoryId,
                'duration' => 60,
                'total_questions' => $questionCount,
                'max_score_raw' => 100,
                'max_score_irt' => 1000,
                'sub_categories' => [[
                    'id' => $subCategoryId,
                    'percentage' => 100,
                ]],
            ]],
        ];
    }
}
