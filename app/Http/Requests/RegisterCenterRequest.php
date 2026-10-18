<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class RegisterCenterRequest extends RegisterStudentRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'name' => $this->input('centerName') ?? $this->input('name'),
            'founded_year' => $this->input('founded_year') ?? $this->input('foundedYear'),
            'bio' => $this->input('bio') ?? $this->input('centerDescription'),
        ]);
    }

    public function rules(): array
    {
        $rules = array_replace(parent::rules(), [
            'role' => ['required', Rule::in(['center'])],
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'founded_year' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
            'bio' => ['nullable', 'string', 'max:5000'],
        ]);
        unset($rules['date_of_birth']);

        return $rules;
    }

    public function messages(): array
    {
        return array_replace(parent::messages(), [
            'name.max' => 'Tên trung tâm tối đa 200 ký tự.',
            'founded_year.required' => 'Vui lòng nhập năm thành lập.',
            'founded_year.min' => 'Năm thành lập phải từ 1950 trở đi.',
            'founded_year.max' => 'Năm thành lập không được nằm trong tương lai.',
            'role.in' => 'Vai trò đăng ký không hợp lệ.',
        ]);
    }
}
