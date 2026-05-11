<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'user_id',
        'name',
        'whatsapp',
        'address',
        'access_code',
        'started_at',
        'finished_at'
    ];

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->belongsToMany(QuestionBank::class, 'participant_questions', 'participant_id', 'question_bank_id')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function result()
    {
        return $this->hasOne(ExamResult::class, 'participant_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'participant_id');
    }
}
