<?php

namespace Tests\Unit;

use App\Services\AIService;
use PHPUnit\Framework\TestCase;

class AIServicePromptTest extends TestCase
{
    public function test_participant_result_prompt_only_uses_irt_score(): void
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
            'total_score' => '850.00 (Predikat Istimewa)',
            'category_stats' => [],
        ]);

        $this->assertStringContainsString('Skor IRT dan Predikat: 850.00', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('raw', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('mentah', $prompt);
    }

    public function test_participant_progress_prompt_only_uses_irt_score(): void
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
                'irt_score' => 850,
                'predicate' => 'Istimewa',
            ]],
        ]);

        $this->assertStringContainsString('Skor IRT 850', $prompt);
        $this->assertStringContainsString('Predikat Istimewa', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('raw', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('mentah', $prompt);
    }
}
