<?php

namespace App\Repositories;

use App\Models\ExamSession;

class ExamSessionRepository extends BaseRepository
{
    public function __construct(ExamSession $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code)
    {
        return $this->model->where('code', $code)->first();
    }
}
