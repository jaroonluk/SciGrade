<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Support\SciGradeRole;

use Illuminate\Foundation\Http\FormRequest;

class GradeReportApprovalRequest extends FormRequest
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
            'remark' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
