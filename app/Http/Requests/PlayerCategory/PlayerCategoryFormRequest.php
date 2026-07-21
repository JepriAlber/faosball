<?php

namespace App\Http\Requests\PlayerCategory;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerCategoryFormRequest extends FormRequest
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
                Rule::unique('player_categories', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('player_category')?->id_player_category, 'id_player_category'),
            ],

            'description' => ['nullable', 'string'],

            'min_age' => ['required', 'integer', 'min:0', 'max:99'],

            // gte:min_age -- WAJIB. Rentang terbalik (min 15, max 12) tidak akan
            // pernah cocok dengan umur siapa pun, dan kegagalannya diam-diam:
            // kategori itu sekadar tidak pernah tersaran, tanpa error apapun.
            'max_age' => ['required', 'integer', 'min:0', 'max:99', 'gte:min_age'],

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

            'name.required' => __('Nama kategori wajib diisi.'),
            'name.string' => __('Nama kategori harus berupa teks.'),
            'name.max' => __('Nama kategori maksimal :max karakter.'),
            'name.unique' => __('Nama kategori sudah digunakan pada academy ini.'),

            'description.string' => __('Deskripsi harus berupa teks.'),

            'min_age.required' => __('Umur minimal wajib diisi.'),
            'min_age.integer' => __('Umur minimal harus berupa angka.'),
            'min_age.min' => __('Umur minimal tidak valid.'),
            'min_age.max' => __('Umur minimal maksimal :max tahun.'),

            'max_age.required' => __('Umur maksimal wajib diisi.'),
            'max_age.integer' => __('Umur maksimal harus berupa angka.'),
            'max_age.min' => __('Umur maksimal tidak valid.'),
            'max_age.max' => __('Umur maksimal maksimal :max tahun.'),
            'max_age.gte' => __('Umur maksimal tidak boleh lebih kecil dari umur minimal.'),

            'status.required' => __('Status wajib ditentukan.'),
            'status.boolean' => __('Status tidak valid.'),
        ];
    }
}
