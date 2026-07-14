<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Support\SciGradeRole;

use Illuminate\Foundation\Http\FormRequest;

class GradeReportBulkApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SciGradeRole::isFacultyCapable();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grade_ids' => ['required', 'array', 'min:1'],
            'grade_ids.*' => ['integer', 'distinct'],
            'remark' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return list<int>
     */
    public function gradeIds(): array
    {
        return array_values(array_map('intval', $this->input('grade_ids', [])));
    }
}
