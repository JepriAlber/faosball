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
            | Subscription
            |--------------------------------------------------------------------------
            | Nullable di level DATABASE karena academy yang sudah ada (termasuk
            | "FAOS Academy" dari RolePermissionSeeder) belum punya data ini saat
            | migration ini dijalankan. WAJIB diisi lewat Form Request untuk academy
            | yang dibuat/diedit setelahnya -- lihat AcademyFormRequest.
            |
            | subscription_type disimpan sebagai string biasa, BUKAN kolom ENUM
            | MySQL, supaya menambah tipe baru nanti (mis. "lifetime") tidak perlu
            | migration ALTER TABLE ENUM. Nilai yang diperbolehkan divalidasi di
            | Form Request (Rule::in()), bukan dipaksa oleh database.
            */
            $table->string('subscription_type', 20)
                ->nullable()
                ->after('status');

            $table->decimal('subscription_fee', 12, 2)
                ->nullable()
                ->after('subscription_type');

            $table->date('subscription_started_at')
                ->nullable()
                ->after('subscription_fee');

            $table->date('subscription_ends_at')
                ->nullable()
                ->after('subscription_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_type',
                'subscription_fee',
                'subscription_started_at',
                'subscription_ends_at',
            ]);
        });
    }
};
