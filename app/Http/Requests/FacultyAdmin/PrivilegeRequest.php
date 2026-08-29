<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Models\TblPrivilege;
use App\Support\SciGradeRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PrivilegeRequest extends FormRequest
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
        $allowedLevels = ['0', '1', '3', '4'];
        if (SciGradeRole::canAssignSuperPrivilege()) {
            $allowedLevels[] = '2';
        }

        $isUpdate = $this->route('privilege') !== null;

        $rules = [
            'level' => ['required', 'integer', Rule::in($allowedLevels)],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => [
                'integer',
                Rule::exists('scigrad.tbldepartment', 'department_id'),
            ],
        ];

        if (! $isUpdate) {
            $rules['username'] = [
                'required',
                'string',
                'max:10',
                Rule::exists('scigrad.tbluser', 'username'),
                Rule::unique('scigrad.tblprivileges', 'username')
                    ->where(fn ($q) => $q->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT)),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ((int) $this->input('level') !== TblPrivilege::LEVEL_DEPT) {
                return;
            }

            $ids = array_filter((array) $this->input('department_ids', []));
            if ($ids === []) {
                $validator->errors()->add(
                    'department_ids',
                    'กรุณาเลือกสาขาวิชาอย่างน้อย 1 สาขา สำหรับเจ้าหน้าที่สาขาวิชา',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'department_ids.*.exists' => 'พบรหัสสาขาที่ไม่ถูกต้อง',
        ];
    }
}
