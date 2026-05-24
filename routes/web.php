<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', function() {
    if (auth()->check()) {
        $role = auth()->user()->role;
        if ($role === 'superadmin') return redirect()->route('admin.dashboard');
        if ($role === 'admin_sesi') return redirect()->route('admin.sessions.index');
        return redirect()->route('participant.dashboard');
    }
    return view('participant.auth.login');
})->name('login');

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function() {
    Route::get('/dashboard', [\App\Http\Controllers\Participant\DashboardController::class, 'index'])->name('participant.dashboard');
    Route::post('/dashboard/join-session', [\App\Http\Controllers\Participant\DashboardController::class, 'joinSession'])->name('participant.join-session');
    Route::get('/dashboard/result/{registrationId}', [\App\Http\Controllers\Participant\DashboardController::class, 'showResult'])->name('participant.result');
    Route::get('/dashboard/review/{registrationId}', [\App\Http\Controllers\Participant\DashboardController::class, 'showReview'])->name('participant.review');
    Route::get('/dashboard/review/{registrationId}/category/{categoryId}', [\App\Http\Controllers\Participant\DashboardController::class, 'showReviewCategory'])->name('participant.review.category');
    Route::post('/dashboard/analyze/{registrationId}', [\App\Http\Controllers\Participant\DashboardController::class, 'generateAIAnalysis'])->name('participant.analyze');
    Route::post('/dashboard/retake/{sessionId}', [\App\Http\Controllers\Participant\DashboardController::class, 'retakeSession'])->name('participant.retake');
    Route::get('/dashboard/aggregate-analysis/{sessionId}', [\App\Http\Controllers\Participant\DashboardController::class, 'generateAggregateAnalysis'])->name('participant.aggregate-analysis');
    Route::get('/dashboard/statistics/{sessionId}', [\App\Http\Controllers\Participant\DashboardController::class, 'showStatistics'])->name('participant.statistics');
    // Exam Execution
    // Exam Execution
    Route::get('/exam/{code}/terms', [\App\Http\Controllers\ExamController::class, 'terms'])->name('exam.terms');
    Route::post('/exam/{code}/agree', [\App\Http\Controllers\ExamController::class, 'agreeTerms'])->name('exam.agree');
    Route::get('/exam/{code}/categories', [\App\Http\Controllers\ExamController::class, 'categories'])->name('exam.categories');
    Route::post('/exam/{code}/category/{id}/start', [\App\Http\Controllers\ExamController::class, 'startCategory'])->name('exam.start_category');
    Route::get('/exam/{code}/category/{id}', [\App\Http\Controllers\ExamController::class, 'main'])->name('exam.main');
    Route::post('/exam/{code}/category/{id}/submit', [\App\Http\Controllers\ExamController::class, 'submitCategory'])->name('exam.submit_category');
    Route::post('/exam/{code}/finish', [\App\Http\Controllers\ExamController::class, 'finishSession'])->name('exam.finish');
    Route::get('/exam/{code}/success', [\App\Http\Controllers\ExamController::class, 'success'])->name('exam.success');
});

// Alias for admin_sesi dashboard
Route::get('/admin-sesi', function () {
    return redirect()->route('admin.sessions.index');
})->middleware(\App\Http\Middleware\AdminMiddleware::class);

// Public Session Registration (Can be used by anyone to register for a session)
Route::get('/register-session/{code}', [\App\Http\Controllers\PublicSessionController::class, 'registration'])->name('public.session.registration');
Route::post('/register-session/{code}', [\App\Http\Controllers\PublicSessionController::class, 'register'])->name('public.session.register');
Route::get('/register-session/{code}/success', [\App\Http\Controllers\PublicSessionController::class, 'success'])->name('public.session.success');

Route::middleware(AdminMiddleware::class)->group(function () {
    Route::get('/admin', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // Routes available to both Superadmin and Admin Sesi
    Route::resource('/admin/sessions', \App\Http\Controllers\Admin\ExamSessionController::class)->names('admin.sessions');
    Route::get('/admin/sessions/{id}/preview-questions', [\App\Http\Controllers\Admin\ExamSessionController::class, 'previewQuestions'])->name('admin.sessions.preview-questions');
    Route::patch('/admin/sessions/{id}/toggle-status', [\App\Http\Controllers\Admin\ExamSessionController::class, 'toggleStatus'])->name('admin.sessions.toggle-status');
    Route::post('/admin/sessions/{id}/generate-irt', [\App\Http\Controllers\Admin\ExamSessionController::class, 'generateIRTResults'])->name('admin.sessions.generate-irt');
    Route::post('/admin/sessions/{id}/upload-discussion', [\App\Http\Controllers\Admin\ExamSessionController::class, 'uploadDiscussionPdf'])->name('admin.sessions.upload-discussion');
    Route::get('/admin/sessions/{id}/export', [\App\Http\Controllers\Admin\ExamSessionController::class, 'exportResults'])->name('admin.sessions.export');
    Route::post('/admin/session-participants', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'store'])->name('admin.session-participants.store');
    Route::post('/admin/session-participants/new', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'storeNewUser'])->name('admin.session-participants.store-new');
    Route::patch('/admin/session-participants/{id}/privilege', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'updatePrivilege'])->name('admin.session-participants.update-privilege');
    Route::delete('/admin/session-participants/{id}', [\App\Http\Controllers\Admin\ExamSessionParticipantController::class, 'destroy'])->name('admin.session-participants.destroy');

    // Routes exclusively for Superadmin
    Route::middleware(\App\Http\Middleware\SuperAdminMiddleware::class)->group(function () {
        Route::resource('/admin/categories', CategoryController::class)->names('admin.categories');
        Route::resource('/admin/sub-categories', \App\Http\Controllers\Admin\SubCategoryController::class)->names('admin.sub-categories');
        Route::resource('/admin/questions', \App\Http\Controllers\Admin\QuestionBankController::class)->names('admin.questions');
        
        // Admin Management of Participants (Users)
        Route::resource('/admin/participants', \App\Http\Controllers\Admin\ParticipantController::class)->names('admin.participants');
    });
});
