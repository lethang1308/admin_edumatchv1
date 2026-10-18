<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class RegisterTeacherRequest extends RegisterStudentRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $experience = $this->input('experience_years', $this->input('experience', 0));
        if (is_string($experience) && ! is_numeric($experience) && preg_match('/\d+/', $experience, $matches)) {
            $experience = (int) $matches[0];
        }
        $this->merge([
            'headline' => $this->input('headline') ?? $this->input('qualification') ?? $this->input('qualifications'),
            'bio' => $this->input('bio') ?? $this->input('biography'),
            'experience_years' => $experience,
            'show_phone' => $this->input('show_phone', $this->input('isPhonePublic', false)),
        ]);
    }

    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'role' => ['required', Rule::in(['teacher'])],
            'headline' => ['nullable', 'string', 'max:200'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'show_phone' => ['required', 'boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_replace(parent::messages(), [
            'headline.max' => 'Bằng cấp/tiêu đề nghề nghiệp tối đa 200 ký tự.',
            'experience_years.integer' => 'Vui lòng nhập số năm kinh nghiệm hợp lệ.',
            'experience_years.max' => 'Số năm kinh nghiệm tối đa 60 năm.',
            'role.in' => 'Vai trò đăng ký không hợp lệ.',
        ]);
    }
}
