<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSessionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'category_id',
        'duration',
        'total_questions',
        'max_score_raw',
        'min_score_irt',
        'max_score_irt',
    ];

    protected $casts = [
        'min_score_irt' => 'decimal:2',
    ];

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategories()
    {
        return $this->hasMany(ExamSessionSubCategory::class);
    }
}
