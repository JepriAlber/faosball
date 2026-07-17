<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {

            // Halaman index sekarang menyaring player per status (tab
            // Aktif/Nonaktif/Lulus/Keluar) yang selalu dikombinasikan dengan
            // AcademyScope (WHERE id_academy = ?). Index composite supaya
            // filter status tetap kena index, bukan full scan setelah
            // AcademyScope menyempitkan baris.
            $table->index(['id_academy', 'status'], 'players_academy_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('players_academy_status_index');
        });
    }
};
