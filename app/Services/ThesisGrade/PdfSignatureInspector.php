<?php

namespace App\Services\ThesisGrade;

class PdfSignatureInspector
{
    /**
     * Soft check: PDF has a digital signature dictionary.
     *
     * @return array{signed: bool, status: string, message: string}
     */
    public function inspectPath(string $absolutePath): array
    {
        if (! is_readable($absolutePath)) {
            return $this->unsigned('ไม่สามารถอ่านไฟล์เพื่อตรวจลายเซ็นได้');
        }

        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            return $this->unsigned('ไม่สามารถอ่านไฟล์เพื่อตรวจลายเซ็นได้');
        }

        $signed = false;
        while (! feof($handle)) {
            $chunk = fread($handle, 1024 * 512);
            if ($chunk === false || $chunk === '') {
                break;
            }
            if (
                str_contains($chunk, '/ByteRange')
                && (str_contains($chunk, '/Type /Sig') || str_contains($chunk, '/Type/Sig'))
            ) {
                $signed = true;
                break;
            }
        }
        fclose($handle);

        if ($signed) {
            return [
                'signed' => true,
                'status' => 'signed',
                'message' => 'พบลายเซ็นดิจิทัลในไฟล์',
            ];
        }

        return $this->unsigned('ยังไม่ลงนามดิจิทัล — สามารถอัปโหลดได้ แต่แนะนำให้ลงนามก่อนส่งเข้าสาขา');
    }

    public function inspectUploaded(\Illuminate\Http\UploadedFile $file): array
    {
        return $this->inspectPath($file->getRealPath() ?: $file->getPathname());
    }

    /**
     * @return array{signed: bool, status: string, message: string}
     */
    private function unsigned(string $message): array
    {
        return [
            'signed' => false,
            'status' => 'unsigned',
            'message' => $message,
        ];
    }
}
