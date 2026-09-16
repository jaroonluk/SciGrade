<?php

namespace App\Services\ThesisGrade;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Support\ThesisCourse;
use InvalidArgumentException;

class ThesisGradeService
{
    public function __construct(
        private readonly ThesisGradeComplianceService $compliance,
        private readonly ThesisGradeNotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, string $username, string $teacher, ?ThesisGrade $report = null): ThesisGrade
    {
        $payload = $this->normalizedPayload($data, $username, $teacher);

        if ($report === null) {
            $existing = ThesisGrade::query()
                ->where('username', $username)
                ->where('subject_code', $payload['subject_code'])
                ->where('section', $payload['section'])
                ->where('term', $payload['term'])
                ->where('year', $payload['year'])
                ->first();

            if ($existing) {
                if (! $existing->isEditable()) {
                    throw new InvalidArgumentException('รายวิชานี้ถูกส่งแล้ว ไม่สามารถสร้างซ้ำได้');
                }
                $report = $existing;
            }
        }

        if ($report) {
            if (! $report->isEditable()) {
                throw new InvalidArgumentException('รายการนี้ไม่สามารถแก้ไขได้ในสถานะปัจจุบัน');
            }
            $report->update($payload);
        } else {
            $report = ThesisGrade::query()->create($payload);
        }

        $this->syncStudents($report, is_array($data['students'] ?? null) ? $data['students'] : []);

        return $report->fresh(['students', 'files']);
    }

    /**
     * @return list<string>
     */
    public function submit(ThesisGrade $report): array
    {
        $report->loadMissing('students', 'files');

        $errors = $this->compliance->errorsForSubmit(
            $report->tsFiles()->isNotEmpty(),
            (bool) $report->checked_proposal,
            (bool) $report->checked_signed,
            $this->studentSnapshots($report),
        );

        if ($errors !== []) {
            return $errors;
        }

        $report->update([
            'status' => ThesisGrade::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'return_reason' => null,
        ]);

        $this->notifications->notifyDeptAdminsOfSubmit($report->fresh(['students', 'files']) ?? $report);

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $students
     */
    public function syncStudents(ThesisGrade $report, array $students): void
    {
        $keepIds = [];

        foreach (array_values($students) as $index => $row) {
            $code = trim((string) ($row['student_code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $attributes = [
                'student_code' => $code,
                'name_prefix' => trim((string) ($row['name_prefix'] ?? '')) ?: null,
                'first_name' => trim((string) ($row['first_name'] ?? '')) ?: null,
                'last_name' => trim((string) ($row['last_name'] ?? '')) ?: null,
                'student_name' => $this->composeStudentName($row),
                'degree' => ($row['degree'] ?? '') === ThesisGradeStudent::DEGREE_DOCTORAL
                    ? ThesisGradeStudent::DEGREE_DOCTORAL
                    : ThesisGradeStudent::DEGREE_MASTER,
                'thesis_terms_count' => max(1, (int) ($row['thesis_terms_count'] ?? 1)),
                'proposal_approved' => $this->toBool($row['proposal_approved'] ?? false),
                'grade' => strtoupper(trim((string) ($row['grade'] ?? 'S'))) ?: 'S',
                'credits_registered' => $this->nullableDecimal($row['credits_registered'] ?? null),
                'credits_passed' => $this->nullableDecimal($row['credits_passed'] ?? null),
                // คง sync กับ credits_passed เพื่อ logic S=0 เดิม
                'progress_credits' => $this->nullableDecimal($row['credits_passed'] ?? $row['progress_credits'] ?? null),
                'completed' => $this->toBool($row['completed'] ?? false),
                'defense_date' => $this->toBool($row['completed'] ?? false)
                    ? $this->nullableDate($row['defense_date'] ?? null)
                    : null,
                'note' => ThesisGradeStudent::sanitizeNote($row['note'] ?? null),
                'sort_order' => $index + 1,
            ];

            $id = (int) ($row['id'] ?? $row['student_id'] ?? 0);
            $student = $id > 0
                ? $report->students()->whereKey($id)->first()
                : null;

            if ($student) {
                $student->update($attributes);
            } else {
                $student = $report->students()->create($attributes);
            }

            $keepIds[] = (int) $student->student_id;
        }

        $removed = $report->students()
            ->with('files')
            ->when($keepIds !== [], fn ($query) => $query->whereNotIn('student_id', $keepIds))
            ->get();

        foreach ($removed as $student) {
            $student->files->each->delete();
            $student->delete();
        }
    }

    /**
     * อัปเดตหน่วยกิตที่ลง / ผ่าน / หมายเหตุ / เกรด จากผลอ่าน PDF โดยจับคู่รหัสนักศึกษา
     *
     * @param  list<array<string, mixed>>  $parsedStudents
     * @return int จำนวนคนที่อัปเดต
     */
    public function applyParsedCreditsToStudents(ThesisGrade $report, array $parsedStudents): int
    {
        $report->loadMissing('students');
        $byCode = [];
        foreach ($parsedStudents as $row) {
            $code = trim((string) ($row['student_code'] ?? ''));
            if ($code !== '') {
                $byCode[$code] = $row;
            }
        }

        $updated = 0;
        foreach ($report->students as $student) {
            $code = trim((string) $student->student_code);
            if ($code === '' || ! isset($byCode[$code])) {
                continue;
            }
            $row = $byCode[$code];
            $payload = [];
            if (array_key_exists('credits_registered', $row) && $row['credits_registered'] !== null && $row['credits_registered'] !== '') {
                $payload['credits_registered'] = $this->nullableDecimal($row['credits_registered']);
            }
            if (array_key_exists('credits_passed', $row) && $row['credits_passed'] !== null && $row['credits_passed'] !== '') {
                $payload['credits_passed'] = $this->nullableDecimal($row['credits_passed']);
                $payload['progress_credits'] = $payload['credits_passed'];
            }
            if (array_key_exists('note', $row)) {
                $payload['note'] = ThesisGradeStudent::sanitizeNote($row['note'] ?? null);
            }
            if (array_key_exists('grade', $row) && trim((string) ($row['grade'] ?? '')) !== '') {
                $payload['grade'] = strtoupper(trim((string) $row['grade']));
            }
            if ($payload === []) {
                continue;
            }
            $student->update($payload);
            $updated++;
        }

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedPayload(array $data, string $username, string $teacher): array
    {
        $subject = trim((string) ($data['subject'] ?? ''));
        $kind = ThesisCourse::courseKind($subject);

        return [
            'term' => (int) $data['term'],
            'year' => (int) $data['year'],
            'subject_code' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $data['subject_code']) ?: ''),
            'subject' => $subject,
            'section' => str_pad((string) ((int) preg_replace('/\D/', '', (string) ($data['section'] ?? '1')) ?: 1), 2, '0', STR_PAD_LEFT),
            'course_kind' => $kind,
            'username' => $username,
            'teacher' => $teacher !== '' ? $teacher : ($data['teacher'] ?? null),
            'checked_proposal' => $this->toBool($data['checked_proposal'] ?? false),
            'checked_signed' => $this->toBool($data['checked_signed'] ?? false),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function studentSnapshots(ThesisGrade $report): array
    {
        return $report->students->map(fn (ThesisGradeStudent $student) => [
            'student_code' => $student->student_code,
            'student_name' => $student->displayName(),
            'degree' => $student->degree,
            'thesis_terms_count' => (int) $student->thesis_terms_count,
            'proposal_approved' => (bool) $student->proposal_approved,
            'grade' => $student->grade,
            'progress_credits' => $student->credits_passed ?? $student->progress_credits,
            'completed' => (bool) $student->completed,
            'defense_date' => $student->defense_date?->toDateString(),
            'has_s0_letter' => $report->files->contains(
                fn (ThesisGradeFile $file) => $file->isS0Letter() && (int) $file->student_id === (int) $student->student_id
            ),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function composeStudentName(array $row): string
    {
        $composed = trim(preg_replace(
            '/\s+/u',
            ' ',
            trim((string) ($row['name_prefix'] ?? '')).' '.trim((string) ($row['first_name'] ?? '')).' '.trim((string) ($row['last_name'] ?? ''))
        ) ?? '');

        if ($composed !== '') {
            return $composed;
        }

        return trim((string) ($row['student_name'] ?? ''));
    }

    private function toBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'on', 'yes', 'true'], true);
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
