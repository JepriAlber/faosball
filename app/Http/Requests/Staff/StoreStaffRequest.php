<?php

namespace App\Http\Requests\Staff;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
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

            'id_employment_type' => [
                'required',
                'uuid',
                Rule::exists('employment_types', 'id_employment_type')
                    ->where(fn ($query) => $query->where('id_academy', $academyId)->where('status', true)),
            ],

            'id_staff_position' => [
                'required',
                'uuid',
                Rule::exists('staff_positions', 'id_staff_position')
                    ->where(fn ($query) => $query->where('id_academy', $academyId)->where('status', true)),
            ],

            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],

            'gender' => ['required', 'in:male,female'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'in:islam,kristen,katolik,hindu,buddha,konghucu,lainnya'],
            'blood_type' => ['nullable', 'in:A,B,AB,O'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],

            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'join_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive,resigned'],

            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => __('Academy wajib dipilih.'),
            'id_academy.prohibited' => __('Academy tidak dapat dipilih.'),
            'id_academy.uuid' => __('Academy tidak valid.'),
            'id_academy.exists' => __('Academy tidak ditemukan.'),

            'id_employment_type.required' => __('Employment type wajib dipilih.'),
            'id_employment_type.exists' => __('Employment type tidak valid.'),

            'id_staff_position.required' => __('Staff position wajib dipilih.'),
            'id_staff_position.exists' => __('Staff position tidak valid.'),

            'full_name.required' => __('Nama lengkap wajib diisi.'),
            'full_name.max' => __('Nama lengkap maksimal :max karakter.'),
            'nickname.max' => __('Nickname maksimal :max karakter.'),

            'gender.required' => __('Jenis kelamin wajib dipilih.'),
            'gender.in' => __('Jenis kelamin tidak valid.'),

            'birth_place.required' => __('Tempat lahir wajib diisi.'),
            'birth_date.required' => __('Tanggal lahir wajib diisi.'),
            'birth_date.date' => __('Tanggal lahir tidak valid.'),

            'nationality.max' => __('Kewarganegaraan maksimal :max karakter.'),
            'religion.in' => __('Agama tidak valid.'),
            'blood_type.in' => __('Golongan darah tidak valid.'),
            'marital_status.in' => __('Status pernikahan tidak valid.'),

            'phone.required' => __('Nomor telepon wajib diisi.'),
            'phone.max' => __('Nomor telepon maksimal :max karakter.'),
            'email.email' => __('Format email tidak valid.'),

            'end_date.after_or_equal' => __('Tanggal berhenti tidak boleh sebelum tanggal bergabung.'),
            'salary.numeric' => __('Gaji harus berupa angka.'),
            'salary.min' => __('Gaji tidak boleh negatif.'),
            'status.in' => __('Status tidak valid.'),

            'photo.image' => __('Foto harus berupa gambar.'),
            'photo.max' => __('Ukuran foto tidak boleh lebih dari 2MB.'),
        ];
    }
}
