<?php

namespace App\Http\Requests\TeamPlayer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jersey_number' => ['required', 'integer', 'min:1', 'max:99'],
            'is_captain' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'jersey_number.required' => __('Nomor punggung wajib diisi.'),
            'jersey_number.max' => __('Nomor punggung maksimal 99.'),
        ];
    }
}
