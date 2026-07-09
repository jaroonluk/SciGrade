<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Models\TblPrivilege;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivilegeRequest extends FormRequest
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
        $isUpdate = $this->route('privilege') !== null;

        if ($isUpdate) {
            return [
                'level' => ['required', 'integer', 'in:0,1'],
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
            'level' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
