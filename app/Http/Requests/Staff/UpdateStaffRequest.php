<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],

            'gender' => ['required', 'in:male,female'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'in:islam,kristen,katolik,hindu,buddha,konghucu,lainnya'],
            'blood_type' => ['nullable', 'in:A,B,AB,O'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],

            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => __('Nama lengkap wajib diisi.'),
            'full_name.max' => __('Nama lengkap maksimal :max karakter.'),
            'nickname.max' => __('Nickname maksimal :max karakter.'),

            'gender.required' => __('Jenis kelamin wajib dipilih.'),
            'gender.in' => __('Jenis kelamin tidak valid.'),

            'birth_place.required' => __('Tempat lahir wajib diisi.'),
            'birth_date.required' => __('Tanggal lahir wajib diisi.'),
            'birth_date.date' => __('Tanggal lahir tidak valid.'),

            'nationality.max' => __('Kewarganegaraan maksimal :max karakter.'),
            'religion.in' => __('Agama tidak valid.'),
            'blood_type.in' => __('Golongan darah tidak valid.'),
            'marital_status.in' => __('Status pernikahan tidak valid.'),

            'phone.required' => __('Nomor telepon wajib diisi.'),
            'phone.max' => __('Nomor telepon maksimal :max karakter.'),
            'email.email' => __('Format email tidak valid.'),

            'photo.image' => __('Foto harus berupa gambar.'),
            'photo.max' => __('Ukuran foto tidak boleh lebih dari 2MB.'),
        ];
    }
}
