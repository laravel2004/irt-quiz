<?php

namespace App\Repositories;

use App\Models\QuestionBank;

class QuestionBankRepository extends BaseRepository
{
    public function __construct(QuestionBank $model)
    {
        parent::__construct($model);
    }
    public function paginate(int $perPage = 10, array $filters = [])
    {
        $query = $this->model->with(['category', 'subCategory'])->latest();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->paginate($perPage);
    }
}
