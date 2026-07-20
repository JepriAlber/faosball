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
            'name.required' => 'Nama academy wajib diisi.',
            'name.max' => 'Nama academy tidak boleh lebih dari 255 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.max' => 'Nomor telepon tidak boleh lebih dari 50 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari 255 karakter.',
            'address.required' => 'Alamat wajib diisi.',
            'tagline.required' => 'Tagline wajib diisi.',
            'tagline.max' => 'Tagline tidak boleh lebih dari 255 karakter.',
            'status.boolean' => 'Format status harus berupa boolean.',

            'subscription_type.required' => 'Tipe langganan wajib dipilih.',
            'subscription_type.in' => 'Tipe langganan tidak valid.',

            'subscription_fee.required' => 'Biaya langganan wajib diisi.',
            'subscription_fee.numeric' => 'Biaya langganan harus berupa angka.',
            'subscription_fee.min' => 'Biaya langganan tidak boleh negatif.',

            'subscription_started_at.required' => 'Tanggal mulai langganan wajib diisi.',
            'subscription_started_at.date' => 'Tanggal mulai langganan tidak valid.',

            'subscription_ends_at.required' => 'Tanggal berakhir langganan wajib diisi.',
            'subscription_ends_at.date' => 'Tanggal berakhir langganan tidak valid.',
            'subscription_ends_at.after_or_equal' => 'Tanggal berakhir langganan tidak boleh sebelum tanggal mulai.',

            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Format gambar logo harus berupa: jpeg, png, jpg, webp, atau svg.',
            'logo.max' => 'Ukuran logo tidak boleh lebih dari 2MB.',

            'primary_color.required' => 'Warna utama wajib dipilih.',
            'primary_color.regex' => 'Format warna tidak valid.',

            'code.required'=>'Kode academy wajib diisi.',
            'code.max'=>'Kode academy maksimal 10 karakter.',
            'code.alpha_dash'=>'Kode academy hanya boleh berisi huruf, angka, dan tanda strip.',

            'owner_email.required_if' => 'Email akun Owner wajib diisi.',
            'owner_email.email' => 'Format email akun Owner tidak valid.',
            'owner_email.unique' => 'Email sudah digunakan oleh akun lain.',

            'owner_password.required_if' => 'Password akun Owner wajib diisi.',
            'owner_password.min' => 'Password akun Owner minimal :min karakter.',
            'owner_password.confirmed' => 'Konfirmasi password akun Owner tidak sesuai.',
        ];
    }
}
