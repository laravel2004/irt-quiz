<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'user_id',
        'generated_by',
        'session_ids',
        'status',
        'report_data',
        'error_message',
    ];

    protected $casts = [
        'session_ids'  => 'array',
        'report_data'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
