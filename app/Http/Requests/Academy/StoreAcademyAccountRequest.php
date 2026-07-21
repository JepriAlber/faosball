<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('Email akun Owner wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.unique' => __('Email sudah digunakan oleh akun lain.'),

            'password.required' => __('Password akun Owner wajib diisi.'),
            'password.min' => __('Password minimal :min karakter.'),
            'password.confirmed' => __('Konfirmasi password tidak sesuai.'),
        ];
    }
}
