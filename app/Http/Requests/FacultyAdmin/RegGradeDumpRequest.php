<?php

namespace App\Http\Requests\FacultyAdmin;

use App\Support\SciGradeRole;

use Illuminate\Foundation\Http\FormRequest;

class RegGradeDumpRequest extends FormRequest
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
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2560', 'max:2580'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'term.required' => 'กรุณาเลือกภาคการศึกษา',
            'year.required' => 'กรุณาเลือกปีการศึกษา',
        ];
    }
}
