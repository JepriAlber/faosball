<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('id_academy')->nullable()->after('id');

            $table->foreign('id_academy')
                ->references('id_academy')
                ->on('academies')
                ->cascadeOnDelete();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_name_unique');
            $table->unique(['id_academy', 'name', 'guard_name'], 'roles_academy_name_guard_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_academy_name_guard_unique');
            $table->dropForeign(['id_academy']);
            $table->dropColumn('id_academy');
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });
    }
};
