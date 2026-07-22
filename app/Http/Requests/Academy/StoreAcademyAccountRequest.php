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

            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'phone' => ['required', 'string', 'max:50'],
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

            'full_name.required' => __('Nama lengkap wajib diisi.'),
            'full_name.max' => __('Nama lengkap maksimal :max karakter.'),

            'gender.required' => __('Jenis kelamin wajib dipilih.'),
            'gender.in' => __('Jenis kelamin tidak valid.'),

            'birth_place.required' => __('Tempat lahir wajib diisi.'),
            'birth_date.required' => __('Tanggal lahir wajib diisi.'),
            'birth_date.date' => __('Tanggal lahir tidak valid.'),

            'phone.required' => __('Nomor telepon wajib diisi.'),
            'phone.max' => __('Nomor telepon maksimal :max karakter.'),
        ];
    }
}
