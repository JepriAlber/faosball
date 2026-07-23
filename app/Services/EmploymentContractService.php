<?php

namespace App\Services;

use App\Models\EmploymentContract;
use App\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmploymentContractService
{
    /**
     * Search cocok ke nama staff ATAU kode kontrak. Filter bulan berakhir
     * (`end_month`, format "YYYY-MM" dari <input type="month">) pakai
     * whereYear()+whereMonth() -- end_date bertipe date, BUKAN string
     * matching (lihat issue14.md Aturan Emas).
     */
    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('contract_code', 'like', "%{$search}%")
                    ->orWhereHas('staff', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($includeStatus && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }

        if (! empty($filters['end_month'])) {

            [$year, $month] = explode('-', $filters['end_month']);

            $query->whereYear('end_date', $year)->whereMonth('end_date', $month);
        }
    }

    /**
     * Daftar kontrak lintas-staff untuk halaman index (issue14.md).
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = EmploymentContract::with(['staff', 'employmentType', 'position', 'academy']);

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'end_date_asc' => $query->orderBy('end_date'),
            'end_date_desc' => $query->orderByDesc('end_date'),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    /**
     * Jumlah kontrak per status (5 nilai), untuk badge di tab halaman
     * index. $includeStatus=false -- hitungan tiap tab tidak boleh ikut
     * kefilter oleh status tab yang sedang aktif. Pola sama
     * EmploymentTypeService::statusCounts().
     */
    public function statusCounts(array $filters = []): array
    {
        $countFor = function (string $status) use ($filters) {

            $query = EmploymentContract::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return [
            'draft' => $countFor('draft'),
            'active' => $countFor('active'),
            'completed' => $countFor('completed'),
            'terminated' => $countFor('terminated'),
            'cancelled' => $countFor('cancelled'),
        ];
    }

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
