<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DeptAdmin\DepartmentReportController;
use App\Http\Controllers\DeptAdmin\GradeReportReviewController;
use App\Http\Controllers\DeptAdmin\DeptSubmissionFileController;
use App\Http\Controllers\FacultyAdmin\GradeReportReviewController as FacultyGradeReportReviewController;
use App\Http\Controllers\FacultyAdmin\GradeTermController;
use App\Http\Controllers\FacultyAdmin\PrivilegeController;
use App\Http\Controllers\FacultyAdmin\ProgramController;
use App\Http\Controllers\FacultyDeptSubmissionController;
use App\Http\Controllers\GradeReportController;
use App\Http\Controllers\GradeReportFileController;
use App\Http\Controllers\GradeReportPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubjectController;
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
        Route::get('/{gradeReport}/files/{file}', [GradeReportFileController::class, 'show'])->name('files.show');
    });

    Route::middleware('dept.admin')->prefix('dept-admin')->name('dept-admin.')->group(function () {
        Route::get('/reviews', [GradeReportReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{gradeReport}/approve', [GradeReportReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{gradeReport}/reject', [GradeReportReviewController::class, 'reject'])->name('reviews.reject');
        Route::get('/reports', [DepartmentReportController::class, 'form'])->name('reports.form');
        Route::post('/reports/export', [DepartmentReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('faculty.admin')->prefix('faculty-admin')->name('faculty-admin.')->group(function () {
        Route::get('/reviews', [FacultyGradeReportReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/bulk-approve', [FacultyGradeReportReviewController::class, 'bulkApprove'])->name('reviews.bulk-approve');
        Route::post('/reviews/{gradeReport}/approve', [FacultyGradeReportReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{gradeReport}/reject', [FacultyGradeReportReviewController::class, 'reject'])->name('reviews.reject');

        Route::get('/settings/term', [GradeTermController::class, 'edit'])->name('settings.term');
        Route::put('/settings/term', [GradeTermController::class, 'update'])->name('settings.term.update');

        Route::get('/settings/programs', [ProgramController::class, 'index'])->name('settings.programs.index');
        Route::get('/settings/programs/create', [ProgramController::class, 'create'])->name('settings.programs.create');
        Route::post('/settings/programs', [ProgramController::class, 'store'])->name('settings.programs.store');
        Route::get('/settings/programs/{program}/edit', [ProgramController::class, 'edit'])->name('settings.programs.edit');
        Route::put('/settings/programs/{program}', [ProgramController::class, 'update'])->name('settings.programs.update');
        Route::delete('/settings/programs/{program}', [ProgramController::class, 'destroy'])->name('settings.programs.destroy');

        Route::get('/settings/privileges', [PrivilegeController::class, 'index'])->name('settings.privileges.index');
        Route::post('/settings/privileges', [PrivilegeController::class, 'store'])->name('settings.privileges.store');
        Route::put('/settings/privileges/{privilege}', [PrivilegeController::class, 'update'])->name('settings.privileges.update');
        Route::delete('/settings/privileges/{privilege}', [PrivilegeController::class, 'destroy'])->name('settings.privileges.destroy');
    });

    Route::redirect('/templade', '/grade-reports/create')->name('templade');

    Route::get('/api/subjects/search', [SubjectController::class, 'search']);

    Route::get('/dept-submissions/files/{file}', [DeptSubmissionFileController::class, 'show'])->name('dept-submissions.files.show');

    Route::post('/api/dept-submissions/files', [DeptSubmissionFileController::class, 'store']);
    Route::put('/api/dept-submissions/files/{file}', [DeptSubmissionFileController::class, 'update']);
    Route::post('/api/dept-submissions/files/{file}', [DeptSubmissionFileController::class, 'update']);
    Route::delete('/api/dept-submissions/files/{file}', [DeptSubmissionFileController::class, 'destroy']);
    Route::post('/api/faculty-admin/dept-submissions/{submission}/receive', [FacultyDeptSubmissionController::class, 'receive']);

    Route::get('/api/grade-reports', [GradeReportController::class, 'index']);
    Route::get('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'show']);
    Route::post('/api/grade-reports', [GradeReportController::class, 'store']);
    Route::put('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'update']);
    Route::delete('/api/grade-reports/{gradeReport}', [GradeReportController::class, 'destroy']);
    Route::post('/api/grade-reports/{gradeReport}/files', [GradeReportFileController::class, 'store']);
    Route::delete('/api/grade-reports/{gradeReport}/files/{file}', [GradeReportFileController::class, 'destroy']);
});
