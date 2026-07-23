<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {

            $table->uuid('id_document')->primary();

            $table->uuid('id_academy');

            // Polymorphic -- 1 dokumen menempel ke 1 entitas (Staff, Player,
            // dst di masa depan). uuidMorphs() bikin documentable_id (uuid)
            // + documentable_type (string) + index gabungan otomatis.
            $table->uuidMorphs('documentable');

            $table->string('type', 50); // ijazah/akte/ktp/dst -- lihat config('faos.document_types')
            $table->string('original_name');
            $table->string('path'); // path relatif di disk 'local', BUKAN disk 'public'
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size'); // bytes

            $table->uuid('uploaded_by')->nullable();

            $table->timestamps();
            // TIDAK ada softDeletes() -- hapus dokumen = hapus permanen
            // (file fisik ikut dihapus, lihat Tahap 4), tidak perlu recycle bin.

            $table->index('id_academy');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
