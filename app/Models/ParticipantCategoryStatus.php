<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantCategoryStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_participant_id',
        'exam_session_category_id',
        'started_at',
        'finished_at'
    ];

    public function participant()
    {
        return $this->belongsTo(ExamSessionParticipant::class, 'exam_session_participant_id');
    }

    public function sessionCategory()
    {
        return $this->belongsTo(ExamSessionCategory::class, 'exam_session_category_id');
    }
}
