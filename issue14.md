# Brief: Halaman Index Employment Contract (Kontrak Kerja)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: `issue9.md`–`issue12.md` (modul Office: Employment Type, Staff Position, Staff, Employment Contract) **wajib sudah selesai & merged** — brief ini murni **menambah** kemampuan baca/cari ke modul Employment Contract yang sudah ada, tidak mengubah struktur data apapun. Baca juga `docs/frontend-standard.md` (khususnya *Tabs Status + Toolbar Filter/Search* dan *Wajib: Filter Academy — khusus Super Admin*), `docs/permission-reference.md`. Modul referensi paling mirip (**contoh paling dekat, banyak kode di brief ini tinggal disalin & disesuaikan**): `app/Http/Controllers/EmploymentTypeController.php` (method `index()`), `app/Services/EmploymentTypeService.php` (method `applyFilters()`/`paginate()`/`statusCounts()`), `resources/views/employment-types/index.blade.php`.
> **Bukan module baru** — ini halaman index tambahan untuk entitas `EmploymentContract` yang modelnya sudah ada (`app/Models/EmploymentContract.php`), tidak ada migration, tidak ada permission baru.
> **Cara pakai brief ini**: Kerjakan Tahap 1 → 8 berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: Tambah halaman `employment-contracts.index` — daftar **seluruh** kontrak kerja lintas-staff dalam 1 academy (atau lintas-academy untuk Super Admin), dengan search (nama staff/kode kontrak), filter status (5 nilai: draft/active/completed/terminated/cancelled), filter **bulan berakhir kontrak** (`end_date`), sort, dan filter Academy (Super Admin). Tujuannya supaya admin HR bisa langsung mencari "staff mana yang kontraknya berakhir bulan Agustus", tanpa harus buka satu-satu halaman detail staff. **Bukan scope**: mengubah alur create/edit/activate/complete/terminate/cancel yang sudah ada di `staff/{staff}/contracts/*` (tetap seperti sekarang, nested di bawah Staff) — halaman index ini **cuma baca**, tidak ada tombol "Buat Kontrak" di sini (lihat Aturan Emas). Tidak ada halaman `show` detail kontrak tersendiri — klik baris cukup melempar ke halaman Staff yang bersangkutan (`staff.show`), yang sudah punya tab "Riwayat Kontrak" lengkap dengan tombol aksi.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Taruh tombol "Buat Kontrak Baru" di halaman index ini | Kontrak **selalu** dibuat dalam konteks 1 staff tertentu (`staff/{staff}/contracts/create`) — halaman index lintas-staff ini tidak tahu staff mana yang dimaksud tanpa tambahan dropdown pilih staff yang justru membuat alur create ganda dan membingungkan. Pembuatan kontrak tetap **hanya** lewat halaman Staff Detail, sama seperti sekarang | [Tahap 4](#tahap-4--view-employment-contractsindexbladephp) |
| Bikin route `employment-contracts.show`/`.edit`/`.destroy` baru | Sudah ada `staff.contracts.edit` untuk edit Draft, dan Contract memang tidak pernah dihapus (Rule 3, `issue12.md`). Baris di index ini cukup link ke `staff.show`, bukan halaman detail baru | [Tahap 3](#tahap-3--route), [Tahap 4](#tahap-4--view-employment-contractsindexbladephp) |
| Bikin permission baru `employment_contract.view` | **Reuse** `staff.view` — daftar kontrak adalah bagian dari data kepegawaian staff, konsisten dengan sub-module Employment Contract yang sudah ada (`staff.update` untuk aksi tulis) dan `docs/permission-reference.md` yang sudah mendokumentasikan pola reuse ini | [Tahap 3](#tahap-3--route), [Tahap 7](#tahap-7--dokumentasi) |
| Filter status di halaman ini pakai 2 tab (Aktif/Nonaktif) seperti Staff/Academy | `EmploymentContract.status` punya **5** nilai nyata (`draft`/`active`/`completed`/`terminated`/`cancelled`) — beda dari status kepegawaian Staff yang cuma diturunkan jadi 2 nilai (Aktif/Nonaktif). Tab di halaman ini WAJIB merepresentasikan 5 status aslinya, pakai label & warna badge **persis sama** dengan yang sudah dipakai di `resources/views/staff/show.blade.php` baris 272–277 (Draft/Active/Completed/Terminated/Cancelled — sengaja tetap istilah Inggris, ikuti presedan yang sudah ada, jangan diterjemahkan sendiri jadi tidak konsisten) | [Tahap 4](#tahap-4--view-employment-contractsindexbladephp) |
| Query filter bulan berakhir pakai `LIKE '2026-08%'` pada kolom `end_date` | `end_date` bertipe `date` — filter bulan yang benar pakai `whereYear()`+`whereMonth()` (atau `whereBetween` rentang awal–akhir bulan), bukan string matching yang rapuh terhadap format tanggal | [Tahap 1](#tahap-1--service-employmentcontractserviceapplyfilters-paginate-statuscounts) |
| Lupa `withoutGlobalScopes()`-kan query count kalau nantinya dipakai lintas-academy | Tidak relevan di brief ini (Super Admin sudah otomatis lolos `AcademyScope` lewat query builder biasa, sama seperti `EmploymentTypeService::paginate()`) — disebut supaya tidak salah tiru dari `generateContractCode()`/`lockStaff()` yang sengaja `withoutGlobalScopes()` untuk keperluan lain (mutex/kode urut), BUKAN pola yang perlu diikuti di sini | [Tahap 1](#tahap-1--service-employmentcontractserviceapplyfilters-paginate-statuscounts) |

---

## 1. Konteks & Tujuan

Modul Employment Contract (`issue12.md`) sekarang hanya bisa diakses lewat halaman Staff Detail (`staff.show`, tab "Riwayat Kontrak") — tidak ada satupun halaman yang menampilkan kontrak **lintas-staff**. Untuk pertanyaan operasional sehari-hari seperti "staff mana saja yang kontraknya berakhir bulan ini/bulan depan", admin HR harus membuka satu-per-satu halaman detail tiap staff dan membaca tab Riwayat Kontrak-nya — tidak scalable begitu jumlah staff bertambah.

Brief ini menambah **1 halaman index baru** (`employment-contracts.index`) yang menampilkan seluruh kontrak dalam bentuk tabel yang bisa dicari, difilter status, dan difilter **bulan berakhirnya**, mengikuti pola Tabs Status + Toolbar Filter/Search yang sudah jadi standar wajib untuk halaman index/list module (lihat `docs/frontend-standard.md`) dan baru saja diterapkan ulang di `employment-types.index`/`staff-positions.index`.

## 2. Cara Kerja Solusi

### 2a. Index ini murni baca — aksi tetap di halaman Staff

Halaman baru ini **tidak** punya tombol create/edit/delete sendiri. Baris tabel menampilkan info kontrak (staff, posisi, jenis, tanggal, status) dan 1 tombol "Lihat" yang melempar ke `staff.show`, tempat semua aksi (buat kontrak baru, edit Draft, aktifkan, selesaikan, hentikan, batalkan) sudah ada dan tetap dipakai apa adanya. Ini konsisten dengan Rule di `issue12.md` bahwa kontrak selalu dikelola dalam konteks 1 staff tertentu.

### 2b. Filter "Bulan Berakhir" pakai `<input type="month">`

Daripada membuat dropdown berisi daftar bulan yang harus di-generate manual (dan cepat basi), filter ini pakai elemen HTML5 native `<input type="month" name="end_month">` — browser modern sudah punya date-picker bulan bawaan, hasilnya string format `YYYY-MM` (mis. `2026-08`), langsung dipakai `EmploymentContractService::applyFilters()` untuk query `whereYear()`/`whereMonth()` pada kolom `end_date`. Admin bisa pilih bulan manapun (lalu/sekarang/akan datang), tidak terbatas ke preset "bulan ini"/"bulan depan" saja.

### 2c. Permission: reuse `staff.view`, bukan permission baru

Melihat daftar kontrak adalah bagian dari melihat data kepegawaian staff — sama persis pertimbangannya dengan kenapa aksi tulis kontrak reuse `staff.update` (`issue12.md` Bagian 0, baris "Buat permission baru `employment_contract.*`"). Siapapun yang boleh melihat halaman Staff (`staff.view`) otomatis boleh melihat halaman index kontrak ini.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `app/Services/EmploymentContractService.php` | ✏️ Tambah `applyFilters()`, `paginate()`, `statusCounts()` | 1 |
| `app/Http/Controllers/EmploymentContractController.php` | ✏️ Inject `AcademyService`, tambah method `index()` | 2 |
| `routes/web.php` | ✏️ Tambah `Route::get('employment-contracts', ...)` di luar prefix nested | 3 |
| `resources/views/employment-contracts/index.blade.php` | 🆕 Baru | 4 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Tambah item menu "Kontrak Kerja" di grup Office, update `$officeRoutes` | 5 |
| `lang/en.json` | ✏️ Entry baru | 6 |
| `tests/Feature/EmploymentContractTest.php` | ✏️ Tambah helper `actingAsOwner()` + 4 test HTTP baru | 7 |
| `docs/permission-reference.md` | ✏️ Update sub-section Employment Contract — tambah baris `staff.view` untuk index | 8 |

---

## Tahap 1 — Service: `EmploymentContractService::applyFilters()`, `paginate()`, `statusCounts()`

`app/Services/EmploymentContractService.php` — tambah 2 import di atas, dan 3 method baru (taruh di bagian paling atas class, sebelum `lockStaff()`):

```php
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

    // ... method lockStaff(), generateContractCode(), createFirstContract(), dst
    // TETAP SAMA, tidak diubah sama sekali.
```

**✅ Cek dulu**

```bash
php -l app/Services/EmploymentContractService.php
php artisan tinker
```

```php
app(\App\Services\EmploymentContractService::class)->paginate(); // tidak error, hasil paginator (boleh kosong)
app(\App\Services\EmploymentContractService::class)->statusCounts(); // array 5 key: draft/active/completed/terminated/cancelled
app(\App\Services\EmploymentContractService::class)->paginate(['end_month' => '2026-08']); // tidak error
```

---

## Tahap 2 — Controller: `EmploymentContractController::index()`

`app/Http/Controllers/EmploymentContractController.php` — tambah import & inject `AcademyService`:

```php
use App\Http\Requests\EmploymentContract\StoreEmploymentContractRequest;
use App\Http\Requests\EmploymentContract\UpdateEmploymentContractRequest;
use App\Models\Academy;
use App\Models\EmploymentContract;
use App\Models\Staff;
use App\Services\AcademyService;
use App\Services\EmploymentContractService;
use App\Services\EmploymentTypeService;
use App\Services\StaffPositionService;
use Illuminate\Http\Request;

class EmploymentContractController extends Controller
{
    protected EmploymentContractService $employmentContractService;
    protected EmploymentTypeService $employmentTypeService;
    protected StaffPositionService $staffPositionService;
    protected AcademyService $academyService;

    public function __construct(
        EmploymentContractService $employmentContractService,
        EmploymentTypeService $employmentTypeService,
        StaffPositionService $staffPositionService,
        AcademyService $academyService
    ) {
        $this->employmentContractService = $employmentContractService;
        $this->employmentTypeService = $employmentTypeService;
        $this->staffPositionService = $staffPositionService;
        $this->academyService = $academyService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'id_academy', 'sort', 'end_month']));

        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('employment-contracts.index', [
            'title' => __('Kontrak Kerja'),
            'breadcrumb' => [
                ['label' => __('Office')],
                ['label' => __('Kontrak Kerja')],
            ],
            'contracts' => $this->employmentContractService->paginate($filters),
            'statusCounts' => $this->employmentContractService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            // Opsi dropdown filter Academy -- cuma dibutuhkan Super Admin,
            // yang melihat kontrak lintas seluruh academy.
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
        ]);
    }

    // ... method create()/store()/edit()/update()/activate()/complete()/
    // terminate()/cancel() TETAP SAMA, tidak diubah sama sekali.
```

**✅ Cek dulu**: `php -l app/Http/Controllers/EmploymentContractController.php` tidak ada error.

---

## Tahap 3 — Route

`routes/web.php` — tambah route baru **sebelum** `Route::prefix('staff/{staff}/contracts')` yang sudah ada, di dalam komentar blok yang sama:

```php
/*
|--------------------------------------------------------------------------
| Employment Contract Management (nested di bawah Staff)
|--------------------------------------------------------------------------
| Index (`employment-contracts.index`) TIDAK nested -- daftar lintas-staff,
| reuse staff.view (bukan staff.update, karena cuma baca, lihat
| issue14.md). Aksi create/edit/activate/dst TETAP nested di bawah
| staff/{staff}/contracts/* seperti sebelumnya.
| TIDAK ADA route destroy -- Contract tidak pernah dihapus (Rule 3).
| Reuse permission staff.view/staff.create/staff.update, BUKAN permission baru.
*/
Route::get('employment-contracts', [EmploymentContractController::class, 'index'])
    ->name('employment-contracts.index')
    ->middleware('permission:staff.view');

Route::prefix('staff/{staff}/contracts')
    ->name('staff.contracts.')
    ->group(function () {

        Route::middleware('permission:staff.update')->group(function () {

            Route::get('/create', [EmploymentContractController::class, 'create'])->name('create');
            Route::post('/', [EmploymentContractController::class, 'store'])->name('store');

            Route::get('/{contract}/edit', [EmploymentContractController::class, 'edit'])->name('edit');
            Route::put('/{contract}', [EmploymentContractController::class, 'update'])->name('update');

            Route::patch('/{contract}/activate', [EmploymentContractController::class, 'activate'])->name('activate');
            Route::patch('/{contract}/complete', [EmploymentContractController::class, 'complete'])->name('complete');
            Route::patch('/{contract}/terminate', [EmploymentContractController::class, 'terminate'])->name('terminate');
            Route::patch('/{contract}/cancel', [EmploymentContractController::class, 'cancel'])->name('cancel');

        });

    });
```

**✅ Cek dulu**

```bash
php artisan route:list --name=employment-contracts
```

Harus muncul 1 baris `GET|HEAD employment-contracts ... employment-contracts.index`, middleware `permission:staff.view`.

---

## Tahap 4 — View `employment-contracts/index.blade.php`

`resources/views/employment-contracts/index.blade.php` — **baru**, ikuti pola persis `resources/views/employment-types/index.blade.php` (Tabs + Toolbar + Table + Card List responsif), dengan penyesuaian kolom & 5 status:

```blade
@extends('layouts.app', ['page' => 'employment-contracts'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Daftar Kontrak Kerja') }}</h3>
                <p class="card-description">{{ __('Seluruh kontrak kerja staff -- cari kontrak yang aktif atau akan berakhir pada bulan tertentu. Untuk membuat/mengubah kontrak, buka halaman detail staff yang bersangkutan.') }}</p>
            </div>
        </div>

        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            @php
                $totalContracts = array_sum($statusCounts);
            @endphp
            <x-table.tabs route="employment-contracts.index" :active="$filters['status'] ?? ''" :tabs="[
                '' => ['label' => __('Semua'), 'count' => $totalContracts],
                'draft' => ['label' => 'Draft', 'count' => $statusCounts['draft']],
                'active' => ['label' => 'Active', 'count' => $statusCounts['active']],
                'completed' => ['label' => 'Completed', 'count' => $statusCounts['completed']],
                'terminated' => ['label' => 'Terminated', 'count' => $statusCounts['terminated']],
                'cancelled' => ['label' => 'Cancelled', 'count' => $statusCounts['cancelled']],
            ]" />
        </div>

        <x-table.toolbar route="employment-contracts.index" :filters="$filters" placeholder="{{ __('Cari nama staff atau kode kontrak...') }}">

            <div class="form-group">
                <label class="form-label">{{ __('Bulan Berakhir Kontrak') }}</label>
                <input type="month" name="end_month" value="{{ $filters['end_month'] ?? '' }}" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Urutkan') }}</label>
                <select name="sort" class="form-select">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('Terbaru') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('Terlama') }}</option>
                    <option value="end_date_asc" @selected(($filters['sort'] ?? '') === 'end_date_asc')>{{ __('Tanggal Berakhir Terdekat') }}</option>
                    <option value="end_date_desc" @selected(($filters['sort'] ?? '') === 'end_date_desc')>{{ __('Tanggal Berakhir Terjauh') }}</option>
                </select>
            </div>

            @if ($isSuperAdmin)
                <div class="form-group">
                    <label class="form-label">{{ __('Academy') }}</label>
                    <select name="id_academy" class="form-select">
                        <option value="">{{ __('Semua Academy') }}</option>
                        @foreach ($academies as $academy)
                            <option value="{{ $academy->id_academy }}" @selected(($filters['id_academy'] ?? '') === $academy->id_academy)>
                                {{ $academy->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

        </x-table.toolbar>

        @php
            $contractStatusBadge = fn ($status) => match ($status) {
                'draft' => ['label' => 'Draft', 'class' => 'badge-secondary'],
                'active' => ['label' => 'Active', 'class' => 'badge-success'],
                'completed' => ['label' => 'Completed', 'class' => 'badge-primary'],
                'terminated' => ['label' => 'Terminated', 'class' => 'badge-danger'],
                'cancelled' => ['label' => 'Cancelled', 'class' => 'badge-secondary'],
            };
        @endphp

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Staff') }}</th>
                        <th class="table-header-cell">{{ __('Posisi & Jenis') }}</th>
                        <th class="table-header-cell">{{ __('Mulai') }}</th>
                        <th class="table-header-cell">{{ __('Berakhir') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($contracts as $contract)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $contract->staff->full_name }}</span>
                                    <span class="table-subtitle">{{ $contract->contract_code }}</span>
                                </div>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $contract->position->name ?? '-' }}</span>
                                <span class="table-subtitle">{{ $contract->employmentType->name ?? '-' }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $contract->start_date?->format('d M Y') }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $contract->end_date?->format('d M Y') ?? __('Tanpa batas') }}</span>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $contract->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                <span class="badge {{ $contractStatusBadge($contract->status)['class'] }}">
                                    {{ $contractStatusBadge($contract->status)['label'] }}
                                </span>
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">
                                    <a href="{{ route('staff.show', $contract->staff) }}" class="btn-icon" title="{{ __('Lihat Staff') }}">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                                            <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                                        </svg>
                                    </a>
                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z" stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada kontrak yang cocok') }}</h4>
                                    <p class="empty-description">{{ __('Coba ubah kata kunci pencarian atau filter yang dipakai.') }}</p>
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <!-- Card List (mobile & tablet) -->
        <div class="table-card-list">
            @forelse ($contracts as $contract)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $contract->staff->full_name }}</span>
                            <span class="table-subtitle">{{ $contract->contract_code }}</span>
                        </div>

                        <span class="badge {{ $contractStatusBadge($contract->status)['class'] }} shrink-0">
                            {{ $contractStatusBadge($contract->status)['label'] }}
                        </span>
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Posisi & Jenis') }}</span>
                            <span class="table-text">{{ $contract->position->name ?? '-' }} &middot; {{ $contract->employmentType->name ?? '-' }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Mulai') }}</span>
                            <span class="table-text">{{ $contract->start_date?->format('d M Y') }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Berakhir') }}</span>
                            <span class="table-text">{{ $contract->end_date?->format('d M Y') ?? __('Tanpa batas') }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <div class="table-card-field">
                                <span class="table-card-label">{{ __('Academy') }}</span>
                                <span class="badge badge-secondary w-fit">{{ $contract->academy->name }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="table-card-actions">
                        <a href="{{ route('staff.show', $contract->staff) }}" class="btn-icon" title="{{ __('Lihat Staff') }}">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                                <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </a>
                    </div>
                </div>
            @empty
                <div class="table-card">
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" class="text-gray-300 dark:text-gray-700 mb-3">
                            <path d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z" stroke="currentColor" stroke-width="2.5" />
                        </svg>
                        <h4 class="empty-title">{{ __('Belum ada kontrak yang cocok') }}</h4>
                        <p class="empty-description">{{ __('Coba ubah kata kunci pencarian atau filter yang dipakai.') }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($contracts->hasPages())
            <div class="table-footer">
                {{ $contracts->links() }}
            </div>
        @endif

    </div>

@endsection
```

Catatan: tombol "Lihat" sengaja pakai `btn-icon` polos (tanpa varian warna `-warning`/`-danger` yang biasa dipakai Edit/Hapus) -- ini aksi netral (buka halaman lain), bukan aksi tulis/hapus, jadi tidak butuh varian warna baru.

**✅ Cek dulu**: buka `/employment-contracts` di browser (login sebagai user dengan `staff.view`) — tabel/card list tampil, tab status berfungsi, filter bulan berakhir & search berfungsi, klik "Lihat Staff" membuka halaman staff yang benar. Cek juga tampilan mobile (resize browser < 1024px) — Card List yang tampil, bukan tabel dengan scroll horizontal.

---

## Tahap 5 — Menu Sidebar

`resources/views/partials/sidebar.blade.php` — update `$officeRoutes` (sekitar baris 229):

```php
$officeRoutes = ['staff.*', 'employment-contracts.*', 'staff-positions.*', 'employment-types.*'];
```

Tambah item menu baru **setelah** blok `{{-- Staff --}}` (sekitar baris 289) dan **sebelum** blok `{{-- Staff Position --}}`:

```blade
{{-- Kontrak Kerja --}}
@can('staff.view')
    <li>
        <a href="{{ route('employment-contracts.index') }}" class="menu-dropdown-item group"
            :class="{{ Route::is('employment-contracts.*') ? 'true' : 'false' }}
                ?
                'menu-dropdown-item-active' :
                'menu-dropdown-item-inactive'">
            {{ __('Kontrak Kerja') }}
        </a>
    </li>
@endcan
```

Urutan submenu akhir jadi: Staff → **Kontrak Kerja** → Staff Position → Employment Type.

**✅ Cek dulu**: login sebagai user dengan `staff.view`, buka sidebar, dropdown "Office" harus menampilkan 4 item (Staff, Kontrak Kerja, Staff Position, Employment Type). Klik "Kontrak Kerja" harus ke `/employment-contracts` dan item itu ter-highlight aktif.

---

## Tahap 6 — Multi-Language

Tambah ke `lang/en.json` (jalankan `php -r "json_decode(file_get_contents('lang/en.json'), true) or die('invalid json');"` setelah edit):

```json
"Kontrak Kerja": "Employment Contract",
"Daftar Kontrak Kerja": "Employment Contract List",
"Seluruh kontrak kerja staff -- cari kontrak yang aktif atau akan berakhir pada bulan tertentu. Untuk membuat/mengubah kontrak, buka halaman detail staff yang bersangkutan.": "All staff employment contracts -- search for contracts that are active or ending in a specific month. To create/edit a contract, open the relevant staff's detail page.",
"Cari nama staff atau kode kontrak...": "Search staff name or contract code...",
"Bulan Berakhir Kontrak": "Contract End Month",
"Tanggal Berakhir Terdekat": "Nearest End Date",
"Tanggal Berakhir Terjauh": "Furthest End Date",
"Posisi & Jenis": "Position & Type",
"Tanpa batas": "No end date",
"Lihat Staff": "View Staff",
"Belum ada kontrak yang cocok": "No matching contracts yet",
"Coba ubah kata kunci pencarian atau filter yang dipakai.": "Try changing your search keyword or the filters used."
```

(`Staff`/`Mulai`/`Berakhir`/`Status`/`Academy`/`Aksi`/`Urutkan`/`Terbaru`/`Terlama`/`Semua Academy`/`Semua` kemungkinan **sudah ada** di `lang/en.json` dari module lain — cek dulu sebelum menambah duplikat key.)

**✅ Cek dulu**: buka `/employment-contracts?locale=en`, pastikan tidak ada teks Bahasa Indonesia yang bocor.

---

## Tahap 7 — Tests

`tests/Feature/EmploymentContractTest.php` — tambah import & helper `actingAsOwner()` (pola identik `StaffViewsSmokeTest::actingAsOwner()`):

```php
use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

// ... di dalam class, sebelum method test yang sudah ada:

protected function actingAsOwner(Academy $academy): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['staff.view', 'staff.create', 'staff.update'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $role = Role::create([
        'id_academy' => $academy->id_academy,
        'name' => 'Owner',
        'guard_name' => 'web',
    ]);

    $role->syncPermissions(Permission::whereIn('name', ['staff.view', 'staff.create', 'staff.update'])->get());

    $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
    $owner->assignRole($role);

    $this->actingAs($owner);

    return $owner;
}
```

Tambah 4 method test baru (di akhir class, sebelum `}` penutup):

```php
public function test_halaman_index_kontrak_bisa_diakses_dan_menampilkan_data(): void
{
    $academy = Academy::factory()->create();
    $prereqs = $this->makePrereqs($academy);
    $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

    $contract = EmploymentContract::factory()->create([
        'id_academy' => $academy->id_academy,
        'id_staff' => $staff->id_staff,
        'id_employment_type' => $prereqs['employmentType']->id_employment_type,
        'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
        'status' => 'active',
    ]);

    $this->actingAsOwner($academy);

    $this->get(route('employment-contracts.index'))
        ->assertOk()
        ->assertSee($staff->full_name)
        ->assertSee($contract->contract_code);
}

public function test_filter_status_di_halaman_index_kontrak(): void
{
    $academy = Academy::factory()->create();
    $prereqs = $this->makePrereqs($academy);
    $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

    $activeContract = EmploymentContract::factory()->create([
        'id_academy' => $academy->id_academy,
        'id_staff' => $staff->id_staff,
        'id_employment_type' => $prereqs['employmentType']->id_employment_type,
        'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
        'status' => 'active',
        'contract_code' => 'ACTIVE-001',
    ]);

    $cancelledContract = EmploymentContract::factory()->create([
        'id_academy' => $academy->id_academy,
        'id_staff' => $staff->id_staff,
        'id_employment_type' => $prereqs['employmentType']->id_employment_type,
        'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
        'status' => 'cancelled',
        'contract_code' => 'CANCELLED-001',
    ]);

    $this->actingAsOwner($academy);

    $response = $this->get(route('employment-contracts.index', ['status' => 'active']));

    $response->assertOk()->assertSee('ACTIVE-001')->assertDontSee('CANCELLED-001');
}

public function test_filter_bulan_berakhir_di_halaman_index_kontrak(): void
{
    $academy = Academy::factory()->create();
    $prereqs = $this->makePrereqs($academy);
    $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

    $endingAugust = EmploymentContract::factory()->create([
        'id_academy' => $academy->id_academy,
        'id_staff' => $staff->id_staff,
        'id_employment_type' => $prereqs['employmentType']->id_employment_type,
        'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
        'end_date' => '2026-08-15',
        'contract_code' => 'AUG-001',
    ]);

    $endingSeptember = EmploymentContract::factory()->create([
        'id_academy' => $academy->id_academy,
        'id_staff' => $staff->id_staff,
        'id_employment_type' => $prereqs['employmentType']->id_employment_type,
        'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
        'end_date' => '2026-09-15',
        'contract_code' => 'SEP-001',
    ]);

    $this->actingAsOwner($academy);

    $response = $this->get(route('employment-contracts.index', ['end_month' => '2026-08']));

    $response->assertOk()->assertSee('AUG-001')->assertDontSee('SEP-001');
}

public function test_user_tanpa_staffview_ditolak_akses_index_kontrak(): void
{
    $academy = Academy::factory()->create();
    $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

    $this->actingAs($user)->get(route('employment-contracts.index'))->assertForbidden();
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=EmploymentContractTest
php artisan test
```

Semua test baru lulus, jumlah pass/fail total tidak berkurang dibanding sebelum brief ini dikerjakan.

---

## Tahap 8 — Dokumentasi

`docs/permission-reference.md` — update sub-section **"Sub-module: Employment Contract (histori kontrak kerja staff)"** (cari heading ini), ganti tabel & catatan jadi:

```markdown
### Sub-module: Employment Contract (histori kontrak kerja staff)

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `staff.view` | Lihat daftar kontrak lintas-staff, cari kontrak yang akan berakhir bulan tertentu | `employment-contracts.index` (route middleware) |
| `staff.update` | Buat/edit/aktifkan/selesaikan/hentikan/batalkan kontrak | `staff.contracts.*` (route middleware) |

Catatan:
- **Reuse** permission `staff.view`/`staff.update` — bukan permission baru `employment_contract.*` (pola sama Staff Account yang reuse `user.create`/`user.update`).
- `employment-contracts.index` (`issue14.md`) murni halaman baca lintas-staff — tidak ada tombol buat/edit di situ, aksi tulis tetap lewat `staff.contracts.*` dalam konteks 1 staff.
- Tidak ada permission/route untuk hapus kontrak — Contract adalah histori permanen (Rule 3), tidak pernah dihapus lewat UI maupun API.
```

**✅ Cek dulu**: baca ulang bagian yang diedit, pastikan tabel permission mencerminkan route middleware yang benar-benar ada di `routes/web.php` (bandingkan langsung dengan `php artisan route:list --name=employment-contracts,staff.contracts`).

---

## Ringkasan Alur Akhir

```text
Sidebar > Office > Kontrak Kerja (staff.view)
│
GET /employment-contracts
│
├── EmploymentContractController::index()
│   ├── filters: search, status, id_academy, sort, end_month
│   └── EmploymentContractService::paginate()/statusCounts()
│
└── View: Tabs (5 status) + Toolbar (search/bulan berakhir/sort/academy) + Table/Card List
        │
        └── klik "Lihat Staff" -> staff.show (aksi buat/edit/aktifkan/dst tetap di sini, TIDAK berubah)
```
