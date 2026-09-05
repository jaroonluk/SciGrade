<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DeptAdmin\DepartmentReportController;
use App\Http\Controllers\DeptAdmin\GradeReportReviewController;
use App\Http\Controllers\DeptAdmin\DeptSubmissionFileController;
use App\Http\Controllers\DeptAdmin\RegGradeStatusController as DeptRegGradeStatusController;
use App\Http\Controllers\FacultyAdmin\DeptSubmissionHistoryController;
use App\Http\Controllers\FacultyAdmin\FacultyReportController;
use App\Http\Controllers\FacultyAdmin\GradeReportReviewController as FacultyGradeReportReviewController;
use App\Http\Controllers\FacultyAdmin\GradeTermController;
use App\Http\Controllers\FacultyAdmin\PrivilegeController;
use App\Http\Controllers\FacultyAdmin\ProgramController;
use App\Http\Controllers\FacultyAdmin\RegCourseController;
use App\Http\Controllers\FacultyAdmin\RegGradeDumpController;
use App\Http\Controllers\FacultyAdmin\RegGradeManageController;
use App\Http\Controllers\FacultyAdmin\RegGradeStatusController;
use App\Http\Controllers\FacultyDeptSubmissionController;
use App\Http\Controllers\GradeReportController;
use App\Http\Controllers\GradeReportFileController;
use App\Http\Controllers\GradeReportFileDownloadController;
use App\Http\Controllers\GradeReportPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\DepartmentSubjectPatternController;
use App\Http\Controllers\SuperAdmin\GradReport2GroupController;
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
    Route::match(['get', 'post'], '/logout', [GoogleAuthController::class, 'logout'])->name('logout');

    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    Route::middleware('super.admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/impersonate', [ImpersonationController::class, 'index'])->name('impersonate');
        Route::post('/impersonate', [ImpersonationController::class, 'start'])->name('impersonate.start');
        Route::get('/impersonate/users/search', [ImpersonationController::class, 'searchUsers'])->name('impersonate.users.search');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::prefix('grade-reports')->name('grade-reports.')->group(function () {
        Route::get('/create', [GradeReportPageController::class, 'create'])->name('create');
        Route::get('/upload', [GradeReportPageController::class, 'upload'])->name('upload');
        Route::post('/upload', [GradeReportPageController::class, 'storeUpload'])->name('upload.store');
        Route::post('/parse-section-pdf', [GradeReportPageController::class, 'parseSectionPdf'])->name('parse-section-pdf');
        Route::get('/my', [GradeReportPageController::class, 'my'])->name('my');
        Route::post('/{gradeReport}/submit-corrections', [GradeReportPageController::class, 'submitCorrections'])->name('submit-corrections');
        Route::get('/approve', [GradeReportPageController::class, 'approve'])->name('approve');
        Route::get('/reports', [GradeReportPageController::class, 'reports'])->name('reports');
        Route::post('/reports/export', [FacultyReportController::class, 'export'])->name('reports.export');
        Route::get('/print-summary', [GradeReportPageController::class, 'printSummary'])->name('print.summary');
        Route::get('/{gradeReport}/edit', [GradeReportPageController::class, 'edit'])->name('edit');
        Route::get('/{gradeReport}/print', [GradeReportPageController::class, 'print'])->name('print');
        Route::get('/{gradeReport}/files-zip', [GradeReportFileDownloadController::class, 'downloadReport'])->name('files.zip');
        Route::get('/{gradeReport}/files/{file}', [GradeReportFileController::class, 'show'])->name('files.show');
    });

    Route::middleware('dept.admin')->prefix('dept-admin')->name('dept-admin.')->group(function () {
        Route::get('/reviews', [GradeReportReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/files/download', [GradeReportFileDownloadController::class, 'downloadDept'])->name('reviews.files.download');
        Route::post('/reviews/registrar-files/preview', [GradeReportReviewController::class, 'previewRegistrarUploads'])->name('reviews.registrar-files.preview');
        Route::post('/reviews/registrar-files', [GradeReportReviewController::class, 'uploadRegistrarFiles'])->name('reviews.registrar-files.store');
        Route::delete('/reviews/{gradeReport}/registrar-files/{file}', [GradeReportReviewController::class, 'destroyRegistrarFile'])->name('reviews.registrar-files.destroy');
        Route::post('/reviews/{gradeReport}/approve', [GradeReportReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{gradeReport}/reject', [GradeReportReviewController::class, 'reject'])->name('reviews.reject');
        Route::post('/reviews/{gradeReport}/send-back', [GradeReportReviewController::class, 'sendBack'])->name('reviews.send-back');
        Route::post('/reviews/{gradeReport}/revert', [GradeReportReviewController::class, 'revert'])->name('reviews.revert');
        Route::get('/reports', [DepartmentReportController::class, 'form'])->name('reports.form');
        Route::get('/reports/date-summary', [DepartmentReportController::class, 'dateSummary'])->name('reports.date-summary');
        Route::post('/reports/export', [DepartmentReportController::class, 'export'])->name('reports.export');
        Route::get('/reg-grade-status', [DeptRegGradeStatusController::class, 'index'])->name('reg-grade-status.index');
        Route::post('/reg-grade-status/{gradeReport}/approve-dept', [DeptRegGradeStatusController::class, 'approveDepartment'])->name('reg-grade-status.approve-dept');
        Route::post('/reg-grade-status/{gradeReport}/revert-dept', [DeptRegGradeStatusController::class, 'revertDepartment'])->name('reg-grade-status.revert-dept');
    });

    Route::middleware('faculty.admin')->prefix('faculty-admin')->name('faculty-admin.')->group(function () {
        Route::get('/reviews', [FacultyGradeReportReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/bulk-approve', [FacultyGradeReportReviewController::class, 'bulkApprove'])->name('reviews.bulk-approve');
        Route::post('/reviews/files/download', [GradeReportFileDownloadController::class, 'downloadFaculty'])->name('reviews.files.download');
        Route::post('/reviews/{gradeReport}/approve', [FacultyGradeReportReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{gradeReport}/mark-checked', [FacultyGradeReportReviewController::class, 'markChecked'])->name('reviews.mark-checked');
        Route::post('/reviews/{gradeReport}/reject', [FacultyGradeReportReviewController::class, 'reject'])->name('reviews.reject');
        Route::post('/reviews/{gradeReport}/send-back', [FacultyGradeReportReviewController::class, 'sendBack'])->name('reviews.send-back');

        Route::get('/settings/term', [GradeTermController::class, 'edit'])->name('settings.term');
        Route::put('/settings/term', [GradeTermController::class, 'update'])->name('settings.term.update');

        Route::middleware('super.admin')->group(function () {
            Route::get('/settings/programs', [ProgramController::class, 'index'])->name('settings.programs.index');
            Route::get('/settings/programs/create', [ProgramController::class, 'create'])->name('settings.programs.create');
            Route::post('/settings/programs', [ProgramController::class, 'store'])->name('settings.programs.store');
            Route::get('/settings/programs/{program}/edit', [ProgramController::class, 'edit'])->name('settings.programs.edit');
            Route::put('/settings/programs/{program}', [ProgramController::class, 'update'])->name('settings.programs.update');
            Route::delete('/settings/programs/{program}', [ProgramController::class, 'destroy'])->name('settings.programs.destroy');
        });

        Route::get('/department-patterns', [DepartmentSubjectPatternController::class, 'index'])->name('department-patterns.index');
        Route::post('/department-patterns', [DepartmentSubjectPatternController::class, 'store'])->name('department-patterns.store');
        Route::put('/department-patterns/{pattern}', [DepartmentSubjectPatternController::class, 'update'])->name('department-patterns.update');
        Route::delete('/department-patterns/{pattern}', [DepartmentSubjectPatternController::class, 'destroy'])->name('department-patterns.destroy');
        Route::post('/department-patterns/restore', [DepartmentSubjectPatternController::class, 'restoreDefaults'])->name('department-patterns.restore');

        Route::get('/settings/privileges', [PrivilegeController::class, 'index'])->name('settings.privileges.index');
        Route::get('/settings/privileges/users/search', [PrivilegeController::class, 'searchUsers'])->name('settings.privileges.users.search');
        Route::post('/settings/privileges', [PrivilegeController::class, 'store'])->name('settings.privileges.store');
        Route::put('/settings/privileges/{privilege}', [PrivilegeController::class, 'update'])->name('settings.privileges.update');
        Route::delete('/settings/privileges/{privilege}', [PrivilegeController::class, 'destroy'])->name('settings.privileges.destroy');

        Route::get('/settings/reg-courses', [RegCourseController::class, 'index'])->name('settings.reg-courses.index');
        Route::post('/settings/reg-courses/sync', [RegCourseController::class, 'sync'])->name('settings.reg-courses.sync');

        Route::get('/settings/reg-grade-dump', [RegGradeDumpController::class, 'index'])->name('settings.reg-grade-dump.index');
        Route::post('/settings/reg-grade-dump', [RegGradeDumpController::class, 'dump'])->name('settings.reg-grade-dump.dump');
        Route::post('/settings/reg-grade-dump/store', [RegGradeDumpController::class, 'store'])->name('settings.reg-grade-dump.store');
        Route::delete('/settings/reg-grade-dump', [RegGradeDumpController::class, 'destroy'])->name('settings.reg-grade-dump.destroy');
        Route::delete('/settings/reg-grade-dump/bulk', [RegGradeDumpController::class, 'bulkDestroy'])->name('settings.reg-grade-dump.bulk-destroy');

        Route::get('/settings/reg-grade-manage', [RegGradeManageController::class, 'index'])->name('settings.reg-grade-manage.index');
        Route::post('/settings/reg-grade-manage', [RegGradeManageController::class, 'store'])->name('settings.reg-grade-manage.store');
        Route::get('/settings/reg-grade-manage/instructors/search', [RegGradeManageController::class, 'searchInstructors'])->name('settings.reg-grade-manage.instructors.search');
        Route::get('/settings/reg-grade-manage/edit', [RegGradeManageController::class, 'edit'])->name('settings.reg-grade-manage.edit');
        Route::put('/settings/reg-grade-manage', [RegGradeManageController::class, 'update'])->name('settings.reg-grade-manage.update');
        Route::delete('/settings/reg-grade-manage', [RegGradeManageController::class, 'destroy'])->name('settings.reg-grade-manage.destroy');
        Route::delete('/settings/reg-grade-manage/bulk', [RegGradeManageController::class, 'bulkDestroy'])->name('settings.reg-grade-manage.bulk-destroy');

        Route::get('/settings/reg-grade-status', [RegGradeStatusController::class, 'index'])->name('settings.reg-grade-status.index');
        Route::post('/settings/reg-grade-status/{gradeReport}/approve-faculty', [RegGradeStatusController::class, 'approveFaculty'])->name('settings.reg-grade-status.approve-faculty');
        Route::post('/settings/reg-grade-status/{gradeReport}/revert-faculty', [RegGradeStatusController::class, 'revertFaculty'])->name('settings.reg-grade-status.revert-faculty');

        Route::get('/dept-submission-history', [DeptSubmissionHistoryController::class, 'index'])->name('dept-submission-history.index');

        Route::get('/grad-report2-groups', [GradReport2GroupController::class, 'index'])->name('grad-report2-groups.index');
        Route::post('/grad-report2-groups', [GradReport2GroupController::class, 'store'])->name('grad-report2-groups.store');
        Route::put('/grad-report2-groups', [GradReport2GroupController::class, 'updateGroup'])->name('grad-report2-groups.update');
        Route::delete('/grad-report2-groups', [GradReport2GroupController::class, 'destroyGroup'])->name('grad-report2-groups.destroy');
        Route::post('/grad-report2-groups/members', [GradReport2GroupController::class, 'storeMember'])->name('grad-report2-groups.members.store');
        Route::put('/grad-report2-groups/members', [GradReport2GroupController::class, 'updateMember'])->name('grad-report2-groups.members.update');
        Route::delete('/grad-report2-groups/members', [GradReport2GroupController::class, 'destroyMember'])->name('grad-report2-groups.members.destroy');
    });

    Route::redirect('/super-admin/grad-report2-groups', '/faculty-admin/grad-report2-groups');
    Route::redirect('/super-admin/department-patterns', '/faculty-admin/department-patterns');

    Route::redirect('/templade', '/grade-reports/create')->name('templade');

    Route::get('/api/subjects/search', [SubjectController::class, 'search']);
    Route::get('/api/grad-report2/peers', [SubjectController::class, 'jointPeers']);

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
