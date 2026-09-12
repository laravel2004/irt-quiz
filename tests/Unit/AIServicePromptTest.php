<?php

namespace Tests\Unit;

use App\Services\AIService;
use PHPUnit\Framework\TestCase;

class AIServicePromptTest extends TestCase
{
    public function test_participant_result_prompt_uses_filtered_score_summary(): void
    {
        $service = new class extends AIService
        {
            public function resultPrompt(array $data): string
            {
                return $this->buildPrompt($data);
            }
        };

        $prompt = $service->resultPrompt([
            'participant_name' => 'Peserta',
            'session_name' => 'Try Out',
            'total_questions' => 13,
            'correct' => 10,
            'incorrect' => 2,
            'blank' => 1,
            'total_score' => 'Skor Raw 75.00 (Predikat Raw Baik)',
            'category_stats' => [],
        ]);

        $this->assertStringContainsString('Skor dan Predikat: Skor Raw 75.00 (Predikat Raw Baik)', $prompt);
        $this->assertStringNotContainsString('IRT', $prompt);
    }

    public function test_participant_progress_prompt_uses_filtered_score_summary(): void
    {
        $service = new class extends AIService
        {
            public function aggregatePrompt(array $data): string
            {
                return $this->buildAggregatePrompt($data);
            }
        };

        $prompt = $service->aggregatePrompt([
            'participant_name' => 'Peserta',
            'session_name' => 'Try Out',
            'attempts' => [[
                'attempt_number' => 1,
                'total_correct' => 10,
                'total_incorrect' => 2,
                'total_blank' => 1,
                'score_summary' => 'Skor Raw 75.00 (Predikat Raw Baik); Skor IRT 850.00 (Predikat IRT Istimewa)',
            ]],
        ]);

        $this->assertStringContainsString('Skor Raw 75.00 (Predikat Raw Baik)', $prompt);
        $this->assertStringContainsString('Skor IRT 850.00 (Predikat IRT Istimewa)', $prompt);
    }
}
