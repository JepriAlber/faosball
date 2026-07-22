<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_positions', function (Blueprint $table) {

            $table->uuid('id_staff_position')->primary();

            $table->uuid('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Default Role
            |--------------------------------------------------------------------------
            | unsignedBigInteger, BUKAN uuid -- roles.id adalah PK bawaan
            | spatie/laravel-permission (bigint auto-increment), beda dari seluruh
            | FK lain di app ini yang uuid. Nullable: posisi boleh belum punya
            | default role, admin isi manual belakangan lewat form edit.
            */
            $table->unsignedBigInteger('role_id')->nullable();

            $table->string('code', 20);
            $table->string('name', 100);
            $table->boolean('is_coach')->default(false);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');
            $table->index('role_id');
            $table->unique(['id_academy', 'name'], 'staff_positions_academy_name_unique');
            $table->unique(['id_academy', 'code'], 'staff_positions_academy_code_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_positions');
    }
};
