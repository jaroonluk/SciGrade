<?php

namespace App\Services\ThesisGrade;

use App\Models\TblPrivilege;
use App\Models\TblUser;
use App\Models\ThesisGrade;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\KkuEmailService;
use Illuminate\Support\Facades\Log;

class ThesisGradeNotificationService
{
    public function __construct(
        private readonly KkuEmailService $mail,
        private readonly DepartmentSubjectFilter $subjectFilter,
        private readonly DepartmentAccessService $departmentAccess,
    ) {}

    public function notifyDeptAdminsOfSubmit(ThesisGrade $report): void
    {
        $emails = $this->deptAdminEmailsForSubject((string) $report->subject_code);
        if ($emails === []) {
            Log::info('ThesisGrade notify: no dept admin emails', [
                'thesis_grade_id' => $report->thesis_grade_id,
                'subject_code' => $report->subject_code,
            ]);

            return;
        }

        $teacher = trim((string) ($report->teacher ?: $report->username));
        $code = $report->displayCode();
        $section = $report->paddedSection();
        $termYear = $report->termLabel().' '.$report->year;
        $url = rtrim((string) config('app.url'), '/').'/';

        $html = "<html><body style='font-family: Tahoma, Arial, sans-serif;'>"
            ."<h3 style='color:#854d0e;'>แจ้งเตือน — ส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ</h3>"
            .'<p>มีอาจารย์ <strong>'.e($teacher).'</strong> ส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ ให้ท่านเข้าไปตรวจสอบ</p>'
            ."<div style='background:#fefce8;padding:12px 16px;border-left:4px solid #ca8a04;'>"
            .'<p style="margin:4px 0;"><strong>วิชา:</strong> '.e($code).' · กลุ่ม '.e($section).'</p>'
            .'<p style="margin:4px 0;"><strong>ชื่อวิชา:</strong> '.e((string) $report->subject).'</p>'
            .'<p style="margin:4px 0;"><strong>ภาคการศึกษา:</strong> '.e($termYear).'</p>'
            .'</div>'
            .'<p style="margin-top:16px;">เข้าสู่ระบบได้ที่ <a href="'.e($url).'">'.e($url).'</a></p>'
            .'<p style="color:#666;margin-top:20px;">SciGrade · คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น</p>'
            .'</body></html>';

        $this->mail->send(
            $emails,
            'แจ้งเตือน: มีการส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ — '.$code,
            $html,
        );
    }

    /**
     * @return list<string>
     */
    public function deptAdminEmailsForSubject(string $subjectCode): array
    {
        $departmentIds = $this->subjectFilter->departmentIdsMatchingSubject($subjectCode);
        if ($departmentIds === []) {
            return [];
        }

        $usernames = TblPrivilege::query()
            ->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT)
            ->where('level', TblPrivilege::LEVEL_DEPT)
            ->pluck('username')
            ->map(fn ($u) => trim((string) $u))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($usernames === []) {
            return [];
        }

        $emails = [];
        foreach ($usernames as $username) {
            $staff = TblUser::query()->where('username', $username)->first();
            if (! $staff) {
                continue;
            }

            $allowed = $this->departmentAccess->allowedDepartmentIds($staff);
            if (array_intersect($allowed, $departmentIds) === []) {
                continue;
            }

            $email = strtolower(trim((string) ($staff->email ?? '')));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }
}
