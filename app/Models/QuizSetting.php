<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reset_time', 'questions_per_day', 'xp_per_correct'])]
class QuizSetting extends Model
{
    /** Ambil satu-satunya baris setting (dibuat otomatis jika belum ada). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([])->fresh();
    }
}
