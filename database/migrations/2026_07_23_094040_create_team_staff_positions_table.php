<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_staff_positions', function (Blueprint $table) {

            $table->uuid('id_team_staff_position')->primary();
            $table->uuid('id_academy');

            $table->string('code', 20); // "HC" -- WAJIB, dipakai guard Head Coach (Bagian 2e)
            $table->string('name', 100); // "Head Coach"
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');
            $table->unique(['id_academy', 'name'], 'team_staff_positions_academy_name_unique');
            $table->unique(['id_academy', 'code'], 'team_staff_positions_academy_code_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_staff_positions');
    }
};
