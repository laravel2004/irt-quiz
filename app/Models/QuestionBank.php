<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $fillable = [
        'category_id',
        'type',
        'question_text',
        'question_image',
        'options',
        'correct_answer',
        'difficulty',
        'score_correct',
        'score_incorrect'
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
