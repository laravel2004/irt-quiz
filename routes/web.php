<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', function() {
    return auth()->check() ? redirect()->route('participant.dashboard') : view('participant.auth.login');
})->name('participant.login');

Route::post('/participant/login', [\App\Http\Controllers\AuthController::class, 'participantLogin'])->name('participant.login.post');

Route::middleware(['auth'])->group(function() {
    Route::get('/dashboard', [\App\Http\Controllers\Participant\DashboardController::class, 'index'])->name('participant.dashboard');
    Route::post('/dashboard/join-session', [\App\Http\Controllers\Participant\DashboardController::class, 'joinSession'])->name('participant.join-session');
    Route::get('/dashboard/result/{registrationId}', [\App\Http\Controllers\Participant\DashboardController::class, 'showResult'])->name('participant.result');
    Route::get('/dashboard/review/{registrationId}', [\App\Http\Controllers\Participant\DashboardController::class, 'showReview'])->name('participant.review');
    Route::post('/dashboard/analyze/{registrationId}', [\App\Http\Controllers\Participant\DashboardController::class, 'generateAIAnalysis'])->name('participant.analyze');
    
    // Exam Execution
    Route::get('/exam/{code}', [\App\Http\Controllers\ExamController::class, 'main'])->name('exam.main');
    Route::post('/exam/login', [\App\Http\Controllers\ExamController::class, 'login'])->name('exam.login');
    Route::get('/exam/finish', [\App\Http\Controllers\ExamController::class, 'finish'])->name('exam.finish');
    Route::post('/exam/submit', [\App\Http\Controllers\ExamController::class, 'submit'])->name('exam.submit');
});

Route::get('/admin/login', function () {
    return view('login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Session Registration (Can be used by anyone to register for a session)
Route::get('/register-session/{code}', [\App\Http\Controllers\PublicSessionController::class, 'registration'])->name('public.session.registration');
Route::post('/register-session/{code}', [\App\Http\Controllers\PublicSessionController::class, 'register'])->name('public.session.register');
Route::get('/register-session/{code}/success', [\App\Http\Controllers\PublicSessionController::class, 'success'])->name('public.session.success');

Route::middleware(AdminMiddleware::class)->group(function () {
    Route::get('/admin', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    Route::resource('/admin/categories', CategoryController::class)->names('admin.categories');
    Route::resource('/admin/questions', \App\Http\Controllers\Admin\QuestionBankController::class)->names('admin.questions');
    Route::resource('/admin/sessions', \App\Http\Controllers\Admin\ExamSessionController::class)->names('admin.sessions');
    Route::get('/admin/sessions/{id}/preview-questions', [\App\Http\Controllers\Admin\ExamSessionController::class, 'previewQuestions'])->name('admin.sessions.preview-questions');
    Route::patch('/admin/sessions/{id}/toggle-status', [\App\Http\Controllers\Admin\ExamSessionController::class, 'toggleStatus'])->name('admin.sessions.toggle-status');
    Route::post('/admin/sessions/{id}/generate-irt', [\App\Http\Controllers\Admin\ExamSessionController::class, 'generateIRTResults'])->name('admin.sessions.generate-irt');
    Route::post('/admin/sessions/{id}/upload-discussion', [\App\Http\Controllers\Admin\ExamSessionController::class, 'uploadDiscussionPdf'])->name('admin.sessions.upload-discussion');
    Route::get('/admin/sessions/{id}/export', [\App\Http\Controllers\Admin\ExamSessionController::class, 'exportResults'])->name('admin.sessions.export');
    
    // Admin Management of Participants (Users)
    Route::resource('/admin/participants', \App\Http\Controllers\Admin\ParticipantController::class)->names('admin.participants');
    
    // Legacy Session Participant routes (for adding by admin)
    Route::post('/admin/session-participants', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'store'])->name('admin.session-participants.store');
    Route::delete('/admin/session-participants/{id}', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'destroy'])->name('admin.session-participants.destroy');
});
