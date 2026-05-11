<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'duration',
        'total_questions',
        'is_active',
        'max_score_raw',
        'max_score_irt',
        'discussion_pdf'
    ];
    
    protected $casts = [
        'duration' => 'integer',
        'total_questions' => 'integer',
        'is_active' => 'boolean',
        'max_score_raw' => 'integer',
        'max_score_irt' => 'integer'
    ];

    public function sessionCategories()
    {
        return $this->hasMany(ExamSessionCategory::class);
    }

    public function participants()
    {
        return $this->hasMany(ExamSessionParticipant::class);
    }

    public function results()
    {
        return $this->hasMany(ExamResult::class);
    }

    public function questions()
    {
        return $this->belongsToMany(QuestionBank::class, 'session_questions', 'exam_session_id', 'question_bank_id');
    }
}
