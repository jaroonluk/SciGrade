<?php

namespace App\Http\Requests\DeptAdmin;

use Illuminate\Foundation\Http\FormRequest;

class GradeReportReviewFilterRequest extends FormRequest
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
            'subject_code' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'integer', 'in:-1,0,1,2,3'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2600'],
            'department_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(array $departmentIds): array
    {
        return [
            'department_ids' => $departmentIds,
            'department_id' => $this->integer('department_id') ?: null,
            'subject_code' => $this->input('subject_code'),
            'subject' => $this->input('subject'),
            'status' => $this->input('status'),
            'term' => $this->integer('term') ?: null,
            'year' => $this->integer('year') ?: null,
        ];
    }
}
