<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id', 'quiz_id', 'period_key', 'question_snapshot',
    'answer', 'is_correct', 'xp_earned', 'answered_at',
])]
class QuizAttempt extends Model
{
    // period_key sengaja TIDAK di-cast: dibiarkan string 'Y-m-d'.
    protected $casts = [
        'answer' => 'array',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
