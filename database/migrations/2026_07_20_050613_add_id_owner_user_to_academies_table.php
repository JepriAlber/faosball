<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Owner Account
            |--------------------------------------------------------------------------
            | Nullable -- academy boleh ada tanpa akun Owner (dibuat belakangan lewat
            | AcademyAccountController). Arah relasi SENGAJA dari academies ke users,
            | bukan sebaliknya, supaya "akun Owner mana yang aktif untuk academy ini"
            | selalu jelas lewat satu FK langsung -- pola yang sama dengan
            | players.id_user. Lihat issue2.md Bagian 2a.
            */
            $table->uuid('id_owner_user')
                ->nullable()
                ->after('id_academy');

            $table->index('id_owner_user');

            $table->foreign('id_owner_user')
                ->references('id_user')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropForeign(['id_owner_user']);
            $table->dropColumn('id_owner_user');
        });
    }
};
