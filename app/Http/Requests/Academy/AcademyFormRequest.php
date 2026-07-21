<?php

namespace App\Http\Requests\Academy;

use App\Services\AcademyManagementService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademyFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'code' => [
                'required',
                'string',
                'max:10',
                'alpha_dash',
                Rule::unique('academies','code')
                    ->ignore($this->academy?->id_academy,'id_academy')
            ],

            'phone' => [
                'required',
                'string',
                'max:50'
            ],

            'email' => [
                'required',
                'email',
                'max:255'
            ],

            'address' => [
                'required',
                'string'
            ],

            'tagline' => [
                'required',
                'string',
                'max:255'
            ],

            'status' => [
                'nullable',
                'boolean'
            ],

            'subscription_type' => [
                'required',
                'string',
                Rule::in(array_keys(AcademyManagementService::SUBSCRIPTION_TYPES)),
            ],

            'subscription_fee' => [
                'required',
                'numeric',
                'min:0',
            ],

            'subscription_started_at' => [
                'required',
                'date',
            ],

            'subscription_ends_at' => [
                'required',
                'date',
                'after_or_equal:subscription_started_at',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp,svg',
                'max:2048'
            ],

            'logo_sidebar' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp,svg',
                'max:2048',
            ],

            'primary_color' => [
                'required',
                'string',
                'regex:/^#[0-9a-fA-F]{6}$/',
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'create_account' => [
                'nullable',
                'boolean',
            ],

            'owner_email' => [
                'required_if:create_account,1',
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'owner_password' => [
                'required_if:create_account,1',
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Nama academy wajib diisi.'),
            'name.max' => __('Nama academy tidak boleh lebih dari 255 karakter.'),
            'phone.required' => __('Nomor telepon wajib diisi.'),
            'phone.max' => __('Nomor telepon tidak boleh lebih dari 50 karakter.'),
            'email.required' => __('Email wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.max' => __('Email tidak boleh lebih dari 255 karakter.'),
            'address.required' => __('Alamat wajib diisi.'),
            'tagline.required' => __('Tagline wajib diisi.'),
            'tagline.max' => __('Tagline tidak boleh lebih dari 255 karakter.'),
            'status.boolean' => __('Format status harus berupa boolean.'),

            'subscription_type.required' => __('Tipe langganan wajib dipilih.'),
            'subscription_type.in' => __('Tipe langganan tidak valid.'),

            'subscription_fee.required' => __('Biaya langganan wajib diisi.'),
            'subscription_fee.numeric' => __('Biaya langganan harus berupa angka.'),
            'subscription_fee.min' => __('Biaya langganan tidak boleh negatif.'),

            'subscription_started_at.required' => __('Tanggal mulai langganan wajib diisi.'),
            'subscription_started_at.date' => __('Tanggal mulai langganan tidak valid.'),

            'subscription_ends_at.required' => __('Tanggal berakhir langganan wajib diisi.'),
            'subscription_ends_at.date' => __('Tanggal berakhir langganan tidak valid.'),
            'subscription_ends_at.after_or_equal' => __('Tanggal berakhir langganan tidak boleh sebelum tanggal mulai.'),

            'logo.image' => __('Logo harus berupa gambar.'),
            'logo.mimes' => __('Format gambar logo harus berupa: jpeg, png, jpg, webp, atau svg.'),
            'logo.max' => __('Ukuran logo tidak boleh lebih dari 2MB.'),

            'logo_sidebar.image' => __('Logo sidebar harus berupa gambar.'),
            'logo_sidebar.mimes' => __('Format gambar logo sidebar harus berupa: jpeg, png, jpg, webp, atau svg.'),
            'logo_sidebar.max' => __('Ukuran logo sidebar tidak boleh lebih dari 2MB.'),

            'primary_color.required' => __('Warna utama wajib dipilih.'),
            'primary_color.regex' => __('Format warna tidak valid.'),

            'code.required'=>__('Kode academy wajib diisi.'),
            'code.max'=>__('Kode academy maksimal 10 karakter.'),
            'code.alpha_dash'=>__('Kode academy hanya boleh berisi huruf, angka, dan tanda strip.'),

            'owner_email.required_if' => __('Email akun Owner wajib diisi.'),
            'owner_email.email' => __('Format email akun Owner tidak valid.'),
            'owner_email.unique' => __('Email sudah digunakan oleh akun lain.'),

            'owner_password.required_if' => __('Password akun Owner wajib diisi.'),
            'owner_password.min' => __('Password akun Owner minimal :min karakter.'),
            'owner_password.confirmed' => __('Konfirmasi password akun Owner tidak sesuai.'),
        ];
    }
}
