<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // $this->staff -- route-model-binding magic property (nama
            // parameter route {staff}), pola sama UpdatePlayerAccountRequest.
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('id_academy', $this->staff->id_academy)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('Email akun staff wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.unique' => __('Email sudah digunakan oleh akun lain.'),

            'password.required' => __('Password akun staff wajib diisi.'),
            'password.min' => __('Password minimal :min karakter.'),
            'password.confirmed' => __('Konfirmasi password tidak sesuai.'),

            'role_id.required' => __('Role wajib dipilih.'),
            'role_id.exists' => __('Role tidak ditemukan pada academy ini.'),
        ];
    }
}
