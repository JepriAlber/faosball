<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {

            $table->uuid('id_staff')->primary();

            /*
            |--------------------------------------------------------------------------
            | Tenant & Account
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_academy');
            $table->uuid('id_user')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Klasifikasi (WAJIB -- tabel baru, tidak ada data lama untuk
            | dikhawatirkan seperti players.id_player_type yang nullable).
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_employment_type');
            $table->uuid('id_staff_position');

            /*
            |--------------------------------------------------------------------------
            | Staff Identity
            |--------------------------------------------------------------------------
            */
            $table->string('staff_code', 30)->unique();
            $table->string('photo')->nullable();
            $table->string('full_name');
            $table->string('nickname', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Biodata
            |--------------------------------------------------------------------------
            */
            $table->enum('gender', ['male', 'female']);
            $table->string('birth_place', 100);
            $table->date('birth_date');
            $table->string('nationality', 50)->default('Indonesia');
            $table->enum('religion', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya'])->nullable();
            $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Info
            |--------------------------------------------------------------------------
            | `email` di sini KONTAK/informasi, TERPISAH dari users.email (akun
            | login) -- staff boleh punya email kontak tanpa punya akun sama
            | sekali, dan akun login (kalau ada) boleh pakai email berbeda.
            */
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Employment Info
            |--------------------------------------------------------------------------
            */
            $table->date('join_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'resigned'])->default('active');

            /*
            |--------------------------------------------------------------------------
            | Additional
            |--------------------------------------------------------------------------
            */
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('id_academy');
            $table->index('id_user');
            $table->index('id_employment_type');
            $table->index('id_staff_position');
            $table->index(['id_academy', 'status'], 'staff_academy_status_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();

            // restrictOnDelete() (bukan nullOnDelete()) -- id_employment_type &
            // id_staff_position WAJIB terisi (NOT NULL), jadi FK tidak mungkin
            // di-set NULL saat baris induknya dihapus. Ini jaring pengaman
            // DATABASE, guard "ramah" (pesan __()) ada di Tahap 9.
            $table->foreign('id_employment_type')->references('id_employment_type')->on('employment_types')->restrictOnDelete();
            $table->foreign('id_staff_position')->references('id_staff_position')->on('staff_positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
