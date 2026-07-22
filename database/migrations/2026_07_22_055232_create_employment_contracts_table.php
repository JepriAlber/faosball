<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_contracts', function (Blueprint $table) {

            $table->uuid('id_employment_contract')->primary();

            $table->uuid('id_academy');
            $table->uuid('id_staff');

            /*
            |--------------------------------------------------------------------------
            | Klasifikasi kontrak (WAJIB -- 1 baris = 1 periode kerja yang utuh)
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_employment_type');
            $table->uuid('id_staff_position');

            $table->string('contract_code', 40)->unique();

            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = kontrak tidak berbatas waktu (permanent)
            $table->decimal('salary', 12, 2)->nullable();

            $table->enum('status', ['draft', 'active', 'completed', 'terminated', 'cancelled'])
                ->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
            // TIDAK ada softDeletes() -- Contract memang tidak pernah dihapus (Rule 3),
            // jadi tidak perlu mekanisme delete/restore sama sekali.

            $table->index('id_academy');
            $table->index('id_staff');
            $table->index('id_employment_type');
            $table->index('id_staff_position');
            $table->index(['id_staff', 'status'], 'employment_contracts_staff_status_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_staff')->references('id_staff')->on('staff')->cascadeOnDelete();

            // restrictOnDelete() -- alasan sama seperti staff.id_employment_type/id_staff_position
            // di issue11.md: kolom WAJIB terisi, FK tidak boleh diam-diam jadi NULL.
            $table->foreign('id_employment_type')->references('id_employment_type')->on('employment_types')->restrictOnDelete();
            $table->foreign('id_staff_position')->references('id_staff_position')->on('staff_positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');
    }
};
