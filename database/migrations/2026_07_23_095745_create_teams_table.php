<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {

            $table->uuid('id_team')->primary();
            $table->uuid('id_academy');

            $table->uuid('id_season');
            $table->uuid('id_player_category');

            $table->string('code', 20); // "TM001"
            $table->string('name', 100); // "U15 A"
            $table->enum('team_type', ['regular', 'tournament', 'event', 'temporary'])->default('regular');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes(); // "Hapus Team" = archive, lihat issue16.md Bagian 2a

            $table->index('id_academy');
            $table->index('id_season');
            $table->index('id_player_category');
            $table->unique(['id_academy', 'code'], 'teams_academy_code_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();

            // restrictOnDelete -- kolom WAJIB terisi, FK tidak boleh diam-diam
            // NULL (pola sama staff.id_employment_type di issue11.md). Guard
            // Service (SeasonService::delete()/cek players()) jadi lapis
            // pertama sebelum constraint DB ini kena.
            $table->foreign('id_season')->references('id_season')->on('seasons')->restrictOnDelete();
            $table->foreign('id_player_category')->references('id_player_category')->on('player_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
