<?php

namespace App\Support;

class GradeReportRemarks
{
    public const FLAG_JOINT = 1;

    public const FLAG_I = 2;

    public const FLAG_OTHER = 4;

    /** ใช้กับ reasonid แบบเลือกหลายข้อ เพื่อไม่ชนกับค่าเดิม 1/2/3 */
    public const NEW_FORMAT = 8;

    public const ENTRY_SEP = ' || ';

    /**
     * @return array{
     *     joint_line: ?string,
     *     i_entries: list<string>,
     *     other_entries: list<string>,
     *     flags: int
     * }
     */
    public static function parse(?string $reason, ?int $reasonid = null): array
    {
        $reason = trim((string) $reason);
        $jointLine = null;
        $iEntries = [];
        $otherEntries = [];

        if ($reason !== '') {
            if (preg_match('/ตัดเกรดร่วมกับ\s*:?\s*(.+?)(?=\nได้ I เนื่องจาก|\nอื่นๆ\s*:|$)/us', $reason, $m)
                || preg_match('/ซ้อนวิชากับ\s*:?\s*(.+?)(?=\nได้ I เนื่องจาก|\nอื่นๆ\s*:|$)/us', $reason, $m)) {
                $jointLine = trim($m[1]);
            }

            if (preg_match_all('/ได้ I เนื่องจาก\s*:?\s*(.+?)(?=\nตัดเกรดร่วมกับ|\nซ้อนวิชากับ|\nอื่นๆ\s*:|$)/us', $reason, $matches)) {
                foreach ($matches[1] as $chunk) {
                    foreach (self::splitEntries($chunk) as $entry) {
                        $iEntries[] = $entry;
                    }
                }
            }

            if (preg_match_all('/(?:^|\n)อื่นๆ\s*:?\s*(.+?)(?=\nตัดเกรดร่วมกับ|\nซ้อนวิชากับ|\nได้ I เนื่องจาก|$)/us', $reason, $matches)) {
                foreach ($matches[1] as $chunk) {
                    foreach (self::splitEntries($chunk) as $entry) {
                        $otherEntries[] = $entry;
                    }
                }
            }

            // legacy: reasonid=2/3 with plain body
            if ($iEntries === [] && $otherEntries === [] && $jointLine === null && $reasonid !== null) {
                $rid = (int) $reasonid;
                if ($rid === 2) {
                    $body = preg_replace('/^ได้ I เนื่องจาก\s*:?\s*/u', '', $reason) ?? $reason;
                    $body = trim($body);
                    if ($body !== '') {
                        $iEntries[] = $body;
                    }
                } elseif ($rid === 3) {
                    if (! str_starts_with($reason, 'ตัดเกรดร่วมกับ') && ! str_starts_with($reason, 'ซ้อนวิชากับ')) {
                        $otherEntries[] = $reason;
                    }
                }
            }
        }

        $flags = 0;
        if ($jointLine !== null && $jointLine !== '') {
            $flags |= self::FLAG_JOINT;
        }
        if ($iEntries !== []) {
            $flags |= self::FLAG_I;
        }
        if ($otherEntries !== []) {
            $flags |= self::FLAG_OTHER;
        }

        $flags |= self::reasonidToFlags($reasonid);

        return [
            'joint_line' => $jointLine !== '' ? $jointLine : null,
            'i_entries' => array_values(array_unique($iEntries)),
            'other_entries' => array_values(array_unique($otherEntries)),
            'flags' => $flags,
        ];
    }

    /**
     * @param  list<string>  $iEntries
     * @param  list<string>  $otherEntries
     */
    public static function build(?string $jointLine, array $iEntries, array $otherEntries): ?string
    {
        $parts = [];
        $jointLine = trim((string) $jointLine);
        if ($jointLine !== '') {
            $parts[] = 'ตัดเกรดร่วมกับ :'.$jointLine;
        }

        $iEntries = array_values(array_filter(array_map('trim', $iEntries)));
        if ($iEntries !== []) {
            $parts[] = 'ได้ I เนื่องจาก :'.implode(self::ENTRY_SEP, $iEntries);
        }

        $otherEntries = array_values(array_filter(array_map('trim', $otherEntries)));
        if ($otherEntries !== []) {
            $parts[] = 'อื่นๆ :'.implode(self::ENTRY_SEP, $otherEntries);
        }

        if ($parts === []) {
            return null;
        }

        return implode("\n", $parts);
    }

    public static function reasonidToFlags(?int $reasonid): int
    {
        $rid = (int) $reasonid;
        if ($rid <= 0) {
            return 0;
        }

        $mask = self::FLAG_JOINT | self::FLAG_I | self::FLAG_OTHER;

        // รูปแบบใหม่: 8 | flags (เช่น 11 = ตัดเกรดร่วม + ได้ I)
        if (($rid & self::NEW_FORMAT) === self::NEW_FORMAT) {
            return $rid & $mask;
        }

        // ค่าเดิมแบบเลือกข้อเดียว
        return match ($rid) {
            1 => self::FLAG_JOINT,
            2 => self::FLAG_I,
            3 => self::FLAG_OTHER,
            default => $rid & $mask,
        };
    }

    public static function flagsToReasonid(int $flags): ?int
    {
        $flags = $flags & (self::FLAG_JOINT | self::FLAG_I | self::FLAG_OTHER);
        if ($flags === 0) {
            return null;
        }

        // คงค่าเดิมเมื่อเลือกข้อเดียว
        if ($flags === self::FLAG_JOINT) {
            return 1;
        }
        if ($flags === self::FLAG_I) {
            return 2;
        }
        if ($flags === self::FLAG_OTHER) {
            return 3;
        }

        return self::NEW_FORMAT | $flags;
    }

    public static function hasJoint(?int $reasonid, ?string $reason = null): bool
    {
        return (self::parse($reason, $reasonid)['flags'] & self::FLAG_JOINT) === self::FLAG_JOINT;
    }

    /**
     * @return list<string>
     */
    private static function splitEntries(string $chunk): array
    {
        $chunk = trim($chunk);
        if ($chunk === '') {
            return [];
        }

        $parts = preg_split('/\s*\|\|\s*/u', $chunk) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    /**
     * รวมหมายเหตุเดิมกับค่าใหม่ โดยไม่ทับข้อความเดิม
     *
     * @return array{reason: ?string, reasonid: ?int}
     */
    public static function merge(?string $existingReason, ?int $existingReasonid, ?string $incomingReason, ?int $incomingReasonid): array
    {
        $old = self::parse($existingReason, $existingReasonid);
        $new = self::parse($incomingReason, $incomingReasonid);

        $jointLine = $new['joint_line'] ?: $old['joint_line'];
        $iEntries = array_values(array_unique([...$old['i_entries'], ...$new['i_entries']]));
        $otherEntries = array_values(array_unique([...$old['other_entries'], ...$new['other_entries']]));
        $flags = ($old['flags'] | $new['flags']);
        if ($jointLine) {
            $flags |= self::FLAG_JOINT;
        }

        $reason = self::build($jointLine, $iEntries, $otherEntries);

        return [
            'reason' => $reason,
            'reasonid' => self::flagsToReasonid($flags),
        ];
    }
}
