<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/admin/quizzes', 'GET');
$app->make('auth')->loginUsingId(1);
$response = $kernel->handle($request);
file_put_contents('output.html', $response->getContent());
echo "Saved to output.html\n";
