<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Models\TblProgramQa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session('scigrade_role') === 'faculty_admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $programId = $this->route('program')?->programid;

        $isUpdate = $this->route('program') !== null;

        return [
            'programid' => [
                Rule::requiredIf($isUpdate),
                'nullable',
                'string',
                'max:255',
                Rule::unique('scigrad.tblprogram_qa', 'programid')->ignore($programId, 'programid'),
            ],
            'programname' => ['required', 'string', 'max:255'],
            'departmentid' => ['nullable', 'string', 'max:255'],
            'depart_id' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'string', 'max:255', Rule::in(TblProgramQa::ALLOWED_DEPARTMENT_IDS)],
            'typestudy' => ['required', 'string', 'max:255'],
        ];
    }
}
