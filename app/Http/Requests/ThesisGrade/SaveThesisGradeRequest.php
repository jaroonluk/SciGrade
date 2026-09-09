<?php

namespace App\Http\Requests\ThesisGrade;

use App\Services\ThesisGrade\ThesisGradePdfParser;
use App\Support\ThesisCourse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveThesisGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $subject = trim((string) $this->input('subject', ''));
        if ($subject !== '') {
            $normalized = app(ThesisGradePdfParser::class)->normalizeSubjectChoice($subject);
            if ($normalized !== null) {
                $this->merge(['subject' => $normalized]);
            }
        }

        $code = strtoupper(preg_replace('/\s+/', '', (string) $this->input('subject_code', '')) ?? '');
        if ($code !== '') {
            $this->merge(['subject_code' => $code]);
        }
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
            'subject' => ['required', 'string', Rule::in(ThesisGradePdfParser::SUBJECT_CHOICES)],
            'section' => ['required', 'string', 'max:4'],
            'checked_proposal' => ['nullable'],
            'checked_signed' => ['nullable'],
            'intent' => ['nullable', 'in:draft,submit'],
            'students' => ['nullable', 'array'],
            'students.*.id' => ['nullable', 'integer'],
            'students.*.student_code' => ['nullable', 'string', 'max:20'],
            'students.*.name_prefix' => ['nullable', 'string', 'max:50'],
            'students.*.first_name' => ['nullable', 'string', 'max:120'],
            'students.*.last_name' => ['nullable', 'string', 'max:120'],
            'students.*.student_name' => ['nullable', 'string', 'max:255'],
            'students.*.degree' => ['nullable', 'in:master,doctoral'],
            'students.*.thesis_terms_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'students.*.proposal_approved' => ['nullable'],
            'students.*.grade' => ['nullable', 'string', 'max:8'],
            'students.*.credits_registered' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'students.*.credits_passed' => ['nullable', 'numeric', 'min:0', 'max:99'],
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
            'subject_code.required' => 'กรุณากรอกรหัสวิชา',
            'subject.required' => 'กรุณาเลือกชื่อวิชา',
            'subject.in' => 'ชื่อวิชาต้องเป็น THESIS, INDEPENDENT STUDY หรือ DISSERTATION เท่านั้น',
            'section.required' => 'กรุณาระบุกลุ่มเรียน',
        ];
    }
}
