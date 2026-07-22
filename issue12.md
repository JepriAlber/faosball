# Brief: Modul Employment Contract

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: `issue9.md` (Employment Type), `issue10.md` (Staff Position), `issue11.md` (Staff) **wajib sudah selesai & merged** — brief ini **mengubah struktur data Staff yang sudah ada** (memindahkan sebagian kolom `staff` ke tabel baru). Baca juga `docs/module-standard.md`, `docs/development-guide.md`, `docs/multi-tenancy.md`, `docs/authorization.md`, `docs/frontend-standard.md` (termasuk subsection *Wajib: Filter Academy — khusus Super Admin* yang baru ditambahkan). Modul referensi paling mirip: `app/Models/Staff.php`, `app/Services/StaffService.php`, `app/Http/Controllers/StaffController.php`, `resources/views/staff/*.blade.php` — **plus** `app/Http/Controllers/StaffAccountController.php` (pola sub-resource nested di bawah Staff, karena Employment Contract juga sub-resource: `staff/{staff}/contracts/*`, bukan module top-level baru).
> **Bukan bagian dari 3 brief "Office"** (`issue9`–`issue11`) — ini brief lanjutan setelah "Office" selesai, TAPI mengubah beberapa file yang dibuat 3 brief itu. Baca [Bagian 0](#0-aturan-emas) dan [Peta Perubahan File](#3-peta-perubahan-file) dengan teliti sebelum mulai — banyak breaking change ke kode yang sudah ada, bukan cuma penambahan file baru.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 15** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus. Tahap 1–2 (migration) adalah yang paling berisiko (mengubah tabel `staff` yang sudah punya data) — jangan diskip validasinya. Tahap 14–15 adalah fitur **Salary Visibility** (masking nominal gaji berbasis permission) — dikerjakan PALING TERAKHIR, setelah seluruh tampilan gaji (Tahap 6/7/11) sudah ada, supaya tinggal "menyuntikkan" masking ke tempat yang sudah jadi, bukan bolak-balik.
> **Scope**: Entitas `EmploymentContract` — histori hubungan kerja Staff↔Academy, dengan state machine 5 status (Draft/Active/Completed/Terminated/Cancelled) dan rule "maksimal 1 Active + 1 Draft per staff". **Migrasi field** `id_employment_type`/`id_staff_position`/`join_date`/`end_date`/`salary`/`status` dari tabel `staff` ke `employment_contracts` (lihat [Bagian 2a](#2a-kenapa-field-employment-pindah-dari-staff-ke-contract)). **Salary Visibility**: permission baru `salary.view` — user TANPA permission ini cuma bisa melihat gaji **miliknya sendiri** (staff yang akun login-nya = user yang sedang login), gaji staff lain tampil tersamar (`*****`), berlaku di **setiap** tempat nominal gaji ditampilkan (lihat [Bagian 2e](#2e-skema-visibilitas-gaji-salary-masking)). **Bukan scope**: upload dokumen kontrak (PDF/scan — ditunda, sama seperti Staff tidak ada upload dokumen di `issue11.md`), auto-transition status via scheduled job (ditunda — project ini belum punya infrastruktur `Schedule::command()` sama sekali, transisi status **manual** oleh admin), field alasan/reason khusus untuk aksi Terminate (kalau dibutuhkan, brief terpisah), item menu sidebar baru (Contract diakses lewat halaman *Staff Detail*, bukan dropdown "Office"), histori/audit log siapa yang pernah membuka gaji siapa (kalau dibutuhkan, brief terpisah).

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Hapus kolom `staff.id_employment_type`/`id_staff_position`/`join_date`/`end_date`/`salary`/`status` tanpa backfill dulu | Tabel `staff` **sudah punya data** (beda dengan `staff`/`employment_types`/`staff_positions` di 3 brief sebelumnya yang tabelnya baru). Migration Tahap 2 WAJIB insert 1 `employment_contracts` per baris `staff` yang ada **sebelum** kolom lama di-drop, kalau tidak data histori hilang permanen | [Tahap 2](#tahap-2--migration-backfill--drop-kolom-lama-di-staff) |
| Enforce "1 Active + 1 Draft per staff" pakai unique index DB biasa | MySQL tidak punya *partial/filtered unique index* (`UNIQUE WHERE status='active'`) seperti Postgres — constraint itu tidak bisa dibuat bersih di MySQL. Guard ditegakkan di **Service layer** dalam `DB::transaction()` + `lockForUpdate()` pada baris `staff` (dipakai sebagai mutex per-staff), pola sama `StaffService::generateStaffCode()` | [Tahap 5](#tahap-5--employmentcontractservice) |
| Bikin route/method `destroy()` untuk Contract | Rule 3: **Contract lama tidak boleh dihapus** — ini histori permanen. Modul ini SENGAJA tidak punya delete sama sekali, beda dari semua module CRUD sebelumnya | [Tahap 6](#tahap-6--controller), [Tahap 8](#tahap-8--routes--permission) |
| Izinkan field kontrak (posisi/type/gaji/tanggal) diedit setelah status bukan Draft | Rule 4: **kontrak baru tidak mengubah histori lama, yang berubah cuma status**. Form Edit Contract (`UpdateEmploymentContractRequest`/`EmploymentContractService::updateDraft()`) WAJIB menolak kalau `$contract->status !== 'draft'` | [Tahap 5](#tahap-5--employmentcontractservice), [Tahap 6](#tahap-6--controller) |
| Biarkan `EmploymentType::staff()`/`StaffPosition::staff()` relasi lama tetap ada tanpa diubah | Relasi itu (`hasMany(Staff::class, 'id_employment_type', ...)`) akan **error/silently wrong** begitu kolom `staff.id_employment_type` di-drop di Tahap 2 — dipakai guard delete `EmploymentTypeService::delete()`/`StaffPositionService::delete()`. WAJIB diganti relasi baru `contracts()` menunjuk ke `EmploymentContract`, dan guard delete-nya ikut disesuaikan | [Tahap 10](#tahap-10--guard-delete-employmenttypestaffposition-menunjuk-ke-contract) |
| Bikin dropdown menu baru "Contract" di sidebar "Office" | Contract bukan entitas berdiri sendiri di navigasi — dia diakses dari halaman **Staff Detail** (tab "Riwayat Kontrak" + tombol aksi), sama seperti Staff Account diakses dari halaman Staff, bukan dari sidebar sendiri | [Tahap 11](#tahap-11--views) |
| Buat permission baru `employment_contract.*` | **Reuse** `staff.create`/`staff.update` yang sudah ada — mengelola Contract adalah bagian dari mengelola data kepegawaian Staff, pola sama Staff Account yang reuse `user.create`/`user.update` alih-alih `staff_account.*` (`issue11.md` Tahap 8) | [Tahap 8](#tahap-8--routes--permission) |
| Contract renewal (dibuat lewat halaman Staff Detail) langsung berstatus Active | Cuma kontrak **pertama** (dibuat otomatis bersamaan `StaffController@store`) yang langsung Active — kontrak susulan (renewal/promosi) WAJIB mulai dari **Draft**, admin baru meng-*activate*-nya belakangan (biasanya pas kontrak lama mau berakhir). Meng-*activate* Draft otomatis meng-*complete*-kan Contract Active lama milik staff yang sama, dalam transaksi yang sama | [2c](#2c-alur-activate-draft-otomatis-menutup-contract-active-lama), [Tahap 5](#tahap-5--employmentcontractservice) |
| Format ulang nominal gaji manual (`'Rp ' . number_format($salary, ...)`) di tiap Blade yang butuh menampilkannya | Masking gaji WAJIB konsisten di **semua** tempat (Aturan Emas berikutnya) — pakai satu Component `<x-salary-amount>` (Tahap 14) di setiap lokasi, JANGAN tulis ulang logic format+masking manual per file, gampang ada 1 lokasi yang lupa dimasking | [Tahap 14](#tahap-14--permission-salaryview-staffpolicy-x-salary-amount-component), [Tahap 15](#tahap-15--terapkan-masking-ke-semua-tampilan--form-gaji) |
| Pakai `$data['salary'] ?? null` di `EmploymentContractService::updateDraft()` setelah field gaji disembunyikan dari form (karena user tidak punya `salary.view`) | Kalau field `salary` tidak dirender sama sekali di form (disembunyikan karena masking), request TIDAK mengirim key itu — `?? null` akan diam-diam **menghapus gaji yang sudah tersimpan**. WAJIB pakai `array_key_exists('salary', $data)` supaya "field tidak dikirim" (dipertahankan) beda dari "field dikirim kosong" (memang sengaja dikosongkan oleh user yang BERWENANG) | [Tahap 15](#tahap-15--terapkan-masking-ke-semua-tampilan--form-gaji) |

---

## 1. Konteks & Tujuan

Modul "Office" (`issue9`–`issue11`) sudah membangun Staff sebagai identitas pegawai, tapi field kepegawaian (`id_employment_type`, `id_staff_position`, `join_date`, `end_date`, `salary`, `status`) masih menempel langsung di tabel `staff` — cuma menyimpan **1 nilai "saat ini"**, tanpa jejak histori. Begitu staff dipromosikan, pindah posisi, atau kontraknya diperpanjang dengan gaji baru, nilai lama hilang begitu saja ditimpa `UPDATE`.

Brief ini memperbaiki itu dengan `EmploymentContract` — setiap baris merepresentasikan **satu periode hubungan kerja** (posisi + jenis pekerjaan + tanggal + gaji tertentu), dengan status yang menceritakan riwayat hidupnya sendiri:

```text
Staff (identitas, TIDAK BERUBAH sepanjang masa kerja)
│
└── EmploymentContract (BANYAK, 1 baris = 1 periode kerja)
    ├── Draft       -- dibuat, belum berlaku (maks. 1 per staff)
    ├── Active      -- sedang berlaku (maks. 1 per staff, WAJIB tepat 1 begitu staff bekerja)
    ├── Completed   -- selesai sesuai masa berlaku
    ├── Terminated  -- dihentikan sebelum waktunya
    └── Cancelled   -- Draft yang batal, tidak pernah berlaku
```

## 2. Cara Kerja Solusi

### 2a. Kenapa field employment pindah dari `staff` ke Contract

`join_date`/`end_date`/`salary`/`id_staff_position`/`id_employment_type` bukan atribut staff sebagai **orang**, itu atribut dari **satu periode kontrak kerja** — begitu dia diperpanjang atau dipromosikan, nilai-nilai ini berubah tapi versi lamanya tetap harus bisa dilihat sebagai histori. Menyimpannya di `staff` (1 baris = 1 nilai) berarti tidak ada tempat untuk versi lama begitu di-`UPDATE`. Karena itu field-field ini pindah total ke `employment_contracts`; `staff` hanya menyisakan field identitas (nama, biodata, kontak, foto). Nilai "posisi/gaji staff **saat ini**" di halaman Staff (index/show) diambil dengan relasi `$staff->activeContract` — bukan kolom langsung.

Konsekuensinya, `staff.status` (`active/inactive/resigned`) juga **dihapus** — status kepegawaian sekarang diturunkan (*derived*, tidak disimpan) dari ada/tidaknya `activeContract`: staff dianggap "Aktif" kalau punya 1 Contract berstatus Active, "Nonaktif" kalau tidak. Tab status di halaman index Staff yang tadinya 3 nilai (Aktif/Nonaktif/Resign) jadi 2 (Aktif/Nonaktif) — histori kenapa dia jadi nonaktif (selesai kontrak vs diberhentikan vs belum pernah dikontrak) tetap terlihat lengkap di tab "Riwayat Kontrak" halaman Staff Detail.

### 2b. Kontrak pertama dibuat otomatis, bukan langkah terpisah

Form "Tambah Staff" (`staff/create.blade.php`) **tetap** menanyakan Employment Type/Staff Position/Tanggal Bergabung/Gaji (persis field yang sebelumnya ada) — bukan karena field itu balik ke `staff`, tapi karena data itu dipakai `StaffService::create()` untuk langsung membuat **Contract pertama berstatus Active** dalam transaksi yang sama. Staff tidak pernah ada dalam kondisi "menggantung" tanpa kontrak sama sekali. `staff/edit.blade.php` sebaliknya **tidak lagi** menanyakan field-field ini — perubahan employment (posisi baru, gaji naik, dst.) dilakukan lewat Contract baru di halaman Staff Detail, bukan lewat form Edit Staff.

### 2c. Alur *activate* Draft otomatis menutup Contract Active lama

Rule 1 ("1 Active per staff") dan Rule 4 ("kontrak baru tidak mengubah histori lama") ketemu di sini: begitu admin meng-klik "Aktifkan" pada sebuah Draft, `EmploymentContractService::activate()` — dalam **transaksi yang sama** — mencari Contract Active milik staff yang sama (kalau ada) dan mengubah statusnya jadi **Completed**, baru kemudian Draft yang di-*activate* berubah jadi **Active**. Data (gaji/tanggal/posisi) Contract lama itu sendiri **tidak disentuh sama sekali** — cuma kolom `status`-nya yang berubah, sesuai Rule 4.

### 2d. Guard "1 Active + 1 Draft per staff" — row lock, bukan constraint DB

Karena MySQL tidak punya *partial unique index*, guard ditegakkan di `EmploymentContractService` dengan mengunci baris `staff` (bukan `employment_contracts`) sebagai mutex per-staff sebelum membuat/meng-*activate* Contract:

```php
DB::transaction(function () use ($staff, $data) {

    // Lock baris staff sebagai mutex -- staff yang sama tidak bisa diproses
    // 2 request bersamaan (race condition saat 2 admin klik "Buat Kontrak" nyaris bersamaan).
    Staff::withoutGlobalScopes()->whereKey($staff->id_staff)->lockForUpdate()->first();

    if (EmploymentContract::where('id_staff', $staff->id_staff)->where('status', 'draft')->exists()) {
        throw new \Exception(__('Staff ini sudah punya kontrak Draft, selesaikan atau batalkan dulu sebelum membuat yang baru.'));
    }

    // ... insert Draft baru
});
```

### 2e. Skema Visibilitas Gaji (Salary Masking)

Nominal gaji adalah data sensitif — brief ini menambahkan permission baru **`salary.view`** yang mengontrol siapa yang boleh melihat nominal gaji **siapapun**. User yang tidak punya permission ini tetap boleh melihat nominal gajinya **sendiri** (kalau staff itu punya akun login yang sedang dipakai login), tapi gaji staff LAIN selalu tampil tersamar `*****`, di **manapun** nominal gaji itu muncul (ringkasan Contract Active di halaman Edit Staff, tab Kepegawaian & Riwayat Kontrak di halaman Show Staff, form Edit Contract).

```text
Lihat gaji staff X, oleh user U:

  U py permission `salary.view`?
    YA  -> tampil nominal asli (berlaku utk SEMUA staff, termasuk milik sendiri)
    TIDAK ->
        staff X adalah "diri sendiri" U (staff.id_user === U.id_user)?
          YA  -> tampil nominal asli
          TIDAK -> tampil "*****"
```

Aturan ini diterapkan lewat 2 potongan yang saling melengkapi:

1. **`StaffPolicy@viewSalary`** (Tahap 14) — keputusan ya/tidak, dipanggil `@can('viewSalary', $staff)`/`Auth::user()->can('viewSalary', $staff)` di manapun dibutuhkan.
2. **`<x-salary-amount>`** (Tahap 14) — Blade Component yang membungkus keputusan itu + format angka jadi satu pemanggilan (`<x-salary-amount :staff="$staff" :amount="$contract->salary" />`), supaya tidak ada 1 tempat pun yang menulis ulang logic format+masking manual dan lupa menyamarkan (lihat Aturan Emas).

Super Admin tidak pernah kena masking — `Gate::before()` di `AppServiceProvider` sudah meloloskan Super Admin dari `@can()` manapun sebelum sampai ke `StaffPolicy`, konsisten dengan seluruh permission lain di app ini.

**Konsekuensi ke form input (bukan cuma tampilan baca)**: form Edit Contract (Tahap 11e) menampilkan nilai gaji LAMA di dalam `<input>` — kalau field itu tetap dirender apa adanya untuk user tanpa `salary.view`, masking di atas jadi percuma (nilainya kebaca langsung dari HTML/`value` attribute). Karena itu Tahap 15 mengganti field `salary` di form Edit Contract jadi **tampilan tersamar non-editable** (bukan `<input>`) kalau user tidak berwenang — dan submit form itu WAJIB mempertahankan nilai gaji lama di database (bukan menghapusnya jadi `null` cuma karena field-nya tidak ikut dikirim), lihat Aturan Emas soal `array_key_exists()`. Form Buat Staff & Buat Contract baru (data gaji **baru**, belum ada nilai lama yang perlu disembunyikan) memakai aturan lebih sederhana: field `salary` disembunyikan total kalau user tidak punya `salary.view` — kalau seseorang tidak berwenang *melihat* gaji, dia juga tidak diberi kendali menentukan angka yang tidak bisa dia verifikasi sendiri; gaji staf itu tetap `null` sampai user yang berwenang mengisinya lewat Contract baru.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/..._create_employment_contracts_table.php` | 🆕 Baru | 1 |
| `database/migrations/..._migrate_staff_employment_fields_to_contracts.php` | 🆕 Baru — backfill data + drop kolom lama `staff` | 2 |
| `app/Models/EmploymentContract.php` | 🆕 Baru | 3 |
| `database/factories/EmploymentContractFactory.php` | 🆕 Baru | 3 |
| `app/Models/Staff.php` | ✏️ Hapus relasi `employmentType()`/`position()`, fillable dikurangi, tambah relasi `contracts()`/`activeContract()`/`draftContract()` | 4 |
| `app/Models/EmploymentType.php` | ✏️ Relasi `staff()` → `contracts()` (menunjuk `EmploymentContract`) | 4 |
| `app/Models/StaffPosition.php` | ✏️ Relasi `staff()` → `contracts()` (menunjuk `EmploymentContract`) | 4 |
| `app/Services/EmploymentContractService.php` | 🆕 Baru | 5 |
| `app/Services/StaffService.php` | ✏️ `create()`/`update()`/`applyFilters()`/`statusCounts()` dirombak (lihat Tahap 6) | 6 |
| `app/Http/Controllers/EmploymentContractController.php` | 🆕 Baru | 7 |
| `app/Http/Controllers/StaffController.php` | ✏️ `index()`/`create()`/`edit()`/`show()` menyesuaikan sumber data employment | 7 |
| `app/Http/Requests/EmploymentContract/StoreEmploymentContractRequest.php` | 🆕 Baru | 8 |
| `app/Http/Requests/EmploymentContract/UpdateEmploymentContractRequest.php` | 🆕 Baru | 8 |
| `app/Http/Requests/Staff/StoreStaffRequest.php` | ✏️ Hapus rule `status`, field employment lain tetap | 8 |
| `app/Http/Requests/Staff/UpdateStaffRequest.php` | ✏️ Hapus rule `id_employment_type`/`id_staff_position`/`join_date`/`end_date`/`salary`/`status` | 8 |
| `routes/web.php` | ✏️ Tambah nested resource `staff/{staff}/contracts/*` | 9 |
| `app/Services/EmploymentTypeService.php` | ✏️ `delete()` & `paginate()` pakai relasi `contracts()` | 10 |
| `app/Services/StaffPositionService.php` | ✏️ `delete()` & `paginate()` pakai relasi `contracts()` | 10 |
| `resources/views/employment-types/index.blade.php` | ✏️ `$employmentType->staff_count` → `contracts_count` | 10 |
| `resources/views/staff-positions/index.blade.php` | ✏️ `$staffPosition->staff_count` → `contracts_count` | 10 |
| `resources/views/staff/create.blade.php` | ✏️ Hapus dropdown "Status Kepegawaian" | 11 |
| `resources/views/staff/edit.blade.php` | ✏️ Hapus semua field employment, tambah ringkasan Contract Active (read-only) | 11 |
| `resources/views/staff/index.blade.php` | ✏️ Kolom/filter Employment Type & Staff Position via `activeContract`, tab status jadi 2 nilai | 11 |
| `resources/views/staff/show.blade.php` | ✏️ Tab "Kepegawaian" tampilkan `activeContract`, tambah tab "Riwayat Kontrak" | 11 |
| `resources/views/staff/contracts/create.blade.php` | 🆕 Baru | 11 |
| `resources/views/staff/contracts/edit.blade.php` | 🆕 Baru | 11 |
| `lang/en.json` | ✏️ Entry baru | 12 |
| `tests/Feature/EmploymentContractTest.php` | 🆕 Baru | 13 |
| `docs/permission-reference.md` | ✏️ Tambah sub-section Employment Contract di bawah Module Staff | 13 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah permission `salary.view` | 14 |
| `config/faos.php` | ✏️ Tambah `salary.view` ke `role_templates.Owner` & `role_templates.Finance` | 14 |
| `app/Policies/StaffPolicy.php` | 🆕 Baru | 14 |
| `app/View/Components/SalaryAmount.php` | 🆕 Baru | 14 |
| `resources/views/components/salary-amount.blade.php` | 🆕 Baru | 14 |
| `app/Http/Controllers/StaffController.php` | ✏️ Kirim `canViewSalary` ke `create()`, cek `viewSalary` di `edit()`/`show()` | 15 |
| `app/Http/Controllers/EmploymentContractController.php` | ✏️ Kirim `canViewSalary` ke `create()`, cek `viewSalary` di `edit()` | 15 |
| `app/Services/EmploymentContractService.php` | ✏️ `updateDraft()` pakai `array_key_exists()` untuk `salary` | 15 |
| `resources/views/staff/create.blade.php` | ✏️ Sembunyikan field Gaji kalau `!$canViewSalary` | 15 |
| `resources/views/staff/edit.blade.php` | ✏️ Ringkasan gaji pakai `<x-salary-amount>` | 15 |
| `resources/views/staff/show.blade.php` | ✏️ Tab Kepegawaian & Riwayat Kontrak pakai `<x-salary-amount>` | 15 |
| `resources/views/staff/contracts/create.blade.php` | ✏️ Sembunyikan field Gaji kalau `!$canViewSalary` | 15 |
| `resources/views/staff/contracts/edit.blade.php` | ✏️ Field Gaji jadi tampilan tersamar non-editable kalau `!$canViewSalary` | 15 |
| `lang/en.json` | ✏️ Entry baru (Tahap 14–15) | 15 |
| `tests/Feature/SalaryVisibilityTest.php` | 🆕 Baru | 15 |
| `docs/permission-reference.md` | ✏️ Tambah entry permission `salary.view` | 15 |

---

## Tahap 1 — Migration: `employment_contracts`

```bash
php artisan make:migration create_employment_contracts_table
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
        Schema::create('employment_contracts', function (Blueprint $table) {

            $table->uuid('id_employment_contract')->primary();

            $table->uuid('id_academy');
            $table->uuid('id_staff');

            /*
            |--------------------------------------------------------------------------
            | Klasifikasi kontrak (WAJIB -- 1 baris = 1 periode kerja yang utuh)
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_employment_type');
            $table->uuid('id_staff_position');

            $table->string('contract_code', 40)->unique();

            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = kontrak tidak berbatas waktu (permanent)
            $table->decimal('salary', 12, 2)->nullable();

            $table->enum('status', ['draft', 'active', 'completed', 'terminated', 'cancelled'])
                ->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
            // TIDAK ada softDeletes() -- Contract memang tidak pernah dihapus (Rule 3),
            // jadi tidak perlu mekanisme delete/restore sama sekali.

            $table->index('id_academy');
            $table->index('id_staff');
            $table->index('id_employment_type');
            $table->index('id_staff_position');
            $table->index(['id_staff', 'status'], 'employment_contracts_staff_status_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_staff')->references('id_staff')->on('staff')->cascadeOnDelete();

            // restrictOnDelete() -- alasan sama seperti staff.id_employment_type/id_staff_position
            // di issue11.md: kolom WAJIB terisi, FK tidak boleh diam-diam jadi NULL.
            $table->foreign('id_employment_type')->references('id_employment_type')->on('employment_types')->restrictOnDelete();
            $table->foreign('id_staff_position')->references('id_staff_position')->on('staff_positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table employment_contracts
```

`id_employment_type`/`id_staff_position`/`start_date` harus **NOT NULL**. `end_date`/`salary`/`notes` nullable. Tidak ada kolom `deleted_at`.

---

## Tahap 2 — Migration: Backfill + Drop Kolom Lama di `staff`

**Ini tahap paling berisiko di brief ini** — tabel `staff` sudah punya data (dibuat & mungkin sudah diisi di `issue11.md`). Urutan wajib: **insert dulu ke `employment_contracts`, baru drop kolom lama** — kalau dibalik, data hilang permanen.

```bash
php artisan make:migration migrate_staff_employment_fields_to_contracts
```

```php
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
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan tinker
```

```php
\App\Models\EmploymentContract::count(); // harus sama dengan jumlah baris staff sebelum migration ini
\Illuminate\Support\Facades\Schema::hasColumn('staff', 'id_employment_type'); // harus false
\Illuminate\Support\Facades\Schema::hasColumn('staff', 'status'); // harus false
```

Kalau sebelumnya sudah ada data test/seed di tabel `staff` (dari testing manual `issue11.md`), pastikan jumlah baris `employment_contracts` cocok — kalau meleset, **jangan lanjut**, cek ulang query backfill.

---

## Tahap 3 — Model `EmploymentContract`

`app/Models/EmploymentContract.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentContract extends FaosModel
{
    use HasFactory;

    protected $table = 'employment_contracts';
    protected $primaryKey = 'id_employment_contract';

    protected $fillable = [
        'id_academy', 'id_staff', 'id_employment_type', 'id_staff_position',
        'contract_code', 'start_date', 'end_date', 'salary', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff', 'id_staff');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'id_employment_type', 'id_employment_type');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(StaffPosition::class, 'id_staff_position', 'id_staff_position');
    }
}
```

`database/factories/EmploymentContractFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\EmploymentContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentContract>
 */
class EmploymentContractFactory extends Factory
{
    protected $model = EmploymentContract::class;

    public function definition(): array
    {
        return [
            'contract_code' => strtoupper(fake()->unique()->bothify('CTR-####-C1')),
            'start_date' => now()->subMonths(6),
            'end_date' => null,
            'salary' => fake()->randomFloat(2, 3000000, 10000000),
            'status' => 'active',
            'notes' => null,
        ];
    }
}
```

**✅ Cek dulu**: `php artisan tinker` → `(new \App\Models\EmploymentContract)->getFillable()` harus memuat semua field di atas.

---

## Tahap 4 — Update Model `Staff`, `EmploymentType`, `StaffPosition`

`app/Models/Staff.php` — hapus relasi `employmentType()`/`position()` (kolom sumbernya sudah tidak ada), kurangi `$fillable`, tambah relasi Contract:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends FaosModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';
    protected $primaryKey = 'id_staff';

    protected $fillable = [
        'id_academy', 'id_user',
        'staff_code', 'photo', 'full_name', 'nickname',
        'gender', 'birth_place', 'birth_date', 'nationality', 'religion', 'blood_type', 'marital_status',
        'phone', 'email', 'address', 'city', 'province', 'postal_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Seluruh histori kontrak staff ini, terbaru duluan.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class, 'id_staff', 'id_staff')->latest();
    }

    /**
     * Kontrak yang SEDANG berlaku. Maksimal 1 baris -- dijamin
     * EmploymentContractService (lihat Tahap 5), bukan constraint DB
     * (lihat issue12.md Bagian 2d).
     */
    public function activeContract(): HasOne
    {
        return $this->hasOne(EmploymentContract::class, 'id_staff', 'id_staff')->where('status', 'active');
    }

    /**
     * Kontrak yang sudah dibuat tapi belum berlaku (kalau ada).
     * Maksimal 1 baris, dijamin Service yang sama.
     */
    public function draftContract(): HasOne
    {
        return $this->hasOne(EmploymentContract::class, 'id_staff', 'id_staff')->where('status', 'draft');
    }

    /**
     * Accessor supaya komponen shared yang generik mengasumsikan atribut
     * `name` (mis. <x-account.dropdown>, dipakai bersama Player) tetap
     * berfungsi tanpa modifikasi -- TIDAK mengganti nama kolom `full_name`
     * jadi `name` (lihat issue11.md Bagian 4.2).
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }
}
```

`app/Models/EmploymentType.php` — ganti relasi `staff()`:

```php
    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class, 'id_employment_type', 'id_employment_type');
    }
```

(hapus method `staff()` yang lama, `use Illuminate\Database\Eloquent\Relations\HasMany;` sudah ada dari `issue11.md` Tahap 9 — tidak perlu import baru.)

`app/Models/StaffPosition.php` — sama, ganti relasi `staff()`:

```php
    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class, 'id_staff_position', 'id_staff_position');
    }
```

**✅ Cek dulu**: `php -l` ketiga file tidak ada error. `php artisan tinker` → `\App\Models\EmploymentType::first()->contracts` tidak melempar error (boleh collection kosong).

---

## Tahap 5 — `EmploymentContractService`

`app/Services/EmploymentContractService.php`:

```php
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
                'salary' => $data['salary'] ?? null,
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
```

> Tidak ada method `delete()` sama sekali di Service ini — konsisten dengan Aturan Emas (Rule 3, tidak ada route/controller `destroy()`).

**✅ Cek dulu**: `php -l app/Services/EmploymentContractService.php` tidak ada error. Verifikasi penuh menyusul Tahap 13 (butuh Controller + test).

---

## Tahap 6 — Update `StaffService`

`app/Services/StaffService.php` — inject `EmploymentContractService`, sesuaikan `create()`/`update()`/`applyFilters()`/`statusCounts()`:

Tambah import & constructor:

```php
use App\Services\EmploymentContractService;

class StaffService
{
    protected AcademyService $academyService;
    protected EmploymentContractService $employmentContractService;

    public function __construct(AcademyService $academyService, EmploymentContractService $employmentContractService)
    {
        $this->academyService = $academyService;
        $this->employmentContractService = $employmentContractService;
    }
```

`create()` — hapus field employment dari payload `Staff::create()`, panggil `createFirstContract()` setelahnya dalam transaksi yang sama:

```php
    public function create(array $data): Staff
    {
        return DB::transaction(function () use ($data) {

            $academy = $this->resolveAcademy($data);
            $staffCode = $this->generateStaffCode($academy);

            $photo = isset($data['photo']) ? $this->uploadPhoto($data['photo'], $staffCode) : null;

            $staff = Staff::create([
                'id_academy' => $academy->id_academy,
                'staff_code' => $staffCode,
                'photo' => $photo,
                'full_name' => $data['full_name'],
                'nickname' => $data['nickname'] ?? null,
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'religion' => $data['religion'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Kontrak pertama -- langsung Active (issue12.md Bagian 2b).
            $this->employmentContractService->createFirstContract($staff, $data);

            return $staff;
        });
    }
```

`update()` — hapus seluruh field employment dari payload (identitas/biodata/kontak/foto saja):

```php
    public function update(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data) {

            $oldPhoto = $staff->photo;
            $photo = $oldPhoto;

            if (isset($data['photo'])) {
                $photo = $this->uploadPhoto($data['photo'], $staff->staff_code);
            }

            $staff->update([
                // id_academy & staff_code sengaja TIDAK ikut diubah.
                'photo' => $photo,
                'full_name' => $data['full_name'],
                'nickname' => $data['nickname'] ?? null,
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'religion' => $data['religion'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['photo']) && $oldPhoto) {
                $this->deletePhoto($oldPhoto);
            }

            return $staff;
        });
    }
```

`applyFilters()` — filter Employment Type/Staff Position/status sekarang lewat relasi Contract:

```php
    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('staff_code', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }

        if (! empty($filters['id_employment_type'])) {
            $query->whereHas('contracts', fn ($q) => $q
                ->where('status', 'active')
                ->where('id_employment_type', $filters['id_employment_type']));
        }

        if (! empty($filters['id_staff_position'])) {
            $query->whereHas('contracts', fn ($q) => $q
                ->where('status', 'active')
                ->where('id_staff_position', $filters['id_staff_position']));
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        // Status sekarang cuma 2 nilai, diturunkan dari ada/tidaknya
        // Contract Active -- lihat issue12.md Bagian 2a.
        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            match ($filters['status']) {
                'active' => $query->has('activeContract'),
                'inactive' => $query->doesntHave('activeContract'),
                default => null,
            };
        }
    }
```

`statusCounts()` — 2 nilai, bukan 3:

```php
    public function statusCounts(array $filters = []): array
    {
        $countFor = function (string $status) use ($filters) {

            $query = Staff::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $status === 'active' ? $query->has('activeContract')->count() : $query->doesntHave('activeContract')->count();
        };

        return [
            'active' => $countFor('active'),
            'inactive' => $countFor('inactive'),
        ];
    }
```

`paginate()` — eager-load `activeContract` sekalian relasinya, ganti `employmentType`/`position` (sudah tidak ada langsung di `Staff`):

```php
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Staff::with(['academy', 'user', 'activeContract.employmentType', 'activeContract.position']);

        $this->applyFilters($query, $filters);
        // ... sisanya SAMA seperti sebelumnya (sort/paginate)
    }
```

**✅ Cek dulu**: `php -l app/Services/StaffService.php` tidak ada error. Verifikasi penuh menyusul Tahap 13.

---

## Tahap 7 — `EmploymentContractController` + Update `StaffController`

`app/Http/Controllers/EmploymentContractController.php` — nested di bawah Staff (pola sama `StaffAccountController`), **tanpa** `destroy()`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentContract\StoreEmploymentContractRequest;
use App\Http\Requests\EmploymentContract\UpdateEmploymentContractRequest;
use App\Models\EmploymentContract;
use App\Models\Staff;
use App\Services\EmploymentContractService;
use App\Services\EmploymentTypeService;
use App\Services\StaffPositionService;

class EmploymentContractController extends Controller
{
    protected EmploymentContractService $employmentContractService;
    protected EmploymentTypeService $employmentTypeService;
    protected StaffPositionService $staffPositionService;

    public function __construct(
        EmploymentContractService $employmentContractService,
        EmploymentTypeService $employmentTypeService,
        StaffPositionService $staffPositionService
    ) {
        $this->employmentContractService = $employmentContractService;
        $this->employmentTypeService = $employmentTypeService;
        $this->staffPositionService = $staffPositionService;
    }

    public function create(Staff $staff)
    {
        return view('staff.contracts.create', [
            'title' => __('Buat Kontrak Baru'),
            'staff' => $staff,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name, 'url' => route('staff.show', $staff)],
                ['label' => __('Buat Kontrak Baru')],
            ],
            'employmentTypes' => $this->employmentTypeService->selectable($staff->id_academy),
            'staffPositions' => $this->staffPositionService->selectable($staff->id_academy),
        ]);
    }

    public function store(StoreEmploymentContractRequest $request, Staff $staff)
    {
        try {

            $this->employmentContractService->createDraft($staff, $request->validated());

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', __('Kontrak baru berhasil dibuat sebagai Draft.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membuat kontrak'));
        }
    }

    public function edit(Staff $staff, EmploymentContract $contract)
    {
        return view('staff.contracts.edit', [
            'title' => __('Edit Kontrak'),
            'staff' => $staff,
            'contract' => $contract,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name, 'url' => route('staff.show', $staff)],
                ['label' => __('Edit Kontrak')],
            ],
            'employmentTypes' => $this->employmentTypeService->selectable($staff->id_academy, $contract->id_employment_type),
            'staffPositions' => $this->staffPositionService->selectable($staff->id_academy, $contract->id_staff_position),
        ]);
    }

    public function update(UpdateEmploymentContractRequest $request, Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->updateDraft($contract, $request->validated());

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', __('Kontrak berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui kontrak'), 'staff.show', [$staff]);
        }
    }

    public function activate(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->activate($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak berhasil diaktifkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengaktifkan kontrak'), 'staff.show', [$staff]);
        }
    }

    public function complete(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->complete($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak ditandai selesai.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menyelesaikan kontrak'), 'staff.show', [$staff]);
        }
    }

    public function terminate(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->terminate($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak berhasil dihentikan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghentikan kontrak'), 'staff.show', [$staff]);
        }
    }

    public function cancel(Staff $staff, EmploymentContract $contract)
    {
        try {

            $this->employmentContractService->cancel($contract);

            return redirect()->route('staff.show', $staff)->with('success', __('Kontrak Draft berhasil dibatalkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membatalkan kontrak'), 'staff.show', [$staff]);
        }
    }
}
```

`app/Http/Controllers/StaffController.php` — sesuaikan `show()`/`edit()` (hapus dependency `employmentTypes`/`staffPositions` untuk edit karena form Edit Staff sudah tidak butuh itu; `index()`/`create()` **tetap** butuh untuk filter & form create):

```php
    public function show(Staff $staff)
    {
        $staff->load(['academy', 'user.roles', 'contracts.employmentType', 'contracts.position']);

        return view('staff.show', [
            'title' => $staff->full_name,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name],
            ],
            'staff' => $staff,
        ]);
    }

    public function edit(Staff $staff)
    {
        return view('staff.edit', [
            'title' => __('Edit Staff'),
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => __('Edit Staff')],
            ],
            'staff' => $staff->load('activeContract.employmentType', 'activeContract.position'),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }
```

(`index()`/`create()` tidak berubah -- masih memuat `employmentTypeOptions`/`staffPositionOptions`/`employmentTypes`/`staffPositions` seperti semula, karena filter index & form create masih butuh dropdown itu.)

**✅ Cek dulu**: `php -l` kedua Controller tidak ada error.

---

## Tahap 8 — Form Requests

`app/Http/Requests/EmploymentContract/StoreEmploymentContractRequest.php`:

```php
<?php

namespace App\Http\Requests\EmploymentContract;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmploymentContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // $this->staff -- route-model-binding {staff}, sama pola StoreStaffAccountRequest.
        $academyId = $this->staff->id_academy;

        return [
            'id_employment_type' => [
                'required', 'uuid',
                \Illuminate\Validation\Rule::exists('employment_types', 'id_employment_type')
                    ->where(fn ($q) => $q->where('id_academy', $academyId)->where('status', true)),
            ],
            'id_staff_position' => [
                'required', 'uuid',
                \Illuminate\Validation\Rule::exists('staff_positions', 'id_staff_position')
                    ->where(fn ($q) => $q->where('id_academy', $academyId)->where('status', true)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_employment_type.required' => __('Employment type wajib dipilih.'),
            'id_employment_type.exists' => __('Employment type tidak valid.'),
            'id_staff_position.required' => __('Staff position wajib dipilih.'),
            'id_staff_position.exists' => __('Staff position tidak valid.'),
            'start_date.required' => __('Tanggal mulai kontrak wajib diisi.'),
            'start_date.date' => __('Tanggal mulai kontrak tidak valid.'),
            'end_date.after_or_equal' => __('Tanggal berakhir kontrak tidak boleh sebelum tanggal mulai.'),
            'salary.numeric' => __('Gaji harus berupa angka.'),
            'salary.min' => __('Gaji tidak boleh negatif.'),
        ];
    }
}
```

`app/Http/Requests/EmploymentContract/UpdateEmploymentContractRequest.php` — sama persis, **kecuali** exists-check TANPA filter `status: true` (pola sama `UpdateStaffRequest` di `issue11.md` — kontrak Draft yang sudah terlanjur pakai type/position yang kini dinonaktifkan tetap harus bisa disimpan ulang):

```php
<?php

namespace App\Http\Requests\EmploymentContract;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmploymentContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_employment_type' => ['required', 'uuid', 'exists:employment_types,id_employment_type'],
            'id_staff_position' => ['required', 'uuid', 'exists:staff_positions,id_staff_position'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_employment_type.required' => __('Employment type wajib dipilih.'),
            'id_employment_type.exists' => __('Employment type tidak valid.'),
            'id_staff_position.required' => __('Staff position wajib dipilih.'),
            'id_staff_position.exists' => __('Staff position tidak valid.'),
            'start_date.required' => __('Tanggal mulai kontrak wajib diisi.'),
            'start_date.date' => __('Tanggal mulai kontrak tidak valid.'),
            'end_date.after_or_equal' => __('Tanggal berakhir kontrak tidak boleh sebelum tanggal mulai.'),
            'salary.numeric' => __('Gaji harus berupa angka.'),
            'salary.min' => __('Gaji tidak boleh negatif.'),
        ];
    }
}
```

`app/Http/Requests/Staff/StoreStaffRequest.php` — **hapus** rule `status` (tidak ada lagi field ini di form create):

```php
// HAPUS baris ini dari rules():
'status' => ['nullable', 'in:active,inactive,resigned'],
// HAPUS juga messages() 'status.in' => ...
```

`app/Http/Requests/Staff/UpdateStaffRequest.php` — **hapus** rule `id_employment_type`, `id_staff_position`, `join_date`, `end_date`, `salary`, `status` beserta pesannya (field-field itu sudah tidak ada di form Edit Staff sama sekali).

**✅ Cek dulu**: `php -l` keempat file di atas tidak ada error.

---

## Tahap 9 — Routes

`routes/web.php` — tambah import:

```php
use App\Http\Controllers\EmploymentContractController;
```

Tambahkan block route **setelah** block `staff` resource (dari `issue11.md`):

```php
    /*
    |--------------------------------------------------------------------------
    | Employment Contract Management (nested di bawah Staff)
    |--------------------------------------------------------------------------
    | TIDAK ADA route destroy -- Contract tidak pernah dihapus (Rule 3).
    | Reuse permission staff.create/staff.update, BUKAN permission baru.
    */
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

> Parameter route `{contract}` (bukan `{employment_contract}`) supaya URL tetap ringkas (`staff/{staff}/contracts/{contract}/edit`) — route model binding otomatis tetap resolve ke `EmploymentContract` karena nama parameter method Controller (`EmploymentContract $contract`) yang menentukan, bukan segmen URL-nya.

**✅ Cek dulu**

```bash
php artisan route:list --name=staff.contracts
```

Harus tampil 8 route: `create/store/edit/update/activate/complete/terminate/cancel`. **Tidak ada** `destroy`.

---

## Tahap 10 — Guard Delete `EmploymentType`/`StaffPosition` Menunjuk ke Contract

`app/Services/EmploymentTypeService.php`:

```php
    public function paginate(?int $perPage = null)
    {
        return EmploymentType::with('academy')
            ->withCount('contracts')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    public function delete(EmploymentType $employmentType): bool
    {
        return DB::transaction(function () use ($employmentType) {

            if ($employmentType->contracts()->exists()) {
                throw new \Exception(__('Employment type masih digunakan oleh kontrak staff, tidak dapat dihapus. Nonaktifkan employment type ini kalau sudah tidak dipakai.'));
            }

            return $employmentType->delete();
        });
    }
```

`app/Services/StaffPositionService.php` — sama pola:

```php
    public function paginate(?int $perPage = null)
    {
        return StaffPosition::with(['academy', 'role'])
            ->withCount('contracts')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    public function delete(StaffPosition $staffPosition): bool
    {
        return DB::transaction(function () use ($staffPosition) {

            if ($staffPosition->contracts()->exists()) {
                throw new \Exception(__('Staff position masih digunakan oleh kontrak staff, tidak dapat dihapus. Nonaktifkan staff position ini kalau sudah tidak dipakai.'));
            }

            return $staffPosition->delete();
        });
    }
```

`resources/views/employment-types/index.blade.php` & `resources/views/staff-positions/index.blade.php` — `withCount('contracts')` menghasilkan atribut `contracts_count`, bukan `staff_count` lagi. Cari-ganti (ada di 2 tempat tiap file: table & card list):

```blade
:disabled="$employmentType->staff_count > 0"
```
menjadi
```blade
:disabled="$employmentType->contracts_count > 0"
```

(sama untuk `$staffPosition->staff_count` → `$staffPosition->contracts_count`).

`lang/en.json` — pesan lama (dari `issue11.md`) diganti teksnya, jadi butuh key BARU (bukan reuse key lama):

```json
"Employment type masih digunakan oleh kontrak staff, tidak dapat dihapus. Nonaktifkan employment type ini kalau sudah tidak dipakai.": "This employment type is still used by staff contracts and cannot be deleted. Deactivate it instead if it's no longer needed.",
"Staff position masih digunakan oleh kontrak staff, tidak dapat dihapus. Nonaktifkan staff position ini kalau sudah tidak dipakai.": "This staff position is still used by staff contracts and cannot be deleted. Deactivate it instead if it's no longer needed."
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create();
$et = \App\Models\EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
$sp = \App\Models\StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
$staff = \App\Models\Staff::factory()->create(['id_academy' => $academy->id_academy]);
\App\Models\EmploymentContract::factory()->create([
    'id_academy' => $academy->id_academy, 'id_staff' => $staff->id_staff,
    'id_employment_type' => $et->id_employment_type, 'id_staff_position' => $sp->id_staff_position,
]);

app(\App\Services\EmploymentTypeService::class)->delete($et);
// harus throw Exception "...masih digunakan oleh kontrak staff..."
```

---

## Tahap 11 — Views

**Tujuan**: sesuaikan 4 view Staff yang sudah ada + 2 view baru untuk Contract.

### 11a. `resources/views/staff/create.blade.php`

Hapus blok dropdown "Status Kepegawaian" (`<select name="status">`) sepenuhnya — field employment lain (Employment Type/Staff Position/Tanggal Bergabung/Tanggal Keluar/Gaji) **tetap ada**, cuma sekarang dipakai untuk membuat Contract pertama, bukan disimpan langsung ke `staff` (lihat Bagian 2b).

### 11b. `resources/views/staff/edit.blade.php`

Hapus SEMUA field employment (Employment Type/Staff Position/Tanggal Bergabung/Tanggal Keluar/Gaji/Status Kepegawaian) dari form. Sebagai gantinya, tambahkan kotak ringkasan read-only di kolom kanan (posisi yang sama seperti field-field yang dihapus tadi):

```blade
<div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">
    <h4 class="section-title mb-3">{{ __('Kontrak Aktif Saat Ini') }}</h4>

    @if ($staff->activeContract)
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('Staff Position') }}</span>
                <span class="font-medium">{{ $staff->activeContract->position->name ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('Employment Type') }}</span>
                <span class="font-medium">{{ $staff->activeContract->employmentType->name ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('Gaji') }}</span>
                <span class="font-medium">{{ $staff->activeContract->salary !== null ? 'Rp ' . number_format($staff->activeContract->salary, 0, ',', '.') : '-' }}</span>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-400">
            {{ __('Untuk mengubah posisi, gaji, atau membuat kontrak baru, kelola lewat halaman Detail Staff.') }}
        </p>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Staff ini belum punya kontrak aktif.') }}</p>
    @endif
</div>
```

### 11c. `resources/views/staff/index.blade.php`

- Kolom/field "Employment Type"/"Staff Position" di tabel & card list: `{{ $item->employmentType->name ?? '-' }}` → `{{ $item->activeContract->employmentType->name ?? '-' }}` (sama untuk `position`).
- Badge "Akun" (`$item->id_user`) TIDAK berubah.
- Tabs status: dari 4 nilai (`''/active/inactive/resigned`) jadi **3** (`''/active/inactive`) — ikuti pola `<x-table.tabs>` yang sudah ada, cuma hapus opsi `resigned`.

### 11d. `resources/views/staff/show.blade.php`

Tab "Kepegawaian" (yang lama, langsung baca `$staff->employmentType`/`position`/`join_date`/dst.) diganti sumber datanya jadi `$staff->activeContract` (kalau null, tampilkan pesan "belum ada kontrak aktif" alih-alih field kosong semua).

Tambahkan tab ke-4 **"Riwayat Kontrak"** (pola sama 3 tab yang sudah ada: `x-data="{ tab: 'profile' }"`, tombol baru `@click="tab='contracts'"`) — isi tabel/list seluruh `$staff->contracts` (sudah di-load di `StaffController@show`):

```blade
<div x-show="tab==='contracts'" x-cloak class="tab-panel">

    <div class="mb-4 flex justify-end">
        @can('staff.update')
            <a href="{{ route('staff.contracts.create', $staff) }}" class="btn btn-primary btn-sm">
                {{ __('Buat Kontrak Baru') }}
            </a>
        @endcan
    </div>

    <div class="space-y-3">
        @forelse ($staff->contracts as $contract)
            <div class="rounded-lg border border-gray-100 p-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="table-title">{{ $contract->contract_code }}</span>
                        <span class="table-subtitle">
                            {{ $contract->position->name ?? '-' }} &middot; {{ $contract->employmentType->name ?? '-' }}
                        </span>
                    </div>

                    @php
                        $contractStatusBadge = match ($contract->status) {
                            'draft' => ['label' => __('Draft'), 'class' => 'badge-secondary'],
                            'active' => ['label' => __('Active'), 'class' => 'badge-success'],
                            'completed' => ['label' => __('Completed'), 'class' => 'badge-primary'],
                            'terminated' => ['label' => __('Terminated'), 'class' => 'badge-danger'],
                            'cancelled' => ['label' => __('Cancelled'), 'class' => 'badge-secondary'],
                        };
                    @endphp
                    <span class="badge {{ $contractStatusBadge['class'] }}">{{ $contractStatusBadge['label'] }}</span>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-400 md:grid-cols-4">
                    <span>{{ __('Mulai') }}: {{ $contract->start_date?->format('d M Y') }}</span>
                    <span>{{ __('Berakhir') }}: {{ $contract->end_date?->format('d M Y') ?? '-' }}</span>
                    <span>{{ __('Gaji') }}: {{ $contract->salary !== null ? 'Rp ' . number_format($contract->salary, 0, ',', '.') : '-' }}</span>
                </div>

                {{-- Aksi transisi -- tampil sesuai status, ikuti pola konfirmasi
                     x-button.delete (dispatch event + x-modal), atau cukup
                     <form> polos dengan confirm() bawaan browser untuk brief ini. --}}
                <div class="mt-3 flex gap-2">
                    @can('staff.update')
                        @if ($contract->status === 'draft')
                            <a href="{{ route('staff.contracts.edit', [$staff, $contract]) }}" class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">...</a>

                            <form action="{{ route('staff.contracts.activate', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Aktifkan kontrak ini?') }}')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success btn-sm">{{ __('Aktifkan') }}</button>
                            </form>

                            <form action="{{ route('staff.contracts.cancel', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Batalkan kontrak Draft ini?') }}')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Batalkan') }}</button>
                            </form>
                        @elseif ($contract->status === 'active')
                            <form action="{{ route('staff.contracts.complete', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Tandai kontrak ini selesai?') }}')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-primary btn-sm">{{ __('Selesaikan') }}</button>
                            </form>

                            <form action="{{ route('staff.contracts.terminate', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Hentikan kontrak ini sebelum waktunya?') }}')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('Hentikan') }}</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada kontrak.') }}</p>
        @endforelse
    </div>

</div>
```

> Markup aksi transisi di atas cuma sketsa struktur (form + confirm bawaan browser) supaya brief ini tidak melebar ke desain modal baru — implementasi final boleh diselaraskan ke pola `x-modal`/`$dispatch` yang sudah ada di codebase (`<x-modal.status>`, dst.) kalau mau konsisten dengan interaksi lain, itu keputusan teknis kecil yang aman diputuskan saat coding.

### 11e. `resources/views/staff/contracts/create.blade.php` & `edit.blade.php`

Struktur form sama seperti field employment yang **dihapus** dari `staff/create.blade.php` (Tahap 11a) — Employment Type, Staff Position, Tanggal Mulai (`start_date`), Tanggal Berakhir (`end_date`), Gaji (`salary`), Catatan (`notes`). Ikuti pola `form-group`/`form-select`/`@error` yang sudah berulang kali dipakai (`issue9`–`issue11`). `create.blade.php` action `route('staff.contracts.store', $staff)`; `edit.blade.php` action `route('staff.contracts.update', [$staff, $contract])` + `@method('PUT')`, value/`old()` pakai default `$contract->xxx`.

**✅ Cek dulu**: buka `/staff/create` → field employment masih ada (minus status). Submit → staff baru + 1 Contract Active otomatis (cek tab "Riwayat Kontrak" di halaman Detail). Buka `/staff/{id}/edit` → field employment sudah hilang, ada kotak ringkasan Contract Aktif. Klik "Buat Kontrak Baru" di tab Riwayat Kontrak → Draft baru muncul. Klik "Aktifkan" → Contract lama (kalau ada) otomatis Completed, yang baru jadi Active.

---

## Tahap 12 — `lang/en.json`

Tambahkan entry baru (cek dulu duplikat — banyak istilah seperti "Gaji"/"Catatan"/"Employment Type" sudah ada dari `issue11.md`):

```json
"Buat Kontrak Baru": "Create New Contract",
"Kontrak Aktif Saat Ini": "Current Active Contract",
"Untuk mengubah posisi, gaji, atau membuat kontrak baru, kelola lewat halaman Detail Staff.": "To change position, salary, or create a new contract, manage it from the Staff Detail page.",
"Staff ini belum punya kontrak aktif.": "This staff member doesn't have an active contract yet.",
"Riwayat Kontrak": "Contract History",
"Belum ada kontrak.": "No contracts yet.",
"Mulai": "Start",
"Berakhir": "End",
"Draft": "Draft",
"Active": "Active",
"Completed": "Completed",
"Terminated": "Terminated",
"Cancelled": "Cancelled",
"Aktifkan": "Activate",
"Batalkan": "Cancel",
"Selesaikan": "Complete",
"Hentikan": "Terminate",
"Aktifkan kontrak ini?": "Activate this contract?",
"Batalkan kontrak Draft ini?": "Cancel this draft contract?",
"Tandai kontrak ini selesai?": "Mark this contract as completed?",
"Hentikan kontrak ini sebelum waktunya?": "Terminate this contract early?",
"Kontrak baru berhasil dibuat sebagai Draft.": "New contract created successfully as Draft.",
"Kontrak berhasil diperbarui.": "Contract updated successfully.",
"Kontrak berhasil diaktifkan.": "Contract activated successfully.",
"Kontrak ditandai selesai.": "Contract marked as completed.",
"Kontrak berhasil dihentikan.": "Contract terminated successfully.",
"Kontrak Draft berhasil dibatalkan.": "Draft contract cancelled successfully.",
"Staff ini sudah punya kontrak Draft, selesaikan atau batalkan dulu sebelum membuat yang baru.": "This staff member already has a draft contract -- complete or cancel it before creating a new one.",
"Kontrak yang sudah tidak berstatus Draft tidak dapat diubah datanya.": "A contract that is no longer in Draft status cannot have its data edited.",
"Hanya kontrak berstatus Draft yang dapat diaktifkan.": "Only draft contracts can be activated.",
"Hanya kontrak berstatus Active yang dapat diselesaikan.": "Only active contracts can be marked as completed.",
"Hanya kontrak berstatus Active yang dapat dihentikan.": "Only active contracts can be terminated.",
"Hanya kontrak berstatus Draft yang dapat dibatalkan.": "Only draft contracts can be cancelled.",
"Tanggal mulai kontrak wajib diisi.": "Contract start date is required.",
"Tanggal mulai kontrak tidak valid.": "Invalid contract start date.",
"Tanggal berakhir kontrak tidak boleh sebelum tanggal mulai.": "Contract end date cannot be before the start date.",
"Gagal membuat kontrak": "Failed to create contract",
"Gagal memperbarui kontrak": "Failed to update contract",
"Gagal mengaktifkan kontrak": "Failed to activate contract",
"Gagal menyelesaikan kontrak": "Failed to complete contract",
"Gagal menghentikan kontrak": "Failed to terminate contract",
"Gagal membatalkan kontrak": "Failed to cancel contract"
```

**✅ Cek dulu**: ganti locale Inggris → seluruh halaman Staff (create/edit/index/show) + halaman Contract tampil Bahasa Inggris.

---

## Tahap 13 — Test & Dokumentasi

### 13a. `tests/Feature/EmploymentContractTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentContract;
use App\Models\EmploymentType;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Services\EmploymentContractService;
use App\Services\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentContractTest extends TestCase
{
    use RefreshDatabase;

    protected function makePrereqs(Academy $academy): array
    {
        return [
            'employmentType' => EmploymentType::factory()->create(['id_academy' => $academy->id_academy]),
            'staffPosition' => StaffPosition::factory()->create(['id_academy' => $academy->id_academy]),
        ];
    }

    public function test_create_staff_otomatis_membuat_contract_pertama_berstatus_active(): void
    {
        $academy = Academy::factory()->create(['code' => 'FCY']);
        $prereqs = $this->makePrereqs($academy);

        $staff = app(StaffService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'full_name' => 'Dewi Lestari',
            'gender' => 'female',
            'birth_place' => 'Bandung',
            'birth_date' => '1992-05-05',
            'phone' => '081234567891',
        ]);

        $this->assertSame(1, $staff->contracts()->count());
        $this->assertSame('active', $staff->activeContract->status);
        $this->assertStringEndsWith('-C1', $staff->activeContract->contract_code);
    }

    public function test_tidak_bisa_buat_draft_kedua_untuk_staff_yang_sama(): void
    {
        $academy = Academy::factory()->create();
        $prereqs = $this->makePrereqs($academy);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $service = app(EmploymentContractService::class);

        $data = [
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'start_date' => now()->addMonth(),
        ];

        $service->createDraft($staff, $data);

        $this->expectException(\Exception::class);
        $service->createDraft($staff, $data);
    }

    public function test_activate_draft_otomatis_menutup_contract_active_lama(): void
    {
        $academy = Academy::factory()->create();
        $prereqs = $this->makePrereqs($academy);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $oldActive = EmploymentContract::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_staff' => $staff->id_staff,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'status' => 'active',
        ]);

        $service = app(EmploymentContractService::class);

        $draft = $service->createDraft($staff, [
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'start_date' => now(),
        ]);

        $service->activate($draft);

        $this->assertSame('completed', $oldActive->fresh()->status);
        $this->assertSame('active', $draft->fresh()->status);
    }

    public function test_edit_ditolak_kalau_contract_bukan_draft(): void
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

        $this->expectException(\Exception::class);

        app(EmploymentContractService::class)->updateDraft($contract, [
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'start_date' => now(),
        ]);
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=EmploymentContractTest
php artisan test
```

Seluruh test pass, termasuk full suite (baseline + test module `Office` + test baru, tanpa regresi). Perhatikan khusus `StaffTest`/`StaffAccountTest` (`issue11.md`) — kemungkinan besar ada test yang langsung membuat `Staff` dengan field employment lewat factory/array yang sekarang sudah tidak ada di tabel `staff`; sesuaikan test itu supaya tetap valid (pindahkan assersi employment ke `$staff->activeContract`).

### 13b. `docs/permission-reference.md`

Tambahkan sub-section baru di bawah section **Module: Staff** (setelah *Sub-module: Staff Account*):

```markdown
### Sub-module: Employment Contract (histori kontrak kerja staff)

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `staff.update` | Buat/edit/aktifkan/selesaikan/hentikan/batalkan kontrak | `staff.contracts.*` (route middleware) |

Catatan:
- **Reuse** permission `staff.update` — bukan permission baru `employment_contract.*` (pola sama Staff Account yang reuse `user.create`/`user.update`).
- Tidak ada permission/route untuk hapus kontrak — Contract adalah histori permanen (Rule 3), tidak pernah dihapus lewat UI maupun API.
```

**✅ Cek dulu**: `docs/permission-reference.md` section baru muncul.

---

## Tahap 14 — Permission `salary.view`, `StaffPolicy`, `<x-salary-amount>` Component

**Tujuan**: infrastruktur Salary Visibility (lihat Bagian 2e) — permission, keputusan otorisasi, dan Component tampilan. Tahap ini belum mengubah 1 pun view yang sudah ada (itu Tahap 15) — cuma menyiapkan alatnya dulu.

### 14a. Permission — `database/seeders/RolePermissionSeeder.php`

Tambahkan ke array `$permissions`, setelah blok `// Staff` (dari `issue11.md`):

```php
            // Salary Visibility
            'salary.view',
```

### 14b. `config/faos.php`

Tambahkan ke `role_templates.Owner` (Owner secara wajar berhak lihat gaji seluruh staff-nya) dan `role_templates.Finance` (Finance mengurus hal finansial, termasuk payroll):

```php
        'Owner' => [
            // ...rule yang sudah ada...
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
            'salary.view',
        ],

        'Finance' => [
            'payment.view', 'payment.create', 'payment.update', 'payment.report',
            'report.view', 'report.export',
            'salary.view',
        ],
```

> Role lain (Coach/Staff/Player/Parent) SENGAJA tidak diberi `salary.view` secara default — kalau suatu academy butuh role lain juga bisa lihat gaji, delegasikan lewat halaman Role Management, jangan tambah manual di sini.

### 14c. `app/Policies/StaffPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    /**
     * Lihat nominal gaji staff ini secara utuh (bukan tersamar "*****").
     *
     * User dengan permission salary.view melihat gaji SIAPAPUN. User TANPA
     * permission itu cuma boleh melihat gajinya SENDIRI -- staff.id_user
     * sama dengan user yang sedang login. Super Admin tidak pernah sampai
     * ke method ini -- sudah lolos lebih dulu lewat Gate::before()
     * (AppServiceProvider), sama seperti RolePolicy.
     */
    public function viewSalary(User $user, Staff $staff): bool
    {
        if ($user->can('salary.view')) {
            return true;
        }

        return $staff->id_user !== null && $staff->id_user === $user->id_user;
    }
}
```

> Tidak perlu didaftarkan manual ke `Gate::policies()` mana pun -- Laravel auto-discover Policy lewat konvensi nama (`App\Models\Staff` -> `App\Policies\StaffPolicy`), persis seperti `RolePolicy` yang sudah ada di project ini (cek `app/Providers/AppServiceProvider.php`, tidak ada pendaftaran manual di situ juga).

### 14d. `app/View/Components/SalaryAmount.php`

```php
<?php

namespace App\View\Components;

use App\Models\Staff;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class SalaryAmount extends Component
{
    public string $display;

    public function __construct(Staff $staff, ?float $amount)
    {
        $this->display = match (true) {
            $amount === null => '-',
            ! (Auth::user()?->can('viewSalary', $staff) ?? false) => '*****',
            default => 'Rp ' . number_format($amount, 0, ',', '.'),
        };
    }

    public function render(): View
    {
        return view('components.salary-amount');
    }
}
```

`resources/views/components/salary-amount.blade.php`:

```blade
<span>{{ $display }}</span>
```

> Urutan pengecekan SENGAJA: `null` dicek duluan (tidak ada apapun yang perlu disamarkan kalau gajinya memang belum diisi -- tampil "-" untuk siapapun), baru setelah itu cek otorisasi. Kalau dibalik, user tanpa akses akan melihat "*****" untuk baris yang sebenarnya kosong, membocorkan informasi "oh staff ini punya nominal gaji" secara tidak langsung padahal sebenarnya tidak ada.

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
```

Harus sukses. `php artisan tinker` → `\Spatie\Permission\Models\Permission::where('name', 'salary.view')->exists()` → `true`. `php -l` ketiga file PHP di atas tidak ada error.

---

## Tahap 15 — Terapkan Masking ke Semua Tampilan & Form Gaji

**Tujuan**: menyuntikkan `<x-salary-amount>`/`StaffPolicy` ke SEMUA tempat nominal gaji muncul (dibangun di Tahap 6/7/11), plus menutup celah form Edit Contract (lihat Bagian 2e & Aturan Emas).

### 15a. `app/Services/EmploymentContractService.php` — `updateDraft()`

Ganti baris `'salary' => $data['salary'] ?? null,` jadi:

```php
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
```

### 15b. `StaffController` & `EmploymentContractController`

Tambahkan `canViewSalary` ke data yang dikirim ke form CREATE (Staff & Contract) -- dipakai untuk sembunyikan field Gaji total kalau `false`:

```php
// StaffController@create
'canViewSalary' => $this->academyService->isSuperAdmin() || auth()->user()->can('salary.view'),

// EmploymentContractController@create
'canViewSalary' => auth()->user()->can('salary.view'),
```

> Super Admin ditambahkan eksplisit di `StaffController@create` sebagai jaga-jaga kalau ada pemanggilan `can()` di context tanpa `Gate::before()` ikut jalan (harusnya selalu jalan, tapi eksplisit lebih aman untuk field sensitif) -- `EmploymentContractController@create` tidak perlu karena Super Admin memang selalu lolos `can()` apapun lewat `Gate::before()`.

Untuk `StaffController@edit`/`@show` dan `EmploymentContractController@edit`, kirim hasil `viewSalary` Policy (butuh objek `$staff` yang sudah pasti ada di context edit/show, beda dari create):

```php
// StaffController@edit
'canViewSalary' => auth()->user()->can('viewSalary', $staff),

// StaffController@show -- tambahkan ke payload view yang sudah ada
'canViewSalary' => auth()->user()->can('viewSalary', $staff),

// EmploymentContractController@edit
'canViewSalary' => auth()->user()->can('viewSalary', $staff),
```

### 15c. Views — form CREATE (field Gaji disembunyikan total)

`resources/views/staff/create.blade.php` & `resources/views/staff/contracts/create.blade.php` -- bungkus `<div class="form-group">` field Gaji:

```blade
@if ($canViewSalary)
    <div class="form-group">
        <label class="form-label">{{ __('Gaji') }}</label>
        <input type="number" name="salary" value="{{ old('salary') }}" step="1000" min="0" class="form-input @error('salary') form-danger @enderror">
        @error('salary')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>
@endif
```

### 15d. View — form EDIT Contract (field Gaji jadi tampilan tersamar, bukan dihapus)

`resources/views/staff/contracts/edit.blade.php` -- BEDA dari form create: field ini menyimpan nilai LAMA yang harus tetap tersamar (bukan disembunyikan begitu saja, supaya admin tetap tahu field itu ADA, cuma tidak berwenang melihat/mengubah isinya):

```blade
@if ($canViewSalary)
    <div class="form-group">
        <label class="form-label">{{ __('Gaji') }}</label>
        <input type="number" name="salary" value="{{ old('salary', $contract->salary) }}" step="1000" min="0" class="form-input @error('salary') form-danger @enderror">
        @error('salary')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>
@else
    <div class="form-group">
        <label class="form-label">{{ __('Gaji') }}</label>
        <p class="form-input bg-gray-50 dark:bg-gray-800">*****</p>
        <p class="mt-1 text-xs text-gray-400">{{ __('Anda tidak memiliki akses untuk melihat atau mengubah gaji staff ini.') }}</p>
    </div>
@endif
```

> Perhatikan: TIDAK ada `<input type="hidden" name="salary" ...>` di cabang `@else` -- field itu sengaja TIDAK IKUT terkirim sama sekali saat form disubmit, supaya `array_key_exists('salary', $data)` di Tahap 15a bisa membedakannya dari "user berwenang sengaja mengosongkan field". Kalau nekat dikirim lewat hidden input berisi nilai asli, itu sama saja membocorkan nominal gaji lewat HTML source -- tepat yang coba dicegah fitur ini.

### 15e. Views — tampilan baca (Edit Staff summary, Show Staff)

`resources/views/staff/edit.blade.php` (Tahap 11b) -- ganti baris Gaji di kotak ringkasan:

```blade
<div class="flex justify-between">
    <span class="text-gray-400">{{ __('Gaji') }}</span>
    <span class="font-medium"><x-salary-amount :staff="$staff" :amount="$staff->activeContract->salary" /></span>
</div>
```

`resources/views/staff/show.blade.php` (Tahap 11d) -- tab "Kepegawaian" (field Gaji dari `$staff->activeContract`) dan tab "Riwayat Kontrak" (field Gaji tiap `$contract` dalam `@foreach`), ganti semua pemakaian manual `{{ $x->salary !== null ? 'Rp ' . number_format(...) : '-' }}` jadi:

```blade
<x-salary-amount :staff="$staff" :amount="$contract->salary" />
```

(untuk tab Kepegawaian, `$amount="$staff->activeContract->salary"`; `$staff` SELALU objek Staff yang sama, cuma sumber nominalnya beda tergantung konteksnya -- Contract mana yang sedang ditampilkan gajinya).

**✅ Cek dulu**

```bash
php artisan test --filter=SalaryVisibilityTest
php artisan test
```

Manual: login sebagai role TANPA `salary.view` (mis. Coach) → buka Staff Detail milik ORANG LAIN → tab Kepegawaian & Riwayat Kontrak nominal gaji tampil `*****`. Buka Staff Detail milik akun yang sedang login sendiri (staff yang `id_user`-nya = user itu) → nominal gaji tampil ASLI. Buka form Edit Contract (Draft) sebagai role tanpa `salary.view` → field Gaji tampil `*****` non-editable, field lain (posisi/tanggal) tetap bisa diedit, submit tidak mengubah nominal gaji lama. Login sebagai Owner atau Finance → semua nominal gaji (siapapun) tampil asli.

### 15f. `lang/en.json`

```json
"Anda tidak memiliki akses untuk melihat atau mengubah gaji staff ini.": "You do not have access to view or change this staff member's salary."
```

### 15g. `tests/Feature/SalaryVisibilityTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalaryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeStaffWithSalary(Academy $academy, float $salary, ?User $owner = null): Staff
    {
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);

        return Staff::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_user' => $owner?->id_user,
        ])->tap(function (Staff $staff) use ($employmentType, $staffPosition, $salary) {
            $staff->contracts()->create([
                'id_academy' => $staff->id_academy,
                'id_employment_type' => $employmentType->id_employment_type,
                'id_staff_position' => $staffPosition->id_staff_position,
                'contract_code' => $staff->staff_code . '-C1',
                'start_date' => now(),
                'salary' => $salary,
                'status' => 'active',
            ]);
        });
    }

    protected function actingAsWithoutSalaryPermission(Academy $academy): User
    {
        Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Coach', 'guard_name' => 'web']);
        $role->syncPermissions(['staff.view']);

        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_user_tanpa_permission_tidak_bisa_lihat_gaji_staff_lain(): void
    {
        $academy = Academy::factory()->create();
        $staff = $this->makeStaffWithSalary($academy, 5000000);

        $user = $this->actingAsWithoutSalaryPermission($academy);

        $this->assertFalse($user->can('viewSalary', $staff));
    }

    public function test_user_tanpa_permission_tetap_bisa_lihat_gaji_sendiri(): void
    {
        $academy = Academy::factory()->create();
        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $staff = $this->makeStaffWithSalary($academy, 5000000, $owner);

        Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);
        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Coach', 'guard_name' => 'web']);
        $role->syncPermissions(['staff.view']);
        $owner->assignRole($role);

        $this->actingAs($owner);

        $this->assertTrue($owner->can('viewSalary', $staff));
    }

    public function test_user_dengan_permission_bisa_lihat_gaji_siapapun(): void
    {
        $academy = Academy::factory()->create();
        $staff = $this->makeStaffWithSalary($academy, 5000000);

        Permission::firstOrCreate(['name' => 'salary.view', 'guard_name' => 'web']);
        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Finance', 'guard_name' => 'web']);
        $role->syncPermissions(['salary.view']);

        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        $this->assertTrue($user->can('viewSalary', $staff));
    }
}
```

**✅ Cek dulu**: `php artisan test --filter=SalaryVisibilityTest` lulus semua. `php artisan test` (full suite) tidak ada regresi.

---

## 4. Alasan Teknis

### 4.1 Kenapa `staff.status` dihapus, bukan disinkron otomatis dari Contract

Alternatif yang dipertimbangkan: tetap simpan `staff.status` sebagai kolom, disinkronkan otomatis oleh `EmploymentContractService` setiap kali status Contract berubah (mis. Contract jadi Active → set `staff.status = 'active'`). Ini ditolak karena menciptakan 2 sumber kebenaran yang bisa desync kalau suatu saat ada jalur lain yang meng-update Contract tanpa lewat Service (migration lama, tinker manual, dst.) — status staff jadi bisa "berbohong". Menurunkan status langsung dari `has('activeContract')` setiap kali dibutuhkan (bukan disimpan) menghilangkan kelas bug ini sama sekali, dengan trade-off query sedikit lebih kompleks (`whereHas`) yang masih murah untuk skala data academy (ratusan-ribuan staff, bukan jutaan).

### 4.2 Kenapa row-lock di `staff`, bukan di `employment_contracts`

Guard "1 Draft/Active per staff" perlu mengunci **semua** baris `employment_contracts` milik 1 staff sekaligus (bukan 1 baris spesifik) selama transaksi — MySQL tidak punya cara mengunci "kumpulan baris berdasarkan kondisi" seatomik mengunci 1 baris tunggal. Baris `staff` itu sendiri sudah pasti unik per staff dan tidak pernah diubah konkuren oleh proses lain dengan makna yang bertentangan, jadi menguncinya sebagai *mutex* buatan (bukan karena datanya sendiri perlu diubah) adalah trik murah yang sudah lazim dipakai — pola yang sama esensinya dengan row-lock di `generateStaffCode()`/`generatePlayerCode()`, cuma objek yang dikunci beda (baris identitas sebagai proxy, bukan baris yang datanya benar-benar diubah).

### 4.3 Kenapa tidak ada halaman index/detail Contract terpisah

`docs/frontend-standard.md` menegaskan pola *Tabs Status + Toolbar Filter* untuk data yang "berpotensi banyak baris" lintas entitas — Contract secara alami **selalu** dilihat dalam konteks 1 staff tertentu (histori kerja *milik* orang itu), jumlahnya per staff kecil (biasanya < 10 baris seumur kerja), jadi tidak butuh pagination/filter sendiri. Menaruhnya sebagai tab di halaman Staff Detail (bukan halaman index terpisah) menghindari duplikasi navigasi (2 cara sampai ke data yang sama) dan tetap konsisten dengan filosofi "Contract adalah bagian dari histori Staff", bukan entitas berdiri sendiri di sidebar.

### 4.4 Kenapa `StaffPolicy` + `<x-salary-amount>`, bukan helper method biasa

Dua kebutuhan berbeda dipisah sengaja jadi 2 potongan kecil, bukan digabung jadi 1 helper besar:

- **Keputusan otorisasi** ("apa user ini boleh lihat gaji staff ini") secara alami adalah pertanyaan *Policy* di Laravel (izin atas 1 baris data spesifik, bukan izin atas fitur/route secara umum seperti permission `module.action` biasa) — project ini SUDAH punya preseden persis untuk ini: `RolePolicy` (Bagian 4.3 `docs/multi-tenancy.md`, dipakai `@can('view', $role)` dst). `StaffPolicy@viewSalary` mengikuti pola yang sama persis, bukan mekanisme baru.
- **Format + masking tampilan** ("jadi teks apa nominal ini ditampilkan") adalah kebutuhan tampilan berulang di banyak Blade — `docs/frontend-standard.md` (*Reusable View dengan Data Dinamis*) sudah menetapkan Class-based Blade Component untuk kasus begini (lihat `LogoUploadField`/`PlayerPhotoField`/`StaffPhotoField`), bukan `View::composer()` maupun helper function global.

Menggabungkan keduanya jadi 1 helper method (mis. `$staff->formattedSalary($amount)`) akan mencampur 2 concern beda (kebijakan otorisasi vs presentasi) dalam 1 tempat yang lebih sulit di-test terpisah — `StaffPolicy` bisa di-unit-test murni soal ya/tidak (lihat `SalaryVisibilityTest`), `SalaryAmount` Component murni soal format string, tanpa saling bergantung pada detail implementasi satu sama lain.
