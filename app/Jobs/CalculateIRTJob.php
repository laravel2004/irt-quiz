<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;

class CalculateIRTJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return (string) $this->sessionId;
    }

    public $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $assessmentService = new \App\Services\AssessmentService();
        $assessmentService->calculateIRT($this->sessionId);

        // Invalidate leaderboard cache since scores have changed
        \Illuminate\Support\Facades\Cache::forget("statistics_session_{$this->sessionId}");
    }
}
