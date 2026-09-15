<?php

namespace App\Exceptions;

use Exception;

class GradReport2CodeConflictException extends Exception
{
    /**
     * @param  list<array{code: string, group_code: string, subject: string}>  $conflicts
     */
    public function __construct(
        public readonly array $conflicts,
        public readonly string $errorKey = 'member_codes',
        public readonly string $attemptedGroup = '',
    ) {
        parent::__construct($this->formatMessage());
    }

    public function focusGroup(): string
    {
        return (string) ($this->conflicts[0]['group_code'] ?? '');
    }

    public function formatMessage(): string
    {
        $lines = [
            'พบรหัสวิชาซ้ำในระบบ — กรุณาตรวจสอบก่อนบันทึก',
            '',
        ];

        if ($this->attemptedGroup !== '') {
            $lines[] = 'คุณพยายามเพิ่มเข้ากลุ่ม «'.$this->attemptedGroup.'»';
            $lines[] = '';
        }

        foreach ($this->conflicts as $item) {
            $subject = trim((string) ($item['subject'] ?? ''));
            $subjectPart = $subject !== '' ? ' — '.$subject : '';
            $lines[] = '• รหัส '.$item['code'].' อยู่ในกลุ่ม '.$item['group_code'].$subjectPart.' แล้ว';
        }

        $focus = $this->focusGroup();
        if ($focus !== '') {
            $lines[] = '';
            $lines[] = 'กด «ตกลง» เพื่อไปยังกลุ่ม '.$focus.' ที่ต้องตรวจสอบ/จัดการข้อมูล';
        }

        return implode("\n", $lines);
    }
}
