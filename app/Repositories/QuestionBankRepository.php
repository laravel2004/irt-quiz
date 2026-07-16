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
        $query = $this->model->newQuery();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply deferred join pattern to avoid MySQL Out of Sort Memory errors 
        // when rows contain very large base64 images in text/JSON columns
        $paginator = $query->clone()->select('id')->latest()->paginate($perPage);
        $ids = $paginator->pluck('id')->toArray();

        if (!empty($ids)) {
            $models = $this->model->with(['category', 'subCategory'])
                ->whereIn('id', $ids)
                ->get();
                
            // Sort models in PHP to match the ordered IDs
            $sortedModels = collect($ids)->map(function ($id) use ($models) {
                return $models->firstWhere('id', $id);
            })->filter()->values();
            
            $paginator->setCollection($sortedModels);
        }

        return $paginator;
    }
}
