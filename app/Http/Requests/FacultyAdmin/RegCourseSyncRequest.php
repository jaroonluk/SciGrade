<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Support\SciGradeRole;

use Illuminate\Foundation\Http\FormRequest;

class RegCourseSyncRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2560', 'max:2580'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => 'กรุณาเลือกปีการศึกษา',
        ];
    }
}
