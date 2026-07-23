<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            // documentable_type/documentable_id SENGAJA tidak diisi di sini
            // -- wajib dipassing eksplisit tiap kali dipakai di test, pola
            // sama EmploymentContractFactory yang tidak isi id_staff sendiri.
            'type' => 'ijazah',
            'original_name' => 'dokumen.pdf',
            'path' => 'dummy/dummy.pdf',
            'mime_type' => 'application/pdf',
            'size' => 102400,
            'uploaded_by' => null,
        ];
    }
}
