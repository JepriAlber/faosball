<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {

            $table->uuid('id_season')->primary();
            $table->uuid('id_academy');

            $table->string('name', 50); // "2026"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');

            // Dua academy BOLEH punya "2026" masing-masing. Satu academy
            // TIDAK BOLEH punya dua "2026" (pola sama player_categories).
            $table->unique(['id_academy', 'name'], 'seasons_academy_name_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
