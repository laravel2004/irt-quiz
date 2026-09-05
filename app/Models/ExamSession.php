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
        'admin_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_active',
        'is_lock_quiz',
        'discussion_pdf'
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_lock_quiz' => 'boolean',
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
        return $this->belongsToMany(QuestionBank::class, 'session_questions', 'exam_session_id', 'question_bank_id')->withPivot('difficulty');
    }

    public function lockedQuestions()
    {
        return $this->hasMany(QuestionBank::class, 'locked_by_exam_session_id');
    }

    public function getDiscussionPdfAttribute($value)
    {
        return $value ? str_replace('\\', '/', $value) : $value;
    }
}
