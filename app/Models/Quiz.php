<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'jenjang', 'type', 'question_text', 'question_image', 
    'options', 'correct_answer', 'difficulty', 
    'score_correct', 'score_incorrect', 'explanation'
])]
class Quiz extends Model
{
    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];

    public function getQuestionImageAttribute($value)
    {
        return $value ? str_replace('\\', '/', $value) : $value;
    }

    public function getQuestionTextAttribute($value)
    {
        if (!$value) return $value;
        $appUrl = rtrim(url('/'), '/');
        return preg_replace('/src="(?:\.\.\/)*\/?storage\//', 'src="' . $appUrl . '/storage/', $value);
    }

    public function getExplanationAttribute($value)
    {
        if (!$value) return $value;
        $appUrl = rtrim(url('/'), '/');
        return preg_replace('/src="(?:\.\.\/)*\/?storage\//', 'src="' . $appUrl . '/storage/', $value);
    }
}
