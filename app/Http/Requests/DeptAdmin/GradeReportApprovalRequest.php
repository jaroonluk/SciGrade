<?php

namespace App\Http\Requests\DeptAdmin;

use Illuminate\Foundation\Http\FormRequest;

class GradeReportApprovalRequest extends FormRequest
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
            'remark' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
