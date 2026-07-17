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
            'primary_position' => [
                'required',
                'string',
                'max:20'
            ],
            'secondary_position' => [
                'nullable',
                'string',
                'max:20'
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

            'id_academy.required' => 'Academy wajib dipilih.',
            'id_academy.prohibited' => 'Academy tidak dapat dipilih.',
            'id_academy.uuid' => 'Academy tidak valid.',
            'id_academy.exists' => 'Academy tidak ditemukan.',

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
            'height.min' => 'Tinggi badan tidak valid.',

            'weight.integer' => 'Berat badan harus berupa angka.',
            'weight.min' => 'Berat badan tidak valid.',

            'preferred_foot.in' => 'Kaki dominan tidak valid.',

            'primary_position.required' => 'Posisi utama wajib dipilih.',
            'primary_position.string' => 'Posisi utama harus berupa teks.',
            'primary_position.max' => 'Posisi utama maksimal :max karakter.',

            'secondary_position.string' => 'Posisi kedua harus berupa teks.',
            'secondary_position.max' => 'Posisi kedua maksimal :max karakter.',

            'join_date.date' => 'Tanggal bergabung tidak valid.',

            'status.in' => 'Status player tidak valid.',

            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',

            'notes.string' => 'Catatan harus berupa teks.',

            'email.required_if' => 'Email akun player wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',

            'password.required_if' => 'Password akun player wajib diisi.',
            'password.min' => 'Password minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',

        ];
    }

}