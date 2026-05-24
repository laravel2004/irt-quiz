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
        'discussion_pdf'
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
    
    protected $casts = [
        'is_active' => 'boolean'
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
}
