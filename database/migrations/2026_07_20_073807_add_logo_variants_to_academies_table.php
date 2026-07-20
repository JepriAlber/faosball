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
            | Logo Variants
            |--------------------------------------------------------------------------
            | Nullable -- academy boleh belum upload logo, atau upload SVG (tidak bisa
            | diproses Intervention Image / GD, lihat issue3.md Bagian 4.2), dalam
            | kasus itu kedua kolom tetap null dan sistem fallback ke logo statis.
            |
            | logo_sidebar : hasil scaleDown bounding box 245x65 (jaga aspect
            |                ratio, TANPA crop), dipakai di slot "logo lebar"
            |                -- sidebar (expanded) + header mobile.
            | logo_favicon : hasil cover (crop ke persegi), dipakai di slot "ikon
            |                persegi" -- sidebar (collapsed) + <link rel="icon">.
            */
            $table->string('logo_sidebar')
                ->nullable()
                ->after('logo');

            $table->string('logo_favicon')
                ->nullable()
                ->after('logo_sidebar');
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn(['logo_sidebar', 'logo_favicon']);
        });
    }
};
