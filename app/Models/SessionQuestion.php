<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionQuestion extends Model
{
    protected $fillable = ['exam_session_id', 'question_bank_id'];
}
