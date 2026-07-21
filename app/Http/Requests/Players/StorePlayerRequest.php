<?php

namespace App\Http\Requests\Players;


use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StorePlayerRequest extends FormRequest
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

            // Hanya Super Admin yang boleh (dan wajib) memilih academy.
            // User academy: field ini tidak dirender & ditolak kalau tetap dikirim.
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'required' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            // Type WAJIB milik academy yang sama dengan player.
            //
            // Rule::exists() memakai query builder mentah -- AcademyScope TIDAK
            // ikut jalan di sini. Tanpa where('id_academy', ...) eksplisit,
            // Owner Academy A bisa memasang type milik Academy B lewat POST
            // yang dikarang. Lihat Bagian 4.2.
            'id_player_type' => [
                'required',
                'uuid',
                Rule::exists('player_types', 'id_player_type')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $academyId)
                        ->where('status', true)
                    ),
            ],

            // Kategori WAJIB milik academy yang sama dengan player.
            // Rule::exists() TIDAK kena AcademyScope -- where('id_academy')
            // eksplisit di bawah ini yang menjaga batas antar academy.
            // Lihat issue2.md Bagian 4.5.
            //
            // Sengaja TIDAK ada validasi "umur harus cocok dengan rentang
            // kategori". Itu disengaja, bukan kelupaan. Lihat issue2.md Bagian 4.2.
            'id_player_category' => [
                'required',
                'uuid',
                Rule::exists('player_categories', 'id_player_category')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $academyId)
                        ->where('status', true)
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
                'integer',
                'min:1'
            ],
            'weight' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'preferred_foot' => [
                'nullable',
                'in:left,right,both'
            ],

            /*
            | PERHATIKAN: TIDAK ada where('id_academy', ...) di sini -- beda
            | dengan rule id_player_type / id_player_category. Master posisi
            | global; tabelnya tidak punya kolom id_academy, jadi menambahkannya
            | akan menghasilkan SQL error "column not found". Lihat issue3.md
            | Bagian 4.1.
            */
            'id_primary_position' => [
                'required',
                'uuid',
                Rule::exists('player_positions', 'id_player_position')
                    ->where(fn ($query) => $query->where('status', true)),
            ],

            // Posisi kedua tidak boleh sama dengan posisi utama. 'nullable'
            // membuat seluruh rule di bawahnya dilewati kalau field ini memang
            // dikosongkan.
            'id_secondary_position' => [
                'nullable',
                'uuid',
                'different:id_primary_position',
                Rule::exists('player_positions', 'id_player_position')
                    ->where(fn ($query) => $query->where('status', true)),
            ],

            'join_date' => [
                'nullable',
                'date'
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

            /*
            |--------------------------------------------------------------------------
            | Create Player Account
            |--------------------------------------------------------------------------
            */
            'create_account' => [
                'nullable',
                'boolean'
            ],
            'email' => [
                'required_if:create_account,1',
                'nullable',
                'email',
                'unique:users,email'
            ],
            'password' => [
                'required_if:create_account,1',
                'nullable',
                'string',
                'min:8',
                'confirmed'
            ],
        ];

    }

    public function messages(): array
    {
        return [

            'id_academy.required' => __('Academy wajib dipilih.'),
            'id_academy.prohibited' => __('Academy tidak dapat dipilih.'),
            'id_academy.uuid' => __('Academy tidak valid.'),
            'id_academy.exists' => __('Academy tidak ditemukan.'),

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
            'height.min' => __('Tinggi badan tidak valid.'),

            'weight.integer' => __('Berat badan harus berupa angka.'),
            'weight.min' => __('Berat badan tidak valid.'),

            'preferred_foot.in' => __('Kaki dominan tidak valid.'),

            'id_primary_position.required' => __('Posisi utama wajib dipilih.'),
            'id_primary_position.uuid' => __('Posisi utama tidak valid.'),
            'id_primary_position.exists' => __('Posisi utama tidak ditemukan.'),

            'id_secondary_position.uuid' => __('Posisi kedua tidak valid.'),
            'id_secondary_position.exists' => __('Posisi kedua tidak ditemukan.'),
            'id_secondary_position.different' => __('Posisi kedua tidak boleh sama dengan posisi utama.'),

            'join_date.date' => __('Tanggal bergabung tidak valid.'),

            'status.in' => __('Status player tidak valid.'),

            'photo.image' => __('File harus berupa gambar.'),
            'photo.max' => __('Ukuran foto maksimal 2 MB.'),

            'notes.string' => __('Catatan harus berupa teks.'),

            'email.required_if' => __('Email akun player wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.unique' => __('Email sudah digunakan oleh akun lain.'),

            'password.required_if' => __('Password akun player wajib diisi.'),
            'password.min' => __('Password minimal :min karakter.'),
            'password.confirmed' => __('Konfirmasi password tidak sesuai.'),

        ];
    }

}