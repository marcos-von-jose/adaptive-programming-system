<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StudentProgressController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFactorSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseManagementController;
use App\Http\Controllers\Instructor\AnalyticsController;
use App\Http\Controllers\Instructor\ProfileController;
use App\Http\Controllers\LessonVideoController;
use App\Http\Controllers\StudentAssessmentController;
use App\Http\Controllers\StudentExerciseController;
use App\Http\Controllers\StudentFeedbackController;
use App\Http\Controllers\StudentLearningController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentProgressController as OwnProgressController;
use App\Http\Controllers\UserPhotoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/settings/two-factor', [TwoFactorSettingsController::class, 'edit'])->name('account.two-factor.edit');
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('/users/{user}/photo', UserPhotoController::class)->name('users.photo');
    Route::get('/lessons/{content}/video', LessonVideoController::class)->name('lessons.video');

    Route::prefix('admin')->name('admin.')->middleware('role:Administrator')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::resource('users', UserController::class)->except('show');
        Route::get('/settings', [SystemSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');
        Route::resource('content', ContentController::class)->except('show');
        Route::resource('exercises', ExerciseManagementController::class)->except('show');
        Route::post('/exercises/{exercise}/timers/{student}/reset', [ExerciseManagementController::class, 'resetTimer'])->name('exercises.timers.reset');
        Route::resource('assessments', AssessmentController::class)->except('show');
        Route::post('/assessments/{assessment}/questions', [AssessmentController::class, 'storeItem'])->name('assessments.questions.store');
        Route::put('/assessments/{assessment}/questions/{item}', [AssessmentController::class, 'updateItem'])->name('assessments.questions.update');
        Route::delete('/assessments/{assessment}/questions/{item}', [AssessmentController::class, 'destroyItem'])->name('assessments.questions.destroy');
        Route::post('/assessments/{assessment}/timers/{student}/reset', [AssessmentController::class, 'resetTimer'])->name('assessments.timers.reset');
        Route::get('/progress', [StudentProgressController::class, 'index'])->name('progress.index');
        Route::get('/progress/{student}', [StudentProgressController::class, 'show'])->name('progress.show');
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/feedback/create', [FeedbackController::class, 'create'])->name('feedback.create');
        Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
        Route::get('/feedback/{feedback}/edit', [FeedbackController::class, 'edit'])->name('feedback.edit');
        Route::put('/feedback/{feedback}', [FeedbackController::class, 'update'])->name('feedback.update');
        Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('feedback.destroy');
        Route::get('/logs', [SystemLogController::class, 'index'])->name('logs.index');
        Route::delete('/logs', [SystemLogController::class, 'clearAll'])->name('logs.clear');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    Route::prefix('instructor')->name('instructor.')->middleware('role:Instructor')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'instructor'])->name('dashboard');
        Route::resource('content', ContentController::class)->except('show');
        Route::resource('exercises', ExerciseManagementController::class)->except('show');
        Route::post('/exercises/{exercise}/timers/{student}/reset', [ExerciseManagementController::class, 'resetTimer'])->name('exercises.timers.reset');
        Route::resource('assessments', AssessmentController::class)->except('show');
        Route::post('/assessments/{assessment}/questions', [AssessmentController::class, 'storeItem'])->name('assessments.questions.store');
        Route::put('/assessments/{assessment}/questions/{item}', [AssessmentController::class, 'updateItem'])->name('assessments.questions.update');
        Route::delete('/assessments/{assessment}/questions/{item}', [AssessmentController::class, 'destroyItem'])->name('assessments.questions.destroy');
        Route::post('/assessments/{assessment}/timers/{student}/reset', [AssessmentController::class, 'resetTimer'])->name('assessments.timers.reset');
        Route::get('/progress', [StudentProgressController::class, 'index'])->name('progress.index');
        Route::get('/progress/{student}', [StudentProgressController::class, 'show'])->name('progress.show');
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/feedback/create', [FeedbackController::class, 'create'])->name('feedback.create');
        Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::get('/student/dashboard', [DashboardController::class, 'student'])
        ->middleware('role:Student')
        ->name('student.dashboard');
    Route::middleware('role:Student')->prefix('student')->name('student.')->group(function () {
        Route::get('/learning', [StudentLearningController::class, 'index'])->name('learning.index');
        Route::get('/learning/{content}', [StudentLearningController::class, 'show'])->name('learning.show');
        Route::post('/learning/{content}/complete', [StudentLearningController::class, 'complete'])->name('learning.complete');
        Route::get('/assessments', [StudentAssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/assessments/{assessment}', [StudentAssessmentController::class, 'show'])->middleware('lessons.completed')->name('assessments.show');
        Route::post('/assessments/{assessment}/timer', [StudentAssessmentController::class, 'startTimer'])->middleware('lessons.completed')->name('assessments.timer.start');
        Route::get('/assessments/{assessment}/work', [StudentAssessmentController::class, 'work'])->middleware('lessons.completed')->name('assessments.work');
        Route::post('/assessments/{assessment}', [StudentAssessmentController::class, 'store'])->middleware('lessons.completed')->name('assessments.store');
        Route::get('/exercises', [StudentExerciseController::class, 'index'])->name('exercises.index');
        Route::get('/exercises/{exercise}', [StudentExerciseController::class, 'show'])->middleware('lessons.completed')->name('exercises.show');
        Route::post('/exercises/{exercise}/timer', [StudentExerciseController::class, 'startTimer'])->middleware('lessons.completed')->name('exercises.timer.start');
        Route::get('/exercises/{exercise}/work', [StudentExerciseController::class, 'work'])->middleware('lessons.completed')->name('exercises.work');
        Route::post('/exercises/{exercise}/submissions', [StudentExerciseController::class, 'store'])->middleware('lessons.completed')->name('exercises.submit');
        Route::get('/submissions/{submission}', [StudentExerciseController::class, 'submission'])->name('submissions.show');
        Route::get('/progress', [OwnProgressController::class, 'index'])->name('progress.index');
        Route::get('/feedback', [StudentFeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/profile', [StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
    });
});
