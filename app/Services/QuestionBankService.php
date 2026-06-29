<?php

namespace App\Services;

use App\Repositories\QuestionBankRepository;

class QuestionBankService extends BaseService
{
    public function __construct(QuestionBankRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getPaginatedWithFilters(int $perPage = 10, array $filters = [])
    {
        return $this->repository->paginate($perPage, $filters);
    }
}
