<?php

namespace App\Http\Requests\Players;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'=>[
                'required',
                'email',
                'unique:users,email'
            ],
            'password'=>[
                'required',
                'string',
                'min:8',
                'confirmed'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'=>__('Email akun player wajib diisi.'),
            'email.email'=>__('Format email tidak valid.'),
            'email.unique'=>__('Email sudah digunakan oleh akun lain.'),

            'password.required'=>__('Password akun player wajib diisi.'),
            'password.min'=>__('Password minimal :min karakter.'),
            'password.confirmed'=>__('Konfirmasi password tidak sesuai.'),
        ];
    }
}