<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi sesungguhnya sudah di middleware route (nested,
        // permission:staff.update/player.update) -- lihat Tahap 7.
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('Jenis dokumen wajib dipilih.'),
            'file.required' => __('Berkas wajib diunggah.'),
            'file.file' => __('Berkas tidak valid.'),
            'file.mimes' => __('Berkas harus berformat PDF, JPG, JPEG, atau PNG.'),
            'file.max' => __('Ukuran berkas maksimal 5MB.'),
        ];
    }
}
