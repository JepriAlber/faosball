<?php

namespace App\Http\Requests\Team;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamFormRequest extends FormRequest
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

            'id_season' => [
                'required',
                'uuid',
                Rule::exists('seasons', 'id_season')->where(fn ($q) => $q->where('id_academy', $academyId)),
            ],

            'id_player_category' => [
                'required',
                'uuid',
                Rule::exists('player_categories', 'id_player_category')->where(fn ($q) => $q->where('id_academy', $academyId)),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('teams', 'name')
                    ->where(fn ($q) => $q->where('id_academy', $academyId))
                    ->ignore($this->route('team')?->id_team, 'id_team'),
            ],

            'team_type' => ['required', Rule::in(['regular', 'tournament', 'event', 'temporary'])],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_season.required' => __('Season wajib dipilih.'),
            'id_player_category.required' => __('Player category wajib dipilih.'),
            'name.required' => __('Nama tim wajib diisi.'),
            'name.unique' => __('Tim dengan nama ini sudah ada di academy ini.'),
            'team_type.required' => __('Tipe tim wajib dipilih.'),
        ];
    }
}
