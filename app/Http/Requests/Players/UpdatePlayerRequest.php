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

            'id_player_type.required' => 'Type player wajib dipilih.',
            'id_player_type.uuid' => 'Type player tidak valid.',
            'id_player_type.exists' => 'Type player tidak ditemukan pada academy ini.',

            'id_player_category.required' => 'Kategori umur wajib dipilih.',
            'id_player_category.uuid' => 'Kategori umur tidak valid.',
            'id_player_category.exists' => 'Kategori umur tidak ditemukan pada academy ini.',

            'name.required' => 'Nama player wajib diisi.',
            'name.string' => 'Nama player harus berupa teks.',
            'name.max' => 'Nama player maksimal :max karakter.',

            'nick_name.string' => 'Nama panggilan harus berupa teks.',
            'nick_name.max' => 'Nama panggilan maksimal :max karakter.',

            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',

            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin yang dipilih tidak valid.',

            'nationality.string' => 'Kewarganegaraan harus berupa teks.',
            'nationality.max' => 'Kewarganegaraan maksimal :max karakter.',

            'height.integer' => 'Tinggi badan harus berupa angka.',

            'weight.integer' => 'Berat badan harus berupa angka.',

            'preferred_foot.in' => 'Kaki dominan tidak valid.',

            'id_primary_position.required' => 'Posisi utama wajib dipilih.',
            'id_primary_position.uuid' => 'Posisi utama tidak valid.',
            'id_primary_position.exists' => 'Posisi utama tidak ditemukan.',

            'id_secondary_position.uuid' => 'Posisi kedua tidak valid.',
            'id_secondary_position.exists' => 'Posisi kedua tidak ditemukan.',
            'id_secondary_position.different' => 'Posisi kedua tidak boleh sama dengan posisi utama.',

            'status.in' => 'Status player tidak valid.',

            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',

            'notes.string' => 'Catatan harus berupa teks.',

        ];
    }

}