<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Backfill -- 1 employment_contracts per baris staff yang sudah ada.
        //    withoutGlobalScopes tidak relevan di sini (query builder DB:: mentah,
        //    bukan Eloquent) -- sengaja pakai DB:: supaya migration tidak bergantung
        //    ke App\Models\Staff yang strukturnya bisa berubah di masa depan.
        DB::table('staff')->orderBy('id_staff')->chunkById(100, function ($rows) {

            foreach ($rows as $row) {

                DB::table('employment_contracts')->insert([
                    'id_employment_contract' => (string) Str::uuid(),
                    'id_academy' => $row->id_academy,
                    'id_staff' => $row->id_staff,
                    'id_employment_type' => $row->id_employment_type,
                    'id_staff_position' => $row->id_staff_position,
                    'contract_code' => $row->staff_code . '-C1',
                    'start_date' => $row->join_date,
                    'end_date' => $row->end_date,
                    'salary' => $row->salary,
                    // Pemetaan status lama -> status Contract:
                    // active   -> active     (masih berjalan)
                    // resigned -> terminated (berhenti sebelum "wajar")
                    // inactive -> completed  (paling netral untuk kondisi non-aktif lainnya)
                    'status' => match ($row->status) {
                        'active' => 'active',
                        'resigned' => 'terminated',
                        default => 'completed',
                    },
                    'notes' => null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }, 'id_staff');

        // 2. Drop FK + index + kolom lama dari staff.
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['id_employment_type']);
            $table->dropForeign(['id_staff_position']);
            $table->dropIndex(['id_employment_type']);
            $table->dropIndex(['id_staff_position']);
            $table->dropIndex('staff_academy_status_index');

            $table->dropColumn([
                'id_employment_type',
                'id_staff_position',
                'join_date',
                'end_date',
                'salary',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        // Reversal best-effort -- kolom dikembalikan NULLABLE (tidak bisa
        // menjamin NOT NULL lagi seperti semula tanpa tahu urutan asli),
        // lalu diisi ulang dari employment_contracts (ambil Contract terbaru
        // per staff sebagai pendekatan "nilai saat ini").
        Schema::table('staff', function (Blueprint $table) {
            $table->uuid('id_employment_type')->nullable()->after('id_user');
            $table->uuid('id_staff_position')->nullable()->after('id_employment_type');
            $table->date('join_date')->nullable()->after('postal_code');
            $table->date('end_date')->nullable()->after('join_date');
            $table->decimal('salary', 12, 2)->nullable()->after('end_date');
            $table->enum('status', ['active', 'inactive', 'resigned'])->default('active')->after('salary');
        });

        $latestPerStaff = DB::table('employment_contracts')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('id_staff')
            ->map(fn ($rows) => $rows->first());

        foreach ($latestPerStaff as $idStaff => $contract) {
            DB::table('staff')->where('id_staff', $idStaff)->update([
                'id_employment_type' => $contract->id_employment_type,
                'id_staff_position' => $contract->id_staff_position,
                'join_date' => $contract->start_date,
                'end_date' => $contract->end_date,
                'salary' => $contract->salary,
                'status' => match ($contract->status) {
                    'active' => 'active',
                    'terminated' => 'resigned',
                    default => 'inactive',
                },
            ]);
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->uuid('id_employment_type')->nullable(false)->change();
            $table->uuid('id_staff_position')->nullable(false)->change();
            $table->foreign('id_employment_type')->references('id_employment_type')->on('employment_types')->restrictOnDelete();
            $table->foreign('id_staff_position')->references('id_staff_position')->on('staff_positions')->restrictOnDelete();
        });
    }
};
