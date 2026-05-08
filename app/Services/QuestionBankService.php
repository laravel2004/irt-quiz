<?php

namespace App\Services;

use App\Repositories\QuestionBankRepository;

class QuestionBankService extends BaseService
{
    public function __construct(QuestionBankRepository $repository)
    {
        parent::__construct($repository);
    }
}
