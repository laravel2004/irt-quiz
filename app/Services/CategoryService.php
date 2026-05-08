<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Illuminate\Support\Str;

class CategoryService extends BaseService
{
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data)
    {
        $data['slug'] = Str::slug($data['name']);
        return parent::store($data);
    }
}
