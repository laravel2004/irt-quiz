<?php

namespace App\Services;

use App\Models\Quiz;

class QuizAnswerChecker
{
    /**
     * Index jawaban benar (array string).
     * - pilihan_ganda/benar_salah/multiple_choice: index opsi yang benar.
     * - multiple_benar_salah: index pernyataan yang nilainya "benar".
     */
    public function correctIndices(Quiz $quiz): array
    {
        $options = (array) $quiz->options;
        $correct = (array) $quiz->correct_answer;

        if ($quiz->type === 'multiple_benar_salah') {
            // '-1' adalah penanda "tidak ada pernyataan yang benar"
            return array_values(array_map('strval', array_filter($correct, fn ($c) => (string) $c !== '-1')));
        }

        $indices = [];
        foreach ($correct as $c) {
            $key = strtoupper(trim((string) $c));
            if (preg_match('/^[A-Z]$/', $key) && array_key_exists(ord($key) - 65, $options)) {
                $indices[] = (string) (ord($key) - 65);
                continue;
            }
            if (array_key_exists((string) $c, $options)) {
                $indices[] = (string) $c;
            }
        }

        return array_values(array_unique($indices));
    }

    /** True hanya jika jawaban benar PENUH. */
    public function isCorrect(Quiz $quiz, mixed $answer): bool
    {
        $options = (array) $quiz->options;
        $correct = $this->correctIndices($quiz);

        switch ($quiz->type) {
            case 'pilihan_ganda':
            case 'benar_salah':
                return is_scalar($answer) && count($correct) === 1 && (string) $answer === $correct[0];

            case 'multiple_choice':
                if (! is_array($answer) || $correct === []) {
                    return false;
                }
                $user = array_values(array_unique(array_map('strval', $answer)));
                $expected = $correct;
                sort($user);
                sort($expected);
                return $user === $expected;

            case 'multiple_benar_salah':
                if (! is_array($answer) || count($options) === 0) {
                    return false;
                }
                foreach ($options as $idx => $_) {
                    $should = in_array((string) $idx, $correct, true) ? 'benar' : 'salah';
                    if (($answer[(string) $idx] ?? null) !== $should) {
                        return false;
                    }
                }
                return true;
        }

        return false;
    }
}
