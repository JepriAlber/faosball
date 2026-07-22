<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_types', function (Blueprint $table) {

            $table->uuid('id_employment_type')->primary();

            $table->uuid('id_academy');

            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');
            $table->unique(['id_academy', 'name'], 'employment_types_academy_name_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_types');
    }
};
