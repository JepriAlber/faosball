<?php

namespace App\Http\Requests\Academy;

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

            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp,svg',
                'max:2048'
            ],

            'description' => [
                'nullable',
                'string'
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
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Format gambar logo harus berupa: jpeg, png, jpg, webp, atau svg.',
            'logo.max' => 'Ukuran logo tidak boleh lebih dari 2MB.',
            'code.required'=>'Kode academy wajib diisi.',
            'code.max'=>'Kode academy maksimal 10 karakter.',
            'code.alpha_dash'=>'Kode academy hanya boleh berisi huruf, angka, dan tanda strip.',
        ];
    }
}
