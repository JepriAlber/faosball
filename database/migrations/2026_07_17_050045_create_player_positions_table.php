<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_positions', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_player_position')->primary();

            /*
            |--------------------------------------------------------------------------
            | TIDAK ADA id_academy -- INI DISENGAJA
            |--------------------------------------------------------------------------
            | Master posisi bersifat GLOBAL: satu daftar dipakai bersama seluruh
            | academy, persis seperti tabel `permissions`. Berbeda dengan
            | player_types & player_categories yang justru punya id_academy.
            |
            | Kalau kamu tergoda menambahkan id_academy di sini, baca dulu
            | Bagian 4.1 di issue3.md.
            */

            /*
            |--------------------------------------------------------------------------
            | Position Information
            |--------------------------------------------------------------------------
            */

            // Singkatan standar sepak bola: GK, CB, LB, CM, ST, dst.
            $table->string('code', 10)->unique();

            $table->string('name', 50)->unique();

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Grouping
            |--------------------------------------------------------------------------
            | Kelompok posisi: Goalkeeper / Defender / Midfielder / Forward.
            | Dipakai sebagai <optgroup> di dropdown form Player.
            |
            | Namanya "position_group", BUKAN "group" -- GROUP adalah reserved
            | word SQL. Laravel memang meng-escape-nya otomatis, tapi begitu ada
            | yang menulis raw query / orderByRaw, kolom bernama `group` langsung
            | jadi sumber error yang membingungkan. Tidak sepadan.
            */
            $table->string('position_group', 50);

            /*
            |--------------------------------------------------------------------------
            | Sort Order
            |--------------------------------------------------------------------------
            | WAJIB ada. Urutan alfabetis menghasilkan:
            |   Defender, Forward, Goalkeeper, Midfielder
            | Padahal urutan sepak bola yang benar:
            |   Goalkeeper -> Defender -> Midfielder -> Forward
            */
            $table->unsignedSmallInteger('sort_order')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | Posisi nonaktif tidak muncul di dropdown Player baru, tapi player
            | lama yang memakainya tetap utuh.
            */
            $table->boolean('status')->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            | Dipakai untuk pengurutan & pengelompokan dropdown.
            */
            $table->index(['position_group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_positions');
    }
};
