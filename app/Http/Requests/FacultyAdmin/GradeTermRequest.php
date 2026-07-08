<?php

namespace App\Http\Requests\FacultyAdmin;

use Illuminate\Foundation\Http\FormRequest;

class GradeTermRequest extends FormRequest
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
        return [
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2600'],
        ];
    }
}
