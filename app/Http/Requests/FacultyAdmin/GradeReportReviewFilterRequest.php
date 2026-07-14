<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Support\SciGradeRole;

use App\Services\FacultyAdmin\FacultyReportQueryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeReportReviewFilterRequest extends FormRequest
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
            'subject_code' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:150'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'status' => ['nullable', 'integer', 'in:-1,0,1,2'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2600'],
            'department_id' => ['nullable', 'integer', Rule::in(FacultyReportQueryService::FACULTY_DEPARTMENT_IDS)],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
            'sort_by' => ['nullable', 'string', 'in:subject_code,subject,created,status'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'department_id' => $this->integer('department_id') ?: null,
            'subject_code' => $this->input('subject_code'),
            'subject' => $this->input('subject'),
            'created_from' => $this->input('created_from'),
            'created_to' => $this->input('created_to'),
            'status' => $this->input('status'),
            'term' => $this->integer('term') ?: null,
            'year' => $this->integer('year') ?: null,
            'sort_by' => $this->input('sort_by'),
            'sort_dir' => $this->input('sort_dir'),
        ];
    }
}
