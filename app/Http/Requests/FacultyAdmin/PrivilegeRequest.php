<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Models\TblPrivilege;
use App\Support\SciGradeRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $allowedLevels = ['0', '1'];
        if (SciGradeRole::canAssignSuperPrivilege()) {
            $allowedLevels[] = '2';
        }

        $isUpdate = $this->route('privilege') !== null;

        if ($isUpdate) {
            return [
                'level' => ['required', 'integer', Rule::in($allowedLevels)],
            ];
        }

        $privilegeId = $this->route('privilege')?->privilegs_id;

        return [
            'username' => [
                'required',
                'string',
                'max:10',
                Rule::exists('scigrad.tbluser', 'username'),
                Rule::unique('scigrad.tblprivileges', 'username')
                    ->where(fn ($q) => $q->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT))
                    ->ignore($privilegeId, 'privilegs_id'),
            ],
            'level' => ['required', 'integer', Rule::in($allowedLevels)],
        ];
    }
}
