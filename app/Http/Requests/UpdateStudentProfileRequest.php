<?php

namespace App\Http\Requests;

use App\Models\AdministrativeArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'student';
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'date_of_birth' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
        foreach (['province', 'district', 'ward'] as $level) {
            $rules[$level.'_id'] = ['sometimes', 'nullable', 'integer', Rule::exists('administrative_areas', 'id')->where('level', $level)->where('is_active', true)];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $profile = $this->user()->studentProfile;
            $province = $this->input('province_id', $profile?->province_id);
            $district = $this->input('district_id', $profile?->district_id);
            $ward = $this->input('ward_id', $profile?->ward_id);
            if ($district && (! $province || (int) AdministrativeArea::find($district)?->parent_id !== (int) $province)) {
                $validator->errors()->add('district_id', 'Quận/huyện phải thuộc tỉnh/thành đã chọn.');
            }
            if ($ward && (! $province || (int) AdministrativeArea::find($ward)?->parent_id !== (int) ($district ?: $province))) {
                $validator->errors()->add('ward_id', 'Phường/xã phải thuộc khu vực đã chọn.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Họ và tên tối đa 150 ký tự.',
            'bio.max' => 'Giới thiệu/mục tiêu học tập tối đa 1000 ký tự.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không được nằm trong tương lai.',
            '*.exists' => 'Khu vực được chọn không hợp lệ hoặc không còn hoạt động.',
        ];
    }
}
