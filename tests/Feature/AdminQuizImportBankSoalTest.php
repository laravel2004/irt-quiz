<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\QuestionBank;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminQuizImportBankSoalTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create(['role' => 'superadmin']);
    }

    public function test_superadmin_can_view_import_bank_soal_page_with_categories(): void
    {
        $catMath = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);
        $catPhysics = Category::create(['name' => 'Fisika', 'slug' => 'fisika']);

        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.quizzes.import-view'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quizzes.import');
        $response->assertSee('Semua Mata Pelajaran');
        $response->assertSee('Matematika');
        $response->assertSee('Fisika');
    }

    public function test_filter_by_category_only_displays_questions_from_selected_category(): void
    {
        $catMath = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);
        $catPhysics = Category::create(['name' => 'Fisika', 'slug' => 'fisika']);

        $qMath = QuestionBank::create([
            'category_id' => $catMath->id,
            'type' => 'pilihan_ganda',
            'question_text' => 'Soal Matematika Aljabar',
            'correct_answer' => ['A'],
            'options' => ['A' => '1', 'B' => '2'],
        ]);

        $qPhysics = QuestionBank::create([
            'category_id' => $catPhysics->id,
            'type' => 'pilihan_ganda',
            'question_text' => 'Soal Fisika Kinematika',
            'correct_answer' => ['B'],
            'options' => ['A' => '10 m/s', 'B' => '20 m/s'],
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.quizzes.import-view', ['category_id' => $catMath->id]));

        $response->assertStatus(200);
        $response->assertSee('Soal Matematika Aljabar');
        $response->assertDontSee('Soal Fisika Kinematika');
    }

    public function test_filter_by_category_and_search_combines_properly(): void
    {
        $catMath = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);

        $q1 = QuestionBank::create([
            'category_id' => $catMath->id,
            'type' => 'pilihan_ganda',
            'question_text' => 'Trigonometri dasar',
            'correct_answer' => ['A'],
            'options' => ['A' => '1'],
        ]);

        $q2 = QuestionBank::create([
            'category_id' => $catMath->id,
            'type' => 'pilihan_ganda',
            'question_text' => 'Logaritma dasar',
            'correct_answer' => ['A'],
            'options' => ['A' => '1'],
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.quizzes.import-view', [
            'category_id' => $catMath->id,
            'search' => 'Trigonometri',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Trigonometri dasar');
        $response->assertDontSee('Logaritma dasar');
    }

    public function test_reset_button_appears_when_category_filter_is_active(): void
    {
        $cat = Category::create(['name' => 'Biologi', 'slug' => 'biologi']);

        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.quizzes.import-view', ['category_id' => $cat->id]));

        $response->assertStatus(200);
        $response->assertSee('Reset');
    }
}
