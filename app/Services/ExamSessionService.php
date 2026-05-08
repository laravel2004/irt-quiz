<?php

namespace App\Services;

use App\Repositories\ExamSessionRepository;
use App\Models\ExamSessionCategory;
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
                ExamSessionCategory::create([
                    'exam_session_id' => $session->id,
                    'category_id' => $cat['id'],
                    'percentage' => $cat['percentage']
                ]);
            }
            
            return $session;
        });
    }

    public function updateWithCategories(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $session = $this->repository->update($id, $data);
            
            // Delete old categories
            ExamSessionCategory::where('exam_session_id', $id)->delete();
            
            foreach ($data['categories'] as $cat) {
                ExamSessionCategory::create([
                    'exam_session_id' => $id,
                    'category_id' => $cat['id'],
                    'percentage' => $cat['percentage']
                ]);
            }
            
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
}
