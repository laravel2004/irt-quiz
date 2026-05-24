<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AggregateAiAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_session_id',
        'analysis_data',
    ];

    protected $casts = [
        'analysis_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }
}
