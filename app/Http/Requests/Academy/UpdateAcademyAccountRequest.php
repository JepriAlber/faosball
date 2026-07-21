<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->academy->id_owner_user, 'id_user'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Nama akun wajib diisi.'),
            'name.max' => __('Nama maksimal :max karakter.'),

            'email.required' => __('Email akun wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.unique' => __('Email sudah digunakan akun lain.'),
        ];
    }
}
