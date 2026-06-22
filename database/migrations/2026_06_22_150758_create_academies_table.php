<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academies', function (Blueprint $table) {
            $table->uuid('id_academy')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('tagline')->nullable();
            $table->boolean('status')->default(true);
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academies');
    }
};
