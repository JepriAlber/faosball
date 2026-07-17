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
            // Player lama (sebelum module ini) belum punya kategori.
            // Lihat Bagian 4.6.
            $table->uuid('id_player_category')
                ->nullable()
                ->after('id_player_type');

            $table->index('id_player_category');

            // nullOnDelete, BUKAN cascadeOnDelete. Lihat Bagian 4.4.
            $table->foreign('id_player_category')
                ->references('id_player_category')
                ->on('player_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['id_player_category']);
            $table->dropIndex(['id_player_category']);
            $table->dropColumn('id_player_category');
        });
    }
};
