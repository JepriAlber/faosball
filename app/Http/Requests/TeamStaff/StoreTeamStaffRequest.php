<?php

namespace App\Http\Requests\TeamStaff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'id_staff' => [
                'required',
                'uuid',
                Rule::exists('staff', 'id_staff')->where(fn ($q) => $q->where('id_academy', $team->id_academy)),
            ],
            'id_team_staff_position' => [
                'required',
                'uuid',
                Rule::exists('team_staff_positions', 'id_team_staff_position')->where(fn ($q) => $q->where('id_academy', $team->id_academy)),
            ],
            'join_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_staff.required' => __('Staff wajib dipilih.'),
            'id_team_staff_position.required' => __('Peran di tim wajib dipilih.'),
            'join_date.required' => __('Tanggal bergabung wajib diisi.'),
        ];
    }
}
