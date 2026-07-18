<?php

namespace App\Services\SuperAdmin;

use App\Models\GradReport2;
use App\Services\GradReport2Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradReport2GroupService
{
    public function __construct(
        private readonly GradReport2Service $codes,
    ) {}

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateGroups(?string $q = null, int $perPage = 15): LengthAwarePaginator
    {
        $q = trim((string) $q);

        $groupCodesQuery = GradReport2::query()
            ->select('subject_code2')
            ->whereNotNull('subject_code2')
            ->where('subject_code2', '!=', '')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('subject_code2', 'like', $like)
                        ->orWhere('subject_code', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            })
            ->groupBy('subject_code2')
            ->orderBy('subject_code2');

        $paginator = $groupCodesQuery->paginate($perPage)->withQueryString();

        $codes = collect($paginator->items())->pluck('subject_code2')->filter()->values();

        $rowsByGroup = $codes->isEmpty()
            ? collect()
            : GradReport2::query()
                ->whereIn('subject_code2', $codes->all())
                ->orderBy('subject_code')
                ->get()
                ->groupBy(fn (GradReport2 $row) => trim((string) $row->subject_code2));

        $groups = $codes->map(function (string $groupCode) use ($rowsByGroup) {
            /** @var Collection<int, GradReport2> $members */
            $members = $rowsByGroup->get($groupCode, collect());
            $primary = $members->first(fn (GradReport2 $row) => trim((string) $row->subject_code) === $groupCode)
                ?? $members->first();

            return (object) [
                'group_code' => $groupCode,
                'subject' => trim((string) ($primary?->subject ?? '')),
                'member_count' => $members->count(),
                'members' => $members->map(fn (GradReport2 $row) => (object) [
                    'subject_code2' => trim((string) $row->subject_code2),
                    'subject_code' => trim((string) $row->subject_code),
                    'subject' => trim((string) $row->subject),
                    'username' => trim((string) ($row->username ?? '')),
                    'is_group_key' => trim((string) $row->subject_code) === $groupCode,
                ])->values(),
            ];
        });

        $paginator->setCollection($groups);

        return $paginator;
    }

    public function stats(?string $q = null): array
    {
        $q = trim((string) $q);

        $base = GradReport2::query()
            ->whereNotNull('subject_code2')
            ->where('subject_code2', '!=', '')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('subject_code2', 'like', $like)
                        ->orWhere('subject_code', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            });

        return [
            'groups' => (clone $base)->distinct()->count('subject_code2'),
            'members' => (clone $base)->count(),
        ];
    }

    /**
     * สร้างกลุ่มใหม่ หรือเพิ่มสมาชิกเข้ากลุ่มที่มีอยู่แล้ว
     * เงื่อนไขหลักจาก dump_grade_report2.php: รหัสวิชา (subject_code) ต้องไม่ซ้ำในระบบ
     *
     * @param  list<string>  $memberCodes
     */
    public function createGroup(
        string $groupCode,
        string $subject,
        array $memberCodes,
        string $username,
    ): array {
        $groupCode = $this->codes->normalizeSubjectCode($groupCode);
        $subject = mb_strtoupper(trim($subject));
        $username = trim($username);

        if ($groupCode === '') {
            throw ValidationException::withMessages([
                'group_code' => 'กรุณาระบุรหัสกลุ่ม',
            ]);
        }

        if ($subject === '') {
            throw ValidationException::withMessages([
                'subject' => 'กรุณาระบุชื่อวิชา',
            ]);
        }

        $members = $this->normalizeMemberList($memberCodes, $groupCode);
        if ($members === []) {
            throw ValidationException::withMessages([
                'member_codes' => 'กรุณาระบุรหัสวิชาในกลุ่มอย่างน้อย 1 รหัส',
            ]);
        }

        $existingGroup = $this->findGroupPrimary($groupCode);
        $resolvedGroup = $this->resolveGroupCodeLikeDump($groupCode, $members[0]);

        if ($resolvedGroup !== $groupCode && $this->groupExists($resolvedGroup)) {
            throw ValidationException::withMessages([
                'group_code' => "รหัสกลุ่ม «{$groupCode}» เชื่อมกับกลุ่ม «{$resolvedGroup}» อยู่แล้ว — ให้เพิ่มรหัสเข้ากลุ่ม {$resolvedGroup} แทนการสร้างกลุ่มใหม่",
            ]);
        }

        // อนุญาตถ้ารหัสอยู่กลุ่มนี้แล้ว (จะข้าม) แต่ห้ามถ้ารหัสอยู่กลุ่มอื่น
        $this->assertCodesAvailableForGroup($members, $groupCode);

        $subjectName = $existingGroup
            ? (trim((string) $existingGroup->subject) ?: $subject)
            : $subject;

        $inserted = [];

        DB::connection('scigrad')->transaction(function () use (
            $groupCode,
            $subjectName,
            $members,
            $username,
            &$inserted,
        ) {
            foreach ($members as $code) {
                if ($this->memberExists($code)) {
                    continue;
                }

                $this->insertRow($groupCode, $code, $subjectName, $username);
                $inserted[] = $code;
            }
        });

        if ($inserted === []) {
            $already = array_values(array_filter(
                $members,
                fn (string $code) => $this->memberExists($code),
            ));

            if ($existingGroup && $members === [$groupCode]) {
                throw ValidationException::withMessages([
                    'member_codes' => "กลุ่ม «{$groupCode}» มีอยู่ในระบบแล้ว แต่ยังไม่ได้ระบุรหัสวิชาใหม่ที่จะเพิ่ม — พิมพ์รหัสแล้วกด Enter หรือเลือกจากรายการแนะนำ ให้กลายเป็นชิปก่อนกดบันทึก",
                ]);
            }

            throw ValidationException::withMessages([
                'member_codes' => 'ไม่มีการเพิ่มรหัสใหม่ เพราะรหัสต่อไปนี้มีในกลุ่มแล้ว: '.implode(', ', $already)
                    .' — ถ้าเพิ่งพิมพ์รหัสไว้ ต้องกด Enter หรือเลือกจากรายการก่อนบันทึก',
            ]);
        }

        return [
            'group_code' => $groupCode,
            'subject' => $subjectName,
            'inserted' => $inserted,
            'was_existing' => (bool) $existingGroup,
        ];
    }

    public function addMember(
        string $groupCode,
        string $subjectCode,
        ?string $subject,
        string $username,
    ): void {
        $groupCode = $this->codes->normalizeSubjectCode($groupCode);
        $subjectCode = $this->codes->normalizeSubjectCode($subjectCode);
        $username = trim($username);

        if ($groupCode === '' || ! $this->groupExists($groupCode)) {
            throw ValidationException::withMessages([
                'group_code' => 'ไม่พบกลุ่มรายวิชานี้',
            ]);
        }

        if ($subjectCode === '') {
            throw ValidationException::withMessages([
                'subject_code' => 'กรุณาระบุรหัสวิชา',
            ]);
        }

        if ($this->memberExists($subjectCode)) {
            $existing = GradReport2::query()->where('subject_code', $subjectCode)->first();
            $inGroup = trim((string) ($existing?->subject_code2 ?? ''));

            throw ValidationException::withMessages([
                'subject_code' => "รหัส {$subjectCode} อยู่ในกลุ่ม {$inGroup} แล้ว (เงื่อนไขเดิม: ไม่อนุญาตรหัสซ้ำ)",
            ]);
        }

        // dump: ถ้ารหัสที่จะเพิ่มถูกใช้เป็นรหัสกลุ่มของกลุ่มอื่นอยู่แล้ว → ต้องเข้ากลุ่มนั้น
        $asGroupKey = GradReport2::query()
            ->where('subject_code2', $subjectCode)
            ->orderBy('subject_code')
            ->first();

        if ($asGroupKey && trim((string) $asGroupKey->subject_code2) !== $groupCode) {
            throw ValidationException::withMessages([
                'subject_code' => "รหัส {$subjectCode} เป็นรหัสกลุ่มของกลุ่มอื่นอยู่แล้ว — ไม่สามารถเพิ่มเข้ากลุ่ม {$groupCode} ได้",
            ]);
        }

        $primary = $this->findGroupPrimary($groupCode);
        $subjectName = mb_strtoupper(trim((string) ($subject ?: $primary?->subject ?: '')));
        if ($subjectName === '') {
            throw ValidationException::withMessages([
                'subject' => 'กรุณาระบุชื่อวิชา',
            ]);
        }

        $this->insertRow($groupCode, $subjectCode, $subjectName, $username);
    }

    public function updateMember(
        string $groupCode,
        string $subjectCode,
        string $newSubjectCode,
        string $subject,
    ): void {
        $groupCode = $this->codes->normalizeSubjectCode($groupCode);
        $subjectCode = $this->codes->normalizeSubjectCode($subjectCode);
        $newSubjectCode = $this->codes->normalizeSubjectCode($newSubjectCode);
        $subject = mb_strtoupper(trim($subject));

        if ($groupCode === '' || $subjectCode === '' || $newSubjectCode === '') {
            throw ValidationException::withMessages([
                'subject_code' => 'ข้อมูลรหัสวิชาไม่ครบ',
            ]);
        }

        if ($subject === '') {
            throw ValidationException::withMessages([
                'subject' => 'กรุณาระบุชื่อวิชา',
            ]);
        }

        $row = GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->where('subject_code', $subjectCode)
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'subject_code' => 'ไม่พบรหัสวิชานี้ในกลุ่ม',
            ]);
        }

        if ($newSubjectCode !== $subjectCode && $this->memberExists($newSubjectCode)) {
            throw ValidationException::withMessages([
                'subject_code' => "รหัส {$newSubjectCode} มีอยู่ในระบบแล้ว",
            ]);
        }

        GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->where('subject_code', $subjectCode)
            ->update([
                'subject_code' => $newSubjectCode,
                'subject' => $subject,
                'updated_at' => now(),
            ]);

        // ถ้าแก้รหัสที่เป็นตัวแทนกลุ่ม → อัปเดตรหัสกลุ่มของสมาชิกทั้งหมด
        if ($subjectCode === $groupCode && $newSubjectCode !== $groupCode) {
            GradReport2::query()
                ->where('subject_code2', $groupCode)
                ->update([
                    'subject_code2' => $newSubjectCode,
                    'updated_at' => now(),
                ]);
        }
    }

    public function updateGroupSubject(string $groupCode, string $subject): void
    {
        $groupCode = $this->codes->normalizeSubjectCode($groupCode);
        $subject = mb_strtoupper(trim($subject));

        if (! $this->groupExists($groupCode)) {
            throw ValidationException::withMessages([
                'group_code' => 'ไม่พบกลุ่มรายวิชา',
            ]);
        }

        if ($subject === '') {
            throw ValidationException::withMessages([
                'subject' => 'กรุณาระบุชื่อวิชา',
            ]);
        }

        GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->update([
                'subject' => $subject,
                'updated_at' => now(),
            ]);
    }

    public function removeMember(string $groupCode, string $subjectCode): void
    {
        $groupCode = $this->codes->normalizeSubjectCode($groupCode);
        $subjectCode = $this->codes->normalizeSubjectCode($subjectCode);

        $deleted = GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->where('subject_code', $subjectCode)
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'subject_code' => 'ไม่พบรหัสวิชานี้ในกลุ่ม',
            ]);
        }
    }

    public function deleteGroup(string $groupCode): int
    {
        $groupCode = $this->codes->normalizeSubjectCode($groupCode);

        if (! $this->groupExists($groupCode)) {
            throw ValidationException::withMessages([
                'group_code' => 'ไม่พบกลุ่มรายวิชา',
            ]);
        }

        return GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->delete();
    }

    /**
     * เงื่อนไขแบบ dump: หา subject_code2 ที่ควรใช้จริง
     */
    private function resolveGroupCodeLikeDump(string $requestedGroupCode, string $firstMemberCode): string
    {
        // ถ้ารหัสสมาชิกถูกใช้เป็นรหัสกลุ่มอยู่แล้ว → ใช้กลุ่มนั้น
        $asGroup = GradReport2::query()
            ->where('subject_code2', $firstMemberCode)
            ->orderBy('subject_code')
            ->first();
        if ($asGroup) {
            return trim((string) $asGroup->subject_code2);
        }

        // ถ้ารหัสกลุ่มที่ขอ ถูกใช้เป็นสมาชิกของกลุ่มอื่น → ใช้ subject_code2 ของแถวนั้น
        $asMember = GradReport2::query()
            ->where('subject_code', $requestedGroupCode)
            ->first();
        if ($asMember) {
            return trim((string) $asMember->subject_code2);
        }

        // ถ้ามีกลุ่มนี้อยู่แล้ว
        $existing = GradReport2::query()
            ->where('subject_code2', $requestedGroupCode)
            ->orderBy('subject_code')
            ->first();
        if ($existing) {
            return trim((string) $existing->subject_code2);
        }

        return $requestedGroupCode;
    }

    /**
     * @param  list<string>  $codes
     */
    private function assertCodesAvailableForGroup(array $codes, string $groupCode): void
    {
        $conflicts = GradReport2::query()
            ->whereIn('subject_code', $codes)
            ->get(['subject_code', 'subject_code2'])
            ->filter(fn (GradReport2 $row) => trim((string) $row->subject_code2) !== $groupCode);

        if ($conflicts->isEmpty()) {
            return;
        }

        $messages = $conflicts->map(
            fn (GradReport2 $row) => trim((string) $row->subject_code)
                .' (กลุ่ม '.trim((string) $row->subject_code2).')'
        )->all();

        throw ValidationException::withMessages([
            'member_codes' => 'รหัสต่อไปนี้มีในระบบแล้ว — ตามเงื่อนไขเดิมไม่อนุญาตรหัสซ้ำ: '.implode(', ', $messages),
        ]);
    }

    /**
     * @param  list<string>  $memberCodes
     * @return list<string>
     */
    private function normalizeMemberList(array $memberCodes, string $groupCode): array
    {
        $codes = [];
        foreach ($memberCodes as $code) {
            $normalized = $this->codes->normalizeSubjectCode((string) $code);
            if ($normalized !== '') {
                $codes[] = $normalized;
            }
        }

        $codes[] = $groupCode;

        return array_values(array_unique($codes));
    }

    private function memberExists(string $subjectCode): bool
    {
        return GradReport2::query()
            ->where('subject_code', $subjectCode)
            ->exists();
    }

    private function groupExists(string $groupCode): bool
    {
        return GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->exists();
    }

    private function findGroupPrimary(string $groupCode): ?GradReport2
    {
        return GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->where('subject_code', $groupCode)
            ->first()
            ?? GradReport2::query()
                ->where('subject_code2', $groupCode)
                ->orderBy('subject_code')
                ->first();
    }

    private function insertRow(
        string $groupCode,
        string $subjectCode,
        string $subject,
        string $username,
    ): void {
        GradReport2::query()->create([
            'subject_code2' => $groupCode,
            'subject_code' => $subjectCode,
            'subject' => $subject,
            'username' => $username !== '' ? $username : null,
        ]);
    }
}
