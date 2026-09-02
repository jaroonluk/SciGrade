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
            'report_status' => ['required', 'integer', 'in:0,1'],
            'format' => ['required', 'string', 'in:pdf,word'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2600'],
            'created_from' => ['required', 'date'],
            'created_to' => ['required', 'date', 'after_or_equal:created_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'created_from' => 'วันที่เริ่มต้น',
            'created_to' => 'วันที่สิ้นสุด',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'created_from.required' => 'กรุณาเลือกวันที่เริ่มต้นของช่วงพิมพ์รายงาน',
            'created_to.required' => 'กรุณาเลือกวันที่สิ้นสุดของช่วงพิมพ์รายงาน',
            'created_to.after_or_equal' => 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มต้น',
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
            'created_from' => $this->input('created_from'),
            'created_to' => $this->input('created_to'),
        ];
    }
}
