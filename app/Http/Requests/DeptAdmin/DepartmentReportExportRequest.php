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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'format' => ['required', 'string', 'in:pdf,word'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2600'],
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
            'created_from' => $this->input('start_date'),
            'created_to' => $this->input('end_date'),
            'term' => $this->integer('term') ?: null,
            'year' => $this->integer('year') ?: null,
        ];
    }
}
