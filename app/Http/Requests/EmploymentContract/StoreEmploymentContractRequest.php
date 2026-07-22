<?php

namespace App\Http\Requests\EmploymentContract;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmploymentContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // $this->staff -- route-model-binding {staff}, sama pola StoreStaffAccountRequest.
        $academyId = $this->staff->id_academy;

        return [
            'id_employment_type' => [
                'required', 'uuid',
                Rule::exists('employment_types', 'id_employment_type')
                    ->where(fn ($q) => $q->where('id_academy', $academyId)->where('status', true)),
            ],
            'id_staff_position' => [
                'required', 'uuid',
                Rule::exists('staff_positions', 'id_staff_position')
                    ->where(fn ($q) => $q->where('id_academy', $academyId)->where('status', true)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_employment_type.required' => __('Employment type wajib dipilih.'),
            'id_employment_type.exists' => __('Employment type tidak valid.'),
            'id_staff_position.required' => __('Staff position wajib dipilih.'),
            'id_staff_position.exists' => __('Staff position tidak valid.'),
            'start_date.required' => __('Tanggal mulai kontrak wajib diisi.'),
            'start_date.date' => __('Tanggal mulai kontrak tidak valid.'),
            'end_date.after_or_equal' => __('Tanggal berakhir kontrak tidak boleh sebelum tanggal mulai.'),
            'salary.numeric' => __('Gaji harus berupa angka.'),
            'salary.min' => __('Gaji tidak boleh negatif.'),
        ];
    }
}
