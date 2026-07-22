<?php

namespace App\Http\Requests\StaffPosition;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffPositionFormRequest extends FormRequest
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
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'required' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('staff_positions', 'code')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('staff_position')?->id_staff_position, 'id_staff_position'),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('staff_positions', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('staff_position')?->id_staff_position, 'id_staff_position'),
            ],

            // Nullable -- "Default Role" boleh kosong, admin isi belakangan.
            // exists() difilter id_academy supaya tidak bisa pilih role dari
            // academy lain (roles.id itu bigint, BUKAN uuid seperti FK lain).
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('id_academy', $academyId)),
            ],

            'is_coach' => ['required', 'boolean'],

            'description' => ['nullable', 'string'],

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

            'code.required' => __('Kode jabatan wajib diisi.'),
            'code.string' => __('Kode jabatan harus berupa teks.'),
            'code.max' => __('Kode jabatan maksimal :max karakter.'),
            'code.unique' => __('Kode jabatan sudah digunakan pada academy ini.'),

            'name.required' => __('Nama jabatan wajib diisi.'),
            'name.string' => __('Nama jabatan harus berupa teks.'),
            'name.max' => __('Nama jabatan maksimal :max karakter.'),
            'name.unique' => __('Nama jabatan sudah digunakan pada academy ini.'),

            'role_id.integer' => __('Default role tidak valid.'),
            'role_id.exists' => __('Default role tidak ditemukan pada academy ini.'),

            'is_coach.required' => __('Status pelatih wajib ditentukan.'),
            'is_coach.boolean' => __('Status pelatih tidak valid.'),

            'description.string' => __('Deskripsi harus berupa teks.'),

            'status.required' => __('Status wajib ditentukan.'),
            'status.boolean' => __('Status tidak valid.'),
        ];
    }
}
