<?php

$file = 'app/Services/AssessmentService.php';
$content = file_get_contents($file);

$resolveIndicesCode = <<<PHP
    private function resolveCorrectIndices(array \$correctAnswers, array \$options): array
    {
        \$indices = [];
        foreach (\$correctAnswers as \$correctAnswer) {
            \$key = (string) \$correctAnswer;
            \$upperKey = strtoupper(trim(\$key));
            if (preg_match('/^[A-Z]$/', \$upperKey)) {
                \$index = ord(\$upperKey) - 65;
                if (array_key_exists(\$index, \$options)) {
                    \$indices[] = (string) \$index;
                    continue;
                }
            }
            if (array_key_exists(\$key, \$options)) {
                \$indices[] = (string) \$key;
            }
        }
        return array_values(array_unique(\$indices));
    }
PHP;

// Insert resolveCorrectIndices before resolveCorrectValues
if (strpos($content, 'function resolveCorrectIndices') === false) {
    $content = str_replace(
        'private function resolveCorrectValues',
        $resolveIndicesCode . "\n\n    private function resolveCorrectValues",
        $content
    );
}

// Replace the logic block for scoring
$scoringLogic = <<<PHP
                if (\$question->type === 'pilihan_ganda' || \$question->type === 'benar_salah') {
                    \$correctIndex = \$this->resolveCorrectIndices(\$correctArr, \$options)[0] ?? null;
                    \$isCorrect = false;

                    if (is_numeric(\$answer) && array_key_exists((int)\$answer, \$options)) {
                        \$isCorrect = (\$correctIndex !== null && (string)\$answer === \$correctIndex);
                    } else {
                        \$correctValue = \$this->resolveCorrectValues(\$correctArr, \$options)[0] ?? null;
                        \$isCorrect = (\$correctValue !== null && \$this->answersMatch(\$correctValue, \$answer));
                    }
                    \$score = \$isCorrect ? (\$question->score_correct ?? 1) : (\$question->score_incorrect ?? 0);
                } elseif (\$question->type === 'multiple_choice') {
                    \$correctIndices = \$this->resolveCorrectIndices(\$correctArr, \$options);
                    \$isCorrect = false;
                    
                    if (is_array(\$answer)) {
                        \$userIndices = [];
                        foreach (\$answer as \$val) {
                            if (is_numeric(\$val) && array_key_exists((int)\$val, \$options)) {
                                \$userIndices[] = (string) \$val;
                            }
                        }

                        \$totalCorrectAvailable = count(\$correctIndices);
                        \$correctSelected = count(array_intersect(\$userIndices, \$correctIndices));
                        
                        \$netCorrect = \$correctSelected;
                        \$percentage = \$totalCorrectAvailable > 0 ? (\$netCorrect / \$totalCorrectAvailable) : 0;
                        
                        \$score = round(\$percentage * (\$question->score_correct ?? 1), 2);
                        
                        if (\$netCorrect === \$totalCorrectAvailable) {
                            \$isCorrect = true;
                        } else if (\$percentage == 0) {
                            \$score = \$question->score_incorrect ?? 0;
                        }
                    } else {
                        \$score = \$question->score_incorrect ?? 0;
                    }
                } elseif (\$question->type === 'multiple_benar_salah') {
PHP;

$pattern = "/if \(\\\$question->type === 'pilihan_ganda' \|\| \\\$question->type === 'benar_salah'\) \{.*?\} elseif \(\\\$question->type === 'multiple_benar_salah'\) \{/s";
$content = preg_replace($pattern, $scoringLogic, $content);

file_put_contents($file, $content);

echo "AssessmentService patched successfully.\n";
