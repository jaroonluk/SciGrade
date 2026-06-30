<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\GradeReportController;
use App\Http\Controllers\GradeReportPageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::post('/role', [HomeController::class, 'setRole'])->name('role.set');
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

    Route::prefix('grade-reports')->name('grade-reports.')->group(function () {
        Route::get('/create', [GradeReportPageController::class, 'create'])->name('create');
        Route::get('/upload', [GradeReportPageController::class, 'upload'])->name('upload');
        Route::post('/upload', [GradeReportPageController::class, 'storeUpload'])->name('upload.store');
        Route::get('/my', [GradeReportPageController::class, 'my'])->name('my');
        Route::get('/approve', [GradeReportPageController::class, 'approve'])->name('approve');
        Route::get('/reports', [GradeReportPageController::class, 'reports'])->name('reports');
        Route::get('/print-summary', [GradeReportPageController::class, 'printSummary'])->name('print.summary');
        Route::get('/{gradeReport}/edit', [GradeReportPageController::class, 'edit'])->name('edit');
        Route::get('/{gradeReport}/print', [GradeReportPageController::class, 'print'])->name('print');
    });

    Route::redirect('/templade', '/grade-reports/create')->name('templade');

    Route::get('/api/grade-reports', [GradeReportController::class, 'index']);
    Route::get('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'show']);
    Route::post('/api/grade-reports', [GradeReportController::class, 'store']);
    Route::put('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'update']);
    Route::delete('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'destroy']);
});
