<?php

namespace App\Services;

use App\Repositories\ExamSessionRepository;
use App\Models\ExamSessionCategory;
use App\Models\QuestionBank;
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
            
            // Delete old categories (cascades sub_categories)
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
            $totalAllocated = 0;
            
            // Loop through sub categories and allocate questions
            foreach ($sc->subCategories as $subCat) {
                $count = round(($subCat->percentage / 100) * $sc->total_questions);
                
                $questionIds = QuestionBank::where('category_id', $sc->category_id)
                    ->where('sub_category_id', $subCat->sub_category_id)
                    ->inRandomOrder()
                    ->limit($count)
                    ->pluck('id')
                    ->toArray();
                
                $catQuestionIds = array_merge($catQuestionIds, $questionIds);
                $totalAllocated += count($questionIds);
            }

            // Fill missing questions if not enough were found in sub-categories
            if ($totalAllocated < $sc->total_questions) {
                $missingCount = $sc->total_questions - $totalAllocated;
                
                $missingQuestionIds = QuestionBank::where('category_id', $sc->category_id)
                    ->whereNotIn('id', $catQuestionIds) // don't pick duplicates initially
                    ->inRandomOrder()
                    ->limit($missingCount)
                    ->pluck('id')
                    ->toArray();
                
                $catQuestionIds = array_merge($catQuestionIds, $missingQuestionIds);
                $totalAllocated = count($catQuestionIds);
            }

            // If STILL missing (because bank doesn't have enough unique questions), allow duplicates
            if ($totalAllocated < $sc->total_questions) {
                $missingCount = $sc->total_questions - $totalAllocated;
                $allCatQuestions = QuestionBank::where('category_id', $sc->category_id)->pluck('id')->toArray();
                
                if (!empty($allCatQuestions)) {
                    for ($i = 0; $i < $missingCount; $i++) {
                        $catQuestionIds[] = $allCatQuestions[array_rand($allCatQuestions)];
                    }
                }
            }

            $allSelectedIds = array_merge($allSelectedIds, $catQuestionIds);
        }

        // Insert questions to session (allowing duplicates)
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
            // Chunk insert to avoid too many placeholders issue if very large
            foreach (array_chunk($insertData, 500) as $chunk) {
                DB::table('session_questions')->insert($chunk);
            }
        }
    }
}
