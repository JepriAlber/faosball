<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class AcademyProfileFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Nama academy wajib diisi.'),
            'name.max' => __('Nama academy tidak boleh lebih dari 255 karakter.'),
            'tagline.required' => __('Tagline wajib diisi.'),
            'tagline.max' => __('Tagline tidak boleh lebih dari 255 karakter.'),
            'phone.required' => __('Nomor telepon wajib diisi.'),
            'phone.max' => __('Nomor telepon tidak boleh lebih dari 50 karakter.'),
            'email.required' => __('Email wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.max' => __('Email tidak boleh lebih dari 255 karakter.'),
            'address.required' => __('Alamat wajib diisi.'),
            'description.string' => __('Deskripsi harus berupa teks.'),
            'logo.image' => __('Logo harus berupa gambar.'),
            'logo.mimes' => __('Format gambar logo harus berupa: jpeg, png, jpg, webp, atau svg.'),
            'logo.max' => __('Ukuran logo tidak boleh lebih dari 2MB.'),

            'primary_color.required' => __('Warna utama wajib dipilih.'),
            'primary_color.regex' => __('Format warna tidak valid.'),
        ];
    }
}
