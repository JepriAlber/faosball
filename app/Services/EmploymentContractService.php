<?php

namespace App\Services;

use App\Models\EmploymentContract;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class EmploymentContractService
{
    /**
     * Kunci baris staff sebagai mutex per-staff -- mencegah race condition
     * saat 2 request nyaris bersamaan mencoba membuat/meng-activate
     * Contract untuk staff yang sama. WAJIB dipanggil di awal setiap
     * method yang menegakkan rule "1 Active + 1 Draft per staff"
     * (lihat issue12.md Bagian 2d).
     */
    protected function lockStaff(Staff $staff): void
    {
        Staff::withoutGlobalScopes()->whereKey($staff->id_staff)->lockForUpdate()->first();
    }

    /**
     * Pola sama StaffService::generateStaffCode() -- kode kontrak
     * ke-N milik staff ini, format {staff_code}-C{n}.
     */
    protected function generateContractCode(Staff $staff): string
    {
        $sequence = EmploymentContract::withoutGlobalScopes()
            ->where('id_staff', $staff->id_staff)
            ->count() + 1;

        return $staff->staff_code . '-C' . $sequence;
    }

    /**
     * Dipanggil HANYA oleh StaffService::create(), dalam transaksi yang
     * sama dengan pembuatan Staff -- kontrak PERTAMA langsung Active,
     * TIDAK lewat Draft (staff baru dianggap langsung mulai bekerja).
     */
    public function createFirstContract(Staff $staff, array $data): EmploymentContract
    {
        return EmploymentContract::create([
            'id_academy' => $staff->id_academy,
            'id_staff' => $staff->id_staff,
            'id_employment_type' => $data['id_employment_type'],
            'id_staff_position' => $data['id_staff_position'],
            'contract_code' => $this->generateContractCode($staff),
            'start_date' => $data['join_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'salary' => $data['salary'] ?? null,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Kontrak susulan (renewal/promosi) -- SELALU mulai dari Draft
     * (Rule 2 & Aturan Emas). Admin meng-activate belakangan.
     */
    public function createDraft(Staff $staff, array $data): EmploymentContract
    {
        return DB::transaction(function () use ($staff, $data) {

            $this->lockStaff($staff);

            if (EmploymentContract::where('id_staff', $staff->id_staff)->where('status', 'draft')->exists()) {
                throw new \Exception(__('Staff ini sudah punya kontrak Draft, selesaikan atau batalkan dulu sebelum membuat yang baru.'));
            }

            return EmploymentContract::create([
                'id_academy' => $staff->id_academy,
                'id_staff' => $staff->id_staff,
                'id_employment_type' => $data['id_employment_type'],
                'id_staff_position' => $data['id_staff_position'],
                'contract_code' => $this->generateContractCode($staff),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Update Draft -- rule 4: HANYA boleh kalau statusnya masih Draft.
     * id_staff/id_academy/contract_code/status sengaja TIDAK ikut diubah.
     */
    public function updateDraft(EmploymentContract $contract, array $data): EmploymentContract
    {
        return DB::transaction(function () use ($contract, $data) {

            if ($contract->status !== 'draft') {
                throw new \Exception(__('Kontrak yang sudah tidak berstatus Draft tidak dapat diubah datanya.'));
            }

            $contract->update([
                'id_employment_type' => $data['id_employment_type'],
                'id_staff_position' => $data['id_staff_position'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                // array_key_exists, BUKAN ?? -- kalau field salary tidak ikut
                // dikirim (disembunyikan karena user tidak punya salary.view),
                // pertahankan nilai lama. Beda dari field dikirim KOSONG
                // (memang sengaja dikosongkan user yang berwenang).
                'salary' => array_key_exists('salary', $data) ? $data['salary'] : $contract->salary,
                'notes' => $data['notes'] ?? null,
            ]);

            return $contract;
        });
    }

    /**
     * Draft -> Active. Kalau staff sudah punya Contract Active lain,
     * Contract itu otomatis ditutup jadi Completed DALAM transaksi yang
     * sama (issue12.md Bagian 2c) -- datanya sendiri tidak diubah, cuma
     * status (Rule 4).
     */
    public function activate(EmploymentContract $contract): EmploymentContract
    {
        return DB::transaction(function () use ($contract) {

            if ($contract->status !== 'draft') {
                throw new \Exception(__('Hanya kontrak berstatus Draft yang dapat diaktifkan.'));
            }

            $this->lockStaff($contract->staff);

            EmploymentContract::where('id_staff', $contract->id_staff)
                ->where('status', 'active')
                ->update(['status' => 'completed']);

            $contract->update(['status' => 'active']);

            return $contract;
        });
    }

    public function complete(EmploymentContract $contract): EmploymentContract
    {
        if ($contract->status !== 'active') {
            throw new \Exception(__('Hanya kontrak berstatus Active yang dapat diselesaikan.'));
        }

        $contract->update(['status' => 'completed']);

        return $contract;
    }

    public function terminate(EmploymentContract $contract): EmploymentContract
    {
        if ($contract->status !== 'active') {
            throw new \Exception(__('Hanya kontrak berstatus Active yang dapat dihentikan.'));
        }

        $contract->update(['status' => 'terminated']);

        return $contract;
    }

    public function cancel(EmploymentContract $contract): EmploymentContract
    {
        if ($contract->status !== 'draft') {
            throw new \Exception(__('Hanya kontrak berstatus Draft yang dapat dibatalkan.'));
        }

        $contract->update(['status' => 'cancelled']);

        return $contract;
    }
}
