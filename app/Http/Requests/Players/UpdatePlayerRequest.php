<?php

namespace App\Http\Requests\Players;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdatePlayerRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [

            // Academy player TIDAK berubah lewat form edit, jadi acuannya
            // diambil dari player yang sedang diedit.
            //
            // Sengaja TIDAK memfilter status = true: player yang type-nya sudah
            // dinonaktifkan harus tetap bisa disimpan tanpa dipaksa ganti type.
            'id_player_type' => [
                'required',
                'uuid',
                Rule::exists('player_types', 'id_player_type')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $this->route('player')->id_academy)
                    ),
            ],

            // Sengaja TIDAK memfilter status = true: player yang kategorinya
            // sudah dinonaktifkan harus tetap bisa disimpan.
            'id_player_category' => [
                'required',
                'uuid',
                Rule::exists('player_categories', 'id_player_category')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $this->route('player')->id_academy)
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'nick_name' => [
                'nullable',
                'string',
                'max:100'
            ],

            'birth_date' => [
                'required',
                'date'
            ],

            'gender' => [
                'required',
                'in:male,female'
            ],

            'nationality' => [
                'nullable',
                'string',
                'max:50'
            ],

            'height' => [
                'nullable',
                'integer'
            ],

            'weight' => [
                'nullable',
                'integer'
            ],

            'preferred_foot' => [
                'nullable',
                'in:left,right,both'
            ],

            // Buang filter status = true (beda dengan StorePlayerRequest):
            // player yang posisinya sudah dinonaktifkan tetap bisa disimpan
            // tanpa dipaksa ganti posisi.
            'id_primary_position' => [
                'required',
                'uuid',
                'exists:player_positions,id_player_position',
            ],

            'id_secondary_position' => [
                'nullable',
                'uuid',
                'different:id_primary_position',
                'exists:player_positions,id_player_position',
            ],

            'status' => [
                'nullable',
                'in:active,inactive,graduated,left'
            ],

            'photo' => [
                'nullable',
                'image',
                'max:2048'
            ],

            'notes' => [
                'nullable',
                'string'
            ],

        ];

    }

    public function messages(): array
    {
        return [

            'id_player_type.required' => __('Type player wajib dipilih.'),
            'id_player_type.uuid' => __('Type player tidak valid.'),
            'id_player_type.exists' => __('Type player tidak ditemukan pada academy ini.'),

            'id_player_category.required' => __('Kategori umur wajib dipilih.'),
            'id_player_category.uuid' => __('Kategori umur tidak valid.'),
            'id_player_category.exists' => __('Kategori umur tidak ditemukan pada academy ini.'),

            'name.required' => __('Nama player wajib diisi.'),
            'name.string' => __('Nama player harus berupa teks.'),
            'name.max' => __('Nama player maksimal :max karakter.'),

            'nick_name.string' => __('Nama panggilan harus berupa teks.'),
            'nick_name.max' => __('Nama panggilan maksimal :max karakter.'),

            'birth_date.required' => __('Tanggal lahir wajib diisi.'),
            'birth_date.date' => __('Tanggal lahir tidak valid.'),

            'gender.required' => __('Jenis kelamin wajib dipilih.'),
            'gender.in' => __('Jenis kelamin yang dipilih tidak valid.'),

            'nationality.string' => __('Kewarganegaraan harus berupa teks.'),
            'nationality.max' => __('Kewarganegaraan maksimal :max karakter.'),

            'height.integer' => __('Tinggi badan harus berupa angka.'),

            'weight.integer' => __('Berat badan harus berupa angka.'),

            'preferred_foot.in' => __('Kaki dominan tidak valid.'),

            'id_primary_position.required' => __('Posisi utama wajib dipilih.'),
            'id_primary_position.uuid' => __('Posisi utama tidak valid.'),
            'id_primary_position.exists' => __('Posisi utama tidak ditemukan.'),

            'id_secondary_position.uuid' => __('Posisi kedua tidak valid.'),
            'id_secondary_position.exists' => __('Posisi kedua tidak ditemukan.'),
            'id_secondary_position.different' => __('Posisi kedua tidak boleh sama dengan posisi utama.'),

            'status.in' => __('Status player tidak valid.'),

            'photo.image' => __('File harus berupa gambar.'),
            'photo.max' => __('Ukuran foto maksimal 2 MB.'),

            'notes.string' => __('Catatan harus berupa teks.'),

        ];
    }

}