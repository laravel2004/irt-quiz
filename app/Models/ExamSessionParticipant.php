<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
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
}
