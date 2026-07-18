<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Support\SciGradeRole;

use App\Models\TblProgramQa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyReportExportRequest extends FormRequest
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
            'department_id' => ['required', 'integer', Rule::in(TblProgramQa::ALLOWED_DEPARTMENT_IDS)],
            'education_level' => ['required', 'string', 'in:bachelor,master,doctoral,graduate,all'],
            'report_status' => ['required', 'integer', 'in:0,1,2'],
            'format' => ['required', 'string', 'in:pdf,word'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2600'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportFilters(): array
    {
        return [
            'department_ids' => TblProgramQa::ALLOWED_DEPARTMENT_IDS,
            'department_id' => $this->integer('department_id'),
            'education_level' => $this->input('education_level'),
            'report_status' => $this->integer('report_status'),
            'term' => $this->integer('term') ?: null,
            'year' => $this->integer('year') ?: null,
        ];
    }
}
