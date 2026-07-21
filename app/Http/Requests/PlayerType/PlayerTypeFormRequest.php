<?php

namespace App\Http\Requests\PlayerType;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerTypeFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academyService = app(AcademyService::class);

        $academyId = $academyService->isSuperAdmin()
            ? $this->input('id_academy')
            : $academyService->currentId();

        return [
            // Hanya Super Admin yang boleh mengirim id_academy, dan dia WAJIB
            // mengirimnya -- tidak ada "type system" tanpa academy.
            // User academy: field ini tidak dirender & ditolak kalau tetap dikirim.
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'required' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('player_types', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('player_type')?->id_player_type, 'id_player_type'),
            ],

            'description' => ['nullable', 'string'],

            'is_billable' => ['required', 'boolean'],

            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => __('Academy wajib dipilih.'),
            'id_academy.prohibited' => __('Academy tidak dapat dipilih.'),
            'id_academy.uuid' => __('Academy tidak valid.'),
            'id_academy.exists' => __('Academy tidak ditemukan.'),

            'name.required' => __('Nama type wajib diisi.'),
            'name.string' => __('Nama type harus berupa teks.'),
            'name.max' => __('Nama type maksimal :max karakter.'),
            'name.unique' => __('Nama type sudah digunakan pada academy ini.'),

            'description.string' => __('Deskripsi harus berupa teks.'),

            'is_billable.required' => __('Status tagihan wajib ditentukan.'),
            'is_billable.boolean' => __('Status tagihan tidak valid.'),

            'status.required' => __('Status wajib ditentukan.'),
            'status.boolean' => __('Status tidak valid.'),
        ];
    }
}
