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
            'id_academy.required' => 'Academy wajib dipilih.',
            'id_academy.prohibited' => 'Academy tidak dapat dipilih.',
            'id_academy.uuid' => 'Academy tidak valid.',
            'id_academy.exists' => 'Academy tidak ditemukan.',

            'name.required' => 'Nama type wajib diisi.',
            'name.string' => 'Nama type harus berupa teks.',
            'name.max' => 'Nama type maksimal :max karakter.',
            'name.unique' => 'Nama type sudah digunakan pada academy ini.',

            'description.string' => 'Deskripsi harus berupa teks.',

            'is_billable.required' => 'Status tagihan wajib ditentukan.',
            'is_billable.boolean' => 'Status tagihan tidak valid.',

            'status.required' => 'Status wajib ditentukan.',
            'status.boolean' => 'Status tidak valid.',
        ];
    }
}
