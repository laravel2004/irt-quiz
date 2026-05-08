<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    protected $fillable = [
        'participant_id',
        'exam_session_id',
        'question_bank_id',
        'answer',
        'is_correct'
    ];

    protected $casts = [
        'answer' => 'json'
    ];

    public function participant() { return $this->belongsTo(ExamSessionParticipant::class, 'participant_id'); }
    public function session() { return $this->belongsTo(ExamSession::class, 'exam_session_id'); }
    public function question() { return $this->belongsTo(QuestionBank::class, 'question_bank_id'); }
}
