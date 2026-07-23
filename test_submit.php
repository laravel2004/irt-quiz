<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$q = App\Models\QuestionBank::find(16);
$correctArr = (array) $q->correct_answer;
$options = (array) $q->options;

$controller = app()->make(App\Http\Controllers\ExamController::class);

$reflection = new ReflectionClass($controller);
$resolveCorrectValues = $reflection->getMethod('resolveCorrectValues');
$resolveCorrectValues->setAccessible(true);

$correctValues = $resolveCorrectValues->invokeArgs($controller, [$correctArr, $options]);

$answer = ["2", "3"];
$mappedAnswers = array_map(function($val) use ($options) {
    return (is_numeric($val) && array_key_exists((int)$val, $options)) ? $options[(int)$val] : $val;
}, $answer);

$answersMatch = $reflection->getMethod('answersMatch');
$answersMatch->setAccessible(true);

$correctSelected = 0;
foreach ($mappedAnswers as $actual) {
    foreach ($correctValues as $expected) {
        $match = $answersMatch->invokeArgs($controller, [$expected, $actual]);
        echo "Match expected vs actual: " . ($match ? 'TRUE' : 'FALSE') . "\n";
        if ($match) {
            $correctSelected++;
            break;
        }
    }
}
echo "Correct Selected: $correctSelected\n";
