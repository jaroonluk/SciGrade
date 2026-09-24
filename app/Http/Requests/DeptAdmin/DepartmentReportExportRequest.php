<?php

namespace App\Http\Requests\DeptAdmin;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentReportExportRequest extends FormRequest
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
            'department_id' => ['required', 'integer'],
            'education_level' => ['required', 'string', 'in:bachelor,master,doctoral,graduate,all'],
            // 0=ยังไม่ผ่าน, 4=นำเข้าที่ประชุมสาขา, 1=ผ่านที่ประชุมสาขา
            'report_status' => ['required', 'integer', 'in:0,1,4'],
            'format' => ['required', 'string', 'in:pdf,word'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'report_status' => 'สถานะรายงาน',
            'format' => 'รูปแบบไฟล์',
            'department_id' => 'สาขาวิชา',
            'education_level' => 'ระดับการศึกษา',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'report_status.required' => 'กรุณาเลือกสถานะรายงาน',
            'report_status.in' => 'สถานะรายงานไม่ถูกต้อง',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportFilters(array $departmentIds): array
    {
        return [
            'department_ids' => $departmentIds,
            'department_id' => $this->integer('department_id'),
            'education_level' => $this->input('education_level'),
            'report_status' => $this->integer('report_status'),
            'term' => $this->integer('term') ?: null,
            'year' => $this->integer('year') ?: null,
            // Admin สาขา: ตามรหัสวิชา + ต้องเป็นอาจารย์ในสาขา/หน่วยงานที่รับผิดชอบกรอก
            'require_department_instructor' => true,
        ];
    }
}
