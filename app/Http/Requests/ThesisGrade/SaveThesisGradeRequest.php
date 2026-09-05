<?php

namespace App\Http\Requests\ThesisGrade;

use App\Support\ThesisCourse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveThesisGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2700'],
            'subject_code' => ['required', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:4'],
            'checked_proposal' => ['nullable'],
            'checked_signed' => ['nullable'],
            'intent' => ['nullable', 'in:draft,submit'],
            'students' => ['nullable', 'array'],
            'students.*.id' => ['nullable', 'integer'],
            'students.*.student_code' => ['nullable', 'string', 'max:20'],
            'students.*.student_name' => ['nullable', 'string', 'max:255'],
            'students.*.degree' => ['nullable', 'in:master,doctoral'],
            'students.*.thesis_terms_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'students.*.proposal_approved' => ['nullable'],
            'students.*.grade' => ['nullable', 'string', 'max:8'],
            'students.*.progress_credits' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'students.*.completed' => ['nullable'],
            'students.*.defense_date' => ['nullable', 'date'],
            'students.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $code = (string) $this->input('subject_code', '');
            $name = (string) $this->input('subject', '');

            if ($code === '' || $name === '') {
                return;
            }

            if (! ThesisCourse::isThesisTitle($name) && ! ThesisCourse::isThesisSubject($code, $name)) {
                $validator->errors()->add('subject_code', ThesisCourse::EXAM_BLOCK_MESSAGE);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject_code.required' => 'กรุณาเลือกรหัสวิชา',
            'subject.required' => 'กรุณาเลือกชื่อวิชา',
            'section.required' => 'กรุณาระบุกลุ่มเรียน',
        ];
    }
}
