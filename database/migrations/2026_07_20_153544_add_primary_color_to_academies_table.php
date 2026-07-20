<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Color
            |--------------------------------------------------------------------------
            | Format hex "#rrggbb" (7 karakter, termasuk '#'). Nullable di level
            | DATABASE karena academy yang sudah ada belum punya data ini -- WAJIB
            | diisi lewat Form Request untuk academy yang dibuat/diedit setelahnya
            | (lihat Tahap 7). Kalau NULL, AcademyService::brandColorVariables()
            | (Tahap 4) fallback ke biru default variables.css apa adanya --
            | tidak ada backfill migration untuk academy lama.
            */
            $table->string('primary_color', 7)
                ->nullable()
                ->after('logo_favicon');
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn('primary_color');
        });
    }
};
