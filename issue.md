# Brief: Module Academy Subscription + Academy Profile (Self-Service Owner)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu. Terutama `docs/multi-tenancy.md`, `docs/permission-reference.md`, `docs/module-standard.md`, dan `docs/frontend-standard.md`.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 18 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus. Tahap 1–11 membangun data & permission subscription (Super-Admin-only). Tahap 12–18 membangun halaman self-service terpisah supaya Owner tetap bisa mengubah profil umum academy-nya sendiri, tanpa menyentuh field subscription/billing.
> Kalau cuma mau eksekusi, cukup baca Bagian 0–3 lalu langsung ke Tahap 1. Bagian 4 (alasan teknis) boleh dibaca belakangan, **tapi aturannya tetap tidak boleh dilanggar**.

---

## 0. Aturan Emas

Sepuluh larangan ini bukan preferensi gaya. Masing-masing sudah diverifikasi akan bikin bug atau celah nyata. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Bikin kolom `subscription_status` tersimpan di database | Data basi — academy yang sudah kadaluarsa tetap terbaca "Aktif" kalau tidak ada job yang meng-update-nya tiap hari | [4.1](#41-kenapa-subscription_status-dihitung-bukan-disimpan-dan-kenapa-logic-nya-di-service-bukan-model) |
| Taruh logic hitung status subscription di `App\Models\Academy` | Melanggar `docs/module-standard.md` — Model hanya boleh Fillable/Relationship/Scope/Cast/UUID Generation | [4.1](#41-kenapa-subscription_status-dihitung-bukan-disimpan-dan-kenapa-logic-nya-di-service-bukan-model) |
| Lupa parameter `false` di `diffInDays()` | Tanpa itu hasilnya selalu absolut/positif — academy yang **sudah** kadaluarsa terbaca "akan berakhir", bukan "kadaluarsa" | [4.2](#42-gotcha-tanda-diffindays) |
| Bikin `subscription_type` sebagai kolom `ENUM` MySQL | Menambah tipe baru nanti (mis. `lifetime`) butuh migration `ALTER TABLE ... MODIFY ENUM` yang menyakitkan & berisiko lock tabel | [4.3](#43-kenapa-subscription_type-string-biasa-bukan-enum-database) |
| Field subscription baru langsung `NOT NULL` tanpa default di migration | Academy lama (termasuk "FAOS Academy" dari `RolePermissionSeeder`) sudah ada sebelum field ini dibuat — migration akan gagal atau harus menebak-nebak nilainya | [Tahap 1](#tahap-1--migration) |
| Tambahkan `academy.*` ke `config('faos.role_templates')` manapun, termasuk **Owner** | Academy Management memang Super-Admin-only. Kalau Owner diberi `academy.update`, Owner bisa mengubah **biaya langganannya sendiri** — konflik kepentingan langsung | [4.4](#44-kenapa-academy-tetap-super-admin-only-tidak-didelegasikan-ke-owner) |
| `middlewareFor()` di route lupa mencakup `show` untuk `academy.view` | Beda dengan Player Type (`except(['show'])`), Academy **punya** halaman detail yang sudah lama ada — kalau lupa, detail academy (termasuk data subscription) bisa diakses tanpa permission | [Tahap 6](#tahap-6--route--permission-gate) |
| Menambah `@can()` baru **per-item** di menu sidebar Academy | Menu ini sudah dibungkus `isSuperAdmin()` di level grup "Administration" — pola sama dengan Roles/Permissions, **bukan** pola per-item seperti Player/PlayerType | [4.5](#45-kenapa-sidebar-academy-tidak-perlu-can-per-item) |
| Mengubah logika `code`/`slug` generation atau upload logo yang sudah ada | Di luar scope brief ini, dan sudah berjalan benar. Kalau tersentuh berarti ada yang salah di Tahap 3 | [Peta Perubahan File](#3-peta-perubahan-file) |
| Membuat tabel riwayat pembayaran/perpanjangan subscription | Di luar scope brief ini — itu ranah module Payment (`payment.*`) yang belum dibangun. Jangan dicampur | [4.6](#46-kenapa-tidak-ada-tabel-riwayat-padahal-subscription-harusnya-berulang) |
| Di `AcademyProfileController`/`AcademyProfileFormRequest`, percaya `$request->validated()` saja untuk membatasi field yang boleh diubah Owner | `validated()` cuma membatasi field yang **divalidasi**, bukan jaminan arsitektural — Form Request yang salah tambah rule sekali saja bisa membocorkan field sensitif. `AcademyManagementService::updateProfile()` **wajib** membangun payload dengan whitelist eksplisit sebagai lapis kedua | [4.7](#47-kenapa-updateprofile-membangun-payload-manual-bukan-percaya-validated-begitu-saja) |
| Pakai `Route::resource()`/route model binding `{academy}` untuk halaman Profil Academy | Owner bisa ganti angka ID di URL dan mengedit academy lain. Halaman ini **wajib** selalu beroperasi di `$academyService->current()`, tidak pernah menerima ID dari request | [4.8](#48-kenapa-academy-profile-tidak-pakai-route-model-binding) |
| Tambahkan `academy_profile.*` ke daftar `academy.*` yang sudah ada, atau sebaliknya | Dua permission ini **sengaja terpisah** — `academy.*` tidak pernah didelegasikan (lihat 4.4), `academy_profile.update` justru didelegasikan ke Owner secara default. Menggabungkannya menghilangkan pemisahan yang jadi inti brief tambahan ini | [2d](#2d-dua-tingkat-akses-academy-super-admin-penuh-vs-academy-profile-owner-terbatas) |

---

## 1. Tujuan

FAOSBall dipakai banyak academy/club sekaligus (Single Database Multi-Tenant, lihat `docs/multi-tenancy.md`). Setiap academy berlangganan sistem ini dengan skema yang **berbeda-beda**: ada yang bulanan, ada yang tahunan, dan biayanya pun tidak sama antar academy.

Yang kita tuju — Super Admin bisa mencatat & memantau langganan tiap academy langsung dari halaman Academy Management yang sudah ada:

```text
Academy A                          Academy B
├── Tipe    : Bulanan               ├── Tipe    : Tahunan
├── Biaya   : Rp 500.000            ├── Biaya   : Rp 5.000.000
├── Mulai   : 1 Jan 2026            ├── Mulai   : 1 Jul 2025
└── Berakhir: 1 Feb 2026            └── Berakhir: 1 Jul 2026
    → Status: Akan Berakhir             → Status: Aktif
```

**Scope brief ini**: menambahkan 4 field informasi langganan ke data Academy yang sudah ada, plus menutup celah permission Academy Management yang sudah lama dicatat "belum digerbang" di `docs/permission-reference.md`. **Bukan** scope brief ini: invoice, riwayat pembayaran, notifikasi jatuh tempo otomatis, atau penonaktifan academy otomatis saat kadaluarsa — itu semua ranah module Payment yang belum dibangun (lihat [4.6](#46-kenapa-tidak-ada-tabel-riwayat-padahal-subscription-harusnya-berulang)).

**Tambahan (Tahap 12–18)**: menutup permission Academy Management jadi Super-Admin-only (Tahap 6) berarti Owner kehilangan **satu-satunya** jalan untuk mengubah profil academy-nya sendiri (nama, logo, tagline, kontak, alamat) — sebelumnya jalan itu memang ada tapi cuma karena permission-nya belum digerbang (bug, bukan fitur). Supaya Owner tidak kehilangan kemampuan yang seharusnya memang jadi haknya, brief ini juga membangun halaman **Profil Academy** self-service yang terpisah total dari Academy Management: Owner cuma bisa mengubah field profil umum miliknya sendiri, tidak pernah menyentuh `code`, `status`, atau field subscription. Lihat [2d](#2d-dua-tingkat-akses-academy-super-admin-penuh-vs-academy-profile-owner-terbatas).

---

## 2. Cara Kerja Solusi

Baca sampai paham. Kalau bagian ini tidak nyantol, sisa brief akan terasa acak.

### 2a. `academies` adalah root tenant — field baru nempel langsung di tabel itu

Berbeda dengan `player_types`/`player_categories` yang merupakan tabel tenant (punya `id_academy`, pakai `BelongsToAcademy` + `AcademyScope`), `Academy` **adalah** tenant itu sendiri — tidak punya `id_academy`, tidak pakai `BelongsToAcademy` (lihat `docs/multi-tenancy.md` → *Academy Exception*). Jadi 4 field baru ini (`subscription_type`, `subscription_fee`, `subscription_started_at`, `subscription_ends_at`) cukup ditambahkan langsung sebagai kolom di tabel `academies` — tidak perlu tabel terpisah, tidak perlu scope tambahan.

### 2b. 4 kolom yang disimpan, 1 status yang **dihitung**

| Kolom | Tipe | Disimpan? |
|---|---|:---:|
| `subscription_type` | `monthly` \| `yearly` | ✅ Disimpan |
| `subscription_fee` | decimal | ✅ Disimpan |
| `subscription_started_at` | date | ✅ Disimpan |
| `subscription_ends_at` | date | ✅ Disimpan |
| **status** (Aktif/Akan Berakhir/Kadaluarsa) | string | ❌ **Dihitung**, bukan kolom |

Status **tidak** disimpan sebagai kolom karena nilainya bergantung pada waktu berjalan (`now()` vs `subscription_ends_at`) — kalau disimpan, butuh scheduled job harian untuk menjaganya tetap benar, dan brief ini tidak membuat job semacam itu. Statusnya dihitung ulang setiap dibutuhkan oleh `AcademyManagementService::subscriptionStatus()` (Tahap 3), **bukan** oleh Model (lihat [4.1](#41-kenapa-subscription_status-dihitung-bukan-disimpan-dan-kenapa-logic-nya-di-service-bukan-model)).

### 2c. Academy Management jadi Super-Admin-only yang benar-benar ditegakkan

Saat ini (sebelum brief ini): permission `academy.view/create/update/delete` sudah ada di `RolePermissionSeeder`, tapi **route-nya tidak memakainya sama sekali** (`docs/permission-reference.md` menandai 🚨 *Belum digerbang*). Siapapun yang login dan tahu URL `/academies` bisa mengakses, membuat, mengubah, bahkan menghapus academy manapun — termasuk mengubah data subscription-nya sendiri kalau kebetulan tahu URL-nya.

Brief ini menutup celah itu **sekaligus**, dengan pola yang identik dengan `player_position.*` (lihat `docs/permission-reference.md` → *Module: Player Position*): permission `academy.*` ditegakkan lewat `middlewareFor()`, tapi **tidak** diberikan ke role academy manapun (termasuk Owner) di `config('faos.role_templates')`. Karena `Gate::before()` memberi akses penuh ke Super Admin (lihat `docs/multi-tenancy.md` → *Gate Before*), praktiknya modul ini otomatis jadi Super-Admin-exclusive — user academy manapun yang mencoba akses lewat URL langsung akan mendapat **403**, bukan diam-diam berhasil.

### 2d. Dua tingkat akses Academy: Super Admin penuh vs Academy Profile Owner terbatas

Menutup `academy.*` sepenuhnya (2c) menimbulkan pertanyaan wajar: kalau Owner tidak boleh lagi buka `/academies/{id}/edit`, bagaimana caranya Owner mengganti logo atau nomor telepon academy-nya sendiri? Jawabannya **bukan** membuka kembali `academy.update` ke Owner (itu form yang sama, mengandung field `code`/`status`/subscription — membuka salah satu berarti membuka semuanya), tapi membuat **route, controller, form request, dan permission yang benar-benar terpisah**:

| | `academies.*` (Tahap 1–11) | Academy Profile (Tahap 12–18) |
|---|---|---|
| Permission | `academy.view/create/update/delete` | `academy_profile.update` |
| Siapa yang punya | **Hanya** Super Admin (via `Gate::before()`, tidak pernah di `role_templates`) | **Owner** secara default (bisa dicabut/didelegasikan lewat Role Management, seperti `player_type.*`) |
| Operasi pada academy mana | Lintas **seluruh** academy (route model binding `{academy}` by ID) | **Cuma** academy milik Owner sendiri (`$academyService->current()`, tidak pernah menerima ID) |
| Field yang bisa diubah | Semua — profil umum, `code`, `status`, **plus subscription** | **Cuma** profil umum: nama, tagline, telepon, email, alamat, deskripsi, logo |
| Halaman | `/academies`, `/academies/{id}/edit`, dst | `/academy-profile` (singleton, tanpa ID) |

Pola pemisahan seperti ini **sudah ada presedennya** di codebase ini: Player Account (`players/{player}/account/*`, permission `user.*`) adalah sub-resource yang sengaja dipisah dari Player (`player.*`) — lihat `docs/permission-reference.md` → *Sub-module: Player Account*. Academy Profile mengikuti pola yang sama persis: fitur yang secara konsep "bagian dari Academy" tapi butuh permission & batasan field yang beda total dari CRUD utamanya.

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_add_subscription_to_academies_table.php` | 🆕 Baru | 1 |
| `app/Models/Academy.php` | ✏️ Tambah fillable + cast | 2 |
| `app/Services/AcademyManagementService.php` | ✏️ Tambah `SUBSCRIPTION_TYPES`, `subscriptionStatus()`, ubah `paginate()` | 3 |
| `app/Http/Requests/Academy/AcademyFormRequest.php` | ✏️ Tambah rules + messages | 4 |
| `app/Http/Controllers/AcademyController.php` | ✏️ Kirim data subscription ke view | 5 |
| `routes/web.php` | ✏️ Tambah `middlewareFor()` permission | 6 |
| `resources/views/academies/create.blade.php` | ✏️ Tambah field subscription | 7 |
| `resources/views/academies/edit.blade.php` | ✏️ Tambah field subscription | 7 |
| `resources/views/academies/show.blade.php` | ✏️ Tambah card "Informasi Langganan" | 8 |
| `resources/views/academies/index.blade.php` | ✏️ Tambah kolom Langganan + `@can()` guard | 9 |
| `tests/Feature/AcademySubscriptionTest.php` | 🆕 Baru | 10 |
| `docs/permission-reference.md` | ✏️ Ubah status Academy Management jadi ✅ | 11 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah permission `academy_profile.update` | 12 |
| `config/faos.php` → `role_templates` | ✏️ Tambah `academy_profile.update` ke **Owner saja** | 12 |
| `app/Services/AcademyManagementService.php` | ✏️ Tambah method `updateProfile()` | 13 |
| `app/Http/Requests/Academy/AcademyProfileFormRequest.php` | 🆕 Baru | 14 |
| `app/Http/Controllers/AcademyProfileController.php` | 🆕 Baru | 15 |
| `routes/web.php` | ✏️ Tambah route singleton `academy.profile.*` | 15 |
| `resources/views/academy-profile/edit.blade.php` | 🆕 Baru | 16 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Tambah menu "Profil Academy" | 16 |
| `tests/Feature/AcademyProfileTest.php` | 🆕 Baru | 17 |
| `docs/permission-reference.md` | ✏️ Tambah section "Module: Academy Profile" | 18 |
| **`app/Services/AcademyService.php`** | 🚫 **Jangan sentuh** — beda dengan `AcademyManagementService`, ini yang mengelola konteks academy *aktif* (`currentId()`, `isSuperAdmin()`, `current()`), tidak terkait subscription. Dipakai (bukan diubah) oleh `AcademyProfileController` di Tahap 15 | — |
| **`app/Traits/BelongsToAcademy.php`, `app/Scopes/AcademyScope.php`** | 🚫 **Jangan sentuh** — `Academy` adalah root tenant, tidak memakai keduanya | — |
| **`app/Http/Controllers/AcademyController.php`, `app/Http/Requests/Academy/AcademyFormRequest.php`** | 🚫 **Jangan sentuh di Tahap 12–18** — Academy Profile punya Controller & Form Request sendiri, bukan menumpang punya `AcademyController` | — |
| **Logika `code`/`slug` generation, upload/hapus logo** | 🚫 **Jangan sentuh (isinya)** — dipakai ulang apa adanya oleh `updateProfile()` di Tahap 13 | — |

---

## Tahap 1 — Migration

**Tujuan**: 4 kolom subscription ada di tabel `academies`, nullable.

```bash
php artisan make:migration add_subscription_to_academies_table --table=academies
```

Isi filenya:

```php
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
            | yang dibuat/diedit setelahnya -- lihat Tahap 4.
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
```

> Tidak ada index tambahan di tahap ini. `subscription_ends_at` **belum** dipakai untuk query filter/sort apapun di brief ini (cuma ditampilkan). Kalau nanti ada fitur "daftar academy yang akan berakhir dalam N hari", evaluasi index-nya saat itu — jangan tambah index preventif ke tabel yang sudah ada tanpa kebutuhan nyata (lihat `docs/query-performance.md`).

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table academies
```

- Harus ada 4 kolom baru: `subscription_type`, `subscription_fee`, `subscription_started_at`, `subscription_ends_at`.
- Semuanya **nullable** — kalau ada yang `NOT NULL`, ulangi.
- Urutan kolom persis setelah `status` (efek dari `->after()`), bukan wajib fungsional tapi jaga kerapian schema.

---

## Tahap 2 — Model

**Tujuan**: `Academy` tahu field barunya, tanpa logic tambahan.

`app/Models/Academy.php` — tambahkan ke `$fillable`, tepat setelah `'status'`:

```php
protected $fillable = [
    'name',
    'code',
    'slug',
    'phone',
    'email',
    'address',
    'tagline',
    'status',
    'subscription_type',
    'subscription_fee',
    'subscription_started_at',
    'subscription_ends_at',
    'logo',
    'description',
];
```

Ubah method `casts()`:

```php
protected function casts(): array
{
    return [
        'status' => 'boolean',
        'subscription_fee' => 'decimal:2',
        'subscription_started_at' => 'date',
        'subscription_ends_at' => 'date',
    ];
}
```

> **Jangan tambahkan apapun selain ini ke Model** — tidak ada accessor, tidak ada method `subscriptionStatus()`, tidak ada `scopeExpiringSoon()`. Semua logic penghitungan status ada di `AcademyManagementService` (Tahap 3). Lihat [4.1](#41-kenapa-subscription_status-dihitung-bukan-disimpan-dan-kenapa-logic-nya-di-service-bukan-model) kalau tergoda menaruhnya di sini.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\Models\Academy)->getFillable();
// harus memuat 4 field subscription baru

(new \App\Models\Academy)->getCasts()['subscription_fee'];
// harus: "decimal:2"
```

---

## Tahap 3 — `AcademyManagementService`

**Tujuan**: definisi tipe subscription yang valid + logic hitung status, dipakai bersama oleh Form Request, Controller, dan View.

Di `app/Services/AcademyManagementService.php`, tambahkan 2 konstanta di bagian paling atas class (sebelum property `$roleService`):

```php
class AcademyManagementService
{
    /**
     * Tipe subscription yang valid. Key disimpan di kolom subscription_type,
     * value adalah label tampilan. Dipakai bersama oleh Form Request
     * (validasi) dan Controller (populate dropdown + label tampilan) supaya
     * daftarnya tidak dobel-tulis di banyak tempat.
     */
    public const SUBSCRIPTION_TYPES = [
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
    ];

    /**
     * Ambang hari sebelum subscription_ends_at yang dianggap "Akan Berakhir".
     */
    protected const SUBSCRIPTION_EXPIRING_SOON_DAYS = 7;

    protected RoleService $roleService;
    // ... constructor & property lain TETAP, tidak berubah
```

Tambahkan method baru ini di **bagian bawah class**, sebelum `create()`:

```php
    /*
    |--------------------------------------------------------------------------
    | Subscription
    |--------------------------------------------------------------------------
    */

    /**
     * Hitung status subscription academy dari subscription_ends_at.
     *
     * TIDAK disimpan sebagai kolom terpisah -- dihitung ulang tiap dipanggil,
     * supaya tidak ada risiko data basi (kolom status yang lupa di-refresh
     * saat tanggal berjalan). Lihat Bagian 4.1 di issue.md.
     *
     * @return string 'belum_diatur' | 'aktif' | 'akan_berakhir' | 'kadaluarsa'
     */
    public function subscriptionStatus(Academy $academy): string
    {
        if (! $academy->subscription_ends_at) {
            return 'belum_diatur';
        }

        // Parameter FALSE di argumen kedua WAJIB -- tanpa itu hasilnya selalu
        // absolut/positif, academy yang sudah kadaluarsa akan terbaca
        // "akan berakhir" alih-alih "kadaluarsa". Lihat Bagian 4.2.
        $daysLeft = now()->startOfDay()->diffInDays($academy->subscription_ends_at, false);

        return match (true) {
            $daysLeft < 0 => 'kadaluarsa',
            $daysLeft <= self::SUBSCRIPTION_EXPIRING_SOON_DAYS => 'akan_berakhir',
            default => 'aktif',
        };
    }
```

Ubah method `paginate()` yang sudah ada — tambahkan penghitungan `subscription_status` per baris **sebelum** `return`:

```php
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Academy::query();

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $academies = $query->paginate(config('faos.pagination.default'));

        // subscription_status dihitung di sini (Service), BUKAN di Blade,
        // supaya Blade cuma menampilkan, tidak menghitung. Ditempel sebagai
        // atribut dinamis (bukan kolom asli), aman dipakai read-only di view.
        $academies->getCollection()->each(function (Academy $academy) {
            $academy->subscription_status = $this->subscriptionStatus($academy);
        });

        return $academies;
    }
```

> `statusCounts()` **tidak berubah** — itu untuk tab status keaktifan academy (`status` boolean), beda konsep dengan status subscription.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$svc = app(\App\Services\AcademyManagementService::class);
$academy = \App\Models\Academy::factory()->create([
    'subscription_ends_at' => now()->addDays(3),
]);
$svc->subscriptionStatus($academy);
// harus: "akan_berakhir"

$academy->subscription_ends_at = now()->subDays(2);
$svc->subscriptionStatus($academy);
// harus: "kadaluarsa"

$academy->subscription_ends_at = now()->addMonths(2);
$svc->subscriptionStatus($academy);
// harus: "aktif"

$academy->subscription_ends_at = null;
$svc->subscriptionStatus($academy);
// harus: "belum_diatur"
```

Kalau salah satu hasilnya tidak sesuai (terutama yang `kadaluarsa`), cek lagi parameter `false` di `diffInDays()`.

---

## Tahap 4 — `AcademyFormRequest`

**Tujuan**: 4 field subscription wajib diisi & valid setiap kali academy dibuat/diedit.

Di `app/Http/Requests/Academy/AcademyFormRequest.php`, tambahkan import:

```php
use App\Services\AcademyManagementService;
```

Tambahkan rule ini di `rules()`, **tepat setelah** rule `status`:

```php
            'subscription_type' => [
                'required',
                'string',
                Rule::in(array_keys(AcademyManagementService::SUBSCRIPTION_TYPES)),
            ],

            'subscription_fee' => [
                'required',
                'numeric',
                'min:0',
            ],

            'subscription_started_at' => [
                'required',
                'date',
            ],

            'subscription_ends_at' => [
                'required',
                'date',
                'after_or_equal:subscription_started_at',
            ],
```

> Nullable di level database (Tahap 1), tapi **required** di sini. Sama persis pola `id_player_type` pada `players` di brief Player Type sebelumnya — kolom boleh kosong untuk data lama, tapi setiap create/update baru wajib mengisinya.

Tambahkan pesan ini di `messages()`:

```php
            'subscription_type.required' => 'Tipe langganan wajib dipilih.',
            'subscription_type.in' => 'Tipe langganan tidak valid.',

            'subscription_fee.required' => 'Biaya langganan wajib diisi.',
            'subscription_fee.numeric' => 'Biaya langganan harus berupa angka.',
            'subscription_fee.min' => 'Biaya langganan tidak boleh negatif.',

            'subscription_started_at.required' => 'Tanggal mulai langganan wajib diisi.',
            'subscription_started_at.date' => 'Tanggal mulai langganan tidak valid.',

            'subscription_ends_at.required' => 'Tanggal berakhir langganan wajib diisi.',
            'subscription_ends_at.date' => 'Tanggal berakhir langganan tidak valid.',
            'subscription_ends_at.after_or_equal' => 'Tanggal berakhir langganan tidak boleh sebelum tanggal mulai.',
```

**✅ Cek dulu**: submit form create academy tanpa mengisi field subscription → harus muncul 4 pesan error di atas, bukan crash.

---

## Tahap 5 — Controller

**Tujuan**: view create/edit/show dapat daftar tipe subscription + label statusnya.

Di `app/Http/Controllers/AcademyController.php`, **tidak perlu import baru** — `AcademyManagementService` sudah di-import (dipakai untuk type-hint constructor).

Ubah `create()`:

```php
    public function create()
    {
        return view('academies.create',[
            'title'=>'Tambah Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy',
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>'Tambah Academy'
                ]
            ],
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
        ]);
    }
```

Ubah `edit()`:

```php
    public function edit(Academy $academy)
    {
        return view('academies.edit',[
            'title'=>'Edit Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy',
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>'Edit Academy'
                ]
            ],
            'academy'=>$academy,
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
        ]);
    }
```

Ubah `show()`:

```php
    public function show(Academy $academy)
    {
        return view('academies.show',[
            'title'=>'Detail Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy',
                    'url'=>route('academies.index')
                ],
                [
                    'label'=>'Detail Academy'
                ]
            ],
            'academy'=>$academy,
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
            'subscriptionStatus' => $this->academyManagementService->subscriptionStatus($academy),
        ]);
    }
```

Ubah `index()` — tambahkan satu baris ke array view yang sudah ada:

```php
    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'sort']));

        return view('academies.index',[
            'title'=>'Manajemen Academy',
            'breadcrumb'=>[
                [
                    'label'=>'Manajemen Academy'
                ]
            ],
            'academies' => $this->academyManagementService->paginate($filters),
            'statusCounts' => $this->academyManagementService->statusCounts($filters),
            'filters' => $filters,
            'subscriptionTypes' => AcademyManagementService::SUBSCRIPTION_TYPES,
        ]);
    }
```

> `subscription_status` per baris **tidak** ditambahkan di sini — itu sudah otomatis ditempel oleh `AcademyManagementService::paginate()` di Tahap 3. `subscriptionTypes` di sini cuma untuk menerjemahkan `monthly`/`yearly` jadi label "Bulanan"/"Tahunan" di Blade (Tahap 9).

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Http\Controllers\AcademyController::class)` tidak error saat resolve constructor-nya.

---

## Tahap 6 — Route + Permission Gate

**Tujuan**: `/academies/*` benar-benar terkunci permission `academy.*`, termasuk `show`.

Di `routes/web.php`, ganti blok ini:

```php
    Route::resource(
        'academies',
        AcademyController::class
    );
```

Menjadi:

```php
    /*
    |--------------------------------------------------------------------------
    | Academy Management
    |--------------------------------------------------------------------------
    | academy.* SENGAJA tidak ada di config('faos.role_templates') manapun,
    | termasuk Owner -- modul ini Super-Admin-only. Lihat issue.md Bagian 4.4.
    */
    Route::resource('academies', AcademyController::class)
        ->middlewareFor(['index', 'show'], 'permission:academy.view')
        ->middlewareFor(['create', 'store'], 'permission:academy.create')
        ->middlewareFor(['edit', 'update'], 'permission:academy.update')
        ->middlewareFor('destroy', 'permission:academy.delete');
```

> Perhatikan `show` masuk ke `academy.view`, **bukan** `except(['show'])` seperti Player Type — halaman detail Academy sudah lama ada dan dipakai (tombol "Detail" di index), beda dari Player Type yang memang sengaja tidak dibuatkan halaman detail.

**✅ Cek dulu**

```bash
php artisan route:list --name=academies
```

Harus muncul 7 route (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`), masing-masing dengan middleware `permission:academy.*` yang sesuai.

Test manual: login sebagai user academy biasa (bukan Super Admin, mis. `owner@faosacademy.com` / `password`), buka `/academies` langsung lewat URL → harus **403**, bukan berhasil masuk.

---

## Tahap 7 — View: `create.blade.php` & `edit.blade.php`

**Tujuan**: Super Admin bisa mengisi/mengubah data subscription saat membuat/mengedit academy.

### 7a. `create.blade.php`

Tambahkan section baru **"Informasi Langganan"** setelah blok "Status Aktif" (di kolom kiri form, sebelum `</div>` penutup kolom kiri):

```blade
                    {{-- Subscription --}}
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">

                        <h4 class="section-title mb-4">Informasi Langganan</h4>

                        {{-- Tipe Langganan --}}
                        <div class="form-group">
                            <label for="subscription_type" class="form-label">
                                Tipe Langganan <span class="text-error-500">*</span>
                            </label>

                            <select id="subscription_type" name="subscription_type"
                                class="form-select @error('subscription_type') form-danger @enderror" required>
                                <option value="">Pilih Tipe Langganan</option>
                                @foreach ($subscriptionTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('subscription_type') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @error('subscription_type')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Biaya Langganan --}}
                        <div class="form-group">
                            <label for="subscription_fee" class="form-label">
                                Biaya Langganan (Rp) <span class="text-error-500">*</span>
                            </label>

                            <input type="number" id="subscription_fee" name="subscription_fee"
                                value="{{ old('subscription_fee') }}" min="0" step="0.01"
                                placeholder="Contoh: 500000"
                                class="form-input @error('subscription_fee') form-danger @enderror" required>

                            @error('subscription_fee')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Mulai & Berakhir --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            <div class="form-group">
                                <label for="subscription_started_at" class="form-label">
                                    Mulai Langganan <span class="text-error-500">*</span>
                                </label>

                                <input type="date" id="subscription_started_at" name="subscription_started_at"
                                    value="{{ old('subscription_started_at', now()->format('Y-m-d')) }}"
                                    class="form-input @error('subscription_started_at') form-danger @enderror" required>

                                @error('subscription_started_at')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="subscription_ends_at" class="form-label">
                                    Berakhir Langganan <span class="text-error-500">*</span>
                                </label>

                                <input type="date" id="subscription_ends_at" name="subscription_ends_at"
                                    value="{{ old('subscription_ends_at') }}"
                                    class="form-input @error('subscription_ends_at') form-danger @enderror" required>

                                @error('subscription_ends_at')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                    </div>
```

> Tidak ada Alpine/JS auto-hitung tanggal berakhir dari tipe langganan. Super Admin mengisi tanggal mulai & berakhir secara manual — perpanjangan di dunia nyata jarang pas 30/365 hari (ada academy yang bayar telat, ada yang bayar di muka lebih panjang). Kalau nanti dirasa perlu tombol bantu "Hitung Otomatis", diskusikan dulu, jangan ditambahkan sepihak (lihat Aturan Utama di `CLAUDE.md`).

### 7b. `edit.blade.php`

Section yang **identik** dengan 7a, satu-satunya beda: nilai default input memakai data `$academy`, bukan cuma `old()`:

```blade
                            <select id="subscription_type" name="subscription_type"
                                class="form-select @error('subscription_type') form-danger @enderror" required>
                                <option value="">Pilih Tipe Langganan</option>
                                @foreach ($subscriptionTypes as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(old('subscription_type', $academy->subscription_type) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
```

```blade
                            <input type="number" id="subscription_fee" name="subscription_fee"
                                value="{{ old('subscription_fee', $academy->subscription_fee) }}" min="0" step="0.01"
                                class="form-input @error('subscription_fee') form-danger @enderror" required>
```

```blade
                                <input type="date" id="subscription_started_at" name="subscription_started_at"
                                    value="{{ old('subscription_started_at', $academy->subscription_started_at?->format('Y-m-d')) }}"
                                    class="form-input @error('subscription_started_at') form-danger @enderror" required>
```

```blade
                                <input type="date" id="subscription_ends_at" name="subscription_ends_at"
                                    value="{{ old('subscription_ends_at', $academy->subscription_ends_at?->format('Y-m-d')) }}"
                                    class="form-input @error('subscription_ends_at') form-danger @enderror" required>
```

> `$academy->subscription_started_at?->format('Y-m-d')` — pakai `?->` (nullsafe). Academy lama yang belum punya data subscription (field-nya `null`) tidak boleh membuat halaman edit crash.

**✅ Cek dulu**: buka `/academies/create` dan `/academies/{id}/edit` (login Super Admin) → 4 field baru muncul, dropdown tipe berisi "Bulanan"/"Tahunan". Submit dengan data lengkap → academy tersimpan, cek `php artisan tinker` → `Academy::latest()->first()->subscription_type` sesuai yang diisi.

---

## Tahap 8 — View: `show.blade.php`

**Tujuan**: detail academy menampilkan ringkasan langganan + badge status.

Tambahkan card baru di kolom kanan (`<div class="space-y-6">`), **setelah** card "Informasi Ringkas" dan **sebelum** card "Informasi Sistem":

```blade
                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        Informasi Langganan
                    </h4>

                    <div class="mt-4 space-y-4">

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Status</span>

                            @php
                                $subscriptionBadges = [
                                    'aktif' => ['label' => 'Aktif', 'class' => 'badge-success'],
                                    'akan_berakhir' => ['label' => 'Akan Berakhir', 'class' => 'badge-warning'],
                                    'kadaluarsa' => ['label' => 'Kadaluarsa', 'class' => 'badge-danger'],
                                    'belum_diatur' => ['label' => 'Belum Diatur', 'class' => 'badge-secondary'],
                                ];
                                $badge = $subscriptionBadges[$subscriptionStatus];
                            @endphp

                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Tipe Langganan</span>
                            <span class="table-text">
                                {{ $academy->subscription_type ? $subscriptionTypes[$academy->subscription_type] : '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Biaya Langganan</span>
                            <span class="table-text">
                                {{ $academy->subscription_fee ? 'Rp ' . number_format($academy->subscription_fee, 0, ',', '.') : '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Periode</span>
                            <span class="table-text">
                                @if ($academy->subscription_started_at && $academy->subscription_ends_at)
                                    {{ $academy->subscription_started_at->format('d M Y') }}
                                    &mdash;
                                    {{ $academy->subscription_ends_at->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>

                    </div>

                </div>
```

> `$subscriptionTypes[$academy->subscription_type]` cuma dipanggil kalau `$academy->subscription_type` truthy (dicek dulu di ternary sebelum index array) — academy lama yang field-nya masih `null` tidak akan memicu error "undefined array key".

**✅ Cek dulu**: buka `/academies/{id}` untuk academy yang sudah diisi subscription-nya (dari Tahap 7) → card "Informasi Langganan" tampil lengkap dengan badge status yang benar. Buka academy **lama** yang belum pernah diisi (data sebelum brief ini, kalau ada) → tampil "Belum Diatur" / "-", bukan crash.

---

## Tahap 9 — View: `index.blade.php`

**Tujuan**: kolom Langganan tampil di tabel + card list, dan seluruh tombol aksi digerbang `@can()` sesuai `academy.*`.

### 9a. Bungkus tombol "Tambah Academy"

```blade
            <div class="card-actions">
                @can('academy.create')
                    <a href="{{ route('academies.create') }}" class="btn btn-primary">
                        {{-- svg + "Tambah Academy" tetap sama --}}
                    </a>
                @endcan
            </div>
```

### 9b. Tambahkan mapping badge

Tepat di bawah `@php $hasActiveFilters = !empty($filters); @endphp` yang sudah ada, tambahkan:

```blade
        @php
            $subscriptionBadges = [
                'aktif' => ['label' => 'Aktif', 'class' => 'badge-success'],
                'akan_berakhir' => ['label' => 'Akan Berakhir', 'class' => 'badge-warning'],
                'kadaluarsa' => ['label' => 'Kadaluarsa', 'class' => 'badge-danger'],
                'belum_diatur' => ['label' => 'Belum Diatur', 'class' => 'badge-secondary'],
            ];
        @endphp
```

### 9c. Kolom tabel

Tambahkan `<th>` baru **di antara** "Tagline" dan "Status":

```blade
                        <th class="table-header-cell">Tagline</th>
                        <th class="table-header-cell">Langganan</th>
                        <th class="table-header-cell">Status</th>
```

Tambahkan `<td>` yang sepasang, di antara `<td>` Tagline dan `<td>` Status yang sudah ada:

```blade
                            <td class="table-cell">
                                @if ($academy->subscription_type)
                                    <span class="table-text">{{ $subscriptionTypes[$academy->subscription_type] }}</span>
                                    <span class="table-subtitle">
                                        Rp {{ number_format($academy->subscription_fee, 0, ',', '.') }}
                                    </span>
                                    @php $badge = $subscriptionBadges[$academy->subscription_status] @endphp
                                    <span class="badge {{ $badge['class'] }} badge-sm mt-1">{{ $badge['label'] }}</span>
                                @else
                                    <span class="badge badge-secondary badge-sm">Belum Diatur</span>
                                @endif
                            </td>
```

**Ubah `colspan="5"` menjadi `colspan="6"`** di baris empty-state tabel (satu-satunya `<td colspan="5">` di file ini) — kalau tetap 5, empty-state akan tampil menyempit/salah bentuk karena jumlah kolom sekarang 6.

### 9d. Card List (mobile/tablet)

Tambahkan field baru di `table-card-body`, setelah field "Tagline":

```blade
                        <div class="table-card-field">
                            <span class="table-card-label">Langganan</span>
                            @if ($academy->subscription_type)
                                <span class="table-text">{{ $subscriptionTypes[$academy->subscription_type] }}</span>
                                <span class="table-subtitle">
                                    Rp {{ number_format($academy->subscription_fee, 0, ',', '.') }}
                                </span>
                                @php $badge = $subscriptionBadges[$academy->subscription_status] @endphp
                                <span class="badge {{ $badge['class'] }} badge-sm mt-1">{{ $badge['label'] }}</span>
                            @else
                                <span class="badge badge-secondary badge-sm">Belum Diatur</span>
                            @endif
                        </div>
```

### 9e. Bungkus tombol aksi (tabel & card list, dua-duanya)

Tombol "Detail", "Edit", dan `<x-button.delete>` yang sudah ada — masing-masing dibungkus `@can()` yang sesuai, mengikuti pola persis `roles/index.blade.php`:

```blade
                                @can('academy.view')
                                    <a href="{{ route('academies.show', $academy->id_academy) }}"
                                        class="btn-icon btn-icon-primary" title="Detail">
                                        {{-- svg tetap sama --}}
                                    </a>
                                @endcan

                                @can('academy.update')
                                    <a href="{{ route('academies.edit', $academy->id_academy) }}"
                                        class="btn-icon btn-icon-warning" title="Edit">
                                        {{-- svg tetap sama --}}
                                    </a>
                                @endcan

                                @can('academy.delete')
                                    <x-button.delete :action="route('academies.destroy', $academy->id_academy)"
                                        :name="$academy->name" />
                                @endcan
```

**✅ Cek dulu**: buka `/academies` (Super Admin) → kolom "Langganan" tampil di tabel desktop **dan** di card list (cek DevTools lebar 375px). Login sebagai user academy biasa dan cek tombol Detail/Edit/Hapus **tidak muncul** kalau memang tidak seharusnya (walau sebenarnya seluruh halaman ini sudah 403 duluan lewat route middleware — `@can()` di sini adalah lapisan kedua, konsisten dengan pola Role/Permission).

---

## Tahap 10 — Test

**Tujuan**: status subscription terhitung benar, dan permission gate benar-benar menolak user non-Super-Admin.

`tests/Feature/AcademySubscriptionTest.php` — file baru:

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use App\Services\AcademyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang dibuat
        // di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeAcademyOwner(Academy $academy): User
    {
        // Owner sengaja TIDAK diberi academy.*, mensimulasikan role_templates
        // default -- academy.* memang tidak pernah ada di template manapun.
        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Owner',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'id_academy' => $academy->id_academy,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function makeSuperAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'academy.view', 'guard_name' => 'web']);

        $role = Role::firstOrCreate([
            'id_academy' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'id_academy' => null,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_status_aktif_saat_masih_jauh_dari_jatuh_tempo(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => now()->addMonths(2),
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('aktif', $status);
    }

    public function test_status_akan_berakhir_dalam_tujuh_hari(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => now()->addDays(3),
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('akan_berakhir', $status);
    }

    public function test_status_kadaluarsa_setelah_lewat_tanggal(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => now()->subDays(1),
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('kadaluarsa', $status);
    }

    public function test_status_belum_diatur_saat_ends_at_kosong(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => null,
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('belum_diatur', $status);
    }

    public function test_owner_academy_ditolak_403_akses_academies_index(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeAcademyOwner($academy);

        $response = $this->actingAs($owner)->get(route('academies.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_bisa_akses_academies_index(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('academies.index'));

        $response->assertOk();
    }
}
```

> Test `test_super_admin_bisa_akses_academies_index` mengandalkan `Gate::before()` (lihat `docs/multi-tenancy.md`) yang otomatis meloloskan role bernama persis `"Super Admin"` — **bukan** dari permission `academy.view` yang di-assign manual. `Permission::firstOrCreate('academy.view')` di `makeSuperAdmin()` cuma untuk memastikan permission-nya ada di database (dibutuhkan middleware `permission:academy.view` supaya tidak error "permission does not exist"), bukan untuk di-assign ke role Super Admin.

**✅ Cek dulu**

```bash
php artisan test --filter=AcademySubscriptionTest
```

Seluruh 6 test harus **pass**. Kalau `test_status_kadaluarsa_setelah_lewat_tanggal` gagal, cek lagi parameter `false` di `AcademyManagementService::subscriptionStatus()`.

---

## Tahap 11 — Dokumentasi

**Tujuan**: `docs/permission-reference.md` mencerminkan kondisi kode yang sebenarnya (permission Academy Management sudah ditegakkan).

Di `docs/permission-reference.md`, ganti section **"Module: Academy Management"** (status saat ini 🚨 *Belum digerbang*) menjadi:

```markdown
## Module: Academy Management

Status: **✅ Implemented**.

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `academy.view` | Lihat daftar & detail academy (termasuk data subscription) | `academies.index`, `academies.show` (route middleware) + `@can()` tombol Detail |
| `academy.create` | Tambah academy baru | `academies.create`, `academies.store` (route middleware) + `@can()` tombol "Tambah Academy" |
| `academy.update` | Ubah profil academy & data subscription | `academies.edit`, `academies.update` (route middleware) + `@can()` tombol Edit |
| `academy.delete` | Hapus academy | `academies.destroy` (route middleware) + `@can()` tombol Hapus |

Catatan:

- `academy.*` **sengaja tidak ada di `config('faos.role_templates')` manapun**, termasuk Owner — modul ini Super-Admin-only, sama seperti `player_position.*`. Akses dari role academy manapun ditolak dengan **403** (dari middleware permission), bukan 404 seperti module tenant (Player Type/Category) yang mengandalkan `AcademyScope`.
- Menu sidebar "Academy" ada di dalam grup "Administration" yang sudah dibungkus `isSuperAdmin()` — tidak dobel-digerbang `@can()` per-item seperti menu Player, konsisten dengan menu Roles/Permissions di grup yang sama.
- **Subscription** (`subscription_type`, `subscription_fee`, `subscription_started_at`, `subscription_ends_at`) adalah field deskriptif pada tabel `academies` sendiri — dikunci oleh permission yang sama dengan profil academy (`academy.update`), **tidak punya permission terpisah**. Status langganan (Aktif/Akan Berakhir/Kadaluarsa) dihitung dari `subscription_ends_at` lewat `AcademyManagementService::subscriptionStatus()`, bukan kolom tersimpan.
```

Juga update baris **Summary** paling bawah dokumen: ganti kalimat *"Academy Management punya permission tapi belum digerbang sama sekali"* menjadi menyebutkan Academy Management sudah masuk kelompok module yang **sudah digerbang penuh**, sejajar dengan Role, Permission, Player Management, Player Type, Player Category, dan Player Position.

**✅ Cek dulu**: baca ulang section yang baru ditulis, pastikan tidak ada sisa kalimat lama yang menyebut "belum digerbang" untuk Academy Management di file ini.

---

## Tahap 12 — Permission `academy_profile.update`

**Tujuan**: permission baru ada di seeder, dan **hanya** default ke Owner (bukan role lain, bukan Super Admin — Super Admin sudah otomatis lolos segalanya lewat `Gate::before()`).

### 12a. `database/seeders/RolePermissionSeeder.php`

Di array `$permissions`, tambahkan **tepat setelah** blok `// Academy` yang sudah ada (`academy.view/create/update/delete`):

```php
            // Academy
            'academy.view',
            'academy.create',
            'academy.update',
            'academy.delete',

            // Academy Profile (self-service Owner -- BEDA dari academy.* di atas)
            'academy_profile.update',
```

### 12b. `config/faos.php` → `role_templates`

Tambahkan **satu baris di awal** array `Owner` (permission lain di array itu tetap, tidak berubah):

```php
        'Owner' => [
            'academy_profile.update',
            'player.view', 'player.create', 'player.update', 'player.delete',
            // ... baris lain TETAP, tidak berubah
        ],
```

> Role lain (Coach, Staff, Finance, Player, Parent) **tidak** diberi `academy_profile.update` — mengubah profil academy adalah keputusan level Owner, sama seperti alasan `player_type.*`/`player_category.*` hanya default ke Owner (lihat `docs/permission-reference.md`). Owner boleh mendelegasikan permission ini ke role lain lewat halaman Role Management kalau memang perlu — itu bedanya dengan `academy.*` yang **tidak pernah** bisa didelegasikan sama sekali (lihat [4.4](#44-kenapa-academy-tetap-super-admin-only-tidak-didelegasikan-ke-owner)).

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
php artisan tinker
```

```php
\App\Models\User::where('email', 'owner@faosacademy.com')->first()->can('academy_profile.update');
// harus: true

\App\Models\User::where('email', 'admin@faosacademy.com')->first()->can('academy_profile.update');
// harus: false (Staff/Admin FAOS Academy tidak dapat permission ini secara default)
```

---

## Tahap 13 — `AcademyManagementService::updateProfile()`

**Tujuan**: satu method khusus untuk update profil umum, yang **tidak mungkin** menyentuh field sensitif apapun yang dikirim.

Tambahkan method baru ini, **setelah** method `subscriptionStatus()` dari Tahap 3:

```php
    /**
     * Update field profil UMUM academy (dipakai self-service Owner lewat
     * AcademyProfileController). SENGAJA membangun payload dengan whitelist
     * eksplisit -- bukan mengoper $data mentah ke Model::update() -- supaya
     * code/status/subscription_* TIDAK PERNAH bisa lolos lewat method ini,
     * apapun yang terjadi di Form Request nanti. Lihat Bagian 4.7.
     */
    public function updateProfile(Academy $academy, array $data): Academy
    {
        return DB::transaction(function () use ($academy, $data) {

            $payload = [
                'name' => $data['name'],
                'tagline' => $data['tagline'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'description' => $data['description'] ?? null,
            ];

            if (isset($data['logo'])) {

                $this->deleteLogo($academy->logo);

                $payload['logo'] = $this->uploadLogo($data['logo'], $academy->code);
            }

            $academy->update($payload);

            return $academy;
        });
    }
```

> Perhatikan `$academy->code` dipakai (data yang **sudah ada** di record, bukan dari `$data`) untuk prefix nama file logo — sama seperti `uploadLogo()` yang sudah ada, tidak diubah caranya.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create(['code' => 'TEST', 'status' => true]);

app(\App\Services\AcademyManagementService::class)->updateProfile($academy, [
    'name' => 'Nama Baru',
    'tagline' => 'Tagline Baru',
    'phone' => '0812',
    'email' => 'a@a.com',
    'address' => 'Jl. Baru',
    // Coba selipkan field terlarang -- HARUS diabaikan:
    'code' => 'HACK',
    'status' => false,
    'subscription_fee' => 1,
]);

$academy->fresh()->code;   // harus TETAP "TEST"
$academy->fresh()->status; // harus TETAP true
```

Kalau `code`/`status` ikut berubah, berarti `updateProfile()` masih mengoper `$data` mentah alih-alih payload whitelist — ulangi.

---

## Tahap 14 — `AcademyProfileFormRequest`

**Tujuan**: validasi hanya mengenal field profil umum — field sensitif tidak boleh punya rule sama sekali di sini.

`app/Http/Requests/Academy/AcademyProfileFormRequest.php` — file baru:

```php
<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class AcademyProfileFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama academy wajib diisi.',
            'name.max' => 'Nama academy tidak boleh lebih dari 255 karakter.',
            'tagline.required' => 'Tagline wajib diisi.',
            'tagline.max' => 'Tagline tidak boleh lebih dari 255 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.max' => 'Nomor telepon tidak boleh lebih dari 50 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari 255 karakter.',
            'address.required' => 'Alamat wajib diisi.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Format gambar logo harus berupa: jpeg, png, jpg, webp, atau svg.',
            'logo.max' => 'Ukuran logo tidak boleh lebih dari 2MB.',
        ];
    }
}
```

> **Tidak ada** rule untuk `code`, `status`, atau `subscription_*` di file ini — bukan lupa, memang tidak boleh ada. Kalau ke depannya ada yang menambahkan rule untuk field itu di sini, itu tanda brief ini sedang dilanggar.

**✅ Cek dulu**: submit form profil tanpa mengisi `name` → error "Nama academy wajib diisi.", bukan crash.

---

## Tahap 15 — Controller + Route

**Tujuan**: halaman self-service yang **selalu** beroperasi di academy milik user yang login, tidak pernah menerima ID dari luar.

### 15a. `app/Http/Controllers/AcademyProfileController.php` — file baru

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academy\AcademyProfileFormRequest;
use App\Services\AcademyManagementService;
use App\Services\AcademyService;

class AcademyProfileController extends Controller
{
    protected AcademyManagementService $academyManagementService;
    protected AcademyService $academyService;

    public function __construct(
        AcademyManagementService $academyManagementService,
        AcademyService $academyService
    ) {
        $this->academyManagementService = $academyManagementService;
        $this->academyService = $academyService;
    }

    /**
     * Academy diambil dari $academyService->current() -- BUKAN route model
     * binding by ID. Halaman ini tidak pernah menerima ID dari request sama
     * sekali, supaya Owner tidak bisa mengedit academy lain lewat URL yang
     * dikarang. Lihat Bagian 4.8.
     *
     * Super Admin tidak punya "academy sendiri" (id_academy = null), jadi
     * current() akan null untuknya -- ditangani sebagai 404, bukan error 500.
     */
    public function edit()
    {
        $academy = $this->academyService->current();

        abort_if(! $academy, 404, 'Academy tidak ditemukan untuk akun ini.');

        return view('academy-profile.edit', [
            'title' => 'Profil Academy',
            'breadcrumb' => [
                ['label' => 'Profil Academy'],
            ],
            'academy' => $academy,
        ]);
    }

    public function update(AcademyProfileFormRequest $request)
    {
        $academy = $this->academyService->current();

        abort_if(! $academy, 404, 'Academy tidak ditemukan untuk akun ini.');

        try {

            $this->academyManagementService->updateProfile($academy, $request->validated());

            return redirect()
                ->route('academy.profile.edit')
                ->with('success', 'Profil academy berhasil diperbarui.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal memperbarui profil academy');
        }
    }
}
```

### 15b. `routes/web.php`

Tambahkan import:

```php
use App\Http\Controllers\AcademyProfileController;
```

Tambahkan blok route ini — taruh **dekat** blok `academies` (Tahap 6), tapi **jangan** digabung ke dalam `Route::resource('academies', ...)`:

```php
    /*
    |--------------------------------------------------------------------------
    | Academy Profile (Self-Service, Owner)
    |--------------------------------------------------------------------------
    | Singleton -- TANPA {id}, selalu beroperasi pada academy milik user yang
    | login. BEDA TOTAL dari academies.* (CRUD lintas academy, Super Admin).
    */
    Route::prefix('academy-profile')
        ->name('academy.profile.')
        ->middleware('permission:academy_profile.update')
        ->group(function () {
            Route::get('/', [AcademyProfileController::class, 'edit'])->name('edit');
            Route::patch('/', [AcademyProfileController::class, 'update'])->name('update');
        });
```

**✅ Cek dulu**

```bash
php artisan route:list --name=academy.profile
```

Harus muncul 2 route: `academy.profile.edit` (GET) dan `academy.profile.update` (PATCH), **tanpa** parameter `{academy}`/`{id}` di URL-nya (`/academy-profile`, bukan `/academy-profile/{id}`).

---

## Tahap 16 — View + Menu Sidebar

**Tujuan**: Owner punya halaman untuk mengedit profil academy-nya, dan menunya cuma tampil untuk yang punya permission.

### 16a. `resources/views/academy-profile/edit.blade.php` — file baru

Struktur meniru `academies/edit.blade.php` (Tahap 7b), **dikurangi** field `code`, `status`, dan seluruh field subscription:

```blade
@extends('layouts.app', ['page' => 'academy-profile'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Profil Academy</h3>
                <p class="card-description">Kelola informasi profil academy Anda. Kode academy, status, dan
                    informasi langganan hanya dapat diubah oleh Super Admin.</p>
            </div>
        </div>

        <form action="{{ route('academy.profile.update') }}" method="POST" enctype="multipart/form-data">

            @csrf
            @method('PATCH')

            <div class="form-row">

                {{-- Left Column --}}
                <div>

                    <div class="form-group">
                        <label for="name" class="form-label">
                            Nama Academy <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="name" name="name" value="{{ old('name', $academy->name) }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="tagline" class="form-label">
                            Tagline / Slogan <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $academy->tagline) }}"
                            class="form-input @error('tagline') form-danger @enderror" required>

                        @error('tagline')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            Nomor Telepon <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="phone" name="phone" value="{{ old('phone', $academy->phone) }}"
                            class="form-input @error('phone') form-danger @enderror" required>

                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email <span class="text-error-500">*</span>
                        </label>

                        <input type="email" id="email" name="email" value="{{ old('email', $academy->email) }}"
                            class="form-input @error('email') form-danger @enderror" required>

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                {{-- Right Column --}}
                <div>

                    <div class="form-group" x-data="{ imagePreview: null }">
                        <label class="form-label">Logo Academy</label>

                        <div class="form-file-upload">
                            <input type="file" id="logo" name="logo"
                                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" accept="image/*"
                                @change="
                            const file=$event.target.files[0];
                            if(file){
                                const reader=new FileReader();
                                reader.onload=(e)=>imagePreview=e.target.result;
                                reader.readAsDataURL(file);
                            }
                        ">

                            <div x-show="!imagePreview" class="empty-state">
                                @if ($academy->logo)
                                    <div class="avatar avatar-lg avatar-square mb-3">
                                        <img src="{{ asset('storage/' . $academy->logo) }}" class="h-full w-full object-cover">
                                    </div>
                                @endif
                                <p class="empty-title">Klik untuk unggah logo</p>
                                <p class="empty-description">SVG, PNG, JPG, WEBP maksimal 2MB</p>
                            </div>

                            <div x-show="imagePreview" x-cloak class="flex flex-col items-center">
                                <div class="avatar avatar-lg avatar-square mb-3">
                                    <img :src="imagePreview" class="h-full w-full object-cover">
                                </div>
                                <span class="link-primary text-xs font-semibold">Ganti gambar</span>
                            </div>
                        </div>

                        @error('logo')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">
                            Alamat <span class="text-error-500">*</span>
                        </label>

                        <textarea id="address" name="address" rows="3"
                            class="form-textarea @error('address') form-danger @enderror" required>{{ old('address', $academy->address) }}</textarea>

                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Deskripsi</label>

                        <textarea id="description" name="description" rows="3"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description', $academy->description) }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>

        </form>

    </div>

@endsection
```

> Tidak ada field `code`, `status`, atau subscription di halaman ini sama sekali — bukan cuma disembunyikan, memang tidak dirender. Bandingkan dengan aturan yang sama untuk field `id_academy` di form module tenant lain (`docs/multi-tenancy.md`).

### 16b. Menu sidebar

Di `resources/views/partials/sidebar.blade.php`, tambahkan menu baru **setelah** blok "Profile" (item flat yang sudah ada) dan **sebelum** blok "Administration":

```blade
                    {{-- ===== Menu Item: Profil Academy (tanpa dropdown) ===== --}}
                    @can('academy_profile.update')
                        @php
                            $isAcademyProfileActive = Route::is('academy.profile.*');
                        @endphp

                        <li>
                            <a href="{{ route('academy.profile.edit') }}"
                                class="menu-item group {{ $isAcademyProfileActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                                <svg class="{{ $isAcademyProfileActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 21V8L12 3L20 8V21H14V14H10V21H4Z" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    Profil Academy
                                </span>
                            </a>
                        </li>
                    @endcan
                    <!-- ===== END: Profil Academy ===== -->
```

> Item ini **flat** (tanpa dropdown, `Route::is('academy.profile.*')`), **bukan** ditaruh di dalam grup "Administration" yang dibungkus `isSuperAdmin()` — kalau ditaruh di sana, Owner tidak akan pernah melihatnya (grup itu memang cuma untuk Super Admin). Item ini juga **wajib** `@can('academy_profile.update')` (beda dengan item "Profile" tetangganya yang polos tanpa guard) karena tidak semua role academy tentu punya permission ini (bisa dicabut Owner dari role lain, dan role selain Owner defaultnya memang tidak punya).

**✅ Cek dulu**: login sebagai `owner@faosacademy.com` → menu "Profil Academy" tampil, buka halamannya, ubah nama & simpan → berhasil, data berubah. Login sebagai Super Admin → menu ini **tidak tampil** (karena Super Admin tidak dalam konteks academy manapun secara default), dan kalau diakses paksa lewat URL `/academy-profile` → 404 (bukan 500), karena `current()` mengembalikan `null`.

---

## Tahap 17 — Test

**Tujuan**: Owner bisa update profil sendiri, field sensitif tidak bisa diselipkan, role lain ditolak, dan Super Admin tidak crash.

`tests/Feature/AcademyProfileTest.php` — file baru:

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademyProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeAcademyUser(Academy $academy, array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Owner',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::factory()->create([
            'id_academy' => $academy->id_academy,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function makeSuperAdmin(): User
    {
        $role = Role::firstOrCreate([
            'id_academy' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'id_academy' => null,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_owner_bisa_update_profil_academy_sendiri(): void
    {
        $academy = Academy::factory()->create(['name' => 'Nama Lama']);
        $owner = $this->makeAcademyUser($academy, ['academy_profile.update']);

        $response = $this->actingAs($owner)->patch(route('academy.profile.update'), [
            'name' => 'Nama Baru',
            'tagline' => 'Tagline Baru',
            'phone' => '081234567890',
            'email' => 'baru@academy.com',
            'address' => 'Alamat Baru',
        ]);

        $response->assertRedirect(route('academy.profile.edit'));
        $this->assertSame('Nama Baru', $academy->fresh()->name);
    }

    public function test_owner_tidak_bisa_selipkan_perubahan_code_status_atau_subscription(): void
    {
        $academy = Academy::factory()->create([
            'code' => 'LAMA',
            'status' => true,
            'subscription_fee' => 500000,
        ]);
        $owner = $this->makeAcademyUser($academy, ['academy_profile.update']);

        $this->actingAs($owner)->patch(route('academy.profile.update'), [
            'name' => $academy->name,
            'tagline' => $academy->tagline,
            'phone' => $academy->phone,
            'email' => $academy->email,
            'address' => $academy->address,
            // Field terlarang, dikirim seolah request yang dikarang lewat DevTools:
            'code' => 'BARU',
            'status' => false,
            'subscription_fee' => 1,
        ]);

        $fresh = $academy->fresh();
        $this->assertSame('LAMA', $fresh->code);
        $this->assertTrue($fresh->status);
        $this->assertEquals(500000, $fresh->subscription_fee);
    }

    public function test_role_tanpa_permission_ditolak_403(): void
    {
        $academy = Academy::factory()->create();
        $staff = $this->makeAcademyUser($academy, []);

        $response = $this->actingAs($staff)->get(route('academy.profile.edit'));

        $response->assertForbidden();
    }

    public function test_super_admin_tanpa_current_academy_dapat_404_bukan_crash(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('academy.profile.edit'));

        $response->assertNotFound();
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=AcademyProfileTest
```

Seluruh 4 test harus **pass**. `test_owner_tidak_bisa_selipkan_perubahan_code_status_atau_subscription` adalah test paling penting di tahap ini — kalau gagal, berarti `updateProfile()` di Tahap 13 bocor.

---

## Tahap 18 — Dokumentasi

**Tujuan**: `docs/permission-reference.md` mendokumentasikan module baru ini sebagai entitas terpisah dari Academy Management.

Tambahkan section baru **setelah** section "Module: Academy Management" (yang baru diperbarui di Tahap 11):

```markdown
## Module: Academy Profile (Self-Service)

Status: **✅ Implemented**.

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `academy_profile.update` | Owner melihat & mengubah profil UMUM academy miliknya sendiri (nama, tagline, kontak, alamat, deskripsi, logo) | `academy.profile.edit`, `academy.profile.update` (route middleware) + menu sidebar |

Catatan:

- **Beda total** dari `academy.*` (module *Academy Management* di atas) walau sama-sama soal data Academy:
  - `academy.*` — Super-Admin-only, CRUD *lintas seluruh academy*, termasuk field sensitif (`code`, `status`, subscription).
  - `academy_profile.update` — default diberikan ke **Owner** lewat `role_templates` (bisa didelegasikan/dicabut lewat Role Management, seperti `player_type.*`), hanya bisa mengubah *academy miliknya sendiri* (tidak menerima ID dari luar), dan field-nya dibatasi ketat ke profil umum saja.
- `code`, `status`, dan seluruh field subscription **tidak pernah** bisa diubah lewat endpoint ini, ditegakkan dua lapis: `AcademyProfileFormRequest` tidak punya rule untuk field itu, dan `AcademyManagementService::updateProfile()` membangun payload update dengan whitelist eksplisit (tidak mengoper `$data` mentah ke `Model::update()`).
- Super Admin (`id_academy = null`) tidak punya "academy sendiri", jadi mengakses halaman ini menghasilkan **404** (bukan error 500), bukan cuma diblokir permission.
```

Update juga baris **Role Template Default per Academy Baru** (tabel di dokumen yang sama) untuk mencantumkan `academy_profile.update` sebagai bagian dari kolom Owner.

**✅ Cek dulu**: baca ulang seluruh dokumen, pastikan section "Module: Academy Management" dan "Module: Academy Profile" tidak saling tertukar penjelasannya.

---

## 4. Alasan Teknis

### 4.1. Kenapa `subscription_status` dihitung, bukan disimpan, dan kenapa logic-nya di Service bukan Model

Dua keputusan terpisah yang sering digabung jadi satu kesalahan:

**Kenapa dihitung, bukan kolom tersimpan.** Status subscription murni fungsi dari waktu berjalan (`now()` vs `subscription_ends_at`). Kalau disimpan sebagai kolom, nilainya cuma benar tepat setelah di-set — begitu waktu berjalan (mis. besok academy yang tadinya "Akan Berakhir" seharusnya jadi "Kadaluarsa"), kolom itu jadi basi **kecuali** ada scheduled job yang menjalankan ulang perhitungan setiap hari untuk seluruh academy. Brief ini tidak membuat job semacam itu — di luar scope. Dengan dihitung ulang tiap request, status **selalu** benar tanpa job tambahan apapun. Ini beda dengan `player_types.is_billable` (Player Type brief) yang memang layak disimpan sebagai kolom, karena nilainya adalah keputusan sadar ("type ini ditagih atau tidak"), bukan fungsi dari waktu.

**Kenapa di Service, bukan Model.** `docs/module-standard.md` eksplisit: *"Model hanya bertanggung jawab untuk: Fillable, Relationship, Scope, Cast, UUID Generation. Business logic tidak ditempatkan pada Model."* Menghitung status dari selisih tanggal adalah business rule (threshold "7 hari" adalah keputusan bisnis, bukan properti data), jadi tempatnya di `AcademyManagementService`, sejajar dengan method `create()`/`update()`/`paginate()` yang sudah ada di sana.

### 4.2. Gotcha tanda `diffInDays()`

`Carbon::diffInDays($date, $absolute)` — kalau `$absolute` bernilai `true` (default kalau parameter kedua tidak diisi), hasilnya **selalu positif**, tidak peduli apakah `$date` di masa depan atau masa lalu. Dengan `$absolute = false`, hasilnya bertanda: **positif** kalau `$date` di masa depan dari instance pemanggil, **negatif** kalau di masa lalu.

```php
now()->diffInDays(now()->addDays(3), false);   // 3   (ends_at di masa depan)
now()->diffInDays(now()->subDays(3), false);   // -3  (ends_at di masa lalu -> kadaluarsa)
now()->diffInDays(now()->addDays(3));          // 3   (absolute=true, default)
now()->diffInDays(now()->subDays(3));          // 3   (!!) -- SALAH untuk kasus kita, seharusnya -3
```

Baris terakhir itu jebakannya: tanpa `false`, academy yang **sudah lewat 3 hari dari jatuh tempo** akan menghasilkan angka `3` yang positif, lolos kondisi `$daysLeft <= 7` dan salah terbaca `"akan_berakhir"` alih-alih `"kadaluarsa"`. Test `test_status_kadaluarsa_setelah_lewat_tanggal` di Tahap 10 dibuat khusus untuk menangkap kesalahan ini.

### 4.3. Kenapa `subscription_type` string biasa, bukan ENUM database

Kolom `ENUM` di MySQL mengunci daftar nilai valid **di level schema**. Menambah nilai baru (mis. suatu saat ada tipe `lifetime` atau `trial`) butuh migration `ALTER TABLE academies MODIFY subscription_type ENUM(...)` — pada tabel yang sudah berisi banyak baris, operasi ini bisa me-lock tabel dan lambat. Dengan kolom `string` biasa + validasi `Rule::in()` di `AcademyFormRequest` (Tahap 4), menambah tipe baru cukup menambah satu baris di `AcademyManagementService::SUBSCRIPTION_TYPES` — tidak ada migration sama sekali. Ini pola yang sama dengan `player_types.name`/`player_categories.name` yang juga string biasa, bukan ENUM, walau untuk alasan berbeda (di sana nilainya custom per academy; di sini nilainya fixed tapi tetap dijaga app-level supaya gampang diperluas).

### 4.4. Kenapa Academy tetap Super-Admin-only, tidak didelegasikan ke Owner

Alasan paling konkret: kalau `academy.update` diberikan ke role `Owner` (baik lewat `role_templates` default maupun lewat halaman Role Management yang memungkinkan Owner mengatur ulang permission role-nya sendiri), Owner academy tersebut bisa **mengubah `subscription_fee`/`subscription_ends_at` miliknya sendiri** lewat form edit yang sama persis dipakai Super Admin — tidak ada pemisahan field mana yang "boleh diubah Owner" vs "cuma boleh diubah Super Admin" dalam satu `AcademyFormRequest`/`AcademyController`. Ini konflik kepentingan langsung: pihak yang ditagih diberi akses mengubah catatan tagihannya sendiri.

Solusinya sama dengan `player_position.*` (lihat `docs/permission-reference.md` → *Module: Player Position*): `academy.*` **tidak pernah** dimasukkan ke `config('faos.role_templates')` manapun, sehingga tidak ada jalur bagi Owner untuk mendapatkannya — baik dari default maupun kustomisasi lewat Role Management (Role Management hanya bisa mengatur ulang permission yang memang ada di sistem, tapi Owner sendiri tidak pernah otomatis punya `academy.*` untuk didelegasikan ke role lain). Kalau nanti dibutuhkan Owner melihat (bukan mengubah) status langganannya sendiri, itu fitur terpisah (mis. widget read-only di dashboard Owner) — didiskusikan dulu, bukan dengan memberi `academy.view` ke Owner.

### 4.5. Kenapa sidebar Academy tidak perlu `@can()` per-item

Ada dua pola berbeda yang sudah dipakai di `resources/views/partials/sidebar.blade.php`:

1. **Grup "Football Academy"/"Master"** (Players, Player Type, Player Category, Player Position) — menu-menu ini **bisa muncul untuk role selain Super Admin** (mis. Owner, Staff), jadi tiap item **wajib** `@can('module.view')` sendiri-sendiri untuk menentukan item mana yang tampil untuk role yang login.
2. **Grup "Administration"** (Roles, Permissions, Academy) — seluruh grup ini sudah dibungkus satu `@if (app(\App\Services\AcademyService::class)->isSuperAdmin())` di level judul grup. Karena Super Admin **selalu** lolos seluruh permission check (`Gate::before()`), menambahkan `@can()` per-item di dalam blok yang sudah pasti Super-Admin-only tidak mengubah apapun secara fungsional — cuma kode berlebih. Roles dan Permissions (yang permission-nya sudah "✅ Implemented" jauh sebelum brief ini) juga tidak melakukannya, jadi menambahkan `@can()` khusus untuk item Academy akan **tidak konsisten** dengan tetangganya di grup yang sama.

Kesimpulan: cukup pastikan **route** (Tahap 6) dan **tombol aksi di dalam halaman** (Tahap 9e) yang digerbang `@can()` — menu sidebar sudah cukup aman lewat `isSuperAdmin()`.

### 4.6. Kenapa tidak ada tabel riwayat, padahal subscription harusnya berulang

Di dunia nyata, subscription memang berulang: academy bayar, dapat periode aktif, lalu bayar lagi untuk periode berikutnya, dan seterusnya. Idealnya ada tabel `academy_subscription_history`/`payment_transactions` yang mencatat **setiap** periode sebagai baris terpisah (kapan bayar, berapa, periode berapa sampai berapa).

Brief ini **sengaja tidak membangun itu**, karena:

1. Permintaan awal eksplisit: "2 field mungkin bisa lebih" — bukan minta module billing/histori penuh.
2. Riwayat pembayaran adalah domain module **Payment** (`payment.*`), yang permission-nya sudah disiapkan di seeder tapi Controller/View-nya **belum dibangun** (lihat `docs/permission-reference.md` → *Permission Belum Dipakai Module Manapun*). Membangun tabel riwayat sekarang, sebelum ada module yang benar-benar menuliskan ke situ (mis. saat Super Admin mencatat perpanjangan), berisiko bentuk tabelnya salah tebak dan harus diubah lagi saat module Payment benar-benar digarap.

4 kolom di brief ini (`subscription_type/fee/started_at/ends_at`) berfungsi sebagai **snapshot periode aktif saat ini** — cukup untuk kebutuhan "Super Admin tahu academy mana yang harus segera diperpanjang". Kalau/saat module Payment dibangun, field-field ini secara alami jadi tempat yang di-update Payment setiap kali academy memperpanjang langganannya, sementara detail tiap transaksi masuk ke tabel riwayat yang baru. Itu didiskusikan dulu saat module Payment benar-benar digarap, bukan diasumsikan bentuknya sekarang.

### 4.7. Kenapa `updateProfile()` membangun payload manual, bukan percaya `validated()` begitu saja

Pola CRUD lain di codebase ini (`AcademyManagementService::create()`/`update()`, `PlayerTypeService::create()`, dst) memang langsung mengoper `$request->validated()` ke `Model::create()`/`update()` — itu aman **selama** satu-satunya Form Request untuk model itu memvalidasi seluruh field yang boleh diubah lewat form itu.

`AcademyProfileFormRequest` beda konteks: ia sengaja **cuma** memvalidasi sebagian kecil dari seluruh field `Academy` yang sebenarnya ada (7 dari 12 kolom yang bisa diisi). Kalau `updateProfile()` ditulis dengan pola yang sama (`$academy->update($data)` langsung), maka **satu-satunya** yang mencegah Owner mengubah `code`/`status`/`subscription_*` adalah "kebetulan `AcademyProfileFormRequest::rules()` tidak menyebut field itu" — sebuah invariant yang gampang terlanggar tanpa disadari (mis. suatu saat programmer lain menambah `'status' => ['sometimes', 'boolean']` ke `rules()` untuk alasan yang kelihatannya masuk akal, tanpa sadar itu baru saja membuka celah yang brief ini coba tutup).

Dengan payload dibangun eksplisit di `updateProfile()` (Tahap 13) — menyebut satu-satu `name`, `tagline`, `phone`, `email`, `address`, `description`, `logo` — keamanannya tidak bergantung pada "apa yang lupa divalidasi", tapi pada "apa yang eksplisit diizinkan". Menambah field baru ke `rules()` tanpa menyentuh `updateProfile()` tidak akan berpengaruh apapun ke database — defense in depth yang sesuai dengan sensitivitas endpoint ini (satu-satunya endpoint di seluruh sistem yang sengaja memberi tenant user akses tulis ke record `Academy` miliknya sendiri).

### 4.8. Kenapa Academy Profile tidak pakai route model binding

Pola CRUD module lain di FAOSBall memakai route model binding (`Route::resource('academies', ...)`, `PlayerType $playerType`, dst) yang menerima ID lewat URL, lalu isolasi antar-tenant dijaga oleh `AcademyScope` (kalau modelnya tenant) atau Policy (kalau seperti Role). **`Academy` sendiri tidak pernah dilindungi salah satu dari itu** — ia root tenant, tidak ada scope yang otomatis membatasi "academy mana yang boleh diakses user ini" seperti pada model tenant biasa.

Kalau Academy Profile dibuat dengan pola `Route::get('/academy-profile/{academy}', ...)` lalu di dalam Controller cuma mengandalkan `$this->authorize()` atau perbandingan manual, ada risiko besar salah implementasi (mis. lupa membandingkan `$academy->id_academy` dengan academy milik user login) yang berakibat Owner Academy A bisa mengedit profil Academy B lewat URL `/academy-profile/{id-academy-b}` yang ditebak/dikarang. Dengan `$academyService->current()` (Tahap 15a) — yang mengambil academy dari **sesi user yang login**, bukan dari input URL — pertanyaan "apakah ID ini boleh diakses user ini" tidak pernah muncul sama sekali, karena tidak ada ID yang diterima dari luar. Ini pola yang sama dipakai `ProfileController` bawaan Breeze untuk data `User` (`/profile`, tanpa `{user}`) — kita meniru pola yang sudah terbukti aman untuk kasus "resource milik user yang login sendiri".

---

## 5. Development Checklist

Sebelum brief ini dinyatakan selesai, cocokkan dengan checklist `docs/module-standard.md`:

- [ ] Migration: 4 kolom nullable, urutan setelah `status`.
- [ ] Model: fillable + cast bertambah, **tidak ada** logic baru.
- [ ] Service: `SUBSCRIPTION_TYPES`, `subscriptionStatus()`, `paginate()` menempel `subscription_status` per baris.
- [ ] Form Request: 4 rule baru, required, `after_or_equal` untuk tanggal berakhir.
- [ ] Controller: `create()`/`edit()`/`show()`/`index()` mengirim `subscriptionTypes` (dan `show()` juga `subscriptionStatus`).
- [ ] Route: `middlewareFor()` mencakup ketujuh action, termasuk `show`.
- [ ] View create/edit: 4 field baru, nullsafe (`?->`) untuk data lama.
- [ ] View show: card "Informasi Langganan" + badge status.
- [ ] View index: kolom Langganan di tabel **dan** card list, colspan empty-state diperbarui, seluruh tombol aksi dibungkus `@can()`.
- [ ] Test: 6 skenario pass (4 status + 2 permission gate).
- [ ] `docs/permission-reference.md`: Academy Management jadi ✅ Implemented, section Summary ikut diperbarui.
- [ ] Manual: login sebagai role academy biasa, akses `/academies` langsung lewat URL → 403.
- [ ] Permission `academy_profile.update` ada di seeder & default ke role Owner saja.
- [ ] `AcademyManagementService::updateProfile()` membangun payload whitelist eksplisit — terverifikasi lewat test `test_owner_tidak_bisa_selipkan_perubahan_code_status_atau_subscription`.
- [ ] Route `academy.profile.edit`/`academy.profile.update` singleton, tanpa parameter `{id}`.
- [ ] View `academy-profile/edit.blade.php` tidak merender field `code`/`status`/subscription sama sekali.
- [ ] Menu sidebar "Profil Academy" tampil untuk Owner, dibungkus `@can('academy_profile.update')`, di luar grup "Administration".
- [ ] Test: 4 skenario `AcademyProfileTest` pass (update sukses, field terlarang diabaikan, role tanpa permission 403, Super Admin tanpa current academy 404).
- [ ] `docs/permission-reference.md`: section "Module: Academy Profile" ditambahkan, terpisah dari "Module: Academy Management".

## Summary

Brief ini menambahkan 4 kolom informasi langganan (`subscription_type`, `subscription_fee`, `subscription_started_at`, `subscription_ends_at`) langsung ke tabel `academies` — bukan tabel terpisah, karena Academy adalah root tenant. Status langganan (Aktif/Akan Berakhir/Kadaluarsa) dihitung on-the-fly oleh `AcademyManagementService`, tidak disimpan, supaya tidak butuh scheduled job. Sekaligus menutup celah lama: Academy Management yang sejak awal permission-nya sudah ada di seeder tapi tidak pernah ditegakkan di route/controller/view — sekarang jadi Super-Admin-only yang benar-benar terkunci, dengan alasan konkret khusus untuk module ini (Owner tidak boleh mengubah biaya langganannya sendiri).

Menutup akses itu sepenuhnya akan menghilangkan satu-satunya jalan Owner mengelola profil academy-nya sendiri, jadi brief ini juga membangun **Academy Profile** — halaman self-service terpisah total (permission, route, Controller, Form Request, dan Service method sendiri) yang cuma bisa mengubah field profil umum (nama, tagline, kontak, alamat, deskripsi, logo) pada academy milik Owner sendiri, dengan dua lapis pertahanan (Form Request tidak mengenal field sensitif + Service membangun payload whitelist eksplisit) supaya `code`, `status`, dan subscription tidak pernah bisa ikut terselip lewat jalur ini. Riwayat pembayaran/perpanjangan **sengaja** di luar scope, menunggu module Payment.
