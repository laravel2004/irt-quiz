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
        'privilege',
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

    public function categoryStatuses()
    {
        return $this->hasMany(ParticipantCategoryStatus::class, 'exam_session_participant_id');
    }

    /**
     * Scope a query to only include participants currently active in an exam.
     */
    public function scopeActiveInExam($query)
    {
        return $query->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->whereHas('examSession', function ($q) {
                // Ensure session is globally active and not expired
                $q->where('is_active', true)
                  ->whereRaw("CONCAT(end_date, ' ', end_time) > ?", [now()]);
            })
            // Ensure participant has not exceeded the total possible duration of the session
            // total possible duration = sum of durations of all categories in the session
            ->whereRaw("DATE_ADD(started_at, INTERVAL (SELECT COALESCE(SUM(duration), 0) FROM exam_session_categories WHERE exam_session_categories.exam_session_id = exam_session_participants.exam_session_id) MINUTE) > ?", [now()]);
    }
}
