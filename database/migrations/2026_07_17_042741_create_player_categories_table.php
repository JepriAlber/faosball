<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_categories', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_player_category')->primary();

            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            | TIDAK nullable. Tidak ada konsep "kategori system" -- setiap kategori
            | wajib milik satu academy, sama seperti player_types.
            */
            $table->uuid('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Category Information
            |--------------------------------------------------------------------------
            */
            $table->string('name', 50);

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Rentang Umur (inklusif)
            |--------------------------------------------------------------------------
            | Dipakai HANYA untuk MENYARANKAN kategori saat menambah player.
            | Bukan aturan yang memaksa: pemain berbakat boleh "main naik kelas"
            | ke kategori yang umurnya di luar rentang ini. Lihat Bagian 4.2.
            |
            | Academy bebas menentukan rentangnya sendiri lewat form.
            */
            $table->unsignedTinyInteger('min_age');
            $table->unsignedTinyInteger('max_age');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | Kategori nonaktif tidak muncul di dropdown Player baru, tapi player
            | lama yang sudah memakainya tetap utuh.
            */
            $table->boolean('status')->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */
            $table->index('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Unique
            |--------------------------------------------------------------------------
            | Dua academy BOLEH punya "U-12" masing-masing (dengan rentang umur
            | yang boleh berbeda pula). Satu academy TIDAK BOLEH punya dua "U-12".
            */
            $table->unique(['id_academy', 'name'], 'player_categories_academy_name_unique');

            /*
            |--------------------------------------------------------------------------
            | Foreign Key
            |--------------------------------------------------------------------------
            */
            $table->foreign('id_academy')
                ->references('id_academy')
                ->on('academies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_categories');
    }
};
