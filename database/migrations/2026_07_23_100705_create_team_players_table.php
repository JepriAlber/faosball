<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_players', function (Blueprint $table) {

            $table->uuid('id_team_player')->primary();
            $table->uuid('id_academy');

            $table->uuid('id_team');
            $table->uuid('id_player');

            $table->unsignedTinyInteger('jersey_number');
            $table->boolean('is_captain')->default(false);

            $table->date('join_date');
            $table->date('leave_date')->nullable(); // NULL = masih aktif (issue16.md Bagian 2c)

            $table->text('notes')->nullable();

            $table->timestamps();
            // TIDAK ada kolom status -- diturunkan dari leave_date (Aturan Emas).
            // TIDAK ada softDeletes -- baris ini sendiri SUDAH histori permanen,
            // "keluar" direpresentasikan leave_date, bukan dihapus/diarsipkan lagi.

            $table->index('id_academy');
            $table->index('id_team');
            $table->index('id_player');
            $table->index(['id_team', 'leave_date'], 'team_players_team_leave_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_team')->references('id_team')->on('teams')->cascadeOnDelete();
            $table->foreign('id_player')->references('id_player')->on('players')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_players');
    }
};
