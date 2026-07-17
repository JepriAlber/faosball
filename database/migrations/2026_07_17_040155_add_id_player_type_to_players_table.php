<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {

            // Nullable di level DATABASE, tapi WAJIB di Form Request.
            // Alasannya: player yang sudah ada sebelum module ini belum punya
            // type, dan kita tidak mau migration gagal / menebak-nebak type
            // mereka. Lihat Bagian 4.5.
            $table->uuid('id_player_type')
                ->nullable()
                ->after('id_user');

            $table->index('id_player_type');

            // nullOnDelete, BUKAN cascadeOnDelete.
            // cascadeOnDelete akan menghapus SELURUH PLAYER saat sebuah type
            // dihapus. Lihat Bagian 4.1.
            $table->foreign('id_player_type')
                ->references('id_player_type')
                ->on('player_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['id_player_type']);
            $table->dropIndex(['id_player_type']);
            $table->dropColumn('id_player_type');
        });
    }
};
