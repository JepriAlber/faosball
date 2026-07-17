<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {

            // Nullable di level DATABASE, tapi id_primary_position WAJIB di
            // Form Request. Player lama (posisinya masih teks bebas) tidak punya
            // FK ini, dan migration TIDAK menebak-nebak pemetaannya.
            // Lihat Bagian 4.5.
            //
            // Sengaja TANPA ->after(): supaya migration ini tidak bergantung
            // pada issue.md / issue2.md. Urutan kolom tidak berpengaruh apa pun
            // secara fungsional.
            $table->uuid('id_primary_position')->nullable();
            $table->uuid('id_secondary_position')->nullable();

            $table->index('id_primary_position');
            $table->index('id_secondary_position');

            // nullOnDelete, BUKAN cascadeOnDelete. Lihat Bagian 4.4.
            $table->foreign('id_primary_position')
                ->references('id_player_position')
                ->on('player_positions')
                ->nullOnDelete();

            $table->foreign('id_secondary_position')
                ->references('id_player_position')
                ->on('player_positions')
                ->nullOnDelete();
        });

        Schema::table('players', function (Blueprint $table) {
            // Kolom teks bebas lama dibuang -- satu sumber kebenaran.
            // Dipisah ke Schema::table() kedua supaya urutannya jelas: kolom
            // baru ada dulu, baru yang lama dibuang.
            $table->dropColumn(['primary_position', 'secondary_position']);
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('primary_position', 20)->nullable();
            $table->string('secondary_position', 20)->nullable();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['id_primary_position']);
            $table->dropForeign(['id_secondary_position']);
            $table->dropIndex(['id_primary_position']);
            $table->dropIndex(['id_secondary_position']);
            $table->dropColumn(['id_primary_position', 'id_secondary_position']);
        });
    }
};
