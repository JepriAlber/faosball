<?php

namespace App\Http\Requests\TeamPlayer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'id_player' => [
                'required',
                'uuid',
                Rule::exists('players', 'id_player')->where(fn ($q) => $q->where('id_academy', $team->id_academy)),
            ],
            'jersey_number' => ['required', 'integer', 'min:1', 'max:99'],
            'is_captain' => ['nullable', 'boolean'],
            'join_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_player.required' => __('Player wajib dipilih.'),
            'jersey_number.required' => __('Nomor punggung wajib diisi.'),
            'jersey_number.max' => __('Nomor punggung maksimal 99.'),
            'join_date.required' => __('Tanggal bergabung wajib diisi.'),
        ];
    }
}
