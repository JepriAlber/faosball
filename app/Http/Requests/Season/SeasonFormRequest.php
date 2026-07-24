<?php

namespace App\Http\Requests\Season;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeasonFormRequest extends FormRequest
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

            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('seasons', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('season')?->id_season, 'id_season'),
            ],

            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => __('Academy wajib dipilih.'),
            'name.required' => __('Nama season wajib diisi.'),
            'name.unique' => __('Season dengan nama ini sudah ada di academy ini.'),
            'end_date.after_or_equal' => __('Tanggal berakhir tidak boleh sebelum tanggal mulai.'),
        ];
    }
}
