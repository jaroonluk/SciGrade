<?php

namespace App\Services\SuperAdmin;

use App\Exceptions\GradReport2CodeConflictException;
use App\Models\GradReport2;
use App\Models\TblPrivilege;
use App\Services\GradReport2Service;
use App\Support\ThesisCourse;
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
    public function paginateGroups(?string $q = null, int $perPage = 50): LengthAwarePaginator
    {
        $q = trim((string) $q);
        $codeSql = GradReport2::normalizedCodeSql('subject_code2');

        $groupCodesQuery = GradReport2::query()
            ->selectRaw($codeSql.' as subject_code2')
            ->whereNotNull('subject_code2')
            ->whereRaw($codeSql." != ''")
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('subject_code2', 'like', $like)
                        ->orWhere('subject_code', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            })
            ->groupByRaw($codeSql)
            ->orderByRaw($codeSql);

        $paginator = $groupCodesQuery->paginate($perPage)->withQueryString();

        $codes = collect($paginator->items())
            ->pluck('subject_code2')
            ->map(fn ($code) => GradReport2::normalizeCode((string) $code))
            ->filter()
            ->unique()
            ->values();

        $rowsByGroup = $codes->isEmpty()
            ? collect()
            : GradReport2::query()
                ->whereNormalizedCodeIn('subject_code2', $codes->all())
                ->orderBy('subject_code')
                ->get()
                ->groupBy(fn (GradReport2 $row) => GradReport2::normalizeCode((string) $row->subject_code2));

        $actorUsernames = $rowsByGroup
            ->flatten(1)
            ->map(fn (GradReport2 $row) => trim((string) ($row->username ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $adminActorKeys = $this->adminActorUsernameKeys($actorUsernames);

        $groups = $codes->map(function (string $groupCode) use ($rowsByGroup, $adminActorKeys) {
            /** @var Collection<int, GradReport2> $members */
            $members = $rowsByGroup->get($groupCode, collect())
                ->unique(fn (GradReport2 $row) => GradReport2::normalizeCode((string) $row->subject_code))
                ->values();
            $primary = $members->first(
                fn (GradReport2 $row) => GradReport2::normalizeCode((string) $row->subject_code) === $groupCode
            ) ?? $members->first();

            $mappedMembers = $members->map(fn (GradReport2 $row) => (object) [
                'subject_code2' => $groupCode,
                'subject_code' => GradReport2::normalizeCode((string) $row->subject_code),
                'subject' => trim((string) $row->subject),
                'username' => trim((string) ($row->username ?? '')),
                'is_group_key' => GradReport2::normalizeCode((string) $row->subject_code) === $groupCode,
            ])->values();

            $enteredBy = $mappedMembers
                ->pluck('username')
                ->map(fn ($u) => trim((string) $u))
                ->filter()
                ->unique()
                ->values();

            return (object) [
                'group_code' => $groupCode,
                'subject' => trim((string) ($primary?->subject ?? '')),
                'member_count' => $members->count(),
                'source' => $this->resolveGroupSource($enteredBy->all(), $adminActorKeys),
                'entered_by' => $enteredBy->implode(', '),
                'members' => $mappedMembers,
            ];
        });

        $paginator->setCollection($groups);

        return $paginator;
    }

    /**
     * @param  list<string>  $usernames
     * @return array<string, true> uppercase username keys that have staff privilege
     */
    private function adminActorUsernameKeys(array $usernames): array
    {
        if ($usernames === []) {
            return [];
        }

        return TblPrivilege::query()
            ->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT)
            ->whereIn('username', $usernames)
            ->pluck('username')
            ->mapWithKeys(fn ($username) => [strtoupper(trim((string) $username)) => true])
            ->all();
    }

    /**
     * ไม่มีรหัสผู้กรอก = ข้อมูลเดิมจาก Admin
     * มีรหัสผู้กรอกที่เป็นสิทธิ์เจ้าหน้าที่ = Admin
     * นอกนั้น = อาจารย์
     *
     * @param  list<string>  $enteredBy
     * @param  array<string, true>  $adminActorKeys
     */
    private function resolveGroupSource(array $enteredBy, array $adminActorKeys): string
    {
        if ($enteredBy === []) {
            return 'admin';
        }

        foreach ($enteredBy as $username) {
            $key = strtoupper(trim($username));
            if ($key === '' || ! isset($adminActorKeys[$key])) {
                return 'instructor';
            }
        }

        return 'admin';
    }

    public function stats(?string $q = null): array
    {
        $q = trim((string) $q);
        $codeSql = GradReport2::normalizedCodeSql('subject_code2');

        $base = GradReport2::query()
            ->whereNotNull('subject_code2')
            ->whereRaw($codeSql." != ''")
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('subject_code2', 'like', $like)
                        ->orWhere('subject_code', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            });

        return [
            'groups' => (int) (clone $base)->selectRaw('COUNT(DISTINCT '.$codeSql.') as aggregate')->value('aggregate'),
            'members' => (clone $base)->count(),
        ];
    }

    /**
     * แถวสำหรับ Export / วางกลับเข้า Excel — รูปแบบเดียวกับพื้นที่วาง
     * (หนึ่งแถวต่อรหัสวิชาในกลุ่ม)
     *
     * @return list<array{group_code: string, subject: string, member_code: string}>
     */
    public function exportPasteRows(?string $q = null): array
    {
        $q = trim((string) $q);
        $codeSql = GradReport2::normalizedCodeSql('subject_code2');

        $rows = GradReport2::query()
            ->whereNotNull('subject_code2')
            ->whereRaw($codeSql." != ''")
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('subject_code2', 'like', $like)
                        ->orWhere('subject_code', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            })
            ->orderByRaw($codeSql)
            ->orderBy('subject_code')
            ->get();

        $out = [];
        $seen = [];

        foreach ($rows as $row) {
            $groupCode = GradReport2::normalizeCode((string) $row->subject_code2);
            $memberCode = GradReport2::normalizeCode((string) $row->subject_code);
            if ($groupCode === '' || $memberCode === '') {
                continue;
            }

            $key = $groupCode."\0".$memberCode;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $out[] = [
                'group_code' => $groupCode,
                'subject' => mb_strtoupper(trim((string) $row->subject)),
                'member_code' => $memberCode,
            ];
        }

        return $out;
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

        $this->assertExamReportableCodes([$groupCode, ...$memberCodes], $subject);

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

        $this->assertExamReportableCodes([$groupCode, $subjectCode], $subject);

        if ($this->memberExists($subjectCode)) {
            $existing = GradReport2::query()->whereNormalizedCode('subject_code', $subjectCode)->first();
            $inGroup = GradReport2::normalizeCode((string) ($existing?->subject_code2 ?? ''));

            if ($inGroup === $groupCode) {
                throw ValidationException::withMessages([
                    'subject_code' => "รหัส {$subjectCode} อยู่ในกลุ่มนี้แล้ว",
                ]);
            }

            $this->throwCodeConflicts([[
                'code' => $subjectCode,
                'group_code' => $inGroup,
                'subject' => trim((string) ($existing?->subject ?? '')),
            ]], 'subject_code', $groupCode);
        }

        // dump: ถ้ารหัสที่จะเพิ่มถูกใช้เป็นรหัสกลุ่มของกลุ่มอื่นอยู่แล้ว → ต้องเข้ากลุ่มนั้น
        $asGroupKey = GradReport2::query()
            ->whereNormalizedCode('subject_code2', $subjectCode)
            ->orderBy('subject_code')
            ->first();

        if ($asGroupKey && GradReport2::normalizeCode((string) $asGroupKey->subject_code2) !== $groupCode) {
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
            ->whereNormalizedCode('subject_code2', $groupCode)
            ->whereNormalizedCode('subject_code', $subjectCode)
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'subject_code' => 'ไม่พบรหัสวิชานี้ในกลุ่ม',
            ]);
        }

        if ($newSubjectCode !== $subjectCode && $this->memberExists($newSubjectCode)) {
            $existing = GradReport2::query()->whereNormalizedCode('subject_code', $newSubjectCode)->first();
            $inGroup = GradReport2::normalizeCode((string) ($existing?->subject_code2 ?? ''));

            $this->throwCodeConflicts([[
                'code' => $newSubjectCode,
                'group_code' => $inGroup,
                'subject' => trim((string) ($existing?->subject ?? '')),
            ]], 'new_subject_code', $groupCode);
        }

        GradReport2::query()
            ->whereNormalizedCode('subject_code2', $groupCode)
            ->whereNormalizedCode('subject_code', $subjectCode)
            ->update([
                'subject_code' => $newSubjectCode,
                'subject' => $subject,
                'updated_at' => now(),
            ]);

        // ถ้าแก้รหัสที่เป็นตัวแทนกลุ่ม → อัปเดตรหัสกลุ่มของสมาชิกทั้งหมด
        if ($subjectCode === $groupCode && $newSubjectCode !== $groupCode) {
            GradReport2::query()
                ->whereNormalizedCode('subject_code2', $groupCode)
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
            ->whereNormalizedCode('subject_code2', $groupCode)
            ->update([
                'subject_code2' => $groupCode,
                'subject' => $subject,
                'updated_at' => now(),
            ]);
    }

    public function removeMember(string $groupCode, string $subjectCode): void
    {
        $groupCode = $this->codes->normalizeSubjectCode($groupCode);
        $subjectCode = $this->codes->normalizeSubjectCode($subjectCode);

        $deleted = GradReport2::query()
            ->whereNormalizedCode('subject_code2', $groupCode)
            ->whereNormalizedCode('subject_code', $subjectCode)
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
            ->whereNormalizedCode('subject_code2', $groupCode)
            ->delete();
    }

    /**
     * เงื่อนไขแบบ dump: หา subject_code2 ที่ควรใช้จริง
     */
    private function resolveGroupCodeLikeDump(string $requestedGroupCode, string $firstMemberCode): string
    {
        // ถ้ารหัสสมาชิกถูกใช้เป็นรหัสกลุ่มอยู่แล้ว → ใช้กลุ่มนั้น
        $asGroup = GradReport2::query()
            ->whereNormalizedCode('subject_code2', $firstMemberCode)
            ->orderBy('subject_code')
            ->first();
        if ($asGroup) {
            return GradReport2::normalizeCode((string) $asGroup->subject_code2);
        }

        // ถ้ารหัสกลุ่มที่ขอ ถูกใช้เป็นสมาชิกของกลุ่มอื่น → ใช้ subject_code2 ของแถวนั้น
        $asMember = GradReport2::query()
            ->whereNormalizedCode('subject_code', $requestedGroupCode)
            ->first();
        if ($asMember) {
            $linked = GradReport2::normalizeCode((string) $asMember->subject_code2);

            return $linked !== '' ? $linked : $requestedGroupCode;
        }

        // ถ้ามีกลุ่มนี้อยู่แล้ว
        $existing = GradReport2::query()
            ->whereNormalizedCode('subject_code2', $requestedGroupCode)
            ->orderBy('subject_code')
            ->first();
        if ($existing) {
            return GradReport2::normalizeCode((string) $existing->subject_code2);
        }

        return $requestedGroupCode;
    }

    /**
     * @param  list<string>  $codes
     */
    private function assertCodesAvailableForGroup(array $codes, string $groupCode): void
    {
        $conflicts = $this->findCodeConflicts($codes, $groupCode);
        if ($conflicts === []) {
            return;
        }

        $this->throwCodeConflicts($conflicts, 'member_codes', $groupCode);
    }

    /**
     * @param  list<string>  $codes
     * @return list<array{code: string, group_code: string, subject: string}>
     */
    public function findCodeConflicts(array $codes, string $forGroupCode): array
    {
        $forGroupCode = GradReport2::normalizeCode($forGroupCode);
        $normalized = [];
        foreach ($codes as $code) {
            $n = GradReport2::normalizeCode((string) $code);
            if ($n !== '') {
                $normalized[$n] = true;
            }
        }

        if ($normalized === []) {
            return [];
        }

        return GradReport2::query()
            ->whereNormalizedCodeIn('subject_code', array_keys($normalized))
            ->get(['subject_code', 'subject_code2', 'subject'])
            ->filter(fn (GradReport2 $row) => GradReport2::normalizeCode((string) $row->subject_code2) !== $forGroupCode)
            ->map(fn (GradReport2 $row) => [
                'code' => GradReport2::normalizeCode((string) $row->subject_code),
                'group_code' => GradReport2::normalizeCode((string) $row->subject_code2),
                'subject' => trim((string) $row->subject),
            ])
            ->unique('code')
            ->values()
            ->all();
    }

    /**
     * @param  list<array{code: string, group_code: string, subject: string}>  $conflicts
     */
    private function throwCodeConflicts(array $conflicts, string $errorKey, string $attemptedGroup = ''): never
    {
        throw new GradReport2CodeConflictException($conflicts, $errorKey, $attemptedGroup);
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

    /**
     * @param  list<string>  $codes
     */
    private function assertExamReportableCodes(array $codes, ?string $subject = null): void
    {
        if (ThesisCourse::isThesisTitle($subject)) {
            throw ValidationException::withMessages([
                'subject' => ThesisCourse::EXAM_BLOCK_MESSAGE,
            ]);
        }

        foreach ($codes as $code) {
            if (ThesisCourse::isThesisSubject((string) $code, $subject)) {
                throw ValidationException::withMessages([
                    'subject_code' => ThesisCourse::EXAM_BLOCK_MESSAGE,
                ]);
            }
        }
    }

    private function memberExists(string $subjectCode): bool
    {
        return GradReport2::query()
            ->whereNormalizedCode('subject_code', $subjectCode)
            ->exists();
    }

    private function groupExists(string $groupCode): bool
    {
        return GradReport2::query()
            ->whereNormalizedCode('subject_code2', $groupCode)
            ->exists();
    }

    private function findGroupPrimary(string $groupCode): ?GradReport2
    {
        return GradReport2::query()
            ->whereNormalizedCode('subject_code2', $groupCode)
            ->whereNormalizedCode('subject_code', $groupCode)
            ->first()
            ?? GradReport2::query()
                ->whereNormalizedCode('subject_code2', $groupCode)
                ->orderBy('subject_code')
                ->first();
    }

    /**
     * แปลงข้อความที่วางจาก Excel ให้เป็นกลุ่มตามฟิลด์เดียวกับฟอร์มสร้างกลุ่ม
     * คอลัมน์: รหัสกลุ่ม | ชื่อวิชา (ENG) | รหัสวิชาในกลุ่ม
     * แถวที่มีรหัสกลุ่มเดียวกันจะถูกรวมสมาชิกเข้าด้วยกัน
     *
     * @return list<array{group_code: string, subject: string, member_codes: list<string>, lines: list<int>}>
     */
    public function parsePasteIntoGroups(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        if ($raw === '') {
            throw ValidationException::withMessages([
                'paste_text' => 'กรุณาวางข้อมูลจาก Excel อย่างน้อย 1 แถว',
            ]);
        }

        $lines = array_values(array_filter(
            explode("\n", $raw),
            fn (string $line) => trim($line) !== '',
        ));

        $start = 0;
        $firstCells = $this->splitPasteCells($lines[0] ?? '');
        if ($this->looksLikePasteHeader($firstCells)) {
            $start = 1;
        }

        /** @var array<string, array{group_code: string, subject: string, member_codes: list<string>, lines: list<int>}> $groups */
        $groups = [];
        $rowErrors = [];

        for ($i = $start; $i < count($lines); $i++) {
            $lineNo = $i + 1;
            $cells = $this->splitPasteCells($lines[$i]);
            $groupCode = $this->codes->normalizeSubjectCode((string) ($cells[0] ?? ''));
            $subject = mb_strtoupper(trim((string) ($cells[1] ?? '')));
            $memberRaw = trim(implode(' ', array_slice($cells, 2)));

            if ($groupCode === '' && $subject === '' && $memberRaw === '') {
                continue;
            }

            if ($groupCode === '') {
                $rowErrors[] = "แถว {$lineNo}: ขาดรหัสกลุ่ม";
                continue;
            }
            if ($subject === '') {
                $rowErrors[] = "แถว {$lineNo}: ขาดชื่อวิชา (ENG)";
                continue;
            }

            $members = $this->parseLooseCodes($memberRaw);
            if ($members === []) {
                $rowErrors[] = "แถว {$lineNo}: ขาดรหัสวิชาในกลุ่ม";
                continue;
            }

            if (! isset($groups[$groupCode])) {
                $groups[$groupCode] = [
                    'group_code' => $groupCode,
                    'subject' => $subject,
                    'member_codes' => [],
                    'lines' => [],
                ];
            } elseif ($groups[$groupCode]['subject'] !== $subject) {
                $rowErrors[] = "แถว {$lineNo}: ชื่อวิชาของกลุ่ม {$groupCode} ไม่ตรงกับแถวก่อนหน้า («{$groups[$groupCode]['subject']}» vs «{$subject}»)";
                continue;
            }

            foreach ($members as $code) {
                if (! in_array($code, $groups[$groupCode]['member_codes'], true)) {
                    $groups[$groupCode]['member_codes'][] = $code;
                }
            }
            $groups[$groupCode]['lines'][] = $lineNo;
        }

        if ($rowErrors !== []) {
            throw ValidationException::withMessages([
                'paste_text' => implode("\n", $rowErrors),
            ]);
        }

        if ($groups === []) {
            throw ValidationException::withMessages([
                'paste_text' => 'ไม่พบแถวข้อมูลที่ใช้ได้ — ต้องมี 3 คอลัมน์: รหัสกลุ่ม | ชื่อวิชา (ENG) | รหัสวิชาในกลุ่ม',
            ]);
        }

        return array_values($groups);
    }

    /**
     * @return array{
     *     created: list<array{group_code: string, inserted: list<string>, was_existing: bool}>,
     *     errors: list<string>,
     *     conflicts: list<array{code: string, group_code: string, subject: string}>,
     *     conflict_focus: string|null
     * }
     */
    public function importPasteGroups(string $raw, string $username): array
    {
        $parsed = $this->parsePasteIntoGroups($raw);
        $created = [];
        $errors = [];
        $conflicts = [];
        $conflictFocus = null;

        foreach ($parsed as $group) {
            try {
                $result = $this->createGroup(
                    $group['group_code'],
                    $group['subject'],
                    $group['member_codes'],
                    $username,
                );
                $created[] = [
                    'group_code' => $result['group_code'],
                    'inserted' => $result['inserted'],
                    'was_existing' => $result['was_existing'],
                ];
            } catch (GradReport2CodeConflictException $e) {
                $errors[] = 'กลุ่ม '.$group['group_code'].":\n".$e->getMessage();
                $conflicts = array_merge($conflicts, $e->conflicts);
                $conflictFocus ??= $e->focusGroup();
            } catch (ValidationException $e) {
                $messages = collect($e->errors())->flatten()->all();
                $errors[] = 'กลุ่ม '.$group['group_code'].': '.implode(' ', $messages);
            }
        }

        if ($created === [] && $errors !== []) {
            if ($conflicts !== []) {
                throw new GradReport2CodeConflictException(
                    collect($conflicts)->unique('code')->values()->all(),
                    'paste_text',
                );
            }

            throw ValidationException::withMessages([
                'paste_text' => implode("\n", $errors),
            ]);
        }

        return [
            'created' => $created,
            'errors' => $errors,
            'conflicts' => collect($conflicts)->unique('code')->values()->all(),
            'conflict_focus' => $conflictFocus,
        ];
    }

    /**
     * @return list<string>
     */
    private function splitPasteCells(string $line): array
    {
        $line = rtrim($line, "\t ");
        if (str_contains($line, "\t")) {
            return array_map(static fn ($c) => trim((string) $c), explode("\t", $line));
        }

        // Excel ที่คัดลอกในบาง locale อาจใช้ ; เป็นตัวคั่นคอลัมน์
        if (substr_count($line, ';') >= 2) {
            return array_map(static fn ($c) => trim((string) $c), explode(';', $line));
        }

        // CSV แบบคั่นด้วยจุลภาค — ระวังว่าคอลัมน์สมาชิกอาจมีจุลภาคหลายรหัส
        if (substr_count($line, ',') >= 2) {
            $parts = array_map(static fn ($c) => trim((string) $c), explode(',', $line));
            if (count($parts) >= 3) {
                return [
                    $parts[0],
                    $parts[1],
                    implode(',', array_slice($parts, 2)),
                ];
            }
        }

        $parts = preg_split('/\s{2,}/', trim($line)) ?: [];

        return array_map(static fn ($c) => trim((string) $c), $parts);
    }

    /**
     * @param  list<string>  $cells
     */
    private function looksLikePasteHeader(array $cells): bool
    {
        $joined = mb_strtolower(implode(' ', $cells));

        return str_contains($joined, 'group')
            || str_contains($joined, 'รหัสกลุ่ม')
            || str_contains($joined, 'subject')
            || str_contains($joined, 'ชื่อวิชา')
            || str_contains($joined, 'member')
            || str_contains($joined, 'รหัสวิชา');
    }

    /**
     * @return list<string>
     */
    private function parseLooseCodes(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r", ';', '|'], [',', ',', ',', ','], $raw);
        $parts = preg_split('/[\s,]+/', $raw) ?: [];

        $codes = [];
        foreach ($parts as $part) {
            $code = $this->codes->normalizeSubjectCode((string) $part);
            if ($code !== '' && ! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
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
