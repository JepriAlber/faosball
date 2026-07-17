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
            'id_academy.required' => 'Academy wajib dipilih.',
            'id_academy.prohibited' => 'Academy tidak dapat dipilih.',
            'id_academy.uuid' => 'Academy tidak valid.',
            'id_academy.exists' => 'Academy tidak ditemukan.',

            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal :max karakter.',
            'name.unique' => 'Nama kategori sudah digunakan pada academy ini.',

            'description.string' => 'Deskripsi harus berupa teks.',

            'min_age.required' => 'Umur minimal wajib diisi.',
            'min_age.integer' => 'Umur minimal harus berupa angka.',
            'min_age.min' => 'Umur minimal tidak valid.',
            'min_age.max' => 'Umur minimal maksimal :max tahun.',

            'max_age.required' => 'Umur maksimal wajib diisi.',
            'max_age.integer' => 'Umur maksimal harus berupa angka.',
            'max_age.min' => 'Umur maksimal tidak valid.',
            'max_age.max' => 'Umur maksimal maksimal :max tahun.',
            'max_age.gte' => 'Umur maksimal tidak boleh lebih kecil dari umur minimal.',

            'status.required' => 'Status wajib ditentukan.',
            'status.boolean' => 'Status tidak valid.',
        ];
    }
}
