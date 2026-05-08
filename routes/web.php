<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', [\App\Http\Controllers\ExamController::class, 'index'])->name('exam.index');
Route::post('/exam/login', [\App\Http\Controllers\ExamController::class, 'login'])->name('exam.login');
Route::get('/exam', [\App\Http\Controllers\ExamController::class, 'main'])->name('exam.main');
Route::get('/exam/finish', [\App\Http\Controllers\ExamController::class, 'finish'])->name('exam.finish');
Route::post('/exam/submit', [\App\Http\Controllers\ExamController::class, 'submit'])->name('exam.submit');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Session Registration
Route::get('/register-session/{code}', [\App\Http\Controllers\PublicSessionController::class, 'registration'])->name('public.session.registration');
Route::post('/register-session/{code}', [\App\Http\Controllers\PublicSessionController::class, 'register'])->name('public.session.register');
Route::get('/register-session/{code}/success', [\App\Http\Controllers\PublicSessionController::class, 'success'])->name('public.session.success');

Route::middleware(AdminMiddleware::class)->group(function () {
    Route::get('/admin', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    Route::resource('/admin/categories', CategoryController::class)->names('admin.categories');
    Route::resource('/admin/questions', \App\Http\Controllers\Admin\QuestionBankController::class)->names('admin.questions');
    Route::resource('/admin/sessions', \App\Http\Controllers\Admin\ExamSessionController::class)->names('admin.sessions');
    Route::patch('/admin/sessions/{id}/toggle-status', [\App\Http\Controllers\Admin\ExamSessionController::class, 'toggleStatus'])->name('admin.sessions.toggle-status');
    Route::post('/admin/sessions/{id}/generate-irt', [\App\Http\Controllers\Admin\ExamSessionController::class, 'generateIRTResults'])->name('admin.sessions.generate-irt');
    Route::get('/admin/sessions/{id}/export', [\App\Http\Controllers\Admin\ExamSessionController::class, 'exportResults'])->name('admin.sessions.export');
    Route::post('/admin/participants', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'store'])->name('admin.participants.store');
    Route::delete('/admin/participants/{id}', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'destroy'])->name('admin.participants.destroy');
});
