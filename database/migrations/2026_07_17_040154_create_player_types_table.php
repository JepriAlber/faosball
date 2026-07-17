<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_types', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_player_type')->primary();

            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            | TIDAK nullable. Berbeda dengan roles.id_academy, tidak ada konsep
            | "type system" -- setiap type wajib milik satu academy.
            */
            $table->uuid('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Type Information
            |--------------------------------------------------------------------------
            */
            $table->string('name', 100);

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Billing
            |--------------------------------------------------------------------------
            | Penanda apakah player dengan type ini ditagih iuran/SPP.
            | Module Payment WAJIB memfilter lewat kolom ini, bukan lewat nama type.
            */
            $table->boolean('is_billable')->default(true);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | Type nonaktif tidak muncul di dropdown Player baru, tapi player lama
            | yang sudah memakainya tetap utuh. Ini jalan keluar untuk type yang
            | sudah tidak dipakai lagi tapi tidak bisa dihapus karena masih
            | dipegang player lama.
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
            | Dua academy BOLEH punya type bernama sama. Satu academy TIDAK BOLEH
            | punya dua type dengan nama sama. Sama persis dengan pola
            | roles_academy_name_guard_unique.
            */
            $table->unique(['id_academy', 'name'], 'player_types_academy_name_unique');

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
        Schema::dropIfExists('player_types');
    }
};
