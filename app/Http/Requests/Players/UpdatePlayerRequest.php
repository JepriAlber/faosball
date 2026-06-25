<?php

namespace App\Http\Requests\Players;


use Illuminate\Foundation\Http\FormRequest;


class UpdatePlayerRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [

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


            'status' => [
                'nullable',
                'in:active,inactive,graduated,left'
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


            'primary_position.required' => 'Posisi utama wajib dipilih.',

            'primary_position.max' => 'Posisi utama maksimal :max karakter.',


            'secondary_position.max' => 'Posisi kedua maksimal :max karakter.',


            'status.in' => 'Status player tidak valid.',


            'notes.string' => 'Catatan harus berupa teks.',

        ];
    }

}