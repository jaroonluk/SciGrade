<?php

namespace App\Http\Requests\DeptAdmin;

use Illuminate\Foundation\Http\FormRequest;

class DeptRegistrarBulkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session('scigrade_role') === 'dept_admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'department_id' => ['nullable', 'integer'],
            'attachments' => ['required', 'array', 'min:1', 'max:40'],
            'attachments.*' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.required' => 'กรุณาเลือกไฟล์ PDF',
            'attachments.max' => 'อัปโหลดได้ไม่เกิน 40 ไฟล์ต่อครั้ง',
            'attachments.*.mimes' => 'รองรับเฉพาะไฟล์ PDF',
            'attachments.*.max' => 'แต่ละไฟล์ต้องไม่เกิน 20 MB',
        ];
    }
}
