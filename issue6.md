# Brief: Warna Utama (Primary Color) per Academy

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `docs/multi-tenancy.md` (terutama *Academy Exception*), `docs/frontend-standard.md` (terutama *Reusable View dengan Data Dinamis* dan *Urutan & Pengelompokan Field Form*), dan `issue.md` Bagian 4.7–4.8 (pola whitelist eksplisit & kenapa Academy Profile tidak pakai route model binding — brief ini memakai academy yang sama).
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 10** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: Academy bisa menentukan **1 warna utama** (`primary_color`, hex) yang menggantikan warna brand biru default (`#465fff`) di seluruh tampilan sistem — tombol, link, focus ring, badge, dst — khusus saat user dari academy itu login. **Bukan** scope: warna secondary (sudah didiskusikan dan ditunda — lihat percakapan sebelumnya), validasi kontras WCAG, color picker custom/JS library, atau theming per-halaman/per-role.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Taruh logika hex→ramp (1 warna jadi 12 shade `25`–`950`) di Blade/Component/Controller | Harus di class murni (`App\Support\ColorRamp`) supaya bisa di-unit-test terpisah tanpa boot Laravel penuh, dan reusable kalau nanti secondary color dibangun (lihat diskusi sebelumnya — ditunda, bukan dibatalkan) | [Tahap 3](#tahap-3--appsupportcolorramp) |
| Percaya `$academy->primary_color` dari database langsung dipakai tanpa validasi ulang sebelum di-echo ke `<style>` | Defense in depth — pola yang sama persis dengan alasan `updateProfile()` membangun payload manual di `issue.md` Bagian 4.7. Kolom yang lolos validasi **saat disimpan** tidak menjamin nilainya masih valid selamanya (data lama, migrasi manual, dsb) | [4.1](#41-kenapa-validasi-hex-diulang-lagi-saat-render-bukan-percaya-form-request-saja) |
| Taruh override `<style>` di dalam `@theme`/`@layer` manapun (mis. menyuntikkannya ke `variables.css`) | Aturan cascade CSS: style **tanpa layer** selalu menang lawan style **di dalam layer manapun**, terlepas dari urutan penulisan. Kalau override ditaruh di dalam layer, harus pusing mikirin urutan & specificity — dengan sengaja dibuat unlayered, tidak perlu mikirin itu sama sekali | [4.2](#42-kenapa-override-css-var-cukup-diletakkan-di-manapun-di-head-tanpa-mikir-urutan) |
| Generate ramp warna di JavaScript (client-side) atau precompute manual di `variables.css` | Harus PHP server-side (`ColorRamp`) — konsisten hasilnya lintas browser, dan bisa di-unit-test dengan PHPUnit tanpa headless browser | [Tahap 3](#tahap-3--appsupportcolorramp) |
| Tulis `@php` panjang berisi logic warna langsung di `layouts/app.blade.php` | Wajib lewat class-based Blade Component (`<x-academy-theme />`), bukan logic inline di layout atau View Composer — ikuti `docs/frontend-standard.md` → *Reusable View dengan Data Dinamis* | [Tahap 5](#tahap-5--blade-component-academytheme) |
| Tambahkan validasi kontras warna (WCAG), pembatas warna "terlalu terang/gelap", atau UI color-picker custom | Di luar scope brief ini. Native `<input type="color">` browser sudah cukup — jangan tambah dependency JS baru | — |
| Bikin `primary_color` required cuma di salah satu form (Super Admin **atau** Owner, tidak dua-duanya) | Field ini kategori "profil" yang sama dengan `logo` — sudah presedennya ada di 2 tempat (Academy Management utk Super Admin, Academy Profile utk Owner self-service). Harus konsisten required di keduanya | [Tahap 7](#tahap-7--form-request) |

---

## 1. Konteks & Tujuan

Academy sudah bisa upload logo sendiri (fitur sebelumnya). Sekarang kita tambah 1 langkah lagi: academy bisa pilih **1 warna utama** yang menggantikan biru default (`--color-brand-500: #465fff`) di seluruh tampilan — supaya tampilan sistem terasa "milik" academy itu, bukan generik.

```text
Academy A (primary_color: #16a34a hijau)     Academy B (belum set / null)
├── Tombol "Simpan" → hijau                  ├── Tombol "Simpan" → biru (default)
├── Link, focus ring → hijau                 ├── Link, focus ring → biru (default)
└── Badge info → hijau                       └── Badge info → biru (default)
```

## 2. Cara Kerja Solusi

### 2a. Kenapa ini bisa dilakukan tanpa rebuild CSS per academy

FAOSBall pakai Tailwind CSS v4 CSS-first (`@theme` di `variables.css`, lihat `docs/frontend-standard.md`). Yang penting: utility class hasil compile Tailwind **tidak** membakar nilai hex langsung — semua mengacu ke CSS custom property:

```css
/* hasil compile Tailwind, bukan yang kita tulis manual */
.bg-brand-500 { background-color: var(--color-brand-500); }
.text-brand-500 { color: var(--color-brand-500); }
```

Artinya: kalau kita override nilai `--color-brand-500` (dan 11 shade brand lainnya) di `:root` lewat `<style>` inline, **seluruh** utility `bg-brand-*`/`text-brand-*`/`border-brand-*`/dst di **seluruh app** otomatis ikut berubah warna — termasuk varian `dark:` (karena token-nya sama, cuma dipakai kondisional oleh class `.dark`) — tanpa perlu sentuh satu pun file Blade/component lain, dan tanpa perlu rebuild asset Vite per academy.

### 2b. 1 warna academy → 12 shade Tailwind (`25`–`950`)

Token `brand` di `variables.css` bukan 1 warna, tapi ramp 12 shade dipakai untuk state berbeda (hover, subtle background badge, focus ring 10% opacity, dst). Academy cuma pilih **1** warna dasar lewat `<input type="color">` — sisanya (`25`–`400` = versi lebih terang, `600`–`950` = versi lebih gelap) di-generate otomatis lewat pencampuran linear RGB ke arah putih/hitam, dilakukan oleh `App\Support\ColorRamp` (Tahap 3). Bukan ilmu pasti/perceptually-perfect seperti palette biru yang sudah di-tuning manual sekarang — cukup "cukup enak dilihat" untuk warna arbitrary apa pun yang dipilih academy.

### 2c. Kenapa cukup 1 `<style>` block di `<head>`, bukan sentuh `variables.css`

`variables.css` (`@theme { ... }`) **tidak disentuh sama sekali** — tetap jadi fallback default biru untuk halaman yang tidak ada konteks academy (login, register, Super Admin tanpa academy aktif). Kita cukup menambah 1 Blade Component baru (`<x-academy-theme />`) yang dipasang di `layouts/app.blade.php`, yang — **kalau** academy aktif punya `primary_color` — mencetak `<style>:root{--color-brand-25:...;...}</style>` di `<head>`. Karena block ini **tidak** ditulis di dalam `@layer` manapun (beda dengan compiled Tailwind CSS yang ada di `@layer theme`), dia otomatis menang di cascade CSS — berapa pun urutan penempatannya di `<head>` (lihat [4.2](#42-kenapa-override-css-var-cukup-diletakkan-di-manapun-di-head-tanpa-mikir-urutan)).

### 2d. Field ini "profil" — muncul di 2 tempat, sama seperti `logo`

`primary_color` bukan field administratif (beda dengan `code`/`status`/`subscription_*` yang Super-Admin-only). Dia sekelas dengan `logo`/`tagline`/`address` — jadi muncul di **dua** form, persis pola `logo`:

| | Academy Management (Super Admin) | Academy Profile (Owner self-service) |
|---|---|---|
| Form | `academies/create.blade.php`, `academies/edit.blade.php` | `academy-profile/edit.blade.php` |
| Permission | `academy.create`/`academy.update` (sudah ada) | `academy_profile.update` (sudah ada) |
| Perubahan permission baru? | **Tidak ada** — field ini numpang di permission yang sudah digerbang | — |

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_add_primary_color_to_academies_table.php` | 🆕 Baru | 1 |
| `app/Models/Academy.php` | ✏️ Tambah `primary_color` ke `$fillable` | 2 |
| `app/Support/ColorRamp.php` | 🆕 Baru | 3 |
| `app/Services/AcademyService.php` | ✏️ Tambah method `brandColorVariables()` | 4 |
| `app/View/Components/AcademyTheme.php` | 🆕 Baru | 5 |
| `resources/views/components/academy-theme.blade.php` | 🆕 Baru | 5 |
| `resources/views/layouts/app.blade.php` | ✏️ Pasang `<x-academy-theme />` di `<head>` | 6 |
| `app/Http/Requests/Academy/AcademyFormRequest.php` | ✏️ Tambah rule `primary_color` | 7a |
| `app/Http/Requests/Academy/AcademyProfileFormRequest.php` | ✏️ Tambah rule `primary_color` | 7b |
| `resources/views/academies/create.blade.php` | ✏️ Tambah field warna | 8a |
| `resources/views/academies/edit.blade.php` | ✏️ Tambah field warna | 8b |
| `resources/views/academy-profile/edit.blade.php` | ✏️ Tambah field warna | 8c |
| `tests/Unit/ColorRampTest.php` | 🆕 Baru | 9a |
| `tests/Feature/AcademyThemeTest.php` | 🆕 Baru | 9b |
| `docs/frontend-standard.md` | ✏️ Tambah section pola theming ini | 10 |
| **`resources/css/theme/variables.css`** | 🚫 **Jangan sentuh** — tetap fallback default biru, lihat [2c](#2c-kenapa-cukup-1-style-block-di-head-bukan-sentuh-variablescss) | — |
| **`app/Services/AcademyManagementService.php` → `create()`/`update()`** | 🚫 **Jangan sentuh** — kedua method ini sudah mengoper `$data` (hasil validasi Form Request) langsung ke `Academy::create()`/`$academy->update()`; karena `primary_color` sudah masuk `$fillable` (Tahap 2) dan tervalidasi (Tahap 7a), field ini otomatis ikut tersimpan tanpa perlu kode tambahan | — |
| **`config/faos.php` → `role_templates`, `database/seeders/RolePermissionSeeder.php`, `docs/permission-reference.md`** | 🚫 **Jangan sentuh** — tidak ada permission baru, lihat [2d](#2d-field-ini-profil--muncul-di-2-tempat-sama-seperti-logo) | — |

---

## Tahap 1 — Migration

**Tujuan**: kolom `primary_color` ada di tabel `academies`, nullable (academy lama belum punya nilainya).

```bash
php artisan make:migration add_primary_color_to_academies_table --table=academies
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
        Schema::table('academies', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Color
            |--------------------------------------------------------------------------
            | Format hex "#rrggbb" (7 karakter, termasuk '#'). Nullable di level
            | DATABASE karena academy yang sudah ada belum punya data ini -- WAJIB
            | diisi lewat Form Request untuk academy yang dibuat/diedit setelahnya
            | (lihat Tahap 7). Kalau NULL, AcademyService::brandColorVariables()
            | (Tahap 4) fallback ke biru default variables.css apa adanya --
            | tidak ada backfill migration untuk academy lama.
            */
            $table->string('primary_color', 7)
                ->nullable()
                ->after('logo_favicon');
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn('primary_color');
        });
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table academies
```

Harus ada kolom `primary_color`, tipe `varchar(7)`, **nullable**.

---

## Tahap 2 — Model

`app/Models/Academy.php`, tambahkan `'primary_color'` ke `$fillable`, tepat setelah `'logo_favicon'`:

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
    'primary_color',
    'description',
];
```

> Tidak ada cast tambahan — ini string hex biasa. Tidak ada accessor/method warna apa pun di Model, sama seperti alasan `subscription_status` tidak dihitung di Model (`issue.md` Bagian 4.1) — logic ada di Service/class terpisah.

**✅ Cek dulu**: `php artisan tinker` → `(new \App\Models\Academy)->getFillable()` harus memuat `primary_color`.

---

## Tahap 3 — `App\Support\ColorRamp`

**Tujuan**: 1 warna hex → 12 shade (`25`–`950`), murni fungsi tanpa dependency Laravel apa pun (supaya gampang di-unit-test dan reusable).

Buat folder baru `app/Support/` kalau belum ada. File `app/Support/ColorRamp.php`:

```php
<?php

namespace App\Support;

class ColorRamp
{
    /**
     * Shade lebih terang dari base (500) -- rasio campuran ke arah PUTIH.
     * Angka lebih besar = lebih dekat ke putih.
     */
    private const WHITE_MIX = [
        '25' => 0.96,
        '50' => 0.92,
        '100' => 0.84,
        '200' => 0.68,
        '300' => 0.52,
        '400' => 0.26,
    ];

    /**
     * Shade lebih gelap dari base (500) -- rasio campuran ke arah HITAM.
     * Angka lebih besar = lebih dekat ke hitam.
     */
    private const BLACK_MIX = [
        '600' => 0.14,
        '700' => 0.28,
        '800' => 0.42,
        '900' => 0.56,
        '950' => 0.74,
    ];

    /**
     * Generate 12 shade dari 1 warna dasar, meniru struktur ramp Tailwind
     * (25, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950).
     *
     * Pencampuran linear RGB sederhana ke arah putih/hitam -- BUKAN
     * perceptually-uniform seperti ramp biru bawaan yang di-tuning manual.
     * Cukup "layak dilihat" untuk warna arbitrary apa pun yang dipilih
     * academy. Kalau kualitas visualnya dirasa kurang di kemudian hari,
     * cukup ganti isi method mix()/konstanta di atas -- dampaknya terisolasi
     * di class ini saja.
     *
     * @return array<string,string> shade key ("25".."950") => hex "#rrggbb"
     */
    public static function generate(string $baseHex): array
    {
        [$r, $g, $b] = self::hexToRgb($baseHex);

        $ramp = [];

        foreach (self::WHITE_MIX as $shade => $ratio) {
            $ramp[$shade] = self::mix($r, $g, $b, 255, 255, 255, $ratio);
        }

        $ramp['500'] = self::rgbToHex($r, $g, $b);

        foreach (self::BLACK_MIX as $shade => $ratio) {
            $ramp[$shade] = self::mix($r, $g, $b, 0, 0, 0, $ratio);
        }

        return $ramp;
    }

    private static function mix(int $r, int $g, int $b, int $tr, int $tg, int $tb, float $ratio): string
    {
        return self::rgbToHex(
            (int) round($r + ($tr - $r) * $ratio),
            (int) round($g + ($tg - $g) * $ratio),
            (int) round($b + ($tb - $b) * $ratio),
        );
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, $r)),
            max(0, min(255, $g)),
            max(0, min(255, $b)),
        );
    }
}
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$ramp = \App\Support\ColorRamp::generate('#465fff');
count($ramp); // harus 12
$ramp['500']; // harus "#465fff" persis (base tidak berubah)
$ramp['25'];  // harus jauh lebih terang dari 500 (mendekati putih)
$ramp['950']; // harus jauh lebih gelap dari 500 (mendekati hitam)
```

---

## Tahap 4 — `AcademyService::brandColorVariables()`

**Tujuan**: satu method yang tahu cara mengambil `primary_color` academy aktif, memvalidasi ulang, dan mengembalikan ramp siap-pakai — atau `null` kalau tidak ada yang perlu di-override (Super Admin, atau academy belum set warna).

Di `app/Services/AcademyService.php`, tambahkan import di atas:

```php
use App\Support\ColorRamp;
```

Tambahkan method baru, setelah `faviconUrl()`:

```php
    /**
     * Ramp 12 shade warna brand untuk academy AKTIF, siap dipakai sebagai
     * override CSS custom property. Return null kalau tidak perlu override
     * apa pun -- browser tetap pakai default biru dari variables.css:
     * - Super Admin (tidak ada academy aktif)
     * - Academy belum pernah set primary_color (kolom NULL)
     * - primary_color di database ternyata bukan format hex valid (data
     *   korup/lama) -- divalidasi ULANG di sini, bukan percaya kolom DB
     *   begitu saja walau sudah divalidasi Form Request saat disimpan.
     *   Lihat issue6.md Bagian 4.1.
     *
     * @return array<string,string>|null
     */
    public function brandColorVariables(): ?array
    {
        $academy = $this->current();

        if (! $academy || ! $academy->primary_color) {
            return null;
        }

        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $academy->primary_color)) {
            return null;
        }

        return ColorRamp::generate($academy->primary_color);
    }
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create(['primary_color' => '#16a34a']);
$user = \App\Models\User::factory()->create(['id_academy' => $academy->id_academy]);
\Illuminate\Support\Facades\Auth::login($user);

app(\App\Services\AcademyService::class)->brandColorVariables();
// harus array 12 key, ['500'] === '#16a34a'

$academy->update(['primary_color' => null]);
app(\App\Services\AcademyService::class)->brandColorVariables();
// harus null (cache relasi User->academy di Auth::user() mungkin perlu
// Auth::user()->unsetRelation('academy') dulu di tinker biar keliatan --
// di request HTTP asli ini otomatis fresh tiap request)
```

---

## Tahap 5 — Blade Component `AcademyTheme`

**Tujuan**: satu `<x-academy-theme />` yang mencetak `<style>` override kalau perlu, kosong kalau tidak. Ikuti pola `App\View\Components\AcademyLogo` yang sudah ada (constructor manggil Service, view cuma nampilin).

`app/View/Components/AcademyTheme.php` — file baru:

```php
<?php

namespace App\View\Components;

use App\Services\AcademyService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AcademyTheme extends Component
{
    public ?array $ramp;

    public function __construct(AcademyService $academyService)
    {
        $this->ramp = $academyService->brandColorVariables();
    }

    public function render(): View
    {
        return view('components.academy-theme');
    }
}
```

`resources/views/components/academy-theme.blade.php` — file baru:

```blade
@if ($ramp)
    <style>
        :root {
            --color-brand-25: {{ $ramp['25'] }};
            --color-brand-50: {{ $ramp['50'] }};
            --color-brand-100: {{ $ramp['100'] }};
            --color-brand-200: {{ $ramp['200'] }};
            --color-brand-300: {{ $ramp['300'] }};
            --color-brand-400: {{ $ramp['400'] }};
            --color-brand-500: {{ $ramp['500'] }};
            --color-brand-600: {{ $ramp['600'] }};
            --color-brand-700: {{ $ramp['700'] }};
            --color-brand-800: {{ $ramp['800'] }};
            --color-brand-900: {{ $ramp['900'] }};
            --color-brand-950: {{ $ramp['950'] }};
        }
    </style>
@endif
```

> `{{ }}` di Blade otomatis escape HTML (`htmlspecialchars`) — aman dipakai di sini karena `$ramp[...]` sudah dijamin format `#rrggbb` murni oleh `ColorRamp::rgbToHex()` (Tahap 3) dan validasi regex di `brandColorVariables()` (Tahap 4). Tidak akan pernah mengandung karakter `<`/`>`/`&` yang bisa merusak tag `<style>`.

**✅ Cek dulu**: `php artisan tinker` → `app(\Illuminate\View\Factory::class)->make('components.academy-theme', ['ramp' => null])->render()` harus menghasilkan string kosong/whitespace saja (tidak ada `<style>` tercetak).

---

## Tahap 6 — Pasang di Layout

`resources/views/layouts/app.blade.php`, ubah blok `<head>` (baris 22–26):

```blade
    {{-- Vite: CSS & JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Stack untuk CSS tambahan per-halaman --}}
    @stack('styles')
```

Menjadi:

```blade
    {{-- Vite: CSS & JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Override warna brand per academy aktif (kalau primary_color sudah diset) --}}
    <x-academy-theme />

    {{-- Stack untuk CSS tambahan per-halaman --}}
    @stack('styles')
```

> Posisi taruhnya **tidak signifikan secara cascade** (lihat [4.2](#42-kenapa-override-css-var-cukup-diletakkan-di-manapun-di-head-tanpa-mikir-urutan)) — ditaruh setelah `@vite` cuma supaya urutan baca logis (CSS dasar dulu, baru override-nya).

**✅ Cek dulu**: login sebagai user dari academy yang `primary_color`-nya sudah diisi manual lewat tinker (`Academy::find($id)->update(['primary_color' => '#16a34a'])`) → buka halaman apa saja, View Source (`Ctrl+U` atau DevTools → Elements) → cari `<style>` di `<head>` berisi `--color-brand-500: #16a34a`. Tombol primary (`.btn-primary`) di halaman itu harus terlihat **hijau**, bukan biru.

---

## Tahap 7 — Form Request

**Tujuan**: `primary_color` wajib diisi & format hex valid, di kedua form (Super Admin & Owner).

Rule dan pesan **identik** di kedua file:

```php
'primary_color' => [
    'required',
    'string',
    'regex:/^#[0-9a-fA-F]{6}$/',
],
```

```php
'primary_color.required' => 'Warna utama wajib dipilih.',
'primary_color.regex' => 'Format warna tidak valid.',
```

### 7a. `app/Http/Requests/Academy/AcademyFormRequest.php`

Tambahkan rule di atas **tepat setelah** rule `logo` di `rules()`, dan pesannya tepat setelah pesan `logo.*` di `messages()`.

### 7b. `app/Http/Requests/Academy/AcademyProfileFormRequest.php`

Tambahkan rule di atas **tepat setelah** rule `logo` di `rules()`, dan pesannya tepat setelah pesan `logo.*` di `messages()`.

> Kenapa `regex` bukan cuma `string`+`max:7`: `<input type="color">` browser modern **selalu** mengirim value format `#rrggbb` huruf kecil, jadi secara praktis regex ini nyaris tidak pernah gagal lewat UI normal — tapi tetap wajib ada sebagai pertahanan kalau ada yang submit form manual (curl/Postman) dengan value aneh. Ini pasangan dari validasi ulang di `AcademyService::brandColorVariables()` (Tahap 4) — dua lapis, bukan cuma satu.

**✅ Cek dulu**: submit form create/edit academy dengan `primary_color` dikosongkan paksa (mis. lewat DevTools hapus attribute `required` lalu submit) → harus muncul pesan "Warna utama wajib dipilih.", bukan crash atau tersimpan kosong.

---

## Tahap 8 — Views

**Tujuan**: field warna muncul di 3 form, diletakkan **tepat setelah Logo, sebelum Alamat** — sesuai taksonomi kategori "Media/Deskriptif" di `docs/frontend-standard.md` → *Urutan & Pengelompokan Field Form*.

Markup field (dasarnya sama di ketiga file, beda cuma di `value` default):

```blade
                    {{-- Warna Utama --}}
                    <div class="form-group">

                        <label for="primary_color" class="form-label">
                            Warna Utama Sistem <span class="text-error-500">*</span>
                        </label>

                        <input type="color" id="primary_color" name="primary_color"
                            value="{{ old('primary_color', VALUE_DEFAULT) }}"
                            class="form-input h-11 w-20 cursor-pointer p-1 @error('primary_color') form-danger @enderror"
                            required>

                        <p class="mt-1 text-xs text-gray-400">
                            Dipakai untuk warna tombol, link, dan aksen utama tampilan sistem academy ini.
                        </p>

                        @error('primary_color')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>
```

> `h-11 w-20 cursor-pointer p-1` ditambah di atas class `form-input` biasa — `<input type="color">` native browser terlihat aneh kalau dipaksa lebar penuh seperti text input, jadi dibuat kotak kecil (mengikuti aturan *Konsistensi Warna & Token* di `docs/frontend-standard.md`: dimensi arbitrary yang genuinely spesifik untuk 1 komponen itu wajar, tidak perlu dipaksakan jadi token).

### 8a. `resources/views/academies/create.blade.php`

Sisipkan **tepat setelah** `<x-logo-upload-field />` dan **sebelum** blok `{{-- Address --}}`. `VALUE_DEFAULT` = `'#465fff'` (academy baru, belum ada data existing).

### 8b. `resources/views/academies/edit.blade.php`

Sisipkan di posisi yang sama. `VALUE_DEFAULT` = `$academy->primary_color ?? '#465fff'` (academy lama yang belum pernah set, tetap ada default masuk akal di form, bukan kotak kosong).

### 8c. `resources/views/academy-profile/edit.blade.php`

Sisipkan di posisi yang sama (setelah `<x-logo-upload-field :current-logo-url="..." />`, sebelum blok Alamat). `VALUE_DEFAULT` = `$academy->primary_color ?? '#465fff'`.

**✅ Cek dulu**: buka `/academies/create`, `/academies/{id}/edit`, dan `/academy-profile` (login sebagai Owner) — di ketiganya field "Warna Utama Sistem" muncul tepat di bawah upload logo, berupa kotak swatch warna kecil, default biru kalau belum pernah diisi. Submit dengan warna baru → `php artisan tinker` → `Academy::find($id)->primary_color` sesuai yang dipilih, dan halaman manapun yang dibuka setelah itu langsung berubah warna (Tahap 6).

---

## Tahap 9 — Test

### 9a. `tests/Unit/ColorRampTest.php` — file baru

```php
<?php

namespace Tests\Unit;

use App\Support\ColorRamp;
use PHPUnit\Framework\TestCase;

class ColorRampTest extends TestCase
{
    public function test_menghasilkan_12_shade(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        $this->assertCount(12, $ramp);
        $this->assertArrayHasKey('500', $ramp);
        $this->assertArrayHasKey('25', $ramp);
        $this->assertArrayHasKey('950', $ramp);
    }

    public function test_shade_500_sama_persis_dengan_input(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        $this->assertSame('#465fff', $ramp['500']);
    }

    public function test_shade_lebih_terang_dari_500_ke_arah_25(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        // Jumlah RGB shade 25 harus lebih besar (lebih terang/mendekati
        // putih) dibanding 400, dan 400 lebih besar dari 500.
        $this->assertGreaterThan($this->brightness($ramp['400']), $this->brightness($ramp['25']));
        $this->assertGreaterThan($this->brightness($ramp['500']), $this->brightness($ramp['400']));
    }

    public function test_shade_lebih_gelap_dari_500_ke_arah_950(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        $this->assertLessThan($this->brightness($ramp['600']), $this->brightness($ramp['500']));
        $this->assertLessThan($this->brightness($ramp['950']), $this->brightness($ramp['600']));
    }

    public function test_input_hitam_tidak_error_dan_tetap_valid_hex(): void
    {
        $ramp = ColorRamp::generate('#000000');

        $this->assertSame('#000000', $ramp['500']);
        $this->assertSame('#000000', $ramp['950']); // mixing hitam ke hitam tetap hitam
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $ramp['25']);
    }

    private function brightness(string $hex): int
    {
        $hex = ltrim($hex, '#');

        return hexdec(substr($hex, 0, 2)) + hexdec(substr($hex, 2, 2)) + hexdec(substr($hex, 4, 2));
    }
}
```

### 9b. `tests/Feature/AcademyThemeTest.php` — file baru

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_style_override_muncul_untuk_academy_dengan_primary_color(): void
    {
        $academy = Academy::factory()->create(['primary_color' => '#16a34a']);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('--color-brand-500: #16a34a', false);
    }

    public function test_style_override_tidak_muncul_kalau_primary_color_kosong(): void
    {
        $academy = Academy::factory()->create(['primary_color' => null]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('--color-brand-500', false);
    }

    public function test_style_override_tidak_muncul_untuk_super_admin(): void
    {
        $superAdmin = User::factory()->create(['id_academy' => null, 'status' => true]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('--color-brand-500', false);
    }
}
```

> `assertSee(..., false)` — parameter kedua `false` supaya Laravel **tidak** meng-escape string pembanding (defaultnya `assertSee` meng-escape HTML, yang akan merusak pembandingan string CSS `--color-brand-500: #16a34a`).

**✅ Cek dulu**

```bash
php artisan test --filter=ColorRampTest
php artisan test --filter=AcademyThemeTest
```

Seluruh test harus **pass**.

---

## Tahap 10 — Dokumentasi

**Tujuan**: pola "override CSS custom property per-tenant lewat Blade Component unlayered" ini terdokumentasi supaya kalau nanti secondary color (atau theming lain) dibangun, tidak perlu riset ulang dari nol.

Tambahkan section baru di `docs/frontend-standard.md`, setelah section *Reusable View dengan Data Dinamis*:

```markdown
## Theming Per-Academy (CSS Custom Property Override)

FAOSBall mendukung 1 warna utama (`primary_color`) per academy yang menggantikan token `--color-brand-*` default. Mekanismenya:

1. Tailwind v4 compile utility (`bg-brand-500`, dst) selalu mengacu ke `var(--color-brand-500)`, tidak pernah membakar nilai hex langsung — jadi override cukup lewat CSS custom property, tidak perlu rebuild asset per tenant.
2. Override dicetak lewat Blade Component (`<x-academy-theme />`, lihat `App\View\Components\AcademyTheme`) sebagai `<style>:root{...}</style>` **tanpa** `@layer` apa pun — style unlayered otomatis menang lawan Tailwind punya `@theme` (yang di-compile ke dalam `@layer theme`), terlepas dari urutan penempatan di `<head>`.
3. Generate ramp 12 shade dari 1 warna dasar ada di `App\Support\ColorRamp` — class murni, tidak bergantung Laravel, gampang di-unit-test.

**Kapan pola ini dipakai lagi**: kalau ada kebutuhan theming per-tenant lain (secondary color, dst), reuse `ColorRamp` dan pola Component yang sama — jangan bikin mekanisme baru dari nol.
```

**✅ Cek dulu**: buka `docs/frontend-standard.md`, pastikan section baru muncul di Table of Contents juga (tambahkan link-nya).

---

## 4. Alasan Teknis

### 4.1 Kenapa validasi hex diulang lagi saat render, bukan percaya Form Request saja

Pola ini identik dengan alasan `AcademyManagementService::updateProfile()` membangun payload manual alih-alih percaya `$request->validated()` begitu saja (`issue.md` Bagian 4.7): validasi Form Request cuma menjamin data valid **pada saat disimpan**. Kolom di database bisa saja punya nilai tidak terduga di kemudian hari (migrasi data manual, seed lama, restore backup dari versi sebelum brief ini). Kalau `AcademyService::brandColorVariables()` percaya begitu saja lalu meneruskannya ke `ColorRamp::generate()` yang memakai `substr()`/`hexdec()`, nilai yang bukan format hex valid bisa menghasilkan warning PHP atau warna acak yang jelek — validasi ulang dengan regex sebelum diproses membuat kasus itu fallback aman ke `null` (biru default) alih-alih rusak.

### 4.2 Kenapa override CSS var cukup diletakkan di manapun di `<head>`, tanpa mikir urutan

Spesifikasi CSS Cascade Layers menyatakan: style yang **tidak** ditulis di dalam layer manapun otomatis dianggap berada di layer paling akhir/prioritas tertinggi — menang lawan style di layer manapun (termasuk `@layer theme` hasil compile Tailwind v4), **terlepas dari urutan penulisan di dokumen**. Ini sudah diverifikasi langsung: compiled CSS project ini (`public/build/assets/app-*.css`) menaruh seluruh token warna Tailwind di dalam `@layer theme{:root,:host{...}}`. Karena `<x-academy-theme />` mencetak `<style>:root{...}</style>` polos (tanpa `@layer`), override-nya dijamin menang tanpa perlu taruh di posisi tertentu atau naikkan specificity selector secara manual.
