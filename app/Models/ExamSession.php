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
        'participant_score_display',
        'discussion_pdf',
        'predicate_raw_kurang_min',
        'predicate_raw_memadai_min',
        'predicate_raw_baik_min',
        'predicate_raw_istimewa_min',
        'predicate_kurang_min',
        'predicate_memadai_min',
        'predicate_baik_min',
        'predicate_istimewa_min',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'is_lock_quiz' => 'boolean',
        'predicate_raw_kurang_min' => 'decimal:2',
        'predicate_raw_memadai_min' => 'decimal:2',
        'predicate_raw_baik_min' => 'decimal:2',
        'predicate_raw_istimewa_min' => 'decimal:2',
        'predicate_kurang_min' => 'decimal:2',
        'predicate_memadai_min' => 'decimal:2',
        'predicate_baik_min' => 'decimal:2',
        'predicate_istimewa_min' => 'decimal:2',
    ];

    public function predicateForIrtScore(float $score): string
    {
        if ($score >= (float) $this->predicate_istimewa_min) {
            return 'Istimewa';
        }

        if ($score >= (float) $this->predicate_baik_min) {
            return 'Baik';
        }

        if ($score >= (float) $this->predicate_memadai_min) {
            return 'Memadai';
        }

        return 'Kurang';
    }

    public function predicateForRawScore(float $score): string
    {
        if ($score >= (float) $this->predicate_raw_istimewa_min) {
            return 'Istimewa';
        }

        if ($score >= (float) $this->predicate_raw_baik_min) {
            return 'Baik';
        }

        if ($score >= (float) $this->predicate_raw_memadai_min) {
            return 'Memadai';
        }

        return 'Kurang';
    }

    public function showsRawScoreToParticipant(): bool
    {
        return in_array($this->participant_score_display, ['raw', 'both'], true);
    }

    public function showsIrtScoreToParticipant(): bool
    {
        return in_array($this->participant_score_display ?? 'irt', ['irt', 'both'], true);
    }

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
