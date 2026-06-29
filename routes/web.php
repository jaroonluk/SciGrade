<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\GradeReportController;
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
    Route::get('/dashboard', fn () => view('templade'))->name('dashboard');
    Route::get('/templade', fn () => view('templade'))->name('templade');
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

    Route::get('/api/grade-reports', [GradeReportController::class, 'index']);
    Route::post('/api/grade-reports', [GradeReportController::class, 'store']);
    Route::put('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'update']);
    Route::delete('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'destroy']);
});
