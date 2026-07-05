<?php

use App\Http\Controllers\PublicExamSessionController;
use App\Http\Controllers\PublicSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/public/exam-sessions', [PublicExamSessionController::class, 'index']);
Route::post('/public/exam-sessions/{code}/register', [PublicSessionController::class, 'register']);

Route::post('/public/exam-sessions/{code}/register-premium', [PublicSessionController::class, 'registerPremium']);
