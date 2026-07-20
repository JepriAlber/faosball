# Brief: Multi-Language — Bahasa Indonesia (default) + English

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `docs/coding-standard.md` (terutama section *Bahasa Pesan* — brief ini **mengganti total** isi section itu), dan `docs/architecture.md`.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 12** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: (1) Fondasi teknis lengkap — kolom preferensi bahasa, middleware, route+tombol ganti bahasa di header, mekanisme translasi. (2) Konversi **lengkap** untuk layout bersama (header, sidebar, app layout — dipakai semua halaman) dan module **Academy** sebagai referensi penuh. (3) Module lain (Players, Roles, Permissions, Player Type/Category/Position, dst) **belum** dikonversi di brief ini — pola yang sama tinggal diterapkan berulang, dijelaskan di [Tahap 12](#tahap-12--follow-up-konversi-module-lain-di-luar-scope-brief-ini).

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Pakai translasi **key-based** (`lang/id/messages.php` dengan key custom seperti `__('messages.welcome')`) untuk string FAOSBall sendiri | String Indonesia yang sudah ada di kode **jadi sumber kebenaran langsung** dengan pendekatan JSON (`__('Teks aslinya')`) — tidak perlu invent nama key baru untuk ratusan string yang sudah ada. Migrasi jadi jauh lebih murah: tinggal bungkus `__(...)`, tidak menulis ulang | [4.1](#41-kenapa-json-string-based-bukan-key-based) |
| Bikin `lang/id.json` berisi terjemahan Indonesia→Indonesia | **Tidak perlu.** String asli di kode SUDAH Bahasa Indonesia — kalau locale aktif `id` dan tidak ada entry di `lang/id.json`, Laravel otomatis kembalikan string aslinya apa adanya. Cukup isi `lang/en.json` saja | [4.1](#41-kenapa-json-string-based-bukan-key-based) |
| Taruh logic resolve locale (baca session/kolom user) di Controller atau View manapun | Wajib satu pintu di Middleware (`SetLocale`) — supaya SELURUH request (termasuk yang tidak lewat Controller ganti-bahasa) konsisten pakai locale yang sama | [Tahap 4](#tahap-4--middleware-setlocale) |
| Percaya begitu saja value locale dari URL/route parameter tanpa validasi whitelist | Defense in depth — pola yang sama dengan alasan `AcademyService::brandColorVariables()` validasi ulang hex sebelum dipakai (`issue6.md` Bagian 4.1). Locale sembarangan bisa bikin `App::setLocale()` dipanggil dengan value aneh | [Tahap 5](#tahap-5--route--localecontroller) |
| Hapus `docs/coding-standard.md` section *Bahasa Pesan* tanpa menggantinya dengan section baru | Section itu **secara eksplisit** melarang folder `lang/` — brief ini membalik keputusan itu. Wajib diganti isinya (bukan dihapus polos), supaya standar yang tertulis selalu mencerminkan kondisi kode saat ini (lihat Aturan Utama `CLAUDE.md`) | [Tahap 11](#tahap-11--update-docscoding-standardmd) |
| Coba konversi **seluruh** module (Players, Roles, Permissions, dst) dalam satu brief ini | Di luar scope — itu kerja mekanis besar per-module yang lebih aman dikerjakan bertahap, modul demi modul, memakai pola identik yang sudah dibangun di sini. Brief ini cuma menyiapkan fondasi + 1 module referensi penuh (Academy) | [Tahap 12](#tahap-12--follow-up-konversi-module-lain-di-luar-scope-brief-ini) |
| Terjemahkan `lang/en/validation.php` bawaan Laravel | FAOSBall sudah punya konvensi sendiri: pesan validasi ditulis eksplisit di `messages()` tiap Form Request (lihat `docs/coding-standard.md` lama). Validation.php bawaan Laravel cuma dipakai untuk beberapa `validate()` inline milik Controller bawaan Breeze yang belum custom message — cukup diamkan, itu bukan pola FAOSBall | — |

---

## 1. Konteks & Tujuan

FAOSBall saat ini 100% Bahasa Indonesia, di-hardcode langsung di kode (`docs/coding-standard.md` lama bahkan eksplisit melarang folder `lang/`). Sekarang saatnya dibalik: app mendukung **2 bahasa** (Indonesia sebagai default, Inggris sebagai pilihan), dengan tombol ganti bahasa di header — mumpung module yang sudah dibangun masih sedikit (~8 module), migrasinya jauh lebih murah dibanding menunggu Coach/Parent/Staff/Team/Training/dst semua jadi.

```text
User klik "EN" di header
    ↓
Kalau sudah login  → kolom users.locale diupdate ke "en"
Kalau belum login   → session('locale') diisi "en"
    ↓
Redirect balik ke halaman yang sama
    ↓
Middleware SetLocale baca preferensi → App::setLocale('en')
    ↓
Seluruh __('...') di halaman itu langsung tampil Bahasa Inggris
```

**Bukan scope brief ini**: landing page (belum ada sama sekali di codebase — route `/` cuma redirect ke dashboard/login), URL-prefix locale (`/en/...`), deteksi otomatis dari header browser (`Accept-Language`), dan konversi module selain Academy (lihat Tahap 12).

---

## 2. Cara Kerja Solusi

### 2a. JSON string-based translation, bukan key-based

Laravel punya 2 gaya translasi:

```php
// Key-based (BUKAN dipakai di sini)
__('messages.welcome')   // cari key 'welcome' di lang/{locale}/messages.php

// String-based / JSON (DIPAKAI di sini)
__('Selamat datang')     // cari string persis "Selamat datang" di lang/{locale}.json
                          // KALAU TIDAK KETEMU -> kembalikan string aslinya apa adanya
```

Karena string Indonesia yang sudah ada di kode itu sendiri jadi "key"-nya, migrasi cuma perlu **membungkus** string yang sudah ada dengan `__(...)` — tidak perlu invent nama key baru satu-satu. Dan karena locale default-nya `id` (Bahasa Indonesia — sama dengan bahasa string aslinya), **`lang/id.json` tidak perlu dibuat sama sekali** — cukup `lang/en.json` berisi pemetaan `"Teks Indonesia": "English text"`.

### 2b. Prioritas resolusi locale

```text
1. User login DAN users.locale terisi   -> pakai itu
2. Guest/belum ada locale tersimpan     -> session('locale')
3. Tidak ada keduanya                   -> config('app.locale') = 'id' (default)
```

Middleware `SetLocale` (Tahap 4) yang menjalankan prioritas ini di **setiap** request, sebelum halaman dirender.

### 2c. Kenapa Breeze auth controllers (login/register/reset password) otomatis ikut terbenahi

`docs/coding-standard.md` lama mencatat gotcha: controller bawaan Breeze (`PasswordResetLinkController`, dst) manggil `__($status)`/`__('auth.password')` — key-based, bukan string literal — yang tanpa folder `lang/` jatuh ke default Laravel (Inggris). Solusi lama: timpa manual jadi string Indonesia literal.

Sekarang folder `lang/` **akan ada**. Jadi solusi yang benar bukan lagi "timpa manual", tapi publish file bawaan Laravel (`lang/en/auth.php`, `lang/en/passwords.php`) + buat versi Indonesianya (`lang/id/auth.php`, `lang/id/passwords.php`) — lihat Tahap 10. Setelah itu, `__($status)` bawaan Breeze otomatis balik ke Indonesia/Inggris sesuai locale aktif, **tanpa** perlu override manual lagi.

### 2d. Field `users.locale` nullable, bukan wajib

Sama seperti `academies.primary_color` (`issue6.md`) — nullable di DB, fallback aman kalau kosong (ke session lalu ke default `id`). Tidak ada migrasi backfill untuk user lama.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_add_locale_to_users_table.php` | 🆕 Baru | 1 |
| `app/Models/User.php` | ✏️ Tambah `locale` ke `#[Fillable]` | 2 |
| `config/app.php` | ✏️ Tambah `supported_locales` | 3 |
| `.env`, `.env.example` | ✏️ `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=id` | 3 |
| `app/Http/Middleware/SetLocale.php` | 🆕 Baru | 4 |
| `bootstrap/app.php` | ✏️ Daftarkan `SetLocale` ke grup `web` | 4 |
| `app/Http/Controllers/LocaleController.php` | 🆕 Baru | 5 |
| `routes/web.php` | ✏️ Tambah route `locale.switch` | 5 |
| `resources/views/partials/header.blade.php` | ✏️ Tambah dropdown ganti bahasa | 6 |
| `lang/en.json` | 🆕 Baru | 7 |
| `resources/views/layouts/app.blade.php` | ✏️ Bungkus string dengan `__()` | 8 |
| `resources/views/partials/header.blade.php` | ✏️ Bungkus string dengan `__()` | 8 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Bungkus string dengan `__()` | 8 |
| `resources/views/academies/*.blade.php` (index/create/edit/show) | ✏️ Bungkus string dengan `__()` | 9 |
| `resources/views/academies/account/*.blade.php`, `academy-profile/edit.blade.php` | ✏️ Bungkus string dengan `__()` | 9 |
| `app/Http/Requests/Academy/*.php` | ✏️ Bungkus pesan `messages()` dengan `__()` | 9 |
| `app/Http/Controllers/Academy*Controller.php` | ✏️ Bungkus flash message dengan `__()` | 9 |
| `lang/en/auth.php`, `lang/en/passwords.php` | 🆕 Publish bawaan Laravel | 10 |
| `lang/id/auth.php`, `lang/id/passwords.php` | 🆕 Terjemahan Indonesia | 10 |
| `docs/coding-standard.md` | ✏️ Ganti total section *Bahasa Pesan* | 11 |
| `tests/Feature/LocaleTest.php` | 🆕 Baru | 12a |
| **Module Players, Roles, Permissions, Player Type/Category/Position** | 🚫 **Di luar scope brief ini** — konversi menyusul, pola identik Tahap 8-9 | [Tahap 12](#tahap-12--follow-up-konversi-module-lain-di-luar-scope-brief-ini) |
| **`lang/en/validation.php`** | 🚫 **Jangan dibuat** — FAOSBall pakai `messages()` custom per Form Request, bukan validation.php generik | — |
| **Landing page** | 🚫 **Tidak dibuat di brief ini** — belum ada sama sekali di codebase, di luar scope | — |

---

## Tahap 1 — Migration

```bash
php artisan make:migration add_locale_to_users_table --table=users
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
        Schema::table('users', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Locale
            |--------------------------------------------------------------------------
            | Preferensi bahasa user ("id"/"en"). Nullable -- user lama belum
            | punya nilainya, fallback ke session lalu ke config('app.locale')
            | lewat Middleware SetLocale (Tahap 4). Tidak ada backfill migration.
            */
            $table->string('locale', 5)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table users
```

Harus ada kolom `locale`, `varchar(5)`, **nullable**.

---

## Tahap 2 — Model

`app/Models/User.php`, tambahkan `'locale'` ke atribut `#[Fillable]`:

```php
#[Fillable([
    'id_academy',
    'name',
    'email',
    'password',
    'status',
    'locale',
])]
```

> Tidak ada cast tambahan — string biasa. Tidak ada accessor/method resolve-locale di Model — logic ada di Middleware (Tahap 4), sama seperti alasan `subscription_status`/`primary_color` tidak dihitung di Model manapun sebelumnya.

**✅ Cek dulu**: `php artisan tinker` → `(new \App\Models\User)->getFillable()` harus memuat `locale`.

---

## Tahap 3 — Config & Environment

`config/app.php`, tambahkan array baru **setelah** key `'fallback_locale'`:

```php
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    | Sumber kebenaran TUNGGAL daftar bahasa yang didukung -- dipakai bareng
    | oleh SetLocale (validasi whitelist), LocaleController (validasi
    | whitelist), dan dropdown di header (label tampilan). Jangan hardcode
    | daftar ini di tempat lain.
    */
    'supported_locales' => [
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ],
```

Ubah `.env` **dan** `.env.example` (baris `APP_LOCALE`/`APP_FALLBACK_LOCALE`):

```env
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
```

> `fallback_locale` dipakai Laravel kalau `App::setLocale()` dikasih locale yang ternyata tidak valid/tidak ke-load — dengan `id` sebagai fallback, kasus terburuknya user tetap lihat Bahasa Indonesia (bukan Inggris atau error).

**✅ Cek dulu**: `php artisan tinker` → `config('app.supported_locales')` harus mengembalikan array 2 item. `config('app.locale')` harus `"id"`.

---

## Tahap 4 — Middleware `SetLocale`

```bash
php artisan make:middleware SetLocale
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolusi locale aktif untuk request ini, prioritas:
     * 1. User login DAN users.locale terisi
     * 2. session('locale') (guest, atau user yang belum pernah set locale)
     * 3. config('app.locale') default ("id")
     *
     * Validasi whitelist di sini juga -- BUKAN cuma percaya kolom
     * users.locale/session apa adanya, jaga-jaga data lama/corrupt.
     * Lihat issue7.md Bagian 4.2.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = auth()->user()?->locale
            ?? session('locale')
            ?? config('app.locale');

        if (!array_key_exists($locale, config('app.supported_locales'))) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
```

Di `bootstrap/app.php`, tambahkan `SetLocale` ke grup middleware `web`:

```php
use App\Http\Middleware\SetLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
```

> `$middleware->web(append: [...])` — bukan `alias()` — supaya jalan **otomatis** di setiap request lewat grup `web`, tidak perlu didaftarkan manual di tiap route. Endpoint health check (`/up`) tidak lewat grup `web`, jadi tidak kena middleware ini (memang tidak perlu).

**✅ Cek dulu**: `php artisan route:list` → pastikan tidak ada error boot. Buka halaman apa saja → tidak crash (locale fallback ke `id` untuk semua user, karena belum ada yang set locale-nya).

---

## Tahap 5 — Route & `LocaleController`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Ganti preferensi bahasa lalu redirect balik ke halaman asal.
     *
     * $locale divalidasi terhadap whitelist config('app.supported_locales')
     * SEBELUM dipakai -- jangan percaya route parameter mentah-mentah.
     */
    public function switch(Request $request, string $locale)
    {
        abort_unless(
            array_key_exists($locale, config('app.supported_locales')),
            404
        );

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
```

Di `routes/web.php`, tambahkan **di luar** middleware `auth` (harus bisa diakses guest juga, mis. dari halaman login):

```php
use App\Http\Controllers\LocaleController;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
```

> Tidak perlu middleware `permission`/`role` apa pun di route ini — ganti bahasa bukan aksi yang butuh otorisasi khusus, sama seperti dark mode toggle yang juga tidak digerbang permission.

**✅ Cek dulu**: buka `/locale/en` lewat browser (login atau tidak) → redirect balik ke halaman sebelumnya, tidak error. Buka `/locale/fr` (locale tidak terdaftar) → harus **404**, bukan crash/locale ke-set jadi "fr".

---

## Tahap 6 — Tombol Ganti Bahasa di Header

**Tujuan**: dropdown kecil di header (posisi: sebelah tombol dark-mode), mengikuti pola Alpine dropdown yang sudah ada (notification/user menu).

Di `resources/views/partials/header.blade.php`, sisipkan **setelah** blok `<!-- END Dark Mode -->`, **sebelum** blok `<!-- Notification -->`:

```blade
                <!-- Language Switcher -->
                <div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                    <button class="header-icon-btn-circle text-xs font-semibold uppercase"
                        @click.prevent="dropdownOpen = !dropdownOpen">
                        {{ app()->getLocale() }}
                    </button>

                    <div x-show="dropdownOpen" x-cloak
                        class="header-dropdown-panel right-0 w-48">
                        <ul class="header-dropdown-list">
                            @foreach (config('app.supported_locales') as $code => $label)
                                <li>
                                    <a href="{{ route('locale.switch', $code) }}"
                                        class="header-dropdown-item group {{ app()->getLocale() === $code ? 'font-semibold' : '' }}">
                                        {{ $label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <!-- END Language Switcher -->

```

> Tombol menampilkan kode locale aktif (`ID`/`EN`) sebagai teks, bukan ikon SVG baru — konsisten dengan `header-icon-btn-circle` yang sudah ada (dark mode pakai SVG karena itu ikon universal matahari/bulan; bahasa lebih jelas sebagai teks kode daripada bendera/ikon yang ambigu).

**✅ Cek dulu**: buka halaman manapun → tombol "ID" muncul di header sebelah dark-mode toggle. Klik → dropdown muncul 2 pilihan ("Bahasa Indonesia", "English"). Klik "English" → halaman reload, tombol berubah jadi "EN".

---

## Tahap 7 — `lang/en.json`

Buat folder `lang/` di root project (sejajar `app/`, `resources/`, dst) kalau belum ada, lalu file `lang/en.json`:

```json
{
}
```

> Mulai kosong. Entry-nya diisi bertahap di Tahap 8-9 begitu string yang dibungkus `__()` sudah jelas apa saja. **Tidak** membuat `lang/id.json` (lihat [4.1](#41-kenapa-json-string-based-bukan-key-based)).

**✅ Cek dulu**: `php artisan tinker` → `app()->setLocale('en'); __('Ini tidak ada di json');` harus mengembalikan string persis yang dimasukkan apa adanya (belum ada terjemahan, fallback ke string asli — bukan error).

---

## Tahap 8 — Konversi Layout Bersama

**Tujuan**: `layouts/app.blade.php`, `partials/header.blade.php`, `partials/sidebar.blade.php` dikonversi **penuh** — dipakai di SEMUA halaman, jadi nilai konversinya paling tinggi.

Pola konversi (contoh dari `partials/sidebar.blade.php`):

```blade
{{-- SEBELUM --}}
<span class="menu-item-text">Dashboard</span>

{{-- SESUDAH --}}
<span class="menu-item-text">{{ __('Dashboard') }}</span>
```

```blade
{{-- SEBELUM --}}
Ringkasan

{{-- SESUDAH --}}
{{ __('Ringkasan') }}
```

Terapkan pola yang sama untuk **setiap** teks user-facing di 3 file itu: "Dashboard", "Ringkasan", "football academy", "Players", "Player Types", "Player Categories", "Profile", "Edit Profil", "Profil Academy", "Administrasi", "Academy", "Roles & Permissions", "Roles", "Permissions", "Master", "Posisi Pemain", "Notifikasi", "Lihat Semua Notifikasi", "Sign Out", "Keluar dari akun?", dst. **Jangan** bungkus atribut non-teks (`href`, `class`, `:class`, nama route) — cuma teks yang benar-benar tampil ke user.

Tambahkan entry terjemahan ke `lang/en.json` untuk setiap string yang baru dibungkus:

```json
{
    "Dashboard": "Dashboard",
    "Ringkasan": "Summary",
    "football academy": "football academy",
    "Players": "Players",
    "Player Types": "Player Types",
    "Player Categories": "Player Categories",
    "Profile": "Profile",
    "Edit Profil": "Edit Profile",
    "Profil Academy": "Academy Profile",
    "Administrasi": "Administration",
    "Academy": "Academy",
    "Roles &amp; Permissions": "Roles &amp; Permissions",
    "Roles": "Roles",
    "Permissions": "Permissions",
    "Master": "Master",
    "Posisi Pemain": "Player Position",
    "Notifikasi": "Notifications",
    "Lihat Semua Notifikasi": "View All Notifications",
    "Sign Out": "Sign Out",
    "Keluar dari akun?": "Sign out of your account?"
}
```

> String yang secara kebetulan sama di kedua bahasa (mis. "Dashboard", "Academy", "Roles") **tetap** wajib ada entry-nya di `lang/en.json` (walau valuenya sama) — supaya jelas string itu SUDAH direview untuk locale Inggris, bukan kelewat.

**✅ Cek dulu**: ganti locale ke `en` (klik tombol di Tahap 6) → seluruh sidebar/header berubah ke Inggris. Ganti balik ke `id` → kembali ke Indonesia (string asli, tanpa perlu `lang/id.json`).

---

## Tahap 9 — Konversi Module Academy (Referensi Penuh)

**Tujuan**: 1 module dikonversi **lengkap** ujung ke ujung (view + Form Request + Controller) sebagai contoh yang bisa ditiru persis untuk module lain di Tahap 12.

### 9a. View (`academies/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`, `account/*.blade.php`, `academy-profile/edit.blade.php`)

Pola sama seperti Tahap 8. Contoh dari `academies/index.blade.php`:

```blade
{{-- SEBELUM --}}
<h3 class="card-title">Academy List</h3>
<p class="card-description">Manajemen profil, tagline, dan status akademi sepak bola.</p>

{{-- SESUDAH --}}
<h3 class="card-title">{{ __('Academy List') }}</h3>
<p class="card-description">{{ __('Manajemen profil, tagline, dan status akademi sepak bola.') }}</p>
```

Untuk teks dengan variable di dalamnya (interpolasi), pakai placeholder `:nama` bawaan Laravel — **jangan** concat string:

```blade
{{-- SEBELUM --}}
<p class="card-description">Kelola informasi profil academy Anda. Kode academy, status, dan
    informasi langganan hanya dapat diubah oleh Super Admin.</p>

{{-- SESUDAH -- tetap 1 string utuh, TIDAK dipecah per kalimat --}}
<p class="card-description">{{ __('Kelola informasi profil academy Anda. Kode academy, status, dan informasi langganan hanya dapat diubah oleh Super Admin.') }}</p>
```

Untuk badge dinamis (`$subscriptionBadges`, dst di `academies/index.blade.php`/`show.blade.php`), bungkus **value label**-nya, bukan struktur array-nya:

```php
$subscriptionBadges = [
    'aktif' => ['label' => __('Aktif'), 'class' => 'badge-success'],
    'akan_berakhir' => ['label' => __('Akan Berakhir'), 'class' => 'badge-warning'],
    'kadaluarsa' => ['label' => __('Kadaluarsa'), 'class' => 'badge-danger'],
    'belum_diatur' => ['label' => __('Belum Diatur'), 'class' => 'badge-secondary'],
];
```

Terapkan pola ini ke **seluruh** teks di keenam file tersebut (label form, placeholder, helper text, tombol submit, empty-state, breadcrumb label yang dikirim dari Controller lihat 9c).

### 9b. Form Request (`AcademyFormRequest.php`, `AcademyProfileFormRequest.php`, `StoreAcademyAccountRequest.php`, `UpdateAcademyAccountRequest.php`)

Bungkus **value** di `messages()`, bukan key-nya:

```php
public function messages(): array
{
    return [
        'name.required' => __('Nama academy wajib diisi.'),
        'name.max' => __('Nama academy tidak boleh lebih dari 255 karakter.'),
        // ... lanjutkan pola yang sama untuk SEMUA baris di messages()
    ];
}
```

### 9c. Controller (`AcademyController.php`, `AcademyProfileController.php`, `AcademyAccountController.php`)

Bungkus flash message DAN label breadcrumb/title yang dikirim ke view:

```php
return redirect()
    ->route('academies.index')
    ->with('success', __('Academy berhasil ditambahkan.'));
```

```php
return view('academies.index', [
    'title' => __('Manajemen Academy'),
    'breadcrumb' => [
        ['label' => __('Manajemen Academy')],
    ],
    // ...
]);
```

Tambahkan **seluruh** entry baru dari 9a-9c ke `lang/en.json` (akan jadi puluhan baris — itu wajar, module Academy termasuk yang paling banyak halamannya).

**✅ Cek dulu**

```bash
php artisan test --filter=Academy
```

Semua test **harus tetap pass** — `assertSee('Academy List')` dkk masih valid karena saat test jalan locale default `id` (string Indonesia asli tetap tampil apa adanya, `__()` tidak mengubah apapun kalau tidak ada entry `en.json` yang dipanggil).

Lalu cek manual: ganti locale ke `en`, buka `/academies`, `/academies/create`, `/academies/{id}/edit`, `/academies/{id}` — seluruh teks harus berubah ke Inggris sesuai `lang/en.json`. Ganti balik ke `id` → kembali ke Indonesia.

---

## Tahap 10 — Breeze Auth Strings

**Tujuan**: `login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password` ikut mendukung 2 bahasa lewat mekanisme translasi **bawaan** Laravel (key-based, khusus untuk file ini — beda dari Tahap 7-9 yang JSON-based, lihat [2c](#2c-kenapa-breeze-auth-controllers-loginregisterreset-password-otomatis-ikut-terbenahi)).

```bash
php artisan lang:publish
```

Perintah ini membuat `lang/en/auth.php`, `lang/en/pagination.php`, `lang/en/passwords.php`, `lang/en/validation.php`. **Hapus** `lang/en/pagination.php` dan `lang/en/validation.php` (tidak dipakai, lihat Aturan Emas). Sisakan `lang/en/auth.php` dan `lang/en/passwords.php` — isinya sudah Bahasa Inggris standar Laravel, tidak perlu diubah.

Buat versi Indonesianya, `lang/id/auth.php`:

```php
<?php

return [
    'failed' => 'Email atau password tidak sesuai.',
    'password' => 'Password yang dimasukkan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Coba lagi dalam :seconds detik.',
];
```

`lang/id/passwords.php`:

```php
<?php

return [
    'reset' => 'Password Anda telah direset.',
    'sent' => 'Tautan reset password telah dikirim ke email Anda.',
    'throttled' => 'Mohon tunggu sebelum mencoba lagi.',
    'token' => 'Token reset password tidak valid.',
    'user' => 'Kami tidak menemukan user dengan email tersebut.',
];
```

> Kalau ada controller Breeze yang sebelumnya di-patch manual dengan string Indonesia literal (sesuai workaround lama di `docs/coding-standard.md`), **boleh dibiarkan** — string literal Indonesia itu tetap valid untuk locale `id` (sama seperti string FAOSBall lainnya). Yang penting `lang/id/auth.php` & `lang/id/passwords.php` ada untuk kasus yang MASIH manggil `__('auth.failed')` dkk secara langsung.

**✅ Cek dulu**: ganti locale ke `en`, coba login dengan password salah → pesan error harus muncul Bahasa Inggris ("These credentials do not match our records." atau sejenisnya dari `lang/en/auth.php`). Ganti balik ke `id` → pesan error Indonesia.

---

## Tahap 11 — Update `docs/coding-standard.md`

**Tujuan**: ganti total section *Bahasa Pesan (User-Facing Messages)* — section itu eksplisit melarang folder `lang/`, yang sekarang dibalik.

Ganti isi section (cari heading `## Bahasa Pesan (User-Facing Messages)` sampai sebelum `## Summary`) menjadi:

```markdown
## Bahasa Pesan (User-Facing Messages) & Multi-Language

FAOSBall mendukung 2 bahasa: **Bahasa Indonesia (default)** dan **English**. Mekanismenya:

1. **String Indonesia yang sudah ada di kode TETAP ditulis apa adanya** -- cuma dibungkus `__(...)`. Ini translasi berbasis string/JSON (`lang/en.json`), BUKAN key-based (`__('messages.key')`). Tidak ada `lang/id.json` -- kalau locale aktif `id` dan tidak ada entry, `__()` mengembalikan string aslinya apa adanya.

    ```php
    'name.required' => __('Nama academy wajib diisi.'),
    ```

    ```blade
    <h3 class="card-title">{{ __('Academy List') }}</h3>
    ```

2. **Setiap string baru yang dibungkus `__()` WAJIB ditambahkan entry-nya ke `lang/en.json`** di PR yang sama -- termasuk string yang kebetulan sama di kedua bahasa (supaya jelas sudah direview, bukan kelewat).

3. **Preferensi bahasa** disimpan di `users.locale` (user login) atau `session('locale')` (guest), diresolusi oleh `App\Http\Middleware\SetLocale` di setiap request. Jangan taruh logic ini di Controller/View manapun.

4. **Controller bawaan Laravel Breeze** (`PasswordResetLinkController`, `NewPasswordController`, dst) memakai mekanisme **berbeda** -- key-based bawaan Laravel (`lang/{locale}/auth.php`, `lang/{locale}/passwords.php`), BUKAN JSON. Sudah tersedia untuk `id` dan `en` (lihat `issue7.md` Tahap 10) -- tidak perlu override manual lagi.

5. **`lang/en/validation.php` sengaja tidak dipakai** -- pesan validasi FAOSBall selalu eksplisit lewat `messages()` tiap Form Request (lihat *Model Standard*/*Request Structure* di atas), bukan mengandalkan attribute-name generik bawaan Laravel.

Module baru **wajib** ikut pola ini sejak awal dibuat -- jangan menulis string Indonesia hardcode tanpa `__()` lagi.
```

**✅ Cek dulu**: baca ulang section barunya, pastikan tidak ada sisa kalimat lama yang bilang "tidak menggunakan sistem file terjemahan Laravel" (kontradiksi langsung dengan isi baru).

---

## Tahap 12 — Follow-up: Konversi Module Lain (DI LUAR scope brief ini)

Module berikut **belum** dikonversi — dikerjakan menyusul, modul demi modul, memakai pola **identik** Tahap 9 (view + Form Request + Controller + tambah entry `lang/en.json`):

- Players (`players/*.blade.php`, `PlayerFormRequest`, `PlayerController`, `PlayerAccountController`)
- Roles & Permissions (`roles/*.blade.php`, `permissions/*.blade.php`, `RoleFormRequest`, `RoleController`, `PermissionController`)
- Player Type / Player Category / Player Position (index/create/edit + Form Request + Controller masing-masing)
- Profile (Breeze bawaan, `profile/partials/*.blade.php`) — **konvensi beda** (pakai komponen `x-input-label`/dst milik Breeze, bukan `form-label`/`form-input` FAOSBall, lihat `docs/frontend-standard.md`) — evaluasi terpisah apakah mau disamakan dulu ke konvensi FAOSBall atau langsung dikasih `__()` di komponen Breeze-nya

**Checklist per module** (tempel di PR description tiap kali 1 module selesai dikonversi):

- [ ] Semua view index/create/edit/show dibungkus `__()` (label, placeholder, helper text, tombol, empty-state)
- [ ] `messages()` Form Request dibungkus `__()`
- [ ] Flash message & `title`/`breadcrumb` di Controller dibungkus `__()`
- [ ] Entry baru ditambahkan ke `lang/en.json`
- [ ] `php artisan test --filter=<Module>` tetap pass (locale default `id` saat test jalan, jadi assertion `assertSee('...')` dengan string Indonesia tidak perlu berubah)
- [ ] Cek manual di browser: ganti locale ke `en`, buka semua halaman module itu, pastikan tidak ada string Indonesia yang kelewat

---

## Tahap 12a. Test

`tests/Feature/LocaleTest.php` — file baru:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_indonesia_untuk_user_baru(): void
    {
        $user = User::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame('id', app()->getLocale());
    }

    public function test_switch_locale_menyimpan_ke_kolom_user_dan_langsung_berlaku(): void
    {
        $user = User::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get(route('locale.switch', 'en'));

        $response->assertRedirect();
        $this->assertSame('en', $user->fresh()->locale);

        $this->actingAs($user)->get(route('dashboard'));
        $this->assertSame('en', app()->getLocale());
    }

    public function test_switch_locale_untuk_guest_disimpan_ke_session(): void
    {
        $response = $this->get(route('locale.switch', 'en'));

        $response->assertRedirect();
        $this->assertSame('en', session('locale'));
    }

    public function test_locale_tidak_terdaftar_ditolak_404(): void
    {
        $response = $this->get(route('locale.switch', 'fr'));

        $response->assertNotFound();
    }

    public function test_locale_invalid_di_kolom_user_fallback_ke_default(): void
    {
        $user = User::factory()->create(['status' => true, 'locale' => 'fr']);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame('id', app()->getLocale());
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=LocaleTest
php artisan test
```

Seluruh test `LocaleTest` **harus pass**, dan seluruh test suite lain **tidak boleh regresi** (locale default `id` saat test jalan berarti semua `assertSee()` dengan string Indonesia yang sudah ada tetap valid tanpa perubahan).

---

## 4. Alasan Teknis

### 4.1 Kenapa JSON string-based, bukan key-based

Kalau pakai key-based (`__('academy.name_required')`), migrasi berarti: (1) invent nama key untuk **setiap** string yang sudah ada, (2) pindahkan isi string Indonesia-nya ke `lang/id/academy.php`, (3) baru buat versi Inggrisnya. Tiga langkah untuk tiap string, dikali ratusan string di seluruh app.

Dengan JSON string-based: string Indonesia yang sudah ada **langsung** jadi argumen `__()` tanpa dipindah kemana-mana, dan `lang/id.json` tidak perlu dibuat sama sekali (locale default match dengan bahasa asli string di kode). Cuma 1 langkah per string: bungkus, lalu isi terjemahan Inggrisnya di `lang/en.json`. Untuk migrasi besar seperti ini (ratusan string existing), bedanya signifikan.

### 4.2 Kenapa locale divalidasi ulang di 2 tempat (Controller DAN Middleware)

Sama seperti pola defense-in-depth yang sudah dipakai di `AcademyService::brandColorVariables()` (`issue6.md` Bagian 4.1) dan `AcademyManagementService::updateProfile()` (`issue.md` Bagian 4.7): `LocaleController::switch()` memvalidasi locale SAAT DISIMPAN (dari route parameter, bisa dikarang siapa saja lewat URL), dan `SetLocale` middleware memvalidasi ULANG SAAT DIPAKAI (dari kolom `users.locale`/session, yang bisa saja berisi data lama/corrupt kalau daftar locale yang didukung berubah di masa depan, mis. locale `fr` dihapus dari `supported_locales` tapi masih ada user yang kolomnya `fr` dari sebelumnya). Dua lapis, bukan cuma satu.
