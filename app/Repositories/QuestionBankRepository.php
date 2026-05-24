<?php

namespace App\Repositories;

use App\Models\QuestionBank;

class QuestionBankRepository extends BaseRepository
{
    public function __construct(QuestionBank $model)
    {
        parent::__construct($model);
    }
    public function paginate(int $perPage = 10)
    {
        return $this->model->with(['category', 'subCategory'])->latest()->paginate($perPage);
    }
}
