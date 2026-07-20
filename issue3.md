# Brief: Academy Logo — Varian Ukuran untuk Sidebar & Favicon (Dynamic Branding)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu. Terutama `docs/frontend-standard.md` (section *Reusable View dengan Data Dinamis* — Blade Component vs View Composer), `docs/architecture.md`, dan `docs/query-performance.md`. Baca juga `issue.md` dan `issue2.md` karena brief ini menyentuh `AcademyManagementService` dan `AcademyService` yang sudah dibangun/diperluas di sana.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 9 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> Kalau cuma mau eksekusi, cukup baca Bagian 0–3 lalu langsung ke Tahap 1. Bagian 4 (alasan teknis) boleh dibaca belakangan, **tapi aturannya tetap tidak boleh dilanggar**.

---

## 0. Aturan Emas

Sembilan larangan ini bukan preferensi gaya. Masing-masing sudah diverifikasi akan bikin bug atau celah nyata. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Pakai `cover()` (crop paksa) untuk varian **sidebar** | Sempat dicoba (`cover(65, 65)`, persegi) tapi hasilnya terasa kekecilan/kurang proporsional untuk slot sidebar yang sebenarnya lebar (mirip proporsi logo sistem yang sekarang). Slot sidebar **wajib** `scaleDown(245, 65)` — muat di bounding box 245×65, jaga aspect ratio, TANPA crop | [4.1](#41-kenapa-scaledown245-65-untuk-varian-sidebar-tapi-cover6464-untuk-favicon) |
| Pakai `scaleDown()` untuk varian **favicon** | Favicon browser **wajib** persegi — `scaleDown()` bisa menghasilkan file non-persegi. Slot favicon **wajib** tetap `cover(64, 64)` (crop persegi, `Alignment::CENTER` default) | [4.1](#41-kenapa-scaledown245-65-untuk-varian-sidebar-tapi-cover6464-untuk-favicon) |
| Panggil method resize Intervention Image dua kali dari **objek `Image` yang sama** tanpa `clone` | Intervention Image v3 **memutasi objek in-place** (bukan immutable) — resize kedua akan dijalankan di atas hasil resize pertama (ukurannya sudah berubah), bukan dari gambar asli. Hasilnya varian kedua rusak/salah ukuran secara diam-diam, tidak ada error yang muncul | [Tahap 4](#tahap-4--academymanagementservice-generatelogovariants--integrasi) |
| Asumsikan semua upload logo bisa diproses Intervention Image | Validasi upload logo (`AcademyFormRequest`, `AcademyProfileFormRequest`) **mengizinkan SVG** (`mimes:...,svg`). SVG adalah vektor — driver GD (dipakai brief ini, lihat [4.2](#42-kenapa-gd-bukan-imagick-dan-kenapa-svg-di-skip-bukan-ditolak)) **tidak bisa membacanya**. Kalau tidak ditangani, upload logo SVG akan melempar exception dan menggagalkan `Academy::create()`/`update()` yang seharusnya tetap berhasil | [4.2](#42-kenapa-gd-bukan-imagick-dan-kenapa-svg-di-skip-bukan-ditolak) |
| Biarkan kegagalan generate varian logo menggagalkan `create()`/`update()`/`updateProfile()` Academy | Varian logo adalah peningkatan visual (nice-to-have), bukan data inti academy. Kegagalan resize (file korup, GD kehabisan memori, dll) **wajib** ditangkap (try/catch) dan di-log, bukan melempar exception yang membatalkan `DB::transaction()` penyimpanan academy itu sendiri | [Tahap 4](#tahap-4--academymanagementservice-generatelogovariants--integrasi) |
| Lupa memperbarui `updateProfile()` (dipakai Owner self-service, `issue.md` Tahap 13) selain `create()`/`update()` (Super Admin) | Owner bisa ganti logo academy-nya sendiri lewat halaman Profil Academy — kalau method ini dilewatkan, logo yang di-upload Owner **tidak pernah** dapat varian sidebar/favicon, cuma logo yang di-upload lewat Academy Management (Super Admin) yang dapat | [Tahap 4](#tahap-4--academymanagementservice-generatelogovariants--integrasi) |
| Sentuh `resources/views/layouts/app-auth.blade.php` (favicon halaman login/register) | Halaman ini dirender **sebelum** ada sesi login — tidak ada `Auth::user()`, jadi tidak ada "academy aktif" untuk dijadikan sumber favicon. Favicon di layout ini **wajib** tetap statis | [4.3](#43-kenapa-halaman-login-tidak-ikut-dinamis) |
| Pakai `View::composer()` di `AppServiceProvider` untuk mengikat URL logo ke `sidebar.blade.php`/`header.blade.php` | Melanggar `docs/frontend-standard.md` — dokumen itu eksplisit melarang `View::composer()` untuk kasus "partial butuh data dihitung sendiri", **wajib** pakai class-based Blade Component (`App\View\Components\Xxx`), persis pola `AuthSidebar`/`Breadcrumb` yang sudah ada | [Tahap 5](#tahap-5--blade-component-academylogo-baru) |
| Bikin 3 kolom/varian logo terpisah (sidebar, header, favicon) | Baca ulang markup `sidebar.blade.php` dan `header.blade.php` yang sudah ada — **keduanya sudah memakai file logo yang sama persis** untuk slot "lebar" (cek `KantinITSvg.svg` dipakai di dua file itu), dan slot "kecil persegi" di sidebar collapsed **sudah** memakai file yang sama persis dengan favicon (`kantinit-favicon.png`). Jadi cuma **2 varian** yang benar-benar dibutuhkan, bukan 3 — lihat [2a](#2a-cuma-2-varian-yang-dibutuhkan-bukan-3) | [2a](#2a-cuma-2-varian-yang-dibutuhkan-bukan-3) |
| Pakai `:class="sidebarToggle ? ... : ..."` (satu titik dua) di tag `<x-academy-logo>` | Atribut berawalan `:` pada tag **component** (beda dengan tag HTML polos) **selalu** dievaluasi Blade sebagai ekspresi PHP saat compile — `sidebarToggle` adalah variabel Alpine.js, bukan PHP, hasilnya error `Undefined constant "sidebarToggle"`. **Wajib** pakai `::class` (double colon) supaya diteruskan mentah untuk Alpine. Sudah diverifikasi langsung — error ini benar-benar muncul saat brief ini diimplementasikan | [Tahap 7a](#7a-resourcesviewspartialssidebarbladephp) |

---

## 1. Tujuan

Saat ini logo yang tampil di sidebar (desktop), header (mobile), dan favicon browser **selalu** logo sistem statis (`KantinITSvg.svg` / `kantinit-favicon.png`) — sama untuk semua academy, tidak peduli academy mana yang sedang login. Academy sendiri sudah bisa upload logo lewat form Tambah/Edit Academy (Super Admin) maupun halaman Profil Academy (Owner, self-service — `issue.md` Tahap 12–18), tapi logo itu **cuma** dipakai di halaman Detail Academy (avatar) — tidak pernah tampil di elemen navigasi sistem.

**Scope brief ini**: saat academy upload logo, sistem otomatis membuat 2 varian ukuran (selain file asli yang sudah ada) — satu untuk slot "logo lebar" (sidebar desktop + header mobile), satu untuk slot "ikon persegi" (sidebar collapsed + favicon browser). User yang login **dalam konteks academy tertentu** (siapapun kecuali Super Admin) akan melihat logo academy-nya sendiri di ketiga slot itu, bukan logo sistem. Kalau academy belum upload logo (atau upload SVG yang tidak bisa diproses), atau user itu Super Admin (tidak dalam konteks academy manapun), sistem **fallback** ke logo statis yang sudah ada — tidak pernah tampil kosong/rusak.

**Bukan** scope brief ini: mengubah logo yang tampil di halaman Detail Academy (avatar) — itu tetap pakai `$academy->logo` (file asli) seperti sekarang, tidak tersentuh. Juga bukan scope: dark-mode variant logo yang benar-benar berbeda (sistem saat ini pun cuma pakai 1 file yang sama untuk kelas `dark:hidden` dan `hidden dark:block` — brief ini meniru pola yang sama, bukan menambah logo terpisah untuk dark mode).

---

## 2. Cara Kerja Solusi

Baca sampai paham. Kalau bagian ini tidak nyantol, sisa brief akan terasa acak.

### 2a. Cuma 2 varian yang dibutuhkan, bukan 3

Menelusuri markup yang sudah ada di 3 tempat logo muncul:

| File | Slot | Class/Konteks | File logo dipakai saat ini |
|---|---|---|---|
| `sidebar.blade.php` | Logo lebar (sidebar expanded) | `.logo` (2× `<img>`, dark/light — **file yang sama**) | `KantinITSvg.svg` |
| `sidebar.blade.php` | Ikon persegi (sidebar collapsed) | `.logo-icon` | `kantinit-favicon.png` |
| `header.blade.php` | Logo lebar (mobile) | 2× `<img>`, dark/light — **file yang sama** | `KantinITSvg.svg` |
| `layouts/app.blade.php` | Favicon browser | `<link rel="icon">` | `kantinit-favicon.png` |

Slot "logo lebar" muncul di 2 tempat (`sidebar.blade.php` + `header.blade.php`) tapi **sudah** memakai file yang identik hari ini. Slot "ikon persegi" juga muncul di 2 tempat (`sidebar.blade.php` collapsed + favicon `app.blade.php`) dan **juga sudah** memakai file yang identik. Jadi menambah kolom/varian ketiga untuk "header" secara terpisah dari "sidebar" cuma duplikasi tanpa manfaat — sistem yang ada sendiri tidak membedakannya. Brief ini menamai 2 varian itu sesuai kolom database yang dibuat di Tahap 2: `logo_sidebar` (dipakai di slot lebar manapun) dan `logo_favicon` (dipakai di slot ikon persegi manapun).

### 2b. Kenapa resize dilakukan saat upload, bukan on-the-fly

Sudah dibahas & disepakati sebelum brief ini ditulis: resize **wajib** terjadi sekali saat file di-upload (di dalam `AcademyManagementService`), hasilnya disimpan sebagai file statis baru di disk `public`. Yang dilayani ke browser setiap page load cuma file yang sudah jadi — tidak ada proses image processing yang berjalan berulang tiap request. Ini menjaga beban ke performa sistem tetap minimal (lihat [4.4](#44-dampak-performa)).

### 2c. Alur pembuatan varian

```text
1. User upload file logo (lewat Tambah/Edit Academy ATAU Profil Academy)
2. AcademyManagementService::uploadLogo() -- simpan file ASLI apa adanya (TIDAK BERUBAH, sudah ada)
3. AcademyManagementService::generateLogoVariants() -- BARU:
   a. Kalau ekstensi file SVG -> return null untuk kedua varian, SELESAI (lihat 4.2)
   b. Baca file dengan Intervention Image (driver GD)
   c. clone gambar -> scaleDown(245, 65) -> encode PNG -> simpan sebagai logo_sidebar
   d. clone gambar -> cover(64, 64) -> encode PNG -> simpan sebagai logo_favicon
   e. Kalau ada exception di langkah manapun -> tangkap, log warning, return null untuk keduanya
4. Ketiga path (logo, logo_sidebar, logo_favicon) disimpan ke kolom Academy yang sama
```

### 2d. Resolusi URL logo saat render (fallback ke statis)

`AcademyService` (yang sudah ada, dipakai `AcademyProfileController` di `issue.md`) dapat 2 method baru: `sidebarLogoUrl()` dan `faviconUrl()`. Keduanya menggabungkan 2 pengecekan yang sudah didiskusikan sebelumnya jadi satu tempat:

```text
sidebarLogoUrl():
  - current() (academy aktif user login) == null (Super Admin)  -> logo statis sistem
  - current()->logo_sidebar == null (belum upload / SVG di-skip) -> logo statis sistem
  - selain itu                                                   -> URL storage logo_sidebar
```

Logic yang identik berlaku untuk `faviconUrl()` dengan `logo_favicon`. Dengan begini, Blade/Component yang memakainya **tidak perlu tahu** kenapa fallback terjadi (Super Admin? belum upload? SVG di-skip?) — cukup panggil method-nya, satu sumber kebenaran, sama seperti `isSuperAdmin()`/`current()` yang sudah ada.

### 2e. Kenapa Blade Component, bukan View Composer

`docs/frontend-standard.md` sudah punya section eksplisit soal ini (*Reusable View dengan Data Dinamis*) dengan contoh `AuthSidebar`, `Breadcrumb`. Brief ini menambah satu lagi: `App\View\Components\AcademyLogo`, dipakai sebagai `<x-academy-logo variant="sidebar" />` / `<x-academy-logo variant="favicon" />` — meng-encapsulate pemanggilan `AcademyService` dan expose hasilnya (`$url`) ke view component, bukan wiring tersembunyi lewat `View::composer()` di `AppServiceProvider`.

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `composer.json` (`intervention/image`) | ✏️ Tambah dependency | 1 |
| `database/migrations/…_add_logo_variants_to_academies_table.php` | 🆕 Baru | 2 |
| `app/Models/Academy.php` | ✏️ Tambah fillable `logo_sidebar`, `logo_favicon` | 3 |
| `app/Services/AcademyManagementService.php` | ✏️ Tambah `generateLogoVariants()`, `deleteLogoVariants()`, `processLogoUpload()`, ubah `create()`/`update()`/`updateProfile()`/`delete()` | 4 |
| `app/Services/AcademyService.php` | ✏️ Tambah `sidebarLogoUrl()`, `faviconUrl()` | 5 |
| `app/View/Components/AcademyLogo.php` | 🆕 Baru | 6 |
| `resources/views/components/academy-logo.blade.php` | 🆕 Baru | 6 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Ganti 3 `<img>` statis jadi `<x-academy-logo>` | 7 |
| `resources/views/partials/header.blade.php` | ✏️ Ganti 3 `<img>` statis jadi `<x-academy-logo>` (2 logo lebar + 1 avatar user) | 7 |
| `resources/views/layouts/app.blade.php` | ✏️ Ganti `<link rel="icon">` jadi dinamis | 7 |
| `tests/Feature/AcademyLogoVariantTest.php` | 🆕 Baru | 8 |
| `docs/frontend-standard.md` | ✏️ Tambah `AcademyLogo` ke daftar contoh Blade Component | 9 |
| **`resources/views/layouts/app-auth.blade.php`** | 🚫 **Jangan sentuh** — favicon halaman login tidak punya konteks academy, tetap statis. Lihat [4.3](#43-kenapa-halaman-login-tidak-ikut-dinamis) | — |
| **`resources/views/academies/show.blade.php`** (avatar logo) | 🚫 **Jangan sentuh** — avatar di Detail Academy tetap pakai `$academy->logo` (file asli), bukan varian baru | — |
| **`app/Http/Requests/Academy/AcademyFormRequest.php`, `AcademyProfileFormRequest.php`** | 🚫 **Jangan sentuh** — validasi upload logo (termasuk `mimes:...,svg`) tidak berubah. SVG tetap diterima, cuma di-skip dari proses resize | — |

---

## Tahap 1 — Composer: `intervention/image`

**Tujuan**: library resize gambar terpasang, pakai driver GD (Imagick **tidak** terpasang di environment ini — sudah dicek).

```bash
composer require intervention/image
```

**✅ Cek dulu**

```bash
composer show intervention/image
php -m
```

- `intervention/image` muncul di `composer show`.
- `php -m` memuat `gd` (harus sudah ada, environment ini sudah dicek punya GD, bukan Imagick).

---

## Tahap 2 — Migration

**Tujuan**: `academies` punya 2 kolom baru, nullable, tepat setelah `logo`.

```bash
php artisan make:migration add_logo_variants_to_academies_table --table=academies
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
            | Logo Variants
            |--------------------------------------------------------------------------
            | Nullable -- academy boleh belum upload logo, atau upload SVG (tidak bisa
            | diproses Intervention Image / GD, lihat issue3.md Bagian 4.2), dalam
            | kasus itu kedua kolom tetap null dan sistem fallback ke logo statis.
            |
            | logo_sidebar : hasil cover 65x65 (crop persegi, center), dipakai
            |                di slot "logo lebar" -- sidebar (expanded) + header mobile.
            | logo_favicon : hasil cover (crop ke persegi), dipakai di slot "ikon
            |                persegi" -- sidebar (collapsed) + <link rel="icon">.
            */
            $table->string('logo_sidebar')
                ->nullable()
                ->after('logo');

            $table->string('logo_favicon')
                ->nullable()
                ->after('logo_sidebar');
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn(['logo_sidebar', 'logo_favicon']);
        });
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table academies
```

- Ada 2 kolom baru: `logo_sidebar`, `logo_favicon`, keduanya nullable, tepat setelah `logo`.

---

## Tahap 3 — Model

**Tujuan**: `Academy` tahu kolom barunya, tanpa logic tambahan.

`app/Models/Academy.php` — tambahkan ke `$fillable`, tepat setelah `'logo'`:

```php
protected $fillable = [
    'id_owner_user',
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
    'logo_sidebar',
    'logo_favicon',
    'description',
];
```

> **Jangan tambahkan apapun selain ini ke Model.** Tidak ada accessor `getLogoUrlAttribute()`, tidak ada logic fallback di sini — itu semua di `AcademyService` (Tahap 5), bukan Model. Alasan sama seperti `subscription_status` di `issue.md` [4.1](../issue.md#41-kenapa-subscription_status-dihitung-bukan-disimpan-dan-kenapa-logic-nya-di-service-bukan-model).

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\Models\Academy)->getFillable();
// harus memuat 'logo_sidebar' dan 'logo_favicon'
```

---

## Tahap 4 — `AcademyManagementService`: `generateLogoVariants()` + integrasi

**Tujuan**: setiap kali logo di-upload (lewat Academy Management **atau** Academy Profile self-service), 2 varian ikut dibuat — dan kalau upload lama diganti/academy dihapus, file lama (termasuk variannya) ikut dibersihkan.

Tambahkan import di bagian atas file:

```php
use App\Models\Academy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
```

> **Catatan versi**: `composer require intervention/image` menginstall **v4** (v3 sudah bukan versi stable terbaru), yang API-nya beda dari kode di bawah kalau kamu terbiasa dokumentasi v3 lama — `ImageManager::read()` **tidak ada lagi** di v4 (dipakai `decodePath()`), dan `->toPng()` diganti `->encode(new PngEncoder())`. Kode di brief ini **sudah** disesuaikan ke v4, sudah diverifikasi jalan.

Tambahkan 3 method baru **setelah** `deleteLogo()` yang sudah ada:

```php
    /**
     * Hapus file logo asli + kedua variannya (kalau ada). Dipakai saat logo
     * diganti (create ulang tidak butuh ini, cuma update/updateProfile/delete).
     */
    protected function deleteLogoVariants(Academy $academy): void
    {
        $this->deleteLogo($academy->logo);
        $this->deleteLogo($academy->logo_sidebar);
        $this->deleteLogo($academy->logo_favicon);
    }


    /**
     * Batas bounding box varian logo_sidebar -- muat di dalam kotak ini, jaga
     * aspect ratio, TANPA crop (scaleDown). Proporsi lebar (245x65) meniru
     * bentuk logo sistem yang sekarang (wordmark lebar).
     */
    protected const LOGO_SIDEBAR_MAX_WIDTH = 245;
    protected const LOGO_SIDEBAR_MAX_HEIGHT = 65;


    /**
     * Generate 2 varian ukuran dari file logo yang baru di-upload.
     *
     * - logo_sidebar : scaleDown ke bounding box 245x65 (jaga aspect ratio,
     *   TANPA crop) -- lihat issue3.md Bagian 4.1 untuk riwayat keputusan ini.
     * - logo_favicon : cover ke 64x64 (crop persegi, center) -- favicon browser
     *   WAJIB persegi.
     *
     * SVG di-skip (bukan error) -- driver GD tidak bisa membaca vektor. Kegagalan
     * apapun di sini ditangkap, TIDAK BOLEH menggagalkan create/update Academy
     * yang memanggilnya. Lihat issue3.md Bagian 4.2.
     */
    protected function generateLogoVariants($file, string $academyCode): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'svg') {
            return [
                'logo_sidebar' => null,
                'logo_favicon' => null,
            ];
        }

        try {

            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());

            $sidebarPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-sidebar.png';
            $faviconPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-favicon.png';

            // WAJIB clone -- Intervention Image memutasi objek in-place, resize
            // kedua tanpa clone akan dijalankan di atas hasil resize pertama.
            Storage::disk('public')->put(
                $sidebarPath,
                (string) (clone $image)->scaleDown(
                    self::LOGO_SIDEBAR_MAX_WIDTH,
                    self::LOGO_SIDEBAR_MAX_HEIGHT
                )->encode(new PngEncoder())
            );

            Storage::disk('public')->put(
                $faviconPath,
                (string) (clone $image)->cover(64, 64)->encode(new PngEncoder())
            );

            return [
                'logo_sidebar' => $sidebarPath,
                'logo_favicon' => $faviconPath,
            ];

        } catch (\Throwable $e) {

            Log::warning('Gagal generate varian logo academy', [
                'academy_code' => $academyCode,
                'exception' => $e->getMessage(),
            ]);

            return [
                'logo_sidebar' => null,
                'logo_favicon' => null,
            ];
        }
    }


    /**
     * Upload logo asli + generate variannya sekaligus, dipakai bersama oleh
     * create()/update()/updateProfile() supaya logic-nya tidak tertulis 3 kali.
     */
    protected function processLogoUpload($file, string $academyCode): array
    {
        return array_merge(
            ['logo' => $this->uploadLogo($file, $academyCode)],
            $this->generateLogoVariants($file, $academyCode)
        );
    }
```

Ubah `create()` yang sudah ada (ganti blok `if (isset($data['logo']))`):

```php
    public function create(array $data): Academy
    {
        return DB::transaction(function () use ($data) {

            $data['code'] = strtoupper($data['code']);
            $data['slug'] = $this->generateSlug($data['name']);
            $data['status'] = $data['status'] ?? false;

            if (isset($data['logo'])) {
                $data = array_merge($data, $this->processLogoUpload($data['logo'], $data['code']));
            }

            $academy = Academy::create($data);

            $this->roleService->createDefaultRoles($academy);
            $this->playerTypeService->createDefaultPlayerTypes($academy);
            $this->playerCategoryService->createDefaultPlayerCategories($academy);

            if (!empty($data['create_account'])) {

                $owner = $this->accountService->create([
                    'id_academy' => $academy->id_academy,
                    'name' => $academy->name,
                    'email' => $data['owner_email'],
                    'password' => $data['owner_password'],
                ], 'Owner');

                $academy->update([
                    'id_owner_user' => $owner->id_user,
                ]);
            }

            return $academy;
        });
    }
```

Ubah `update()` yang sudah ada:

```php
    public function update(Academy $academy, array $data): Academy
    {
        return DB::transaction(function () use ($academy, $data) {

            $data['code'] = strtoupper($data['code']);
            $data['slug'] = $this->generateSlug($data['name']);
            $data['status'] = $data['status'] ?? false;

            if (isset($data['logo'])) {
                $this->deleteLogoVariants($academy);
                $data = array_merge($data, $this->processLogoUpload($data['logo'], $data['code']));
            }

            $academy->update($data);

            return $academy;
        });
    }
```

Ubah `updateProfile()` yang sudah ada (Owner self-service, `issue.md` Tahap 13 — **jangan lewatkan ini**):

```php
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
                $this->deleteLogoVariants($academy);
                $payload = array_merge($payload, $this->processLogoUpload($data['logo'], $academy->code));
            }

            $academy->update($payload);

            return $academy;
        });
    }
```

Ubah `delete()` yang sudah ada:

```php
    public function delete(Academy $academy): bool
    {
        return DB::transaction(function () use ($academy) {

            $this->deleteLogoVariants($academy);

            return $academy->delete();
        });
    }
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
use Illuminate\Http\UploadedFile;

$svc = app(\App\Services\AcademyManagementService::class);

// Upload logo RASTER (jpg/png) -- harus dapat 2 varian
$file = UploadedFile::fake()->image('logo.png', 300, 300);
$academy = \App\Models\Academy::factory()->create();
$academy = $svc->update($academy, array_merge($academy->only(['name','code','phone','email','address','tagline','status','subscription_type','subscription_fee','subscription_started_at','subscription_ends_at']), ['logo' => $file]));

$academy->fresh()->logo_sidebar; // harus tidak null, path *-sidebar.png
$academy->fresh()->logo_favicon; // harus tidak null, path *-favicon.png
Storage::disk('public')->exists($academy->fresh()->logo_sidebar); // true

// Upload logo SVG -- harus DI-SKIP, bukan error
$svgFile = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');
$academy2 = $svc->update($academy, array_merge($academy->only([...]), ['logo' => $svgFile]));
$academy2->fresh()->logo_sidebar; // harus null
$academy2->fresh()->logo; // harus TETAP tersimpan (file asli SVG-nya)
```

Kalau upload logo SVG melempar exception sampai ke luar (bukan di-skip diam-diam), cek lagi urutan pengecekan ekstensi di `generateLogoVariants()` — harus di baris **pertama**, sebelum `ImageManager::read()` dipanggil.

---

## Tahap 5 — `AcademyService`: `sidebarLogoUrl()` & `faviconUrl()`

**Tujuan**: satu sumber kebenaran untuk "URL logo apa yang harus tampil", termasuk seluruh logic fallback.

Tambahkan 2 method baru di `app/Services/AcademyService.php`, setelah `isSuperAdmin()`:

```php
    /**
     * URL logo untuk slot "lebar" -- sidebar (expanded) + header mobile.
     * Fallback ke logo statis sistem kalau: Super Admin (tidak ada academy
     * aktif), academy belum upload logo, atau logo terakhir di-upload SVG
     * (di-skip dari proses resize, lihat AcademyManagementService::generateLogoVariants()).
     */
    public function sidebarLogoUrl(): string
    {
        $academy = $this->current();

        if ($academy && $academy->logo_sidebar) {
            return asset('storage/' . $academy->logo_sidebar);
        }

        return asset('assets/images/logo/KantinITSvg.svg');
    }


    /**
     * URL logo untuk slot "ikon persegi" -- sidebar (collapsed) + favicon
     * browser. Fallback sama seperti sidebarLogoUrl().
     */
    public function faviconUrl(): string
    {
        $academy = $this->current();

        if ($academy && $academy->logo_favicon) {
            return asset('storage/' . $academy->logo_favicon);
        }

        return asset('assets/images/logo/kantinit-favicon.png');
    }
```

> `current()` sudah ada di class ini (lihat `issue.md`/`issue2.md`) dan mengembalikan `null` untuk Super Admin. Memanggilnya berkali-kali (dari `sidebarLogoUrl()`, `faviconUrl()`, dan tempat lain) **tidak** menyebabkan query berulang — `Auth::user()` mengembalikan instance user yang sama sepanjang request, dan Eloquent meng-cache relasi `->academy` di instance itu setelah diakses pertama kali. Lihat [4.5](#45-kenapa-memanggil-current-berkali-kali-tidak-jadi-masalah-n1).

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$svc = app(\App\Services\AcademyService::class);

// Belum login (atau di test: actingAs Super Admin) -> harus URL statis
auth()->logout();
$svc->sidebarLogoUrl(); // harus berakhiran KantinITSvg.svg
$svc->faviconUrl();     // harus berakhiran kantinit-favicon.png
```

---

## Tahap 6 — Blade Component `AcademyLogo` (baru)

**Tujuan**: satu component reusable, dipakai di 3 lokasi berbeda (sidebar ×3, header ×2), sesuai pola `docs/frontend-standard.md` (Blade Component, bukan View Composer).

`app/View/Components/AcademyLogo.php`:

```php
<?php

namespace App\View\Components;

use App\Services\AcademyService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AcademyLogo extends Component
{
    public string $url;

    public function __construct(AcademyService $academyService, string $variant = 'sidebar')
    {
        $this->url = $variant === 'favicon'
            ? $academyService->faviconUrl()
            : $academyService->sidebarLogoUrl();
    }

    public function render(): View
    {
        return view('components.academy-logo');
    }
}
```

`resources/views/components/academy-logo.blade.php`:

```blade
<img {{ $attributes->merge(['src' => $url, 'alt' => 'Logo']) }} />
```

> `$attributes->merge()` meneruskan atribut HTML apapun yang ditulis di tag `<x-academy-logo ... />` (termasuk `class`, binding Alpine `:class`) ke `<img>` yang dihasilkan — pola yang sama dipakai `resources/views/components/primary-button.blade.php` yang sudah ada di codebase ini.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\View\Components\AcademyLogo(app(\App\Services\AcademyService::class), 'sidebar'))->url;
// tidak error, string URL
```

---

## Tahap 7 — Terapkan ke `sidebar.blade.php`, `header.blade.php`, `app.blade.php`

### 7a. `resources/views/partials/sidebar.blade.php`

Ganti blok logo di bagian atas file:

```blade
<span class="logo hidden lg:block" :class="sidebarToggle ? 'lg:hidden' : ''">
    <img class="dark:hidden" src="{{ asset('assets/images/logo/KantinITSvg.svg') }}" alt="Logo" />
    <img class="hidden dark:block" src="{{ asset('assets/images/logo/KantinITSvg.svg') }}" alt="Logo" />
</span>

<img class="logo-icon" :class="sidebarToggle ? 'lg:block' : 'hidden'"
    src="{{ asset('assets/images/logo/kantinit-favicon.png') }}" alt="Logo" />
```

menjadi:

```blade
<span class="logo hidden lg:block" :class="sidebarToggle ? 'lg:hidden' : ''">
    <x-academy-logo variant="sidebar" class="dark:hidden" />
    <x-academy-logo variant="sidebar" class="hidden dark:block" />
</span>

<x-academy-logo variant="favicon" class="logo-icon" ::class="sidebarToggle ? 'lg:block' : 'hidden'" />
```

> Perhatikan **double colon** (`::class`, bukan `:class`) — atribut berawalan satu titik dua pada tag `<x-component>` **selalu** dievaluasi Blade sebagai ekspresi PHP saat compile, beda dengan tag HTML biasa. `sidebarToggle` di sini adalah variabel Alpine.js (`x-data`), bukan variabel PHP — kalau ditulis `:class` biasa, Blade akan mencoba mengevaluasinya sebagai PHP dan gagal dengan `Undefined constant "sidebarToggle"`. `::class` memberi tahu Blade untuk meneruskannya mentah (literal `:class="..."`) supaya Alpine yang memprosesnya di sisi client.

### 7b. `resources/views/partials/header.blade.php`

Ganti 2 `<img>` logo (baris ~41-42) dari:

```blade
<img class="dark:hidden" src="{{ asset('assets/images/logo/KantinITSvg.svg') }}" alt="Logo" />
<img class="hidden dark:block" src="{{ asset('assets/images/logo/KantinITSvg.svg') }}" alt="Logo" />
```

menjadi:

```blade
<x-academy-logo variant="sidebar" class="dark:hidden" />
<x-academy-logo variant="sidebar" class="hidden dark:block" />
```

Ganti juga `<img>` di `header-avatar` dropdown user (baris ~154) dari:

```blade
<img src="{{ asset('assets/images/logo/kantinit-favicon.png') }}" alt="User" />
```

menjadi:

```blade
<x-academy-logo variant="favicon" alt="User" />
```

> **Bukan** foto user (User belum punya kolom foto sama sekali) — ini cuma mengganti placeholder statis jadi favicon academy yang login, konsisten dengan slot favicon lainnya. Kalau nanti ada fitur upload foto user, prioritas fallback-nya jadi: foto user (kalau ada) → favicon academy → tidak perlu lagi, karena `<x-academy-logo>` sudah menangani fallback academy-nya.

### 7c. `resources/views/layouts/app.blade.php`

Ganti baris favicon:

```blade
<link rel="icon" type="image/png" href="{{ asset('assets/images/logo/kantinit-favicon.png') }}">
```

menjadi:

```blade
<link rel="icon" type="image/png" href="{{ app(\App\Services\AcademyService::class)->faviconUrl() }}">
```

> Dipanggil langsung (bukan lewat `<x-academy-logo>`) karena targetnya tag `<link>`, bukan `<img>` — komponen `AcademyLogo` cuma merender `<img>`. Ini konsisten dengan pola inline `app(\App\Services\AcademyService::class)` yang sudah dipakai di `sidebar.blade.php` untuk `isSuperAdmin()` (lihat `issue.md`).

**✅ Cek dulu**

- Login sebagai Owner academy yang **sudah** punya `logo_sidebar`/`logo_favicon` → sidebar, header mobile, dan favicon tab browser menampilkan logo academy tersebut.
- Login sebagai Owner academy yang **belum** upload logo → ketiganya tampil logo sistem statis (tidak kosong/rusak).
- Login sebagai Super Admin → ketiganya **selalu** logo sistem statis, walaupun ada academy lain yang punya logo custom.
- Buka halaman login (`/login`, belum ada sesi) → favicon tetap logo sistem statis (dari `app-auth.blade.php`, tidak tersentuh brief ini).

---

## Tahap 8 — Test

**Tujuan**: `tests/Feature/AcademyLogoVariantTest.php` — pastikan generate varian, fallback, dan cleanup file berjalan sesuai desain.

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use App\Services\AcademyManagementService;
use App\Services\AcademyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyLogoVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function updatePayload(Academy $academy, array $overrides = []): array
    {
        return array_merge([
            'name' => $academy->name,
            'code' => $academy->code,
            'phone' => $academy->phone,
            'email' => $academy->email,
            'address' => $academy->address,
            'tagline' => $academy->tagline,
            'status' => true,
            'subscription_type' => 'monthly',
            'subscription_fee' => 100000,
            'subscription_started_at' => now()->toDateString(),
            'subscription_ends_at' => now()->addMonth()->toDateString(),
        ], $overrides);
    }

    public function test_upload_logo_raster_menghasilkan_dua_varian(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $file = UploadedFile::fake()->image('logo.png', 300, 300);

        $academy = $svc->update($academy, $this->updatePayload($academy, ['logo' => $file]));

        $this->assertNotNull($academy->logo_sidebar);
        $this->assertNotNull($academy->logo_favicon);
        Storage::disk('public')->assertExists($academy->logo_sidebar);
        Storage::disk('public')->assertExists($academy->logo_favicon);
    }

    public function test_upload_logo_svg_di_skip_bukan_error(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $file = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');

        $academy = $svc->update($academy, $this->updatePayload($academy, ['logo' => $file]));

        $this->assertNull($academy->logo_sidebar);
        $this->assertNull($academy->logo_favicon);
        $this->assertNotNull($academy->logo);
        Storage::disk('public')->assertExists($academy->logo);
    }

    public function test_ganti_logo_menghapus_varian_lama(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo-lama.png', 300, 300),
        ]));

        $sidebarLama = $academy->logo_sidebar;
        $faviconLama = $academy->logo_favicon;

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo-baru.png', 300, 300),
        ]));

        Storage::disk('public')->assertMissing($sidebarLama);
        Storage::disk('public')->assertMissing($faviconLama);
        Storage::disk('public')->assertExists($academy->logo_sidebar);
        Storage::disk('public')->assertExists($academy->logo_favicon);
    }

    public function test_hapus_academy_menghapus_logo_dan_variannya(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ]));

        $logo = $academy->logo;
        $sidebar = $academy->logo_sidebar;
        $favicon = $academy->logo_favicon;

        $svc->delete($academy);

        Storage::disk('public')->assertMissing($logo);
        Storage::disk('public')->assertMissing($sidebar);
        Storage::disk('public')->assertMissing($favicon);
    }

    public function test_academy_service_fallback_ke_logo_statis_untuk_super_admin(): void
    {
        $role = Role::firstOrCreate([
            'id_academy' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $superAdmin = User::factory()->create(['id_academy' => null, 'status' => true]);
        $superAdmin->assignRole($role);

        $this->actingAs($superAdmin);

        $svc = app(AcademyService::class);

        $this->assertStringContainsString('KantinITSvg.svg', $svc->sidebarLogoUrl());
        $this->assertStringContainsString('kantinit-favicon.png', $svc->faviconUrl());
    }

    public function test_academy_service_pakai_logo_academy_saat_sudah_ada_varian(): void
    {
        $academyManagementService = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $academyManagementService->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ]));

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $this->actingAs($owner);

        $svc = app(AcademyService::class);

        $this->assertStringContainsString($academy->logo_sidebar, $svc->sidebarLogoUrl());
        $this->assertStringContainsString($academy->logo_favicon, $svc->faviconUrl());
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=AcademyLogoVariantTest
```

Keenam test harus **pass**. Kalau `test_upload_logo_svg_di_skip_bukan_error` gagal dengan exception (bukan assertion gagal), cek lagi urutan pengecekan ekstensi SVG di `generateLogoVariants()` — harus jadi baris pertama sebelum `ImageManager::read()`.

---

## Tahap 9 — Dokumentasi

**Tujuan**: `docs/frontend-standard.md` mencatat `AcademyLogo` sebagai contoh tambahan Blade Component, supaya developer berikutnya yang cari pola serupa langsung ketemu presedennya.

Di section *Reusable View dengan Data Dinamis*, ubah baris:

```markdown
Contoh yang sudah ada: `App\View\Components\Alert`, `App\View\Components\Breadcrumb`, `App\View\Components\AuthSidebar`. Pola bakunya:
```

menjadi:

```markdown
Contoh yang sudah ada: `App\View\Components\Alert`, `App\View\Components\Breadcrumb`, `App\View\Components\AuthSidebar`, `App\View\Components\AcademyLogo`. Pola bakunya:
```

**✅ Cek dulu**: baca ulang, pastikan tidak menambah section baru yang duplikat — cukup satu baris di daftar contoh yang sudah ada.

---

## 4. Alasan Teknis

### 4.1. Kenapa `scaleDown(245, 65)` untuk varian sidebar, tapi `cover(64, 64)` untuk favicon

Riwayat keputusan (dua kali revisi, sudah dicoba langsung tiap kali):

1. **Draft awal**: `scaleDown(256, 256)` (kotak besar) — hasilnya tidak konsisten, tergantung aspect ratio logo asli.
2. **Revisi pertama**: `cover(65, 65)` (crop persegi kecil, paksa) — hasilnya konsisten ukuran, tapi terasa kekecilan/kurang proporsional untuk slot sidebar yang aslinya lebar (logo sistem yang sekarang berbentuk wordmark lebar, bukan ikon kotak).
3. **Keputusan final**: `scaleDown(245, 65)` — muat di bounding box 245×65 (lebar×tinggi), jaga aspect ratio, **tanpa** crop. Logo persegi (badge/crest) akan mentok di sisi tinggi (mis. 65×65), logo lebar (wordmark) akan mentok di sisi lebar (mis. 245×61) — proporsinya meniru bentuk logo sistem yang sekarang, jadi apapun bentuk logo academy-nya tetap terasa "pas" di slot itu, bukan dipaksa jadi kotak kecil.

Favicon **tidak** ikut revisi ini — favicon browser secara konvensi **wajib** persegi (umumnya dirender 16×16/32×32 oleh browser), jadi `cover(64, 64)` (crop persegi, `Alignment::CENTER` default) tetap paling tepat untuk slot itu, beda konteks dengan sidebar yang memang berbentuk lebar.

### 4.2. Kenapa GD (bukan Imagick), dan kenapa SVG di-skip (bukan ditolak)

Environment ini sudah dicek (`php -m`) hanya punya ekstensi `gd`, tidak ada `imagick` — jadi `Intervention\Image\Drivers\Gd\Driver` adalah satu-satunya pilihan tanpa menambah instalasi ekstensi PHP di server (di luar scope brief ini). Driver GD (dan juga Imagick tanpa dukungan `librsvg` yang belum tentu ada) **tidak bisa membaca file vektor SVG** sebagai raster image.

Alih-alih menolak upload SVG (mengubah validasi `AcademyFormRequest`/`AcademyProfileFormRequest` yang sudah mengizinkannya sejak sebelum brief ini — perubahan itu di luar scope & butuh diskusi terpisah karena mempengaruhi UX upload yang sudah berjalan), `generateLogoVariants()` mendeteksi ekstensi SVG di awal dan **skip** proses resize untuk file itu saja — `logo` (file asli) tetap tersimpan seperti biasa (dipakai di avatar Detail Academy), cuma `logo_sidebar`/`logo_favicon` yang tetap `null`, dan `AcademyService` otomatis fallback ke logo statis untuk slot itu. Upload akun tidak pernah gagal karena format logo.

### 4.3. Kenapa halaman login tidak ikut dinamis

`resources/views/layouts/app-auth.blade.php` dirender untuk halaman `/login`, `/register`, `/forgot-password`, dll — semuanya **sebelum** ada sesi login. `AcademyService::current()` bergantung pada `Auth::user()`, yang selalu `null` di halaman-halaman ini. Tidak ada "academy aktif" yang bisa dijadikan sumber logo pada titik ini (kecuali sistem tahu duluan academy mana dari subdomain/URL — di luar arsitektur single-database-multi-tenant yang dipakai FAOSBall saat ini, lihat `docs/multi-tenancy.md`). Favicon di layout ini **tetap statis**, tidak tersentuh brief ini.

### 4.4. Dampak performa

Resize hanya berjalan sekali per upload logo (aksi Super Admin di Academy Management, atau Owner di Profil Academy — keduanya jarang terjadi dibanding total page load harian). Yang dilayani ke setiap page load (sidebar, header, favicon di **setiap** halaman yang dirender untuk **setiap** user yang login) adalah file statis hasil resize yang sudah tersimpan di disk `public` — sama ringannya dengan menyajikan `KantinITSvg.svg` yang sudah ada sekarang, cuma path-nya dinamis. Tidak ada proses image processing yang berjalan di request path halaman biasa.

### 4.5. Kenapa memanggil `current()` berkali-kali tidak jadi masalah N+1

`sidebarLogoUrl()`/`faviconUrl()` dipanggil sampai 5× dalam satu render halaman (3× di `sidebar.blade.php`, 2× di `header.blade.php`, 1× di `app.blade.php` untuk favicon — total 6×, tapi beberapa lewat Component yang instance-nya baru tiap panggilan). Masing-masing memanggil `$this->current()` yang mengakses `Auth::user()->academy`. `Auth::user()` mengembalikan **instance model yang sama** sepanjang satu request (di-resolve sekali oleh guard, disimpan di container), dan Eloquent otomatis meng-cache hasil relasi `->academy` di instance itu setelah diakses pertama kali — akses kedua dst tidak memicu query baru. Jadi walau dipanggil berkali-kali secara sintaksis, total query ke database untuk resolusi academy aktif tetap **satu**, bukan enam. Ini konsisten dengan standar `docs/query-performance.md` soal N+1.

---

## 5. Development Checklist

Sebelum brief ini dinyatakan selesai, cocokkan dengan checklist `docs/module-standard.md`:

- [ ] `composer.json`: `intervention/image` terpasang.
- [ ] Migration: `logo_sidebar`, `logo_favicon` nullable, urutan setelah `logo`.
- [ ] Model: fillable bertambah 2 kolom, **tidak ada** logic baru di Model.
- [ ] Service: `generateLogoVariants()` skip SVG di baris pertama, pakai `clone` untuk tiap varian, try/catch tidak menggagalkan transaksi induk.
- [ ] Service: `create()`, `update()`, **`updateProfile()`** (jangan lupa — Owner self-service), dan `delete()` semuanya terintegrasi dengan varian logo.
- [ ] `AcademyService`: `sidebarLogoUrl()`/`faviconUrl()` fallback ke statis untuk Super Admin **dan** academy tanpa varian (belum upload / SVG).
- [ ] Blade Component `AcademyLogo` dipakai (bukan `View::composer()`), sesuai `docs/frontend-standard.md`.
- [ ] `sidebar.blade.php` (3 slot), `header.blade.php` (3 slot, termasuk avatar user), `app.blade.php` (favicon) — semuanya dinamis.
- [ ] Varian sidebar (`logo_sidebar`) hasil `scaleDown(245, 65)` — bounding box, jaga aspect ratio, bukan `cover()`.
- [ ] `app-auth.blade.php` — **tidak** tersentuh, favicon tetap statis.
- [ ] `academies/show.blade.php` (avatar Detail Academy) — **tidak** tersentuh, tetap pakai `$academy->logo`.
- [ ] Test: 6 skenario `AcademyLogoVariantTest` pass.
- [ ] `docs/frontend-standard.md`: `AcademyLogo` ditambahkan ke daftar contoh Blade Component.
- [ ] Manual: login sebagai Owner dengan logo custom → cek sidebar, header mobile (resize browser), dan tab favicon browser semuanya berubah.
- [ ] Manual: login sebagai Super Admin → ketiganya tetap logo sistem, walau ada academy lain yang punya logo custom.
- [ ] Manual: upload logo SVG lewat form Academy → tersimpan sukses (tidak error), sidebar/favicon tetap logo statis (bukan crash/gambar rusak).

## Summary

Brief ini membuat logo academy otomatis dipecah jadi 2 varian ukuran saat di-upload (bukan 3 — sidebar dan header ternyata sudah memakai file yang identik di kode yang ada, begitu juga ikon sidebar collapsed dan favicon): `logo_sidebar` (scaleDown bounding box 245×65, jaga aspect ratio tanpa crop, untuk slot logo lebar) dan `logo_favicon` (cover 64×64, crop persegi, untuk slot ikon kecil/favicon browser). Proses resize memakai `intervention/image` v4 (driver GD, satu-satunya yang tersedia di environment ini — API-nya beda dari dokumentasi v3 yang lebih umum ditemukan, sudah disesuaikan di brief ini) dan berjalan **sekali saat upload**, bukan on-the-fly — sehingga tidak membebani performa page load harian, cuma menambah beberapa ratus ms ke aksi upload logo yang jarang terjadi. `AcademyService` jadi satu sumber kebenaran untuk resolusi URL logo (`sidebarLogoUrl()`/`faviconUrl()`), otomatis fallback ke logo statis sistem untuk Super Admin, academy yang belum upload logo, atau logo yang di-upload dalam format SVG (di-skip dari resize karena GD tidak bisa membaca vektor, bukan ditolak saat upload). Tampilannya memakai Blade Component baru (`AcademyLogo`) mengikuti pola yang sudah didokumentasikan di `docs/frontend-standard.md`, dipasang di 4 titik: sidebar, header mobile, favicon `<link>`, dan avatar user di header (fallback favicon academy, karena `User` belum punya kolom foto sendiri) — halaman login (`app-auth.blade.php`) sengaja tidak disentuh karena belum ada konteks academy sebelum user login.
