<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$q = App\Models\QuestionBank::orderByDesc('id')->where('type', 'multiple_choice')->first();
echo "Question ID: {$q->id}\n";
echo "Correct Answer: " . json_encode($q->correct_answer) . "\n";
echo "Options keys: " . json_encode(array_keys((array)$q->options)) . "\n";

$controller = app()->make(App\Http\Controllers\ExamController::class);
$reflection = new ReflectionClass($controller);
$resolveCorrectIndices = $reflection->getMethod('resolveCorrectIndices');
$resolveCorrectIndices->setAccessible(true);

$correctIndices = $resolveCorrectIndices->invokeArgs($controller, [(array)$q->correct_answer, (array)$q->options]);
echo "Correct Indices: " . json_encode($correctIndices) . "\n";

// Let's simulate user submitting ["2", "3"] which are wrong
$answer = ["2", "3"];
$options = (array)$q->options;

$userIndices = [];
foreach ($answer as $val) {
    if (is_numeric($val) && array_key_exists((int)$val, $options)) {
        $userIndices[] = (string) $val;
    }
}
echo "User Indices: " . json_encode($userIndices) . "\n";

$totalCorrectAvailable = count($correctIndices);
$correctSelected = count(array_intersect($userIndices, $correctIndices));
$netCorrect = $correctSelected;
$percentage = $totalCorrectAvailable > 0 ? ($netCorrect / $totalCorrectAvailable) : 0;
$score = round($percentage * ($q->score_correct ?? 1), 2);
$isCorrect = false;
if ($netCorrect === $totalCorrectAvailable) {
    $isCorrect = true;
} else if ($percentage == 0) {
    $score = $q->score_incorrect ?? 0;
}

echo "Score: $score, isCorrect: " . ($isCorrect ? 'true' : 'false') . "\n";

// Let's check the latest UserAnswers for this question
$answers = App\Models\UserAnswer::where('question_bank_id', $q->id)->orderByDesc('updated_at')->take(3)->get();
foreach ($answers as $ans) {
    echo "UserAnswer ID: {$ans->id}, Answer: " . json_encode($ans->answer) . ", is_correct: {$ans->is_correct}, score: {$ans->score}, updated_at: {$ans->updated_at}\n";
}
