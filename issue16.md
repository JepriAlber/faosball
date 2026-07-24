# Brief: Modul Team (Team, Team Player, Team Staff) + Season & Team Staff Position

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Module Player (`app/Models/Player.php`, `PlayerCategory`), module Office/Staff (`app/Models/Staff.php`, `issue9.md`–`issue14.md`) **wajib sudah ada**. Baca juga `docs/architecture.md`, `docs/multi-tenancy.md`, `docs/authorization.md`, `docs/frontend-standard.md` (*Tabs Status + Toolbar Filter/Search*, *Tabel Responsif*, *Urutan & Pengelompokan Field Form*), `docs/permission-reference.md`. Modul referensi paling mirip: `app/Models/PlayerCategory.php`/`PlayerCategoryService`/`PlayerCategoryController` (master data sederhana), `app/Services/StaffPositionService.php` (master data dengan `code`+`name`+seed default), `app/Services/EmploymentContractService.php` (sub-resource dengan row-lock guard business rule, `issue12.md`), `resources/views/players/show.blade.php` (halaman detail dengan tab Alpine).
> **Cakupan besar** — brief ini membangun **5 entity baru sekaligus**: `Season`, `TeamStaffPosition` (master data pendukung), `Team`, `TeamPlayer`, `TeamStaff` (entity inti). **Permission `team.view`/`team.create`/`team.update`/`team.delete` SUDAH ADA** di `RolePermissionSeeder`/`config('faos.role_templates')` sejak awal (placeholder menunggu module ini dibangun, lihat `docs/permission-reference.md` bagian *Permission Belum Dipakai Module Manapun*) — brief ini **tidak perlu** menambah permission `team.*` baru, cukup menggerbang route dengan permission yang sudah ada. `season.*` dan `team_staff_position.*` **permission baru**, mengikuti pola `player_category.*`/`staff_position.*`.
> **Cara pakai brief ini**: Kerjakan Tahap 1 → 19 berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus. Tahap 1-3 (Season & Team Staff Position) adalah fondasi master data yang dipakai Team; Tahap 4-8 membangun `Team` sendiri; Tahap 9-14 membangun `TeamPlayer`/`TeamStaff` (roster).
> **Scope**: CRUD `Team` (dengan `Season`+`Player Category` sebagai klasifikasi), assign/keluarkan Player ke/dari Team (dengan nomor punggung & captain), assign/keluarkan Staff ke/dari Team (dengan Team Staff Position, mis. Head Coach). **Bukan scope**: modul Training/Attendance/Evaluation (belum ada, disebut di domain model cuma untuk konteks masa depan), fitur "pindah tim" otomatis pertengahan musim, laporan statistik tim, jadwal pertandingan.

---

## Progress Implementasi

> Dicentang begitu tahap selesai dikerjakan **dan** lolos blok ✅ Cek dulu masing-masing. Update checklist ini tiap kali sebuah tahap selesai, supaya siapapun yang melanjutkan brief ini (termasuk sesi/agent lain) tahu persis titik berhentinya tanpa perlu re-check semua dari awal.

- [x] Tahap 1 — Migration `seasons` & `team_staff_positions`
- [x] Tahap 2 — Season: Model + Factory + Service + Request + Controller + Routes + Views
- [x] Tahap 3 — TeamStaffPosition: Model + Factory + Service + Request + Controller + Routes + Views + config templates + hook `AcademyManagementService`
- [x] Tahap 4 — Migration `teams`
- [x] Tahap 5 — Model `Team` + Factory
- [x] Tahap 6 — `TeamService` (lint OK; verifikasi fungsional penuh menyusul setelah Tahap 10/11, karena `paginate()`/`delete()` bergantung ke `TeamPlayer`/`TeamStaff`)
- [x] Tahap 7 — Team: Request + Controller + Routes (route:list terkonfirmasi 7 baris, semua permission:team.*)
- [x] Tahap 8 — Views `teams/index.blade.php`, `create.blade.php`, `edit.blade.php` (lint OK; verifikasi HTTP penuh menyusul setelah Tahap 10, karena index butuh `TeamPlayer`/`TeamStaff` utk `withCount`)
- [x] Tahap 9 — Migration `team_players` & `team_staff`
- [x] Tahap 10 — Model `TeamPlayer`/`TeamStaff` + Factory + Relasi Balik
- [x] Tahap 11 — `TeamPlayerService` (verifikasi tinker: nomor punggung unik ditolak, auto-lepas captain lama, reuse nomor setelah leave — semua lolos)
- [x] Tahap 12 — `TeamStaffService` (lint OK; verifikasi tinker: Head Coach baru otomatis mengeluarkan Head Coach lama, leave() menolak keanggotaan yang sudah tidak aktif -- semua lolos)
- [x] Tahap 13 — TeamPlayer/TeamStaff: Requests + Controllers + Routes (route:list terkonfirmasi 5 baris teams.players.*/teams.staff.*, semua permission:team.update, tanpa DELETE)
- [x] Tahap 14 — View `teams/show.blade.php` (blade compile OK; verifikasi HTTP penuh di Tahap 18 test `test_halaman_index_dan_detail_team_bisa_diakses`)
- [x] Tahap 15 — Menu Sidebar (blade compile OK; verifikasi visual login Owner menyusul di Tahap 18 lewat test HTTP)
- [x] Tahap 16 — Permission Seeder & Role Template (permission `season.*`/`team_staff_position.*` ditambah ke `RolePermissionSeeder`/`config('faos.role_templates')`/`PermissionPresenter`; verifikasi count 4+4 lolos. Catatan: `db:seed --class=RolePermissionSeeder` penuh sengaja TIDAK dijalankan di DB dev karena `RoleService::createDefaultRoles()` pakai `Role::create()` bukan `firstOrCreate` -- re-run akan duplikat role Owner utk academy yang sudah ada, bug pra-eksisting di luar scope; permission baru sudah dibuat manual & terverifikasi via `Permission::firstOrCreate` idempotent)
- [x] Tahap 17 — Multi-Language (lang/en.json valid JSON, tanpa duplikat key; dicek lengkap terhadap SEMUA string `__()` yang benar-benar dipakai di file Season/TeamStaffPosition/Team/TeamPlayer/TeamStaff -- termasuk string di view index/create/edit yang tidak disebut eksplisit di draf Tahap 17, bukan cuma list contoh di brief)
- [x] Tahap 18 — Tests (`SeasonTest` 5 test, `TeamStaffPositionTest` 5 test, `TeamTest` 7 test -- semua lulus; full suite: 174 test, 167 passed, 5 failure + 2 error baseline Breeze bawaan tidak bertambah)
- [x] Tahap 19 — Dokumentasi (`docs/permission-reference.md`: baris Team dihapus dari tabel "belum dipakai", 3 section baru ditambah + TOC, Summary diupdate; `route:list` teams/seasons/team-staff-positions cross-check cocok dengan tabel permission)

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Bikin permission baru `team.*` | **Sudah ada** di `RolePermissionSeeder`/`config('faos.role_templates')` sejak awal, tinggal dipakai (`Owner`: penuh, `Coach`/`Staff`: `team.view`). Cukup gerbang route pakai `permission:team.*` yang sudah ada, JANGAN duplikat | [Tahap 7](#tahap-7--team-request--controller--routes), [Tahap 19](#tahap-19--dokumentasi) |
| Hard-delete `Team` (pola `EmploymentType::delete()`) | `Team` **wajib** pakai `SoftDeletes` (pola sama `Staff`/`Player`) — kalau di-hard-delete padahal masih ada histori `TeamPlayer`/`TeamStaff` yang menunjuk ke situ, histori "siapa pernah main di tim ini" bisa hilang/orphan. "Hapus Team" = archive, bukan musnah permanen | [Tahap 5](#tahap-5--model-team--factory) |
| Hapus baris `TeamPlayer`/`TeamStaff` saat Player/Staff "dikeluarkan" dari Team | Sama seperti Contract tidak pernah dihapus (`issue12.md` Rule 3) — keanggotaan tim adalah **histori**. "Keluarkan dari Tim" = isi `leave_date`, BUKAN `DELETE` baris. Jersey number & captain jadi tidak relevan begitu `leave_date` terisi, tapi datanya tetap ada untuk laporan "siapa saja pernah main di tim ini" | [Tahap 11](#tahap-11--teamplayerservice), [Tahap 12](#tahap-12--teamstaffservice) |
| Simpan kolom `status` terpisah di `TeamPlayer`/`TeamStaff` | Redundan dengan `leave_date` — kalau `leave_date` terisi berarti otomatis "Inactive", kalau `null` berarti "Active". Menyimpan keduanya berisiko drift (lihat diskusi kenapa `staff.status` juga dihapus & diturunkan dari `activeContract`, `issue12.md` Bagian 2a). "Status" di UI cukup **dihitung** dari `leave_date IS NULL`, tidak disimpan | [Tahap 9](#tahap-9--migration-team_players--team_staff), [Tahap 11](#tahap-11--teamplayerservice) |
| Enforce "nomor punggung unik", "1 captain", "1 Head Coach aktif" pakai unique index database biasa | MySQL tidak punya *partial/filtered unique index* (`UNIQUE WHERE leave_date IS NULL`) — constraint itu tidak bisa dibuat bersih di MySQL (harus tetap bisa reuse nomor punggung/role yang sama setelah pemain/staff lama keluar). Guard ditegakkan di **Service layer** dalam `DB::transaction()` + `lockForUpdate()` pada baris `teams` (mutex per-team), pola identik `EmploymentContractService::lockStaff()` | [Tahap 11](#tahap-11--teamplayerservice), [Tahap 12](#tahap-12--teamstaffservice) |
| Bikin `createDefaultSeasons()` otomatis tiap academy baru | Beda dari `PlayerCategory`/`EmploymentType`/`StaffPosition` yang punya default universal stabil (U-15, Permanent, dst) — musim ("2026") berubah tiap tahun, tidak ada "musim default" yang masuk akal ditebak sistem. Owner **wajib** membuat Season pertamanya sendiri secara manual | [Tahap 2](#tahap-2--season-model--factory--service--request--controller--routes--views) |
| Reuse `StaffPosition` untuk peran staff di tim ("Head Coach" dst) | Dua dimensi yang berbeda: `StaffPosition` adalah jabatan **kepegawaian** staff di Academy (dipakai `EmploymentContract`, terkait gaji), sedangkan `TeamStaffPosition` adalah peran **fungsional** staff di **1 tim tertentu** — 1 staff bisa jadi Head Coach di 1 tim tapi Assistant Coach di tim lain secara bersamaan (lihat Bagian 2b) | [Tahap 3](#tahap-3--teamstaffposition-model--factory--service--request--controller--routes--views) |
| Hapus `id_player_category` dari `Player` | Di luar scope brief ini — `Player.id_player_category` (kategori umur pemain, fakta melekat ke orangnya) dan `Team.id_player_category` (kategori kompetisi tim) adalah 2 pertanyaan berbeda, BUKAN redundan. Keduanya tetap independen | — |

---

## 1. Konteks & Tujuan

Academy butuh cara mengelompokkan pemain & staff ke dalam tim (reguler per kelompok umur, tim kompetisi/turnamen, tim sementara untuk seleksi/training camp). Satu pemain bisa jadi anggota banyak tim sekaligus (mis. reguler di "U15 A" tapi juga dipanggil ke "Elite Tournament"), dan satu staff bisa menangani banyak tim dengan peran berbeda-beda (Head Coach di satu tim, Assistant di tim lain).

Brief ini membangun 5 entity:

```text
Academy
  ├── Season                 (baru -- master data: "2026", "2027", dst)
  ├── Team Staff Position    (baru -- master data: Head Coach, Assistant Coach, dst)
  ├── Player Category        (SUDAH ADA -- dipakai ulang sebagai klasifikasi Team)
  │
  └── Team (id_season, id_player_category, code, name, team_type, status)
        ├── Team Player (id_team, id_player, jersey_number, is_captain, join_date, leave_date)
        └── Team Staff  (id_team, id_staff, id_team_staff_position, join_date, leave_date)
```

## 2. Cara Kerja Solusi

### 2a. `Team` pakai `SoftDeletes`, bukan hard-delete-block

Beda dari `EmploymentType`/`StaffPosition` (yang menolak hard-delete kalau masih dipakai kontrak), `Team` pakai `SoftDeletes` (pola sama `Staff`/`Player`) — "Hapus Team" adalah archive, tetap bisa di-restore, dan `TeamPlayer`/`TeamStaff` histori tetap utuh (FK tidak pernah benar-benar putus). Guard di Service tetap ada: **tidak bisa** di-archive kalau masih ada Team Player/Team Staff **aktif** (`leave_date IS NULL`) — admin harus keluarkan semua anggota aktif dulu.

### 2b. Kenapa `TeamStaffPosition` tabel baru, bukan reuse `StaffPosition`

`StaffPosition` menjawab "staff ini jabatannya apa di Academy" (dipakai `EmploymentContract`, berkaitan gaji). `TeamStaffPosition` menjawab pertanyaan yang sama sekali berbeda: "peran staff ini di **1 tim tertentu** apa" — dan menurut Business Rule, 1 staff bisa pegang **banyak** tim dengan peran berbeda secara bersamaan (Head Coach di U15 A, Assistant Coach di tim lain), sesuatu yang tidak mungkin direpresentasikan kalau dipaksa reuse `StaffPosition` (yang cuma 1 nilai per staff, terikat kontrak kerja). Dibuat sebagai master data per-academy (pola sama `StaffPosition`: `code`+`name`, seed default lewat `config('faos.team_staff_position_templates')`).

### 2c. Status Team Player/Team Staff diturunkan dari `leave_date`

Tidak ada kolom `status` di `team_players`/`team_staff` — "Active" berarti `leave_date IS NULL`, "Inactive" berarti `leave_date` terisi. Ini konsisten dengan keputusan yang sama persis di `staff.status` (`issue12.md` Bagian 2a): menyimpan status terpisah dari fakta yang sudah bisa diturunkan berisiko drift.

### 2d. Guard "Nomor Punggung Unik" & "1 Captain" & "1 Head Coach Aktif" — row lock, bukan constraint DB

Sama seperti `EmploymentContractService` (`issue12.md` Bagian 2d), MySQL tidak punya *partial unique index*. Guard ditegakkan dengan mengunci baris `teams` sebagai mutex sebelum insert/update:

```php
DB::transaction(function () use ($team, $data) {

    Team::withoutGlobalScopes()->whereKey($team->id_team)->lockForUpdate()->first();

    // ... cek nomor punggung/captain/head coach duplikat, lempar Exception kalau ada
    // ... insert/update
});
```

### 2e. Assign Head Coach baru otomatis mengeluarkan Head Coach lama

Kalau posisi yang di-assign kodenya `HC` (Head Coach — kode ini konvensi tetap, sama seperti `AD` untuk Academy Director di `StaffPositionService::findDefaultForOwner()`), dan tim itu sudah punya Team Staff lain dengan posisi ber-kode `HC` yang masih aktif, maka Team Staff lama itu otomatis di-set `leave_date = now()` dalam transaksi yang sama — supaya tidak pernah ada 2 Head Coach aktif sesaat, tanpa admin harus manual keluarkan yang lama dulu. Kalau academy tidak/belum punya posisi ber-kode `HC` (dihapus/diganti), aturan ini otomatis tidak berlaku (tidak ada error, cuma tidak ada guard-nya).

### 2f. Ubah nomor punggung/captain vs "keluar tim"

"Edit Jersey/Captain" (mengubah `jersey_number`/`is_captain` milik `TeamPlayer` yang **masih aktif**) beda aksi dari "Keluarkan dari Tim" (`leave_date` diisi). Tidak ada `edit` untuk `TeamStaff` — mengubah peran staff di tim berarti keluarkan yang lama + assign baru (bikin baris baru), pola sama Employment Contract (kontrak baru untuk promosi, bukan edit kontrak lama, `issue12.md` Rule 4).

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/..._create_seasons_table.php` | 🆕 Baru | 1 |
| `database/migrations/..._create_team_staff_positions_table.php` | 🆕 Baru | 1 |
| `app/Models/Season.php`, `database/factories/SeasonFactory.php` | 🆕 Baru | 2 |
| `app/Services/SeasonService.php`, `app/Http/Requests/Season/SeasonFormRequest.php`, `app/Http/Controllers/SeasonController.php` | 🆕 Baru | 2 |
| `resources/views/seasons/*.blade.php` | 🆕 Baru | 2 |
| `app/Models/TeamStaffPosition.php`, `database/factories/TeamStaffPositionFactory.php` | 🆕 Baru | 3 |
| `app/Services/TeamStaffPositionService.php`, `app/Http/Requests/TeamStaffPosition/TeamStaffPositionFormRequest.php`, `app/Http/Controllers/TeamStaffPositionController.php` | 🆕 Baru | 3 |
| `resources/views/team-staff-positions/*.blade.php` | 🆕 Baru | 3 |
| `config/faos.php` | ✏️ Tambah `team_staff_position_templates` | 3 |
| `app/Services/AcademyManagementService.php` | ✏️ Inject `TeamStaffPositionService`, panggil `createDefaultTeamStaffPositions()` | 3 |
| `database/migrations/..._create_teams_table.php` | 🆕 Baru | 4 |
| `app/Models/Team.php`, `database/factories/TeamFactory.php` | 🆕 Baru | 5 |
| `app/Services/TeamService.php` | 🆕 Baru | 6 |
| `app/Http/Requests/Team/TeamFormRequest.php`, `app/Http/Controllers/TeamController.php` | 🆕 Baru | 7 |
| `resources/views/teams/index.blade.php`, `create.blade.php`, `edit.blade.php` | 🆕 Baru | 8 |
| `database/migrations/..._create_team_players_table.php`, `..._create_team_staff_table.php` | 🆕 Baru | 9 |
| `app/Models/TeamPlayer.php`, `app/Models/TeamStaff.php` + Factory | 🆕 Baru | 10 |
| `app/Models/Team.php`, `Player.php`, `Staff.php` | ✏️ Tambah relasi balik | 10 |
| `app/Services/TeamPlayerService.php` | 🆕 Baru | 11 |
| `app/Services/TeamStaffService.php` | 🆕 Baru | 12 |
| `app/Http/Requests/TeamPlayer/*`, `app/Http/Requests/TeamStaff/*`, `app/Http/Controllers/TeamPlayerController.php`, `TeamStaffController.php` | 🆕 Baru | 13 |
| `routes/web.php` | ✏️ Semua route module ini | 4,7,13 |
| `resources/views/teams/show.blade.php` | 🆕 Baru | 14 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Tambah menu Teams/Seasons/Team Staff Positions | 15 |
| `database/seeders/RolePermissionSeeder.php`, `config/faos.php` (`role_templates`) | ✏️ Tambah `season.*`/`team_staff_position.*` | 16 |
| `lang/en.json` | ✏️ Entry baru | 17 |
| `tests/Feature/SeasonTest.php`, `TeamStaffPositionTest.php`, `TeamTest.php` | 🆕 Baru | 18 |
| `docs/permission-reference.md` | ✏️ Hapus baris Team dari tabel "belum dipakai", tambah 3 section modul baru | 19 |

---

## Tahap 1 — Migration: `seasons` & `team_staff_positions`

```bash
php artisan make:migration create_seasons_table
php artisan make:migration create_team_staff_positions_table
```

`..._create_seasons_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {

            $table->uuid('id_season')->primary();
            $table->uuid('id_academy');

            $table->string('name', 50); // "2026"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');

            // Dua academy BOLEH punya "2026" masing-masing. Satu academy
            // TIDAK BOLEH punya dua "2026" (pola sama player_categories).
            $table->unique(['id_academy', 'name'], 'seasons_academy_name_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
```

`..._create_team_staff_positions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_staff_positions', function (Blueprint $table) {

            $table->uuid('id_team_staff_position')->primary();
            $table->uuid('id_academy');

            $table->string('code', 20); // "HC" -- WAJIB, dipakai guard Head Coach (Bagian 2e)
            $table->string('name', 100); // "Head Coach"
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');
            $table->unique(['id_academy', 'name'], 'team_staff_positions_academy_name_unique');
            $table->unique(['id_academy', 'code'], 'team_staff_positions_academy_code_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_staff_positions');
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table seasons
php artisan db:table team_staff_positions
```

Kolom `code`/`name` di `team_staff_positions` NOT NULL. Unique constraint `(id_academy, name)` di kedua tabel.

---

## Tahap 2 — Season: Model + Factory + Service + Request + Controller + Routes + Views

`app/Models/Season.php` (pola identik `PlayerCategory`):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends FaosModel
{
    use HasFactory;

    protected $table = 'seasons';
    protected $primaryKey = 'id_season';

    protected $fillable = ['id_academy', 'name', 'start_date', 'end_date', 'status'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'id_season', 'id_season');
    }
}
```

`database/factories/SeasonFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Season> */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'name' => (string) now()->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => true,
        ];
    }
}
```

`app/Services/SeasonService.php` (pola identik `PlayerCategoryService`, minus `suggestFor()`):

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Season;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status'] === 'active');
        }

        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Season::with('academy')->withCount('teams');

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {

            $query = Season::query();
            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return ['active' => $countFor(true), 'inactive' => $countFor(false)];
    }

    /**
     * Dropdown Season di form Team. $includeId -- season yang sedang
     * dipakai Team tetap ikut walau sudah dinonaktifkan.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return Season::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {
                $query->where('status', true);
                if ($includeId) {
                    $query->orWhere('id_season', $includeId);
                }
            })
            ->orderByDesc('name')
            ->get();
    }

    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): Season
    {
        return DB::transaction(fn () => Season::create([
            'id_academy' => $this->resolveAcademyId($data),
            'name' => $data['name'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? true,
        ]));
    }

    public function update(Season $season, array $data): Season
    {
        return DB::transaction(function () use ($season, $data) {

            $season->update([
                'name' => $data['name'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $season;
        });
    }

    public function delete(Season $season): bool
    {
        return DB::transaction(function () use ($season) {

            if ($season->teams()->exists()) {
                throw new \Exception(__('Season masih digunakan oleh tim, tidak dapat dihapus. Nonaktifkan season ini kalau sudah tidak dipakai.'));
            }

            return $season->delete();
        });
    }
}
```

`app/Http/Requests/Season/SeasonFormRequest.php` (pola sama `StaffPositionFormRequest`, minus `role_id`):

```php
<?php

namespace App\Http\Requests\Season;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeasonFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academyService = app(AcademyService::class);

        $academyId = $academyService->isSuperAdmin()
            ? $this->input('id_academy')
            : $academyService->currentId();

        return [
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'required' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('seasons', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('season')?->id_season, 'id_season'),
            ],

            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => __('Academy wajib dipilih.'),
            'name.required' => __('Nama season wajib diisi.'),
            'name.unique' => __('Season dengan nama ini sudah ada di academy ini.'),
            'end_date.after_or_equal' => __('Tanggal berakhir tidak boleh sebelum tanggal mulai.'),
        ];
    }
}
```

`app/Http/Controllers/SeasonController.php` (pola identik `EmploymentTypeController` -- Tabs+Toolbar, lihat `issue14.md`/pekerjaan sebelumnya):

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Season\SeasonFormRequest;
use App\Models\Academy;
use App\Models\Season;
use App\Services\AcademyService;
use App\Services\SeasonService;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    protected SeasonService $seasonService;
    protected AcademyService $academyService;

    public function __construct(SeasonService $seasonService, AcademyService $academyService)
    {
        $this->seasonService = $seasonService;
        $this->academyService = $academyService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'id_academy', 'sort']));
        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('seasons.index', [
            'title' => __('Season'),
            'breadcrumb' => [
                ['label' => __('Football Academy')],
                ['label' => __('Season')],
            ],
            'seasons' => $this->seasonService->paginate($filters),
            'statusCounts' => $this->seasonService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
        ]);
    }

    public function create()
    {
        return view('seasons.create', [
            'title' => __('Tambah Season'),
            'breadcrumb' => [
                ['label' => __('Season'), 'url' => route('seasons.index')],
                ['label' => __('Tambah Season')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin() ? Academy::orderBy('name')->get() : collect(),
        ]);
    }

    public function store(SeasonFormRequest $request)
    {
        try {
            $this->seasonService->create($request->validated());

            return redirect()->route('seasons.index')->with('success', __('Season berhasil ditambahkan.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan season'));
        }
    }

    public function edit(Season $season)
    {
        return view('seasons.edit', [
            'title' => __('Edit Season'),
            'breadcrumb' => [
                ['label' => __('Season'), 'url' => route('seasons.index')],
                ['label' => __('Edit Season')],
            ],
            'season' => $season,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(SeasonFormRequest $request, Season $season)
    {
        try {
            $this->seasonService->update($season, $request->validated());

            return redirect()->route('seasons.index')->with('success', __('Season berhasil diperbarui.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal memperbarui season'));
        }
    }

    public function destroy(Season $season)
    {
        try {
            $this->seasonService->delete($season);

            return redirect()->route('seasons.index')->with('success', __('Season berhasil dihapus.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menghapus season'), 'seasons.index');
        }
    }
}
```

Route (`routes/web.php`, taruh di grup Football, dekat `player-categories`):

```php
Route::resource('seasons', SeasonController::class)
    ->except(['show'])
    ->middlewareFor('index', 'permission:season.view')
    ->middlewareFor(['create', 'store'], 'permission:season.create')
    ->middlewareFor(['edit', 'update'], 'permission:season.update')
    ->middlewareFor('destroy', 'permission:season.delete');
```

Views `resources/views/seasons/index.blade.php`/`create.blade.php`/`edit.blade.php`: **salin persis** `resources/views/employment-types/index.blade.php`/`create.blade.php`/`edit.blade.php` (Tabs Aktif/Nonaktif + Toolbar search/sort/Academy + Table/Card List), ganti field `name`/`description`/`status` jadi `name`/`start_date`/`end_date`/`status`, ganti semua `employment-types`→`seasons`, `EmploymentType`→`Season`, `employment_type.*`→`season.*`.

**✅ Cek dulu**: `php artisan route:list --name=seasons` — 6 baris (index/create/store/edit/update/destroy, tanpa `show`). Buka `/seasons`, buat 1 season baru "2026", muncul di list.

---

## Tahap 3 — TeamStaffPosition: Model + Factory + Service + Request + Controller + Routes + Views

`app/Models/TeamStaffPosition.php` (pola identik `StaffPosition`, minus `role_id`):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamStaffPosition extends FaosModel
{
    use HasFactory;

    protected $table = 'team_staff_positions';
    protected $primaryKey = 'id_team_staff_position';

    protected $fillable = ['id_academy', 'code', 'name', 'description', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function teamStaff(): HasMany
    {
        return $this->hasMany(TeamStaff::class, 'id_team_staff_position', 'id_team_staff_position');
    }
}
```

`database/factories/TeamStaffPositionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\TeamStaffPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamStaffPosition> */
class TeamStaffPositionFactory extends Factory
{
    protected $model = TeamStaffPosition::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->unique()->jobTitle(),
            'description' => null,
            'status' => true,
        ];
    }
}
```

`app/Services/TeamStaffPositionService.php` (pola identik `StaffPositionService`, minus `findDefaultForOwner()`/`role_id`, plus `createDefaultTeamStaffPositions()`):

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\TeamStaffPosition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TeamStaffPositionService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status'] === 'active');
        }

        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = TeamStaffPosition::with('academy')->withCount('teamStaff');

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {
            $query = TeamStaffPosition::query();
            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return ['active' => $countFor(true), 'inactive' => $countFor(false)];
    }

    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return TeamStaffPosition::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {
                $query->where('status', true);
                if ($includeId) {
                    $query->orWhere('id_team_staff_position', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): TeamStaffPosition
    {
        return DB::transaction(fn () => TeamStaffPosition::create([
            'id_academy' => $this->resolveAcademyId($data),
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? true,
        ]));
    }

    public function update(TeamStaffPosition $teamStaffPosition, array $data): TeamStaffPosition
    {
        return DB::transaction(function () use ($teamStaffPosition, $data) {

            $teamStaffPosition->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $teamStaffPosition;
        });
    }

    public function delete(TeamStaffPosition $teamStaffPosition): bool
    {
        return DB::transaction(function () use ($teamStaffPosition) {

            if ($teamStaffPosition->teamStaff()->exists()) {
                throw new \Exception(__('Posisi ini masih digunakan oleh staff tim, tidak dapat dihapus. Nonaktifkan posisi ini kalau sudah tidak dipakai.'));
            }

            return $teamStaffPosition->delete();
        });
    }

    /**
     * Buat Team Staff Position default untuk academy baru dari
     * config('faos.team_staff_position_templates'). Pola sama
     * StaffPositionService::createDefaultStaffPositions() (issue16.md
     * Bagian 2b -- ini dimensi berbeda dari StaffPosition).
     */
    public function createDefaultTeamStaffPositions(Academy $academy): void
    {
        foreach (config('faos.team_staff_position_templates') as $name => $attributes) {

            TeamStaffPosition::create([
                'id_academy' => $academy->id_academy,
                'code' => $attributes['code'],
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'status' => true,
            ]);
        }
    }
}
```

`config/faos.php` — tambah **setelah** blok `staff_position_templates`:

```php
/*
|--------------------------------------------------------------------------
| Team Staff Position Templates
|--------------------------------------------------------------------------
| Peran fungsional staff DI SATU TIM (beda dari staff_position_templates
| yang jabatan kepegawaian Academy) -- lihat issue16.md Bagian 2b.
| Code "HC" WAJIB ada persis segini -- dipakai guard "1 Head Coach aktif
| per tim" (TeamStaffService, issue16.md Bagian 2e).
*/
'team_staff_position_templates' => [
    'Head Coach' => ['code' => 'HC', 'description' => 'Pelatih kepala, penanggung jawab utama tim.'],
    'Assistant Coach' => ['code' => 'AC', 'description' => 'Pelatih asisten, membantu Head Coach.'],
    'Goalkeeper Coach' => ['code' => 'GK', 'description' => 'Pelatih khusus penjaga gawang.'],
    'Fitness Coach' => ['code' => 'FT', 'description' => 'Pelatih kebugaran/fisik.'],
    'Team Manager' => ['code' => 'TM', 'description' => 'Pengurus administratif & logistik tim.'],
    'Physiotherapist' => ['code' => 'PHY', 'description' => 'Penanganan cedera & pemulihan fisik pemain.'],
],
```

`app/Services/AcademyManagementService.php` — inject `TeamStaffPositionService`, panggil di titik yang sama seperti `createDefaultPlayerCategories()`/`createDefaultStaffPositions()` (baris ~424-426 pada method `create()`):

```php
protected TeamStaffPositionService $teamStaffPositionService;

public function __construct(
    // ... parameter lain tetap sama,
    TeamStaffPositionService $teamStaffPositionService
) {
    // ... assignment lain tetap sama
    $this->teamStaffPositionService = $teamStaffPositionService;
}
```

```php
$this->roleService->createDefaultRoles($academy);
$this->playerTypeService->createDefaultPlayerTypes($academy);
$this->playerCategoryService->createDefaultPlayerCategories($academy);
$this->employmentTypeService->createDefaultEmploymentTypes($academy);
$this->staffPositionService->createDefaultStaffPositions($academy);
$this->teamStaffPositionService->createDefaultTeamStaffPositions($academy); // <-- baris baru
```

`app/Http/Requests/TeamStaffPosition/TeamStaffPositionFormRequest.php` — **salin persis** `StaffPositionFormRequest.php` (Tahap sebelumnya sudah ditunjukkan strukturnya), hapus rule `role_id`, ganti nama tabel/model.

`app/Http/Controllers/TeamStaffPositionController.php` — **salin persis** `EmploymentTypeController.php` (bukan `StaffPositionController` yang punya urusan `role_id`), ganti nama Service/Model/route/permission (`team_staff_position.*`).

Route:

```php
Route::resource('team-staff-positions', TeamStaffPositionController::class)
    ->except(['show'])
    ->middlewareFor('index', 'permission:team_staff_position.view')
    ->middlewareFor(['create', 'store'], 'permission:team_staff_position.create')
    ->middlewareFor(['edit', 'update'], 'permission:team_staff_position.update')
    ->middlewareFor('destroy', 'permission:team_staff_position.delete');
```

Views: **salin persis** `resources/views/staff-positions/index.blade.php`/`create.blade.php`/`edit.blade.php`, ganti field `role_id` dihapus, ganti semua `staff-positions`→`team-staff-positions`, `StaffPosition`→`TeamStaffPosition`, `staff_position.*`→`team_staff_position.*`.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create();
app(\App\Services\TeamStaffPositionService::class)->createDefaultTeamStaffPositions($academy);
\App\Models\TeamStaffPosition::where('id_academy', $academy->id_academy)->count(); // 6
\App\Models\TeamStaffPosition::where('id_academy', $academy->id_academy)->where('code', 'HC')->exists(); // true
```

Buat Academy baru lewat form (create_account atau standalone) — 6 Team Staff Position default harus otomatis ada.

---

## Tahap 4 — Migration: `teams`

```bash
php artisan make:migration create_teams_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {

            $table->uuid('id_team')->primary();
            $table->uuid('id_academy');

            $table->uuid('id_season');
            $table->uuid('id_player_category');

            $table->string('code', 20); // "TM001"
            $table->string('name', 100); // "U15 A"
            $table->enum('team_type', ['regular', 'tournament', 'event', 'temporary'])->default('regular');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes(); // "Hapus Team" = archive, lihat issue16.md Bagian 2a

            $table->index('id_academy');
            $table->index('id_season');
            $table->index('id_player_category');
            $table->unique(['id_academy', 'code'], 'teams_academy_code_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();

            // restrictOnDelete -- kolom WAJIB terisi, FK tidak boleh diam-diam
            // NULL (pola sama staff.id_employment_type di issue11.md). Guard
            // Service (SeasonService::delete()/cek players()) jadi lapis
            // pertama sebelum constraint DB ini kena.
            $table->foreign('id_season')->references('id_season')->on('seasons')->restrictOnDelete();
            $table->foreign('id_player_category')->references('id_player_category')->on('player_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

**✅ Cek dulu**: `php artisan migrate` lalu `php artisan db:table teams` — ada kolom `deleted_at`, `id_season`/`id_player_category` NOT NULL, FK `restrictOnDelete`.

---

## Tahap 5 — Model `Team` + Factory

`app/Models/Team.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends FaosModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'teams';
    protected $primaryKey = 'id_team';

    protected $fillable = [
        'id_academy', 'id_season', 'id_player_category',
        'code', 'name', 'team_type', 'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'id_season', 'id_season');
    }

    public function playerCategory(): BelongsTo
    {
        return $this->belongsTo(PlayerCategory::class, 'id_player_category', 'id_player_category');
    }

    /**
     * Seluruh histori Team Player tim ini (aktif maupun sudah keluar).
     * "Aktif" = leave_date IS NULL (issue16.md Bagian 2c), BUKAN kolom
     * status terpisah.
     */
    public function teamPlayers(): HasMany
    {
        return $this->hasMany(TeamPlayer::class, 'id_team', 'id_team')->latest('join_date');
    }

    public function activeTeamPlayers(): HasMany
    {
        return $this->teamPlayers()->whereNull('leave_date');
    }

    public function teamStaff(): HasMany
    {
        return $this->hasMany(TeamStaff::class, 'id_team', 'id_team')->latest('join_date');
    }

    public function activeTeamStaff(): HasMany
    {
        return $this->teamStaff()->whereNull('leave_date');
    }
}
```

`database/factories/TeamFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Team> */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('TM###')),
            'name' => fake()->words(2, true),
            'team_type' => 'regular',
            'description' => null,
            'status' => true,
        ];
    }
}
```

**✅ Cek dulu**: `php artisan tinker` → `(new \App\Models\Team)->getFillable()` memuat semua field, `Team::factory()->make()->team_type` = `'regular'`.

---

## Tahap 6 — `TeamService`

`app/Services/TeamService.php`:

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeamService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status'] === 'active');
        }

        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }

        if (! empty($filters['id_season'])) {
            $query->where('id_season', $filters['id_season']);
        }

        if (! empty($filters['id_player_category'])) {
            $query->where('id_player_category', $filters['id_player_category']);
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Team::with(['academy', 'season', 'playerCategory'])
            ->withCount(['activeTeamPlayers', 'activeTeamStaff']);

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {
            $query = Team::query();
            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return ['active' => $countFor(true), 'inactive' => $countFor(false)];
    }

    /**
     * Pola sama StaffService::generateStaffCode() -- prefix "TM", 3 digit
     * berurutan lintas academy (Team bukan per-academy sequence supaya
     * simpel, beda dari staff_code yang per-academy).
     */
    protected function generateTeamCode(): string
    {
        return DB::transaction(function () {

            $last = Team::withoutGlobalScopes()
                ->where('code', 'like', 'TM%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->first();

            $next = $last ? ((int) substr($last->code, 2)) + 1 : 1;

            do {
                $code = 'TM' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
                $exists = Team::withoutGlobalScopes()->where('code', $code)->exists();
                $next++;
            } while ($exists);

            return $code;
        });
    }

    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): Team
    {
        return DB::transaction(fn () => Team::create([
            'id_academy' => $this->resolveAcademyId($data),
            'id_season' => $data['id_season'],
            'id_player_category' => $data['id_player_category'],
            'code' => $this->generateTeamCode(),
            'name' => $data['name'],
            'team_type' => $data['team_type'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? true,
        ]));
    }

    public function update(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {

            // id_academy & code sengaja TIDAK ikut diubah.
            $team->update([
                'id_season' => $data['id_season'],
                'id_player_category' => $data['id_player_category'],
                'name' => $data['name'],
                'team_type' => $data['team_type'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $team;
        });
    }

    /**
     * SoftDeletes (archive), BUKAN hard delete -- lihat issue16.md
     * Bagian 2a. Ditolak kalau masih ada Team Player/Team Staff aktif.
     */
    public function delete(Team $team): bool
    {
        return DB::transaction(function () use ($team) {

            if ($team->activeTeamPlayers()->exists() || $team->activeTeamStaff()->exists()) {
                throw new \Exception(__('Tim ini masih memiliki player/staff yang aktif, keluarkan semua anggota aktif terlebih dahulu sebelum menghapus tim.'));
            }

            return $team->delete();
        });
    }
}
```

**✅ Cek dulu**

```bash
php -l app/Services/TeamService.php
```

```php
php artisan tinker
$academy = \App\Models\Academy::factory()->create();
$season = \App\Models\Season::factory()->create(['id_academy' => $academy->id_academy]);
$category = \App\Models\PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);
$team = app(\App\Services\TeamService::class)->create([
    'id_academy' => $academy->id_academy, 'id_season' => $season->id_season,
    'id_player_category' => $category->id_player_category, 'name' => 'U15 A', 'team_type' => 'regular',
]);
$team->code; // "TM001" (atau nomor urut lain kalau sudah ada Team lain di DB)
app(\App\Services\TeamService::class)->delete($team); // sukses (belum ada anggota)
$team->fresh(withTrashed: true)->deleted_at; // tidak null
```

---

## Tahap 7 — Team: Request + Controller + Routes

`app/Http/Requests/Team/TeamFormRequest.php`:

```php
<?php

namespace App\Http\Requests\Team;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academyService = app(AcademyService::class);

        $academyId = $academyService->isSuperAdmin()
            ? $this->input('id_academy')
            : $academyService->currentId();

        return [
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'required' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            'id_season' => [
                'required',
                'uuid',
                Rule::exists('seasons', 'id_season')->where(fn ($q) => $q->where('id_academy', $academyId)),
            ],

            'id_player_category' => [
                'required',
                'uuid',
                Rule::exists('player_categories', 'id_player_category')->where(fn ($q) => $q->where('id_academy', $academyId)),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('teams', 'name')
                    ->where(fn ($q) => $q->where('id_academy', $academyId))
                    ->ignore($this->route('team')?->id_team, 'id_team'),
            ],

            'team_type' => ['required', Rule::in(['regular', 'tournament', 'event', 'temporary'])],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_season.required' => __('Season wajib dipilih.'),
            'id_player_category.required' => __('Player category wajib dipilih.'),
            'name.required' => __('Nama tim wajib diisi.'),
            'name.unique' => __('Tim dengan nama ini sudah ada di academy ini.'),
            'team_type.required' => __('Tipe tim wajib dipilih.'),
        ];
    }
}
```

`app/Http/Controllers/TeamController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\TeamFormRequest;
use App\Models\Academy;
use App\Models\PlayerCategory;
use App\Models\Season;
use App\Models\Team;
use App\Services\AcademyService;
use App\Services\PlayerCategoryService;
use App\Services\SeasonService;
use App\Services\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    protected TeamService $teamService;
    protected SeasonService $seasonService;
    protected PlayerCategoryService $playerCategoryService;
    protected AcademyService $academyService;

    public function __construct(
        TeamService $teamService,
        SeasonService $seasonService,
        PlayerCategoryService $playerCategoryService,
        AcademyService $academyService
    ) {
        $this->teamService = $teamService;
        $this->seasonService = $seasonService;
        $this->playerCategoryService = $playerCategoryService;
        $this->academyService = $academyService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'id_academy', 'id_season', 'id_player_category', 'sort']));
        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('teams.index', [
            'title' => __('Team'),
            'breadcrumb' => [
                ['label' => __('Football Academy')],
                ['label' => __('Team')],
            ],
            'teams' => $this->teamService->paginate($filters),
            'statusCounts' => $this->teamService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
            'seasons' => Season::query()->orderByDesc('name')->get(),
            'playerCategories' => PlayerCategory::query()->orderBy('min_age')->get(),
        ]);
    }

    public function create()
    {
        $academyId = $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId();

        return view('teams.create', [
            'title' => __('Tambah Team'),
            'breadcrumb' => [
                ['label' => __('Team'), 'url' => route('teams.index')],
                ['label' => __('Tambah Team')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin() ? Academy::orderBy('name')->get() : collect(),
            'seasons' => $this->seasonService->selectable($academyId),
            'playerCategories' => $this->playerCategoryService->selectable($academyId),
        ]);
    }

    public function store(TeamFormRequest $request)
    {
        try {
            $this->teamService->create($request->validated());

            return redirect()->route('teams.index')->with('success', __('Team berhasil ditambahkan.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan team'));
        }
    }

    public function show(Team $team)
    {
        $team->load(['season', 'playerCategory', 'academy']);

        return view('teams.show', [
            'title' => $team->name,
            'breadcrumb' => [
                ['label' => __('Team'), 'url' => route('teams.index')],
                ['label' => $team->name],
            ],
            'team' => $team,
            'teamPlayers' => $team->teamPlayers()->with('player')->get(),
            'teamStaff' => $team->teamStaff()->with(['staff', 'teamStaffPosition'])->get(),
        ]);
    }

    public function edit(Team $team)
    {
        return view('teams.edit', [
            'title' => __('Edit Team'),
            'breadcrumb' => [
                ['label' => __('Team'), 'url' => route('teams.index')],
                ['label' => __('Edit Team')],
            ],
            'team' => $team,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'seasons' => $this->seasonService->selectable($team->id_academy, $team->id_season),
            'playerCategories' => $this->playerCategoryService->selectable($team->id_academy, $team->id_player_category),
        ]);
    }

    public function update(TeamFormRequest $request, Team $team)
    {
        try {
            $this->teamService->update($team, $request->validated());

            return redirect()->route('teams.index')->with('success', __('Team berhasil diperbarui.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal memperbarui team'));
        }
    }

    public function destroy(Team $team)
    {
        try {
            $this->teamService->delete($team);

            return redirect()->route('teams.index')->with('success', __('Team berhasil dihapus.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menghapus team'), 'teams.index');
        }
    }
}
```

Route (`routes/web.php`, reuse permission `team.*` yang **sudah ada**):

```php
Route::resource('teams', TeamController::class)
    ->middlewareFor(['index', 'show'], 'permission:team.view')
    ->middlewareFor(['create', 'store'], 'permission:team.create')
    ->middlewareFor(['edit', 'update'], 'permission:team.update')
    ->middlewareFor('destroy', 'permission:team.delete');
```

**✅ Cek dulu**: `php artisan route:list --name=teams` — 7 baris (index/create/store/show/edit/update/destroy), semua middleware `permission:team.*` (bukan permission baru).

---

## Tahap 8 — Views `teams/index.blade.php`, `create.blade.php`, `edit.blade.php`

`resources/views/teams/index.blade.php` — **salin persis** `resources/views/employment-types/index.blade.php` (Tabs+Toolbar+Table/Card List), sesuaikan kolom: Team (name+code), Season, Player Category, Type (badge team_type), Jml Player/Staff (`activeTeamPlayers_count`/`activeTeamStaff_count`), Status, Aksi (Edit/Hapus **+ tombol "Lihat"** ke `teams.show`, beda dari module master data biasa yang tidak punya halaman detail). Tambah filter dropdown Season & Player Category di `<x-table.toolbar>` (selain Academy khusus Super Admin).

`resources/views/teams/create.blade.php`/`edit.blade.php` — form dengan field (urutan sesuai taksonomi `docs/frontend-standard.md`): Academy (Super Admin) → Nama & Kode (kode read-only/auto, tidak diinput) → Season & Player Category (dropdown, sejenis "Klasifikasi") → Team Type (dropdown: Regular/Tournament/Event/Temporary) → Description → Status. Pola markup identik `resources/views/employment-types/create.blade.php`/`edit.blade.php`.

**✅ Cek dulu**: buka `/teams`, buat 1 Team baru pilih Season+Player Category yang sudah dibuat di Tahap 2, muncul di list dengan kode `TM001` otomatis.

---

## Tahap 9 — Migration: `team_players` & `team_staff`

```bash
php artisan make:migration create_team_players_table
php artisan make:migration create_team_staff_table
```

`..._create_team_players_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_players', function (Blueprint $table) {

            $table->uuid('id_team_player')->primary();
            $table->uuid('id_academy');

            $table->uuid('id_team');
            $table->uuid('id_player');

            $table->unsignedTinyInteger('jersey_number');
            $table->boolean('is_captain')->default(false);

            $table->date('join_date');
            $table->date('leave_date')->nullable(); // NULL = masih aktif (issue16.md Bagian 2c)

            $table->text('notes')->nullable();

            $table->timestamps();
            // TIDAK ada kolom status -- diturunkan dari leave_date (Aturan Emas).
            // TIDAK ada softDeletes -- baris ini sendiri SUDAH histori permanen,
            // "keluar" direpresentasikan leave_date, bukan dihapus/diarsipkan lagi.

            $table->index('id_academy');
            $table->index('id_team');
            $table->index('id_player');
            $table->index(['id_team', 'leave_date'], 'team_players_team_leave_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_team')->references('id_team')->on('teams')->cascadeOnDelete();
            $table->foreign('id_player')->references('id_player')->on('players')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_players');
    }
};
```

`..._create_team_staff_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_staff', function (Blueprint $table) {

            $table->uuid('id_team_staff')->primary();
            $table->uuid('id_academy');

            $table->uuid('id_team');
            $table->uuid('id_staff');
            $table->uuid('id_team_staff_position');

            $table->date('join_date');
            $table->date('leave_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('id_academy');
            $table->index('id_team');
            $table->index('id_staff');
            $table->index(['id_team', 'leave_date'], 'team_staff_team_leave_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_team')->references('id_team')->on('teams')->cascadeOnDelete();
            $table->foreign('id_staff')->references('id_staff')->on('staff')->cascadeOnDelete();
            $table->foreign('id_team_staff_position')->references('id_team_staff_position')->on('team_staff_positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_staff');
    }
};
```

**✅ Cek dulu**: `php artisan migrate`, `php artisan db:table team_players`/`team_staff` — `leave_date` nullable, tidak ada kolom `status`/`deleted_at`.

---

## Tahap 10 — Model `TeamPlayer`/`TeamStaff` + Factory + Relasi Balik

`app/Models/TeamPlayer.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamPlayer extends FaosModel
{
    use HasFactory;

    protected $table = 'team_players';
    protected $primaryKey = 'id_team_player';

    protected $fillable = [
        'id_academy', 'id_team', 'id_player',
        'jersey_number', 'is_captain', 'join_date', 'leave_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'jersey_number' => 'integer',
            'is_captain' => 'boolean',
            'join_date' => 'date',
            'leave_date' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'id_team', 'id_team');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'id_player', 'id_player');
    }

    public function isActive(): bool
    {
        return $this->leave_date === null;
    }
}
```

`app/Models/TeamStaff.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamStaff extends FaosModel
{
    use HasFactory;

    protected $table = 'team_staff';
    protected $primaryKey = 'id_team_staff';

    protected $fillable = [
        'id_academy', 'id_team', 'id_staff', 'id_team_staff_position',
        'join_date', 'leave_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'leave_date' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'id_team', 'id_team');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff', 'id_staff');
    }

    public function teamStaffPosition(): BelongsTo
    {
        return $this->belongsTo(TeamStaffPosition::class, 'id_team_staff_position', 'id_team_staff_position');
    }

    public function isActive(): bool
    {
        return $this->leave_date === null;
    }
}
```

Factory (`database/factories/TeamPlayerFactory.php`/`TeamStaffFactory.php`) — sengaja **tidak** mengisi `id_team`/`id_player`/`id_staff`/`id_team_staff_position` (harus di-pass eksplisit tiap test, pola sama `EmploymentContractFactory`):

```php
<?php

namespace Database\Factories;

use App\Models\TeamPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamPlayer> */
class TeamPlayerFactory extends Factory
{
    protected $model = TeamPlayer::class;

    public function definition(): array
    {
        return [
            'jersey_number' => fake()->unique()->numberBetween(1, 99),
            'is_captain' => false,
            'join_date' => now(),
            'leave_date' => null,
            'notes' => null,
        ];
    }
}
```

```php
<?php

namespace Database\Factories;

use App\Models\TeamStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamStaff> */
class TeamStaffFactory extends Factory
{
    protected $model = TeamStaff::class;

    public function definition(): array
    {
        return [
            'join_date' => now(),
            'leave_date' => null,
            'notes' => null,
        ];
    }
}
```

`app/Models/Player.php` — tambah relasi (di bawah `documents()` dari `issue15.md`):

```php
/*
|--------------------------------------------------------------------------
| Relationship Team Player
|--------------------------------------------------------------------------
*/
public function teamPlayers()
{
    return $this->hasMany(\App\Models\TeamPlayer::class, 'id_player', 'id_player');
}
```

`app/Models/Staff.php` — tambah relasi (di bawah `documents()`):

```php
public function teamStaff(): HasMany
{
    return $this->hasMany(TeamStaff::class, 'id_staff', 'id_staff');
}
```

(`HasMany` sudah di-import dari relasi lain di file yang sama, tidak perlu import baru.)

**✅ Cek dulu**: `php artisan tinker` → `\App\Models\Player::first()->teamPlayers`/`\App\Models\Staff::first()->teamStaff` tidak error (boleh collection kosong).

---

## Tahap 11 — `TeamPlayerService`

`app/Services/TeamPlayerService.php`:

```php
<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamPlayer;
use Illuminate\Support\Facades\DB;

class TeamPlayerService
{
    /**
     * Kunci baris team sebagai mutex -- mencegah race condition 2 admin
     * assign player ke tim yang sama nyaris bersamaan (pola sama
     * EmploymentContractService::lockStaff(), issue12.md Bagian 2d).
     */
    protected function lockTeam(Team $team): void
    {
        Team::withoutGlobalScopes()->whereKey($team->id_team)->lockForUpdate()->first();
    }

    public function assign(Team $team, array $data): TeamPlayer
    {
        return DB::transaction(function () use ($team, $data) {

            $this->lockTeam($team);

            if (TeamPlayer::where('id_team', $team->id_team)
                ->where('id_player', $data['id_player'])
                ->whereNull('leave_date')
                ->exists()) {
                throw new \Exception(__('Player ini sudah aktif terdaftar di tim ini.'));
            }

            $this->assertJerseyAvailable($team, $data['jersey_number']);

            $teamPlayer = TeamPlayer::create([
                'id_academy' => $team->id_academy,
                'id_team' => $team->id_team,
                'id_player' => $data['id_player'],
                'jersey_number' => $data['jersey_number'],
                'is_captain' => false,
                'join_date' => $data['join_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['is_captain'])) {
                $this->setCaptain($teamPlayer);
            }

            return $teamPlayer->fresh();
        });
    }

    /**
     * Ubah nomor punggung/captain -- HANYA untuk keanggotaan yang masih
     * aktif (issue16.md Bagian 2f). Ganti peran/tim = keluarkan + assign
     * baru, bukan edit histori lama.
     */
    public function update(TeamPlayer $teamPlayer, array $data): TeamPlayer
    {
        return DB::transaction(function () use ($teamPlayer, $data) {

            if (! $teamPlayer->isActive()) {
                throw new \Exception(__('Keanggotaan yang sudah keluar dari tim tidak dapat diubah.'));
            }

            $this->lockTeam($teamPlayer->team);

            if ((int) $data['jersey_number'] !== $teamPlayer->jersey_number) {
                $this->assertJerseyAvailable($teamPlayer->team, $data['jersey_number'], $teamPlayer);
            }

            $teamPlayer->update([
                'jersey_number' => $data['jersey_number'],
                'notes' => $data['notes'] ?? $teamPlayer->notes,
            ]);

            if (! empty($data['is_captain'])) {
                $this->setCaptain($teamPlayer);
            } elseif ($teamPlayer->is_captain) {
                $teamPlayer->update(['is_captain' => false]);
            }

            return $teamPlayer->fresh();
        });
    }

    /**
     * "Keluarkan dari Tim" -- isi leave_date, BUKAN hapus baris
     * (Aturan Emas, histori tetap utuh untuk laporan roster musim lalu).
     */
    public function leave(TeamPlayer $teamPlayer, ?string $leaveDate = null): TeamPlayer
    {
        if (! $teamPlayer->isActive()) {
            throw new \Exception(__('Player ini sudah tidak aktif di tim ini.'));
        }

        $teamPlayer->update(['leave_date' => $leaveDate ?? now()]);

        return $teamPlayer->fresh();
    }

    /**
     * Nomor punggung unik di antara anggota AKTIF tim ini saja -- boleh
     * dipakai ulang setelah pemain lama keluar (Aturan Emas, MySQL tidak
     * punya partial unique index).
     */
    protected function assertJerseyAvailable(Team $team, int $jerseyNumber, ?TeamPlayer $except = null): void
    {
        $query = TeamPlayer::where('id_team', $team->id_team)
            ->whereNull('leave_date')
            ->where('jersey_number', $jerseyNumber);

        if ($except) {
            $query->where('id_team_player', '!=', $except->id_team_player);
        }

        if ($query->exists()) {
            throw new \Exception(__('Nomor punggung ini sudah dipakai pemain aktif lain di tim ini.'));
        }
    }

    /**
     * Set 1 player jadi captain -- otomatis melepas status captain dari
     * player aktif lain di tim yang sama DALAM transaksi yang sama
     * (pola sama EmploymentContractService::activate() menutup contract
     * lama, issue12.md Bagian 2c).
     */
    protected function setCaptain(TeamPlayer $teamPlayer): void
    {
        TeamPlayer::where('id_team', $teamPlayer->id_team)
            ->whereNull('leave_date')
            ->where('id_team_player', '!=', $teamPlayer->id_team_player)
            ->update(['is_captain' => false]);

        $teamPlayer->update(['is_captain' => true]);
    }
}
```

**✅ Cek dulu**

```bash
php -l app/Services/TeamPlayerService.php
```

```php
php artisan tinker
$service = app(\App\Services\TeamPlayerService::class);
$tp1 = $service->assign($team, ['id_player' => $playerA->id_player, 'jersey_number' => 10, 'is_captain' => true]);
$tp2 = $service->assign($team, ['id_player' => $playerB->id_player, 'jersey_number' => 10]); // HARUS throw (nomor dipakai)
$tp3 = $service->assign($team, ['id_player' => $playerB->id_player, 'jersey_number' => 7, 'is_captain' => true]);
$tp1->fresh()->is_captain; // false (otomatis lepas, tp3 yang jadi captain baru)
$service->leave($tp3);
$service->assign($team, ['id_player' => $playerC->id_player, 'jersey_number' => 7]); // sukses (nomor 7 sudah "kosong" lagi)
```

---

## Tahap 12 — `TeamStaffService`

`app/Services/TeamStaffService.php`:

```php
<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamStaff;
use App\Models\TeamStaffPosition;
use Illuminate\Support\Facades\DB;

class TeamStaffService
{
    /** Kode posisi Head Coach yang konvensinya tetap -- lihat issue16.md Bagian 2e. */
    protected const HEAD_COACH_CODE = 'HC';

    protected function lockTeam(Team $team): void
    {
        Team::withoutGlobalScopes()->whereKey($team->id_team)->lockForUpdate()->first();
    }

    public function assign(Team $team, array $data): TeamStaff
    {
        return DB::transaction(function () use ($team, $data) {

            $this->lockTeam($team);

            $position = TeamStaffPosition::findOrFail($data['id_team_staff_position']);

            // Assign Head Coach baru otomatis mengeluarkan Head Coach lama
            // yang masih aktif -- tidak pernah ada 2 Head Coach aktif
            // sesaat (issue16.md Bagian 2e). Kalau academy tidak punya
            // posisi ber-kode HC, guard ini otomatis tidak berlaku.
            if ($position->code === self::HEAD_COACH_CODE) {

                TeamStaff::where('id_team', $team->id_team)
                    ->whereNull('leave_date')
                    ->whereHas('teamStaffPosition', fn ($q) => $q->where('code', self::HEAD_COACH_CODE))
                    ->update(['leave_date' => now()]);
            }

            return TeamStaff::create([
                'id_academy' => $team->id_academy,
                'id_team' => $team->id_team,
                'id_staff' => $data['id_staff'],
                'id_team_staff_position' => $data['id_team_staff_position'],
                'join_date' => $data['join_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * "Keluarkan dari Tim" -- isi leave_date, BUKAN hapus baris.
     * Ganti peran staff di tim = keluarkan + assign baru (issue16.md
     * Bagian 2f), bukan edit di tempat.
     */
    public function leave(TeamStaff $teamStaff, ?string $leaveDate = null): TeamStaff
    {
        if (! $teamStaff->isActive()) {
            throw new \Exception(__('Staff ini sudah tidak aktif di tim ini.'));
        }

        $teamStaff->update(['leave_date' => $leaveDate ?? now()]);

        return $teamStaff->fresh();
    }
}
```

**✅ Cek dulu**

```bash
php -l app/Services/TeamStaffService.php
```

```php
php artisan tinker
$hc = \App\Models\TeamStaffPosition::where('id_academy', $academy->id_academy)->where('code', 'HC')->first();
$service = app(\App\Services\TeamStaffService::class);
$ts1 = $service->assign($team, ['id_staff' => $staffA->id_staff, 'id_team_staff_position' => $hc->id_team_staff_position]);
$ts2 = $service->assign($team, ['id_staff' => $staffB->id_staff, 'id_team_staff_position' => $hc->id_team_staff_position]);
$ts1->fresh()->leave_date; // TIDAK null -- otomatis dikeluarkan begitu Head Coach baru di-assign
$ts2->fresh()->isActive(); // true
```

---

## Tahap 13 — TeamPlayer/TeamStaff: Requests + Controllers + Routes

`app/Http/Requests/TeamPlayer/StoreTeamPlayerRequest.php`:

```php
<?php

namespace App\Http\Requests\TeamPlayer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'id_player' => [
                'required',
                'uuid',
                Rule::exists('players', 'id_player')->where(fn ($q) => $q->where('id_academy', $team->id_academy)),
            ],
            'jersey_number' => ['required', 'integer', 'min:1', 'max:99'],
            'is_captain' => ['nullable', 'boolean'],
            'join_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_player.required' => __('Player wajib dipilih.'),
            'jersey_number.required' => __('Nomor punggung wajib diisi.'),
            'jersey_number.max' => __('Nomor punggung maksimal 99.'),
            'join_date.required' => __('Tanggal bergabung wajib diisi.'),
        ];
    }
}
```

`app/Http/Requests/TeamPlayer/UpdateTeamPlayerRequest.php` — sama, minus `id_player`/`join_date` (tidak ikut diubah):

```php
<?php

namespace App\Http\Requests\TeamPlayer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jersey_number' => ['required', 'integer', 'min:1', 'max:99'],
            'is_captain' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'jersey_number.required' => __('Nomor punggung wajib diisi.'),
            'jersey_number.max' => __('Nomor punggung maksimal 99.'),
        ];
    }
}
```

`app/Http/Requests/TeamStaff/StoreTeamStaffRequest.php`:

```php
<?php

namespace App\Http\Requests\TeamStaff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'id_staff' => [
                'required',
                'uuid',
                Rule::exists('staff', 'id_staff')->where(fn ($q) => $q->where('id_academy', $team->id_academy)),
            ],
            'id_team_staff_position' => [
                'required',
                'uuid',
                Rule::exists('team_staff_positions', 'id_team_staff_position')->where(fn ($q) => $q->where('id_academy', $team->id_academy)),
            ],
            'join_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_staff.required' => __('Staff wajib dipilih.'),
            'id_team_staff_position.required' => __('Peran di tim wajib dipilih.'),
            'join_date.required' => __('Tanggal bergabung wajib diisi.'),
        ];
    }
}
```

`app/Http/Controllers/TeamPlayerController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamPlayer\StoreTeamPlayerRequest;
use App\Http\Requests\TeamPlayer\UpdateTeamPlayerRequest;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Services\TeamPlayerService;

class TeamPlayerController extends Controller
{
    protected TeamPlayerService $teamPlayerService;

    public function __construct(TeamPlayerService $teamPlayerService)
    {
        $this->teamPlayerService = $teamPlayerService;
    }

    public function store(StoreTeamPlayerRequest $request, Team $team)
    {
        try {
            $this->teamPlayerService->assign($team, $request->validated());

            return redirect()->route('teams.show', $team)->with('success', __('Player berhasil ditambahkan ke tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan player ke tim'), 'teams.show', [$team]);
        }
    }

    public function update(UpdateTeamPlayerRequest $request, Team $team, TeamPlayer $teamPlayer)
    {
        try {
            $this->teamPlayerService->update($teamPlayer, $request->validated());

            return redirect()->route('teams.show', $team)->with('success', __('Nomor punggung/captain berhasil diperbarui.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal memperbarui player'), 'teams.show', [$team]);
        }
    }

    public function leave(Team $team, TeamPlayer $teamPlayer)
    {
        try {
            $this->teamPlayerService->leave($teamPlayer);

            return redirect()->route('teams.show', $team)->with('success', __('Player berhasil dikeluarkan dari tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal mengeluarkan player'), 'teams.show', [$team]);
        }
    }
}
```

`app/Http/Controllers/TeamStaffController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamStaff\StoreTeamStaffRequest;
use App\Models\Team;
use App\Models\TeamStaff;
use App\Services\TeamStaffService;

class TeamStaffController extends Controller
{
    protected TeamStaffService $teamStaffService;

    public function __construct(TeamStaffService $teamStaffService)
    {
        $this->teamStaffService = $teamStaffService;
    }

    public function store(StoreTeamStaffRequest $request, Team $team)
    {
        try {
            $this->teamStaffService->assign($team, $request->validated());

            return redirect()->route('teams.show', $team)->with('success', __('Staff berhasil ditambahkan ke tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan staff ke tim'), 'teams.show', [$team]);
        }
    }

    public function leave(Team $team, TeamStaff $teamStaff)
    {
        try {
            $this->teamStaffService->leave($teamStaff);

            return redirect()->route('teams.show', $team)->with('success', __('Staff berhasil dikeluarkan dari tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal mengeluarkan staff'), 'teams.show', [$team]);
        }
    }
}
```

Routes (`routes/web.php`, nested di bawah `teams`, reuse `team.update`):

```php
/*
|--------------------------------------------------------------------------
| Team Player & Team Staff (nested di bawah Team) -- reuse team.update,
| BUKAN permission baru. TIDAK ADA route destroy -- "keluar tim" adalah
| leave_date, bukan hapus baris (issue16.md Rule/Aturan Emas).
|--------------------------------------------------------------------------
*/
Route::prefix('teams/{team}/players')
    ->name('teams.players.')
    ->middleware('permission:team.update')
    ->group(function () {
        Route::post('/', [TeamPlayerController::class, 'store'])->name('store');
        Route::put('/{teamPlayer}', [TeamPlayerController::class, 'update'])->name('update');
        Route::patch('/{teamPlayer}/leave', [TeamPlayerController::class, 'leave'])->name('leave');
    });

Route::prefix('teams/{team}/staff')
    ->name('teams.staff.')
    ->middleware('permission:team.update')
    ->group(function () {
        Route::post('/', [TeamStaffController::class, 'store'])->name('store');
        Route::patch('/{teamStaff}/leave', [TeamStaffController::class, 'leave'])->name('leave');
    });
```

**✅ Cek dulu**: `php artisan route:list --name=teams.players,teams.staff` — 5 baris total, semua middleware `permission:team.update`, tidak ada route DELETE.

---

## Tahap 14 — View `teams/show.blade.php` (Halaman Detail Team)

`resources/views/teams/show.blade.php` — tab Alpine (pola sama `resources/views/players/show.blade.php`):

```blade
@extends('layouts.app', ['page' => 'teams'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card" x-data="{ tab: 'overview' }">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ $team->name }}</h3>
                <p class="card-description">
                    {{ $team->code }} &middot; {{ $team->playerCategory->name }} &middot; {{ $team->season->name }}
                </p>
            </div>

            @can('team.update')
                <div class="card-actions">
                    <a href="{{ route('teams.edit', $team) }}" class="btn btn-secondary">{{ __('Edit Team') }}</a>
                </div>
            @endcan
        </div>

        <div class="border-b border-gray-100 px-5 dark:border-gray-800">
            <div class="flex gap-2">
                <button type="button" class="focus:outline-none" @click="tab='overview'"
                    :class="tab === 'overview' ? 'tab tab-active' : 'tab'">{{ __('Overview') }}</button>

                <button type="button" class="focus:outline-none" @click="tab='players'"
                    :class="tab === 'players' ? 'tab tab-active' : 'tab'">{{ __('Players') }}</button>

                <button type="button" class="focus:outline-none" @click="tab='staff'"
                    :class="tab === 'staff' ? 'tab tab-active' : 'tab'">{{ __('Staff') }}</button>
            </div>
        </div>

        <div class="p-5">

            {{-- Overview --}}
            <div x-show="tab==='overview'" x-cloak class="tab-panel">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Player Category') }}</span>
                        <span class="badge badge-secondary">{{ $team->playerCategory->name }}</span>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Season') }}</span>
                        <span class="table-text">{{ $team->season->name }}</span>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Team Type') }}</span>
                        <span class="table-text">{{ ucfirst($team->team_type) }}</span>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Status') }}</span>
                        @if ($team->status)
                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                        @else
                            <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Jumlah Player Aktif') }}</span>
                        <span class="table-text">{{ $teamPlayers->whereNull('leave_date')->count() }}</span>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Jumlah Staff Aktif') }}</span>
                        <span class="table-text">{{ $teamStaff->whereNull('leave_date')->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Players --}}
            <div x-show="tab==='players'" x-cloak class="tab-panel">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('add-player-form').classList.toggle('hidden')">
                            {{ __('Add Player') }}
                        </button>
                    </div>

                    <form id="add-player-form" action="{{ route('teams.players.store', $team) }}" method="POST"
                        class="mb-4 hidden rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Player') }}</label>
                                <select name="id_player" class="form-select" required>
                                    <option value="">{{ __('Pilih Player') }}</option>
                                    @foreach (\App\Models\Player::where('id_academy', $team->id_academy)->orderBy('name')->get() as $player)
                                        <option value="{{ $player->id_player }}">{{ $player->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Nomor Punggung') }}</label>
                                <input type="number" name="jersey_number" min="1" max="99" class="form-input" required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                @endcan

                <div class="table-wrapper">
                    <table class="table">
                        <thead class="table-head">
                            <tr class="table-header-row">
                                <th class="table-header-cell">{{ __('Player') }}</th>
                                <th class="table-header-cell">{{ __('Nomor') }}</th>
                                <th class="table-header-cell">{{ __('Captain') }}</th>
                                <th class="table-header-cell">{{ __('Status') }}</th>
                                <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse ($teamPlayers as $teamPlayer)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $teamPlayer->player->name }}</td>
                                    <td class="table-cell">{{ $teamPlayer->jersey_number }}</td>
                                    <td class="table-cell">
                                        @if ($teamPlayer->is_captain)
                                            <span class="badge badge-primary">{{ __('Captain') }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($teamPlayer->isActive())
                                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Keluar') }} ({{ $teamPlayer->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($teamPlayer->isActive())
                                                <form action="{{ route('teams.players.leave', [$team, $teamPlayer]) }}" method="POST"
                                                    onsubmit="return confirm('{{ __('Keluarkan player ini dari tim?') }}')" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                            <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="table-empty">{{ __('Belum ada player di tim ini.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Staff --}}
            <div x-show="tab==='staff'" x-cloak class="tab-panel">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('assign-staff-form').classList.toggle('hidden')">
                            {{ __('Assign Staff') }}
                        </button>
                    </div>

                    <form id="assign-staff-form" action="{{ route('teams.staff.store', $team) }}" method="POST"
                        class="mb-4 hidden rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Staff') }}</label>
                                <select name="id_staff" class="form-select" required>
                                    <option value="">{{ __('Pilih Staff') }}</option>
                                    @foreach (\App\Models\Staff::where('id_academy', $team->id_academy)->orderBy('full_name')->get() as $staff)
                                        <option value="{{ $staff->id_staff }}">{{ $staff->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Peran di Tim') }}</label>
                                <select name="id_team_staff_position" class="form-select" required>
                                    <option value="">{{ __('Pilih Peran') }}</option>
                                    @foreach (\App\Models\TeamStaffPosition::where('id_academy', $team->id_academy)->where('status', true)->orderBy('name')->get() as $position)
                                        <option value="{{ $position->id_team_staff_position }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                @endcan

                <div class="table-wrapper">
                    <table class="table">
                        <thead class="table-head">
                            <tr class="table-header-row">
                                <th class="table-header-cell">{{ __('Nama') }}</th>
                                <th class="table-header-cell">{{ __('Peran di Tim') }}</th>
                                <th class="table-header-cell">{{ __('Status') }}</th>
                                <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse ($teamStaff as $ts)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $ts->staff->full_name }}</td>
                                    <td class="table-cell">
                                        <span class="badge badge-secondary">{{ $ts->teamStaffPosition->name }}</span>
                                    </td>
                                    <td class="table-cell">
                                        @if ($ts->isActive())
                                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Keluar') }} ({{ $ts->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($ts->isActive())
                                                <form action="{{ route('teams.staff.leave', [$team, $ts]) }}" method="POST"
                                                    onsubmit="return confirm('{{ __('Keluarkan staff ini dari tim?') }}')" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                            <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="table-empty">{{ __('Belum ada staff di tim ini.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

@endsection
```

Catatan: form "Add Player"/"Assign Staff" sengaja toggle-show pakai `onclick` plain (bukan `x-data` Alpine tersendiri) supaya tidak konflik dengan `x-data="{ tab: ... }"` di parent — kalau mau diperhalus dengan Alpine nested state, boleh, tapi bukan wajib untuk MVP ini.

**✅ Cek dulu**: buka `/teams/{team}`, tab Overview/Players/Staff berfungsi, "Add Player" berhasil menambah baris ke tab Players, "Assign Staff" dengan posisi Head Coach dua kali berturut-turut membuat yang pertama otomatis "Keluar".

---

## Tahap 15 — Menu Sidebar

`resources/views/partials/sidebar.blade.php` — update `$footballAcademyRoutes` (baris ~108):

```php
$footballAcademyRoutes = ['players.*', 'player-types.*', 'player-categories.*', 'teams.*', 'seasons.*', 'team-staff-positions.*', 'training.*'];
```

Tambah 3 item baru **setelah** blok `{{-- Player Categories --}}` (baris ~192), **sebelum** blok `{{-- Training nanti --}}`:

```blade
{{-- Teams --}}
@can('team.view')
    <li>
        <a href="{{ route('teams.index') }}" class="menu-dropdown-item group"
            :class="{{ Route::is('teams.*') ? 'true' : 'false' }}
                ?
                'menu-dropdown-item-active' :
                'menu-dropdown-item-inactive'">
            {{ __('Teams') }}
        </a>
    </li>
@endcan

{{-- Seasons --}}
@can('season.view')
    <li>
        <a href="{{ route('seasons.index') }}" class="menu-dropdown-item group"
            :class="{{ Route::is('seasons.*') ? 'true' : 'false' }}
                ?
                'menu-dropdown-item-active' :
                'menu-dropdown-item-inactive'">
            {{ __('Seasons') }}
        </a>
    </li>
@endcan

{{-- Team Staff Positions --}}
@can('team_staff_position.view')
    <li>
        <a href="{{ route('team-staff-positions.index') }}" class="menu-dropdown-item group"
            :class="{{ Route::is('team-staff-positions.*') ? 'true' : 'false' }}
                ?
                'menu-dropdown-item-active' :
                'menu-dropdown-item-inactive'">
            {{ __('Team Staff Positions') }}
        </a>
    </li>
@endcan
```

**✅ Cek dulu**: login sebagai Owner, dropdown "Football Academy" menampilkan Players → Player Types → Player Categories → **Teams → Seasons → Team Staff Positions**.

---

## Tahap 16 — Permission Seeder & Role Template

`database/seeders/RolePermissionSeeder.php` — tambah **setelah** blok `staff_position.*` (permission `team.*` **sudah ada**, tidak disentuh):

```php
// Season
'season.view',
'season.create',
'season.update',
'season.delete',

// Team Staff Position
'team_staff_position.view',
'team_staff_position.create',
'team_staff_position.update',
'team_staff_position.delete',
```

`config/faos.php` — tambah ke `role_templates['Owner']` (pola sama `player_category.*`/`staff_position.*` -- master data, Owner-only default):

```php
'season.view', 'season.create', 'season.update', 'season.delete',
'team_staff_position.view', 'team_staff_position.create', 'team_staff_position.update', 'team_staff_position.delete',
```

`app/Support/PermissionPresenter.php` — tambah ke `$modules` (baris ~58-74, `'team' => 'Team'` **sudah ada**):

```php
'season' => 'Season',
'team_staff_position' => 'Team Staff Position',
```

**✅ Cek dulu**

```bash
php artisan db:seed --class=RolePermissionSeeder
```

```php
php artisan tinker
\Spatie\Permission\Models\Permission::where('name', 'like', 'season.%')->count(); // 4
\Spatie\Permission\Models\Permission::where('name', 'like', 'team_staff_position.%')->count(); // 4
```

---

## Tahap 17 — Multi-Language

Tambah ke `lang/en.json`:

```json
"Season": "Season",
"Seasons": "Seasons",
"Tambah Season": "Add Season",
"Edit Season": "Edit Season",
"Season berhasil ditambahkan.": "Season added successfully.",
"Season berhasil diperbarui.": "Season updated successfully.",
"Season berhasil dihapus.": "Season deleted successfully.",
"Season masih digunakan oleh tim, tidak dapat dihapus. Nonaktifkan season ini kalau sudah tidak dipakai.": "This season is still used by a team and cannot be deleted. Deactivate it instead if no longer in use.",
"Team Staff Position": "Team Staff Position",
"Team Staff Positions": "Team Staff Positions",
"Posisi ini masih digunakan oleh staff tim, tidak dapat dihapus. Nonaktifkan posisi ini kalau sudah tidak dipakai.": "This position is still used by a team staff member and cannot be deleted. Deactivate it instead if no longer in use.",
"Team": "Team",
"Teams": "Teams",
"Tambah Team": "Add Team",
"Edit Team": "Edit Team",
"Team Type": "Team Type",
"Player Category": "Player Category",
"Jumlah Player Aktif": "Active Player Count",
"Jumlah Staff Aktif": "Active Staff Count",
"Team berhasil ditambahkan.": "Team added successfully.",
"Team berhasil diperbarui.": "Team updated successfully.",
"Team berhasil dihapus.": "Team deleted successfully.",
"Tim ini masih memiliki player/staff yang aktif, keluarkan semua anggota aktif terlebih dahulu sebelum menghapus tim.": "This team still has active players/staff. Remove all active members before deleting the team.",
"Add Player": "Add Player",
"Assign Staff": "Assign Staff",
"Nomor Punggung": "Jersey Number",
"Peran di Tim": "Team Role",
"Captain": "Captain",
"Keluar": "Left",
"Keluarkan": "Remove",
"Keluarkan player ini dari tim?": "Remove this player from the team?",
"Keluarkan staff ini dari tim?": "Remove this staff member from the team?",
"Player berhasil ditambahkan ke tim.": "Player added to the team successfully.",
"Player berhasil dikeluarkan dari tim.": "Player removed from the team successfully.",
"Staff berhasil ditambahkan ke tim.": "Staff added to the team successfully.",
"Staff berhasil dikeluarkan dari tim.": "Staff removed from the team successfully.",
"Nomor punggung ini sudah dipakai pemain aktif lain di tim ini.": "This jersey number is already used by another active player on this team.",
"Player ini sudah aktif terdaftar di tim ini.": "This player is already actively registered on this team.",
"Keanggotaan yang sudah keluar dari tim tidak dapat diubah.": "Membership that has ended cannot be edited.",
"Player ini sudah tidak aktif di tim ini.": "This player is no longer active on this team.",
"Staff ini sudah tidak aktif di tim ini.": "This staff member is no longer active on this team."
```

(Cek dulu key yang kemungkinan sudah ada seperti `Status`/`Nama`/`Simpan`/`Aksi` sebelum menambah duplikat.)

**✅ Cek dulu**: `php -r "json_decode(file_get_contents('lang/en.json'), true) or die('invalid json');"`, buka `/teams?locale=en` tidak ada teks Indonesia bocor.

---

## Tahap 18 — Tests

Buat 3 file: `tests/Feature/SeasonTest.php`, `TeamStaffPositionTest.php` (pola sama `PlayerCategoryTest.php`, cukup CRUD + guard delete), dan `tests/Feature/TeamTest.php` yang jadi fokus utama:

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerCategory;
use App\Models\Role;
use App\Models\Season;
use App\Models\Staff;
use App\Models\Team;
use App\Models\TeamStaffPosition;
use App\Models\User;
use App\Services\TeamPlayerService;
use App\Services\TeamService;
use App\Services\TeamStaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsOwner(Academy $academy): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['team.view', 'team.create', 'team.update', 'team.delete'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['team.view', 'team.create', 'team.update', 'team.delete'])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);
        $this->actingAs($owner);

        return $owner;
    }

    protected function makeTeam(Academy $academy): Team
    {
        $season = Season::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        return app(TeamService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_season' => $season->id_season,
            'id_player_category' => $category->id_player_category,
            'name' => 'U15 A',
            'team_type' => 'regular',
        ]);
    }

    protected function makePlayer(Academy $academy, string $name = 'Test Player'): Player
    {
        return Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST' . random_int(10000, 99999),
            'name' => $name,
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);
    }

    public function test_generate_kode_team_otomatis(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);

        $this->assertStringStartsWith('TM', $team->code);
    }

    public function test_nomor_punggung_tidak_boleh_duplikat_di_tim_yang_sama(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $playerA = $this->makePlayer($academy, 'Player A');
        $playerB = $this->makePlayer($academy, 'Player B');

        $service = app(TeamPlayerService::class);
        $service->assign($team, ['id_player' => $playerA->id_player, 'jersey_number' => 10]);

        $this->expectException(\Exception::class);
        $service->assign($team, ['id_player' => $playerB->id_player, 'jersey_number' => 10]);
    }

    public function test_set_captain_baru_otomatis_melepas_captain_lama(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $playerA = $this->makePlayer($academy, 'Player A');
        $playerB = $this->makePlayer($academy, 'Player B');

        $service = app(TeamPlayerService::class);
        $tpA = $service->assign($team, ['id_player' => $playerA->id_player, 'jersey_number' => 10, 'is_captain' => true]);
        $service->assign($team, ['id_player' => $playerB->id_player, 'jersey_number' => 7, 'is_captain' => true]);

        $this->assertFalse($tpA->fresh()->is_captain);
    }

    public function test_assign_head_coach_baru_otomatis_mengeluarkan_head_coach_lama(): void
    {
        $academy = Academy::factory()->create();
        app(\App\Services\TeamStaffPositionService::class)->createDefaultTeamStaffPositions($academy);
        $team = $this->makeTeam($academy);

        $staffA = Staff::factory()->create(['id_academy' => $academy->id_academy]);
        $staffB = Staff::factory()->create(['id_academy' => $academy->id_academy]);
        $headCoach = TeamStaffPosition::where('id_academy', $academy->id_academy)->where('code', 'HC')->first();

        $service = app(TeamStaffService::class);
        $tsA = $service->assign($team, ['id_staff' => $staffA->id_staff, 'id_team_staff_position' => $headCoach->id_team_staff_position]);
        $service->assign($team, ['id_staff' => $staffB->id_staff, 'id_team_staff_position' => $headCoach->id_team_staff_position]);

        $this->assertNotNull($tsA->fresh()->leave_date);
    }

    public function test_delete_team_ditolak_kalau_masih_ada_player_aktif(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $player = $this->makePlayer($academy);

        app(TeamPlayerService::class)->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);

        $this->expectException(\Exception::class);
        app(TeamService::class)->delete($team);
    }

    public function test_delete_team_sukses_setelah_semua_anggota_keluar_dan_soft_delete(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $player = $this->makePlayer($academy);

        $teamPlayerService = app(TeamPlayerService::class);
        $tp = $teamPlayerService->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);
        $teamPlayerService->leave($tp);

        app(TeamService::class)->delete($team);

        $this->assertSoftDeleted('teams', ['id_team' => $team->id_team]);
    }

    public function test_halaman_index_dan_detail_team_bisa_diakses(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);

        $this->actingAsOwner($academy);

        $this->get(route('teams.index'))->assertOk();
        $this->get(route('teams.show', $team))->assertOk();
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=SeasonTest
php artisan test --filter=TeamStaffPositionTest
php artisan test --filter=TeamTest
php artisan test
```

Semua test baru lulus, baseline kegagalan bawaan Breeze (5 failure + 2 error) tidak bertambah.

---

## Tahap 19 — Dokumentasi

**`docs/permission-reference.md`**:

1. **Hapus** baris `| Team | team.view, team.create, team.update, team.delete |` dari tabel [Permission Belum Dipakai Module Manapun](#permission-belum-dipakai-module-manapun) — module ini sekarang **sudah dibangun**.

2. Tambah 3 section baru **sebelum** heading `## Permission Belum Dipakai Module Manapun`:

```markdown
## Module: Season

Status: **✅ Implemented**

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `season.view` | Lihat daftar season | `seasons.index` (route middleware) + menu sidebar |
| `season.create` | Tambah season baru | `seasons.create`, `seasons.store` |
| `season.update` | Ubah nama/rentang tanggal/status season | `seasons.edit`, `seasons.update` |
| `season.delete` | Hapus season (kalau tidak dipakai tim manapun) | `seasons.destroy` |

Catatan:
- Isolasi antar academy memakai `AcademyScope`, bukan Policy — akses season academy lain menghasilkan 404.
- Default: 4 permission ini cuma di-assign ke role **Owner** (`config('faos.role_templates')`), sama seperti `player_category.*`/`staff_position.*`.
- **Sengaja tidak ada** `createDefaultSeasons()` otomatis tiap academy baru (beda dari Player Category/Employment Type) — musim berganti tiap tahun, tidak ada default universal yang masuk akal, Owner membuat Season pertamanya sendiri (lihat `issue16.md` Aturan Emas).

---

## Module: Team Staff Position

Status: **✅ Implemented**

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `team_staff_position.view` | Lihat daftar peran staff tim | `team-staff-positions.index` |
| `team_staff_position.create` | Tambah peran baru | `team-staff-positions.create`, `.store` |
| `team_staff_position.update` | Ubah kode/nama/status peran | `team-staff-positions.edit`, `.update` |
| `team_staff_position.delete` | Hapus peran (kalau tidak dipakai Team Staff manapun) | `team-staff-positions.destroy` |

Catatan:
- **Bukan** `StaffPosition` — dimensi berbeda (jabatan kepegawaian Academy vs peran fungsional di 1 tim tertentu). Lihat `issue16.md` Bagian 2b.
- Default 6 posisi (Head Coach/Assistant Coach/Goalkeeper Coach/Fitness Coach/Team Manager/Physiotherapist) otomatis dibuat tiap academy baru dari `config('faos.team_staff_position_templates')`, pola sama Employment Type/Staff Position.
- Kode `HC` (Head Coach) adalah konvensi tetap yang dipakai `TeamStaffService` untuk guard "1 Head Coach aktif per tim" — kalau academy mengubah/menghapus posisi ber-kode ini, guard tersebut otomatis tidak berlaku (bukan error).

---

## Module: Team (+ Team Player, Team Staff)

Status: **✅ Implemented**

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `team.view` | Lihat daftar & detail tim | `teams.index`, `teams.show` (route middleware) + menu sidebar |
| `team.create` | Tambah tim baru | `teams.create`, `teams.store` |
| `team.update` | Ubah data tim, assign/keluarkan Player & Staff dari tim | `teams.edit`, `teams.update`, `teams.players.*`, `teams.staff.*` |
| `team.delete` | Hapus (archive) tim | `teams.destroy` |

Catatan:
- Permission `team.*` **sudah ada** di seeder/role template sejak awal (placeholder) — brief `issue16.md` cuma menggerbang route dengannya, tidak menambah permission baru.
- **Team Player**/**Team Staff** (sub-resource nested `teams/{team}/players|staff`) **reuse** `team.view`/`team.update` — bukan permission terpisah, pola sama Employment Contract reuse `staff.*` (`issue12.md`).
- `Team` pakai `SoftDeletes` (archive), bukan hard delete — beda dari kebanyakan master data lain di dokumen ini. Guard: tidak bisa di-archive kalau masih ada Team Player/Team Staff **aktif** (`leave_date IS NULL`).
- Tidak ada route `DELETE` untuk Team Player/Team Staff — "keluar tim" adalah `leave_date` terisi (histori permanen), bukan baris dihapus.
```

3. Update kalimat ringkasan di section `## Summary` (baris terakhir dokumen) — tambahkan Season, Team Staff Position, dan Team ke daftar module yang **sudah digerbang penuh**, hapus dari daftar "menunggu module-nya dibangun".

**✅ Cek dulu**: baca ulang bagian yang diedit, cross-check `php artisan route:list --name=teams,seasons,team-staff-positions` dengan tabel permission di atas.

---

## Ringkasan Alur Akhir

```text
Football Academy > Teams / Seasons / Team Staff Positions (menu baru)
│
├── Season & TeamStaffPosition -- master data (pola PlayerCategory/StaffPosition)
│     TeamStaffPosition dapat 6 default seed per academy baru (kode "HC" dst)
│
├── Team (id_season, id_player_category, code auto "TM001", SoftDeletes)
│     reuse permission team.* yang SUDAH ADA
│     delete = archive, ditolak kalau masih ada anggota aktif
│
└── Team Detail (tab Overview/Players/Staff)
      │
      ├── Add Player -> TeamPlayerService::assign()
      │     guard: nomor punggung unik (di antara yang AKTIF), no duplikat aktif
      │     is_captain=true -> otomatis lepas captain lama (row lock team)
      │
      ├── Assign Staff -> TeamStaffService::assign()
      │     posisi kode "HC" -> otomatis keluarkan Head Coach aktif lama
      │
      └── "Keluarkan" (Player/Staff) -> leave_date = now()
            TIDAK PERNAH hapus baris -- histori roster tetap utuh
```
