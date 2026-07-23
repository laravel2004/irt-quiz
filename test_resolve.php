<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app()->make(App\Http\Controllers\ExamController::class);
$reflection = new ReflectionClass($controller);
$resolveCorrectIndices = $reflection->getMethod('resolveCorrectIndices');
$resolveCorrectIndices->setAccessible(true);

$options = ["A" => 1, "B" => 2, "C" => 3, "D" => 4];
$correctAnswers = ["A", "C"];
$indices = $resolveCorrectIndices->invokeArgs($controller, [$correctAnswers, $options]);
print_r($indices);
