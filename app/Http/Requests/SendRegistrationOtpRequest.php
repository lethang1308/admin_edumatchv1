<?php

namespace App\Http\Requests;

class SendRegistrationOtpRequest extends RegisterStudentRequest
{
    public function rules(): array
    {
        return array_replace(array_intersect_key(parent::rules(), array_flip(['email', 'phone'])), [
            'name' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
