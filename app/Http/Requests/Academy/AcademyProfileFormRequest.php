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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama academy wajib diisi.',
            'name.max' => 'Nama academy tidak boleh lebih dari 255 karakter.',
            'tagline.required' => 'Tagline wajib diisi.',
            'tagline.max' => 'Tagline tidak boleh lebih dari 255 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.max' => 'Nomor telepon tidak boleh lebih dari 50 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari 255 karakter.',
            'address.required' => 'Alamat wajib diisi.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Format gambar logo harus berupa: jpeg, png, jpg, webp, atau svg.',
            'logo.max' => 'Ukuran logo tidak boleh lebih dari 2MB.',
        ];
    }
}
