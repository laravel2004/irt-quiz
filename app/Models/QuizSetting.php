<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reset_time', 'questions_per_day', 'xp_per_correct', 'last_weekly_reset_at'])]
class QuizSetting extends Model
{
    protected $casts = [
        'last_weekly_reset_at' => 'datetime',
    ];

    /** Ambil satu-satunya baris setting (dibuat otomatis jika belum ada). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'last_weekly_reset_at' => now(),
        ])->fresh();
    }
}
