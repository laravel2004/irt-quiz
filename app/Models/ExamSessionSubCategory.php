<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSessionSubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_category_id',
        'sub_category_id',
        'percentage'
    ];

    public function examSessionCategory()
    {
        return $this->belongsTo(ExamSessionCategory::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }
}
