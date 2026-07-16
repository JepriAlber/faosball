<?php

namespace App\Http\Requests\Role;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleFormRequest extends FormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        $academyService = app(AcademyService::class);

        $academyId = $academyService->isSuperAdmin()
            ? $this->input('id_academy')
            : $academyService->currentId();

        return [
            // Hanya Super Admin yang boleh mengirim id_academy.
            // User academy: field ini tidak dirender & ditolak kalau tetap dikirim.
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'nullable' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query
                        ->where('guard_name', config('faos.guard'))
                        ->when(
                            $academyId,
                            fn ($q) => $q->where('id_academy', $academyId),
                            fn ($q) => $q->whereNull('id_academy'),
                        )
                    )
                    ->ignore($this->role?->id),
            ],

            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama role wajib diisi.',
            'name.string' => 'Nama role harus berupa teks.',
            'name.max' => 'Nama role maksimal 100 karakter.',
            'name.unique' => 'Nama role sudah digunakan pada academy ini.',

            'id_academy.prohibited' => 'Academy tidak dapat dipilih.',
            'id_academy.uuid' => 'Academy tidak valid.',
            'id_academy.exists' => 'Academy tidak ditemukan.',

            'permissions.array' => 'Format permission tidak valid.',
            'permissions.*.exists' => 'Permission yang dipilih tidak ditemukan.',
        ];
    }
}
