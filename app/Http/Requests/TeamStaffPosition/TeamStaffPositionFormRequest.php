<?php

namespace App\Http\Requests\TeamStaffPosition;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamStaffPositionFormRequest extends FormRequest
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
                Rule::unique('team_staff_positions', 'code')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('team_staff_position')?->id_team_staff_position, 'id_team_staff_position'),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('team_staff_positions', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('team_staff_position')?->id_team_staff_position, 'id_team_staff_position'),
            ],

            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => __('Academy wajib dipilih.'),
            'code.required' => __('Kode posisi wajib diisi.'),
            'code.unique' => __('Kode posisi ini sudah dipakai di academy ini.'),
            'name.required' => __('Nama posisi wajib diisi.'),
            'name.unique' => __('Posisi dengan nama ini sudah ada di academy ini.'),
        ];
    }
}
