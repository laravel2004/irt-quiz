<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamCategoryResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_result_id',
        'category_id',
        'total_correct',
        'total_incorrect',
        'total_blank',
        'score',
        'irt_score'
    ];

    public function examResult()
    {
        return $this->belongsTo(ExamResult::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
