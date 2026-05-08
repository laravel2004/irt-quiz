<?php

namespace App\Repositories;

use App\Models\QuestionBank;

class QuestionBankRepository extends BaseRepository
{
    public function __construct(QuestionBank $model)
    {
        parent::__construct($model);
    }
}
