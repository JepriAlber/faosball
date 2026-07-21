<?php

namespace App\Http\Requests\PlayerPosition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerPositionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kode posisi dinormalkan jadi huruf besar SEBELUM validasi jalan.
     *
     * Tanpa ini, "st" lolos rule unique walau "ST" sudah ada -- lalu Service
     * menyimpannya dan menabrak unique index di level database, yang muncul
     * sebagai error SQL mentah, bukan pesan validasi yang rapi.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('player_position')?->id_player_position;

        return [
            /*
            | Tidak ada rule 'id_academy' di sini -- master posisi global, tidak
            | punya pemilik academy. Kalau kamu menambahkannya, berarti kamu
            | memperlakukan module ini sebagai tenant. Lihat Bagian 4.1.
            */

            // unique GLOBAL, bukan composite dengan id_academy.
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('player_positions', 'code')->ignore($id, 'id_player_position'),
            ],

            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('player_positions', 'name')->ignore($id, 'id_player_position'),
            ],

            'description' => ['nullable', 'string'],

            'position_group' => ['required', 'string', 'max:50'],

            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],

            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Kode posisi wajib diisi.'),
            'code.max' => __('Kode posisi maksimal :max karakter.'),
            'code.unique' => __('Kode posisi sudah digunakan.'),

            'name.required' => __('Nama posisi wajib diisi.'),
            'name.max' => __('Nama posisi maksimal :max karakter.'),
            'name.unique' => __('Nama posisi sudah digunakan.'),

            'description.string' => __('Deskripsi harus berupa teks.'),

            'position_group.required' => __('Kelompok posisi wajib diisi.'),
            'position_group.max' => __('Kelompok posisi maksimal :max karakter.'),

            'sort_order.required' => __('Urutan wajib diisi.'),
            'sort_order.integer' => __('Urutan harus berupa angka.'),
            'sort_order.min' => __('Urutan tidak valid.'),
            'sort_order.max' => __('Urutan maksimal :max.'),

            'status.required' => __('Status wajib ditentukan.'),
            'status.boolean' => __('Status tidak valid.'),
        ];
    }
}
