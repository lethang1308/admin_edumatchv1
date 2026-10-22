<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\AccountRoles;
use App\Support\VietnamesePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaveAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->status === 'active' && $this->user()->hasAnyRole(['super_admin', 'admin']);
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $phone = $this->input('phone');
        $this->merge([
            'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
            'phone' => is_string($phone) ? VietnamesePhone::normalize($phone) : $phone,
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['nullable', 'required_without:phone', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'required_without:email', 'string', 'regex:/^\+84[35789][0-9]{8}$/D',
                function ($attribute, $value, $fail) use ($user) {
                    if (DB::table('users')->whereIn('phone', VietnamesePhone::variants($value))
                        ->when($user, fn ($query) => $query->where('id', '!=', $user->id))->exists()) {
                        $fail('Số điện thoại này đã được sử dụng.');
                    }
                }],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:128', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(AccountRoles::LABELS))],
            'status' => ['required', Rule::in(['active', 'blocked'])],
            'blocked_reason' => ['nullable', 'required_if:status,blocked', 'string', 'max:2000'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'bio' => ['nullable', 'string', 'max:'.($this->input('role') === 'student' ? '1000' : '5000')],
            'headline' => ['nullable', 'string', 'max:200'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'founded_year' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year],
            'center_name' => ['nullable', 'string', 'max:200'],
            'registered_address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            if ($user instanceof User && $user->is($this->user())
                && ($this->input('status') !== 'active' || $this->input('role') !== AccountRoles::of($user))) {
                $validator->errors()->add('role', 'Bạn không thể tự khóa hoặc thay đổi vai trò quản trị của chính mình.');
            }
        });
    }

    public function attributes(): array
    {
        return ['name' => 'họ tên / tên trung tâm', 'email' => 'email', 'phone' => 'số điện thoại',
            'password' => 'mật khẩu', 'role' => 'vai trò', 'status' => 'trạng thái',
            'blocked_reason' => 'lý do khóa', 'avatar' => 'ảnh đại diện', 'date_of_birth' => 'ngày sinh'];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Số điện thoại Việt Nam phải có dạng 0981234567 hoặc +84981234567.',
            'email.unique' => 'Email này đã được sử dụng.', 'password.confirmed' => 'Mật khẩu xác nhận chưa khớp.',
            'blocked_reason.required_if' => 'Vui lòng nhập lý do khóa tài khoản.'];
    }
}
