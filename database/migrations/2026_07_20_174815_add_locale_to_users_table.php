<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Locale
            |--------------------------------------------------------------------------
            | Preferensi bahasa user ("id"/"en"). Nullable -- user lama belum
            | punya nilainya, fallback ke session lalu ke config('app.locale')
            | lewat Middleware SetLocale (Tahap 4). Tidak ada backfill migration.
            */
            $table->string('locale', 5)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
