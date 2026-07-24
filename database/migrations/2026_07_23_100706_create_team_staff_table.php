<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_staff', function (Blueprint $table) {

            $table->uuid('id_team_staff')->primary();
            $table->uuid('id_academy');

            $table->uuid('id_team');
            $table->uuid('id_staff');
            $table->uuid('id_team_staff_position');

            $table->date('join_date');
            $table->date('leave_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('id_academy');
            $table->index('id_team');
            $table->index('id_staff');
            $table->index(['id_team', 'leave_date'], 'team_staff_team_leave_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_team')->references('id_team')->on('teams')->cascadeOnDelete();
            $table->foreign('id_staff')->references('id_staff')->on('staff')->cascadeOnDelete();
            $table->foreign('id_team_staff_position')->references('id_team_staff_position')->on('team_staff_positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_staff');
    }
};
