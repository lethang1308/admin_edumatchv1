<?php

namespace App\Http\Requests;

use App\Support\VietnamesePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $phone = $this->input('phone');
        $this->merge([
            'name' => $this->input('name') ?? $this->input('full_name') ?? $this->input('fullName'),
            'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
            'phone' => is_string($phone) ? VietnamesePhone::normalize($phone) : $phone,
            'date_of_birth' => $this->input('date_of_birth') ?? $this->input('dateOfBirth') ?? $this->input('dob'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'min:2', 'max:150'],
            'email' => ['bail', 'required', 'string', 'max:191', 'email:rfc,dns', Rule::unique('users', 'email')],
            'phone' => [
                'bail', 'required', 'string', 'max:20', 'regex:/^\+84[35789][0-9]{8}$/D',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (DB::table('users')->whereIn('phone', VietnamesePhone::variants($value))->exists()) {
                        $fail('Số điện thoại này đã được sử dụng.');
                    }
                },
            ],
            'date_of_birth' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'password' => ['bail', 'required', 'string', 'min:8', 'max:128', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'otp' => ['bail', 'required', 'string', 'regex:/^[0-9]{6}$/D'],
            'role' => ['nullable', Rule::in(['student'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.min' => 'Họ và tên cần có ít nhất 2 ký tự.',
            'name.max' => 'Họ và tên tối đa 150 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Vui lòng nhập email hợp lệ và có tên miền đang hoạt động.',
            'email.max' => 'Email tối đa 191 ký tự.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại Việt Nam phải có dạng 0981234567 hoặc +84981234567.',
            'date_of_birth.required' => 'Vui lòng chọn ngày sinh.',
            'date_of_birth.date_format' => 'Ngày sinh không hợp lệ.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không được nằm trong tương lai.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu cần có ít nhất 8 ký tự.',
            'password.max' => 'Mật khẩu tối đa 128 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận chưa khớp.',
            'password_confirmation.required' => 'Vui lòng xác nhận mật khẩu.',
            'otp.required' => 'Vui lòng nhập mã OTP đã gửi đến email đăng ký.',
            'otp.regex' => 'Mã OTP phải gồm đúng 6 chữ số.',
            'role.in' => 'Chức năng này hiện hỗ trợ đăng ký tài khoản học viên.',
        ];
    }
}
