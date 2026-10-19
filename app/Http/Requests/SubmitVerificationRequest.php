<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'center'], true);
    }

    public function rules(): array
    {
        $types = config('verification.'.$this->user()->role.'_types', []);

        return [
            'documents' => ['required', 'array', 'min:3', 'max:'.config('verification.max_documents')],
            'documents.*' => ['array:document_type,document_name,issuing_authority,issued_year,file'],
            'documents.*.document_type' => ['required', Rule::in(array_keys($types))],
            'documents.*.document_name' => ['required', 'string', 'max:200'],
            'documents.*.issuing_authority' => ['required', 'string', 'max:200'],
            'documents.*.issued_year' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'documents.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:'.config('verification.max_file_kb')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $documents = $this->input('documents');
            if (! is_array($documents)) {
                return;
            }
            $types = array_column(array_filter($documents, 'is_array'), 'document_type');
            $required = $this->user()->role === 'center'
                ? ['business_registration', 'education_license', 'identity_front', 'identity_back']
                : ['identity_front', 'identity_back'];
            foreach ($required as $type) {
                if (! in_array($type, $types, true)) {
                    $validator->errors()->add('documents', 'Vui lòng nộp đầy đủ tài liệu bắt buộc và hai mặt CCCD.');
                }
            }
            if ($this->user()->role === 'teacher' && ! array_diff($types, ['identity_front', 'identity_back'])) {
                $validator->errors()->add('documents', 'Vui lòng nộp ít nhất một bằng cấp hoặc chứng chỉ chuyên môn.');
            }
            $bytes = 0;
            foreach ($this->file('documents', []) as $document) {
                $bytes += ($document['file'] ?? null)?->getSize() ?? 0;
            }
            if ($bytes > 7 * 1024 * 1024) {
                $validator->errors()->add('documents', 'Tổng dung lượng hồ sơ tối đa 7 MB.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'documents.required' => 'Vui lòng đính kèm hồ sơ xác minh.',
            'documents.*.file.max' => 'Mỗi tài liệu tối đa 2 MB.',
            'documents.*.file.mimes' => 'Chỉ chấp nhận ảnh JPG/PNG hoặc PDF.',
            'documents.*.issued_year.max' => 'Năm cấp không được nằm trong tương lai.',
        ];
    }
}
