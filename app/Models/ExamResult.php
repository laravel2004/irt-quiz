<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $fillable = [
        'participant_id',
        'exam_session_id',
        'total_correct',
        'total_incorrect',
        'total_blank',
        'score',
        'irt_score',
        'ai_analysis'
    ];

    public function participant() { return $this->belongsTo(ExamSessionParticipant::class, 'participant_id'); }
    public function session() { return $this->belongsTo(ExamSession::class, 'exam_session_id'); }
}
