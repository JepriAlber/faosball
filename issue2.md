# Brief: Module Academy Account (Buat & Kelola Akun Owner saat Tambah Academy)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu. Terutama `docs/multi-tenancy.md`, `docs/authorization.md`, `docs/permission-reference.md`, `docs/module-standard.md`, dan `docs/frontend-standard.md`. Baca juga `issue.md` (brief Academy Subscription + Academy Profile) karena brief ini menempel langsung di atas module yang dibangun di sana — jangan kerjakan brief ini sebelum `issue.md` selesai.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 11 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> Kalau cuma mau eksekusi, cukup baca Bagian 0–3 lalu langsung ke Tahap 1. Bagian 4 (alasan teknis) boleh dibaca belakangan, **tapi aturannya tetap tidak boleh dilanggar**.

---

## 0. Aturan Emas

Delapan larangan ini bukan preferensi gaya. Masing-masing sudah diverifikasi akan bikin bug atau celah nyata. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Pakai nama field `email`/`password` polos di `AcademyFormRequest` untuk akun Owner | `AcademyFormRequest` **sudah** punya field `email` — itu email kontak academy (telepon/alamat/email kantor), bukan email login. Kalau dipakai ulang, submit form akan tabrakan: satu input, dua makna berbeda | [4.1](#41-kenapa-field-akun-dinamai-owner_email--owner_password-bukan-email--password) |
| Gerbang route `academies/{academy}/account/*` pakai permission `user.*` (niru persis Player Account) | `user.create`/`user.update` **sudah** default dimiliki role Owner (lihat `config('faos.role_templates')`) — supaya Owner bisa bikin akun login untuk player-nya sendiri. Kalau dipakai ulang di sini, Owner academy manapun bisa lolos middleware ke rute pembuatan/pengubahan akun Owner **academy lain** (walau ujungnya masih ke-block di `AcademyController` — tapi rute account ini berdiri sendiri, tidak menumpang gate itu) | [4.2](#42-kenapa-permission-academyaccount-pakai-academyupdate-bukan-user-atau-permission-baru) |
| Lupa mengisi `id_owner_user` di baris `academies` setelah `User` Owner berhasil dibuat | `AccountService::create()` cuma membuat `User` + assign role — **tidak** tahu-menahu soal `Academy`. Kalau langkah update `id_owner_user` kelupaan, `$academy->owner` akan selalu `null` walau akunnya sebetulnya ada, dan halaman Detail Academy tidak akan bisa menampilkan/mengelola akun itu | [Tahap 3](#tahap-3--academymanagementservicecreate) |
| Asumsikan akun Owner yang baru dibuat otomatis bisa langsung login | `LoginRequest` mengecek **dua status terpisah**: `$user->status` (akun) **dan** `$academy->status` (academy). Academy baru defaultnya `status = false` (lihat `AcademyManagementService::create()`) kecuali Super Admin eksplisit toggle Aktif. Kalau lupa, Owner dapat pesan "Academy sedang tidak aktif." walau akunnya valid | [4.3](#43-kenapa-membuat-akun-owner-tidak-otomatis-berarti-bisa-login) |
| Taruh logic pembuatan `User` Owner di `AcademyController` | Melanggar Thin Controller (`docs/architecture.md`). Logic penciptaan Owner **wajib** di `AcademyManagementService::create()`, persis pola `PlayerService::create()` untuk `create_account` | [Tahap 3](#tahap-3--academymanagementservicecreate) |
| Buat `User` Owner sebelum `RoleService::createDefaultRoles()` selesai jalan | Role `Owner` untuk academy itu **belum ada** sebelum `createDefaultRoles()` dipanggil — `AccountService::assignRole()` akan lempar exception "Role tidak ditemukan pada academy user." kalau urutannya kebalik | [Tahap 3](#tahap-3--academymanagementservicecreate) |
| Pisahkan `Academy::create()` dan pembuatan `User` Owner ke dua transaksi berbeda (atau tanpa transaksi sama sekali) | Kalau email Owner ternyata sudah dipakai (`unique:users,email` lolos di validasi tapi race condition, atau kasus lain), Academy yang sudah kadung tersimpan jadi "yatim" — ada tapi tanpa Owner dan tidak bisa dibatalkan otomatis. Satu `DB::transaction()` yang sama, bukan dua terpisah | [Tahap 3](#tahap-3--academymanagementservicecreate) |
| Bikin `AcademyAccountController` mewarisi/menumpang `AcademyController` yang sudah ada | Sama seperti alasan Academy Profile terpisah dari Academy Management di `issue.md` (`2d`) — sub-resource `account` punya permission & tanggung jawab beda dari CRUD utama `Academy`, harus jadi Controller & Form Request sendiri | [Tahap 6](#tahap-6--academyaccountcontroller-baru) |

---

## 1. Tujuan

Saat ini (setelah `issue.md` selesai), alur Super Admin menambah academy baru **tidak** menyertakan pembuatan akun login untuk pemilik academy tersebut. Owner baru harus dibuatkan akunnya secara manual lewat jalur lain (mis. Role Management + tinker), yang gampang salah dan tidak konsisten dengan pola yang **sudah ada** di module Player: form Tambah Player sudah punya toggle "Buat Akun Player" yang langsung membuatkan akun login di request yang sama (lihat `PlayerService::create()` → `if (!empty($data['create_account']))`), plus jalur terpisah untuk buat/edit akun belakangan (`PlayerAccountController`).

**Scope brief ini**: meniru pola itu persis untuk `Academy`:

1. Form **Tambah Academy** (`academies.create`) dapat toggle "Buat Akun Owner" — kalau diaktifkan, Super Admin mengisi email & password, dan begitu academy tersimpan, satu akun `User` dengan role **Owner** langsung ikut dibuat dan terhubung ke academy itu.
2. Kalau academy dibuat **tanpa** toggle itu (atau akunnya nanti perlu diganti/direset), tersedia sub-halaman terpisah `academies/{academy}/account/*` (create/edit/reset password/aktif-nonaktifkan) — persis pola `players/{player}/account/*`.

**Bukan** scope brief ini: mengubah alur registrasi Owner mandiri (self-service sign up), mengizinkan Owner mengganti emailnya sendiri (itu ranah Academy Profile di `issue.md`, kalau memang dibutuhkan nanti didiskusikan terpisah), atau menambah role selain Owner lewat jalur ini.

---

## 2. Cara Kerja Solusi

Baca sampai paham. Kalau bagian ini tidak nyantol, sisa brief akan terasa acak.

### 2a. Kenapa perlu kolom baru `id_owner_user` di `academies`

Pola yang sudah ada di `Player` menyimpan relasi ke akun login lewat kolom `id_user` langsung di tabel `players` (lihat `database/migrations/2026_06_25_163827_create_players_table.php`) — **bukan** dicari ulang lewat role setiap saat. Alasannya: satu `Player` = maksimal satu akun, jadi FK langsung adalah cara paling murah & tidak ambigu untuk tahu "akun mana yang terhubung ke baris ini".

`Academy` butuh pola yang sama, tapi **tidak boleh** dicari lewat "user pertama yang punya role Owner di academy ini" — karena:
- Satu academy nanti bisa punya lebih dari satu user dengan role Owner (Super Admin bebas menambah lewat Role Management), jadi "yang mana Owner utama" jadi ambigu.
- Query "cari user pertama dengan role X di academy Y" jauh lebih mahal (join ke tabel pivot Spatie) dibanding satu kolom FK langsung.

Solusinya: tambahkan kolom `id_owner_user` (uuid, nullable) ke tabel `academies`, mengarah ke `users.id_user` — persis pola `players.id_user`, cuma arah penamaannya dibalik supaya jelas maksudnya ("akun Owner milik academy ini"), bukan `id_user` polos yang bisa disalahartikan sebagai "academy ini milik user".

### 2b. Kenapa field akun dinamai `owner_email` / `owner_password`, bukan `email` / `password`

`AcademyFormRequest` **sudah** memvalidasi `email` sebagai email kontak academy (required, dipakai di halaman Detail Academy sebagai info kontak — lihat Tahap 4 di `issue.md`). Kalau field akun Owner juga dinamai `email`, satu `<input name="email">` di halaman yang sama akan menimpa nilai yang lain tergantung urutan render — form HTML tidak punya namespace. Makanya field baru **wajib** pakai prefix `owner_`: `owner_email`, `owner_password`, `owner_password_confirmation`. Ini beda dengan Player, yang form Tambah Player-nya tidak punya field `email` bawaan sama sekali, jadi `email`/`password` polos aman dipakai di sana.

### 2c. Urutan pembuatan (di dalam satu `DB::transaction()`)

```text
1. Academy::create()                        -- academy tersimpan, id_academy ada
2. RoleService::createDefaultRoles($academy) -- role "Owner" utk academy ini ada
3. (kalau create_account) AccountService::create(..., 'Owner')
                                              -- User dibuat, role Owner ter-assign
4. (kalau create_account) $academy->update(['id_owner_user' => $user->id_user])
```

Urutan 2 sebelum 3 **wajib** — `AccountService::assignRole()` mencari baris `Role` bernama "Owner" **milik academy yang baru dibuat itu**, dan baris itu baru ada setelah langkah 2 selesai.

### 2d. Kenapa permission `academy.update`, bukan `user.*` atau permission baru

Player Account sengaja pakai permission `user.*` yang **terpisah** dari `player.*`, supaya role tertentu (Coach, Staff) bisa mengelola data player tapi **tidak otomatis** bisa membuat akun login-nya — pemisahan itu berguna karena Player Management memang didelegasikan ke banyak role.

Academy Management **tidak** didelegasikan ke role manapun sama sekali — sudah, dan tetap, Super-Admin-only (lihat `issue.md` → `2c`, `4.4`). Karena satu-satunya pihak yang pernah menyentuh rute ini adalah Super Admin (yang lolos segalanya lewat `Gate::before()`), menambah permission baru (mis. `academy_account.manage`) tidak memberi manfaat pemisahan apapun — cuma menambah satu baris permission yang tidak pernah dipakai bedakan siapa-boleh-apa. Pakai `academy.update` yang sudah ada, sudah Super-Admin-only, dan sudah dikenal `docs/permission-reference.md`.

### 2e. Dua status yang terpisah: akun Owner vs academy

| | Dikontrol lewat | Efek kalau `false` |
|---|---|---|
| `users.status` | Toggle Aktif/Nonaktif di `AcademyAccountController::status()` (Tahap 6) | Login ditolak: "Akun Anda sedang dinonaktifkan." |
| `academies.status` | Toggle Aktif/Nonaktif di form Tambah/Edit Academy (`issue.md`, sudah ada) | Login ditolak: "Academy sedang tidak aktif." (kecuali user itu Super Admin) |

Keduanya independen. Supaya Owner yang baru dibuat **bisa langsung login**, Super Admin harus memastikan **kedua-duanya** aktif — akun (otomatis `true` dari `AccountService::create()`) **dan** academy (`status` di form Tambah Academy, defaultnya `false` kalau tidak dicentang — lihat `AcademyManagementService::create()`). Brief ini **tidak** mengubah default academy jadi otomatis aktif hanya karena toggle akun dicentang — dua keputusan itu independen dan Super Admin harus tetap sadar menentukan keduanya (lihat [4.3](#43-kenapa-membuat-akun-owner-tidak-otomatis-berarti-bisa-login)).

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_add_id_owner_user_to_academies_table.php` | 🆕 Baru | 1 |
| `app/Models/Academy.php` | ✏️ Tambah fillable `id_owner_user` + relasi `owner()` | 2 |
| `app/Models/User.php` | ✏️ Tambah relasi `ownedAcademy()` (opsional, untuk kebutuhan lain) | 2 |
| `app/Services/AcademyManagementService.php` | ✏️ Ubah `create()`, inject `AccountService` | 3 |
| `app/Http/Requests/Academy/AcademyFormRequest.php` | ✏️ Tambah rules `create_account`, `owner_email`, `owner_password` | 4 |
| `resources/views/academies/create.blade.php` | ✏️ Tambah toggle "Buat Akun Owner" | 5 |
| `app/Http/Controllers/AcademyAccountController.php` | 🆕 Baru | 6 |
| `app/Http/Requests/Academy/StoreAcademyAccountRequest.php` | 🆕 Baru | 7 |
| `app/Http/Requests/Academy/UpdateAcademyAccountRequest.php` | 🆕 Baru | 7 |
| `routes/web.php` | ✏️ Tambah nested route `academies/{academy}/account/*` | 8 |
| `resources/views/academies/show.blade.php` | ✏️ Tambah `<x-account.dropdown>` | 9 |
| `resources/views/academies/account/create.blade.php` | 🆕 Baru | 9 |
| `resources/views/academies/account/edit.blade.php` | 🆕 Baru | 9 |
| `app/Http/Controllers/AcademyController.php` | ✏️ `show()` eager-load relasi `owner` | 9 |
| `tests/Feature/AcademyAccountTest.php` | 🆕 Baru | 10 |
| `docs/permission-reference.md` | ✏️ Tambah sub-section "Sub-module: Academy Account" | 11 |
| **`app/Services/AccountService.php`** | 🚫 **Jangan sentuh** — sudah generik (menerima `id_academy`, `role` apapun), dipakai ulang apa adanya, sama seperti `PlayerService` memakainya | — |
| **`resources/views/components/account/dropdown.blade.php`** | 🚫 **Jangan sentuh** — komponen ini sudah generik (`:model`, `:user`, route props), dipakai ulang apa adanya | — |
| **`app/Http/Controllers/AcademyProfileController.php`, module Academy Profile (`issue.md` Tahap 12–18)** | 🚫 **Jangan sentuh** — beda total, itu self-service Owner mengubah profilnya sendiri, brief ini murni Super Admin membuatkan akun Owner | — |

---

## Tahap 1 — Migration

**Tujuan**: `academies` punya kolom `id_owner_user`, nullable, FK ke `users`.

```bash
php artisan make:migration add_id_owner_user_to_academies_table --table=academies
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
            | Owner Account
            |--------------------------------------------------------------------------
            | Nullable -- academy boleh ada tanpa akun Owner (dibuat belakangan lewat
            | AcademyAccountController). Arah relasi SENGAJA dari academies ke users,
            | bukan sebaliknya, supaya "akun Owner mana yang aktif untuk academy ini"
            | selalu jelas lewat satu FK langsung -- pola yang sama dengan
            | players.id_user. Lihat issue2.md Bagian 2a.
            */
            $table->uuid('id_owner_user')
                ->nullable()
                ->after('id_academy');

            $table->index('id_owner_user');

            $table->foreign('id_owner_user')
                ->references('id_user')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropForeign(['id_owner_user']);
            $table->dropColumn('id_owner_user');
        });
    }
};
```

> `academies` tidak punya kolom `id_academy` sebagai kolom biasa di posisi umum (dia primary key), jadi `->after('id_academy')` di sini merujuk ke primary key `id_academy` yang memang jadi kolom pertama tabel ini — cek dengan `php artisan db:table academies` kalau ragu urutannya.

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table academies
```

- Harus ada kolom `id_owner_user`, nullable, tipe uuid/char(36).
- Foreign key ke `users.id_user` dengan `on delete: set null`.

---

## Tahap 2 — Model

**Tujuan**: `Academy` tahu kolom & relasi barunya, tanpa logic tambahan.

`app/Models/Academy.php` — tambahkan ke `$fillable`:

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
    'description',
];
```

Tambahkan relasi (di bawah `casts()`, sebelum `boot()`):

```php
public function owner()
{
    return $this->belongsTo(User::class, 'id_owner_user');
}
```

`app/Models/User.php` — tambahkan relasi kebalikannya (dipakai kalau nanti butuh "academy mana yang dimiliki user ini", bukan dipakai wajib di brief ini, tapi konsisten dengan relasi `academy()` yang sudah ada di file yang sama):

```php
public function ownedAcademy()
{
    return $this->hasOne(Academy::class, 'id_owner_user');
}
```

> **Jangan tambahkan apapun selain ini ke Model.** Tidak ada accessor `hasOwner()`, tidak ada logic status di sini — itu tetap di Service/Controller kalau dibutuhkan.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\Models\Academy)->getFillable();
// harus memuat 'id_owner_user'

(new \App\Models\Academy)->owner();
// tidak error, instance BelongsTo
```

---

## Tahap 3 — `AcademyManagementService::create()`

**Tujuan**: satu toggle `create_account` di payload → academy **dan** akun Owner-nya sama-sama tersimpan, atau sama-sama batal.

Tambahkan `AccountService` sebagai dependency baru di constructor:

```php
protected RoleService $roleService;
protected PlayerTypeService $playerTypeService;
protected PlayerCategoryService $playerCategoryService;
protected AccountService $accountService;

public function __construct(
    RoleService $roleService,
    PlayerTypeService $playerTypeService,
    PlayerCategoryService $playerCategoryService,
    AccountService $accountService
) {
    $this->roleService = $roleService;
    $this->playerTypeService = $playerTypeService;
    $this->playerCategoryService = $playerCategoryService;
    $this->accountService = $accountService;
}
```

Jangan lupa tambahkan `use App\Services\AccountService;`? **Tidak perlu** — `AccountService` satu namespace (`App\Services`) dengan `AcademyManagementService`, tidak butuh `use` tambahan.

Ubah method `create()` yang sudah ada:

```php
public function create(array $data): Academy
{
    return DB::transaction(function () use ($data) {

        $data['code'] = strtoupper($data['code']);
        $data['slug'] = $this->generateSlug($data['name']);
        $data['status'] = $data['status'] ?? false;

        if (isset($data['logo'])) {
            $data['logo'] = $this->uploadLogo(
                $data['logo'],
                $data['code']
            );
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

> Perhatikan urutan: `createDefaultRoles()` **wajib** dipanggil sebelum blok `create_account` — role "Owner" untuk academy ini baru ada setelah baris itu jalan. Kalau dibalik, `AccountService::assignRole()` lempar exception "Role tidak ditemukan pada academy user."
>
> Nama akun Owner otomatis diambil dari `$academy->name` (bukan field terpisah) — pola yang sama dengan `PlayerService::create()` yang memakai `$player->name` untuk nama akun. Kalau Super Admin ingin nama akun beda dari nama academy, itu bisa diubah belakangan lewat `AcademyAccountController::update()` (Tahap 6).

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$svc = app(\App\Services\AcademyManagementService::class);

$academy = $svc->create([
    'name' => 'Academy Tes',
    'code' => 'TES',
    'phone' => '08123',
    'email' => 'academy@tes.com',
    'address' => 'Jl. Tes',
    'tagline' => 'Tagline',
    'status' => true,
    'subscription_type' => 'monthly',
    'subscription_fee' => 100000,
    'subscription_started_at' => now(),
    'subscription_ends_at' => now()->addMonth(),
    'create_account' => true,
    'owner_email' => 'owner@tes.com',
    'owner_password' => 'password123',
]);

$academy->fresh()->id_owner_user;
// harus tidak null

$academy->fresh()->owner->email;
// harus: "owner@tes.com"

$academy->fresh()->owner->hasRole('Owner');
// harus: true

// Ulangi tanpa create_account:
$academy2 = $svc->create([... /* data sama, code beda */, 'create_account' => false]);
$academy2->fresh()->id_owner_user;
// harus: null
```

---

## Tahap 4 — `AcademyFormRequest`

**Tujuan**: field toggle & akun Owner tervalidasi, **tanpa** menabrak field `email` academy yang sudah ada.

Tambahkan ke `rules()`, di bagian paling bawah (sebelum `description`, urutan tidak wajib tapi biar rapi taruh setelah `logo`):

```php
'create_account' => [
    'nullable',
    'boolean',
],

'owner_email' => [
    'required_if:create_account,1',
    'nullable',
    'email',
    'max:255',
    'unique:users,email',
],

'owner_password' => [
    'required_if:create_account,1',
    'nullable',
    'string',
    'min:8',
    'confirmed',
],
```

> `unique:users,email` di sini **tidak** butuh `->ignore()` — beda dengan `code` di rule yang sudah ada (yang perlu `ignore($this->academy?->id_academy, 'id_academy')` untuk kasus edit). Form ini cuma dipakai di `academies.create` (halaman edit academy **tidak** boleh membuat/mengganti akun Owner lewat sini — itu tugas `AcademyAccountController`), jadi tidak pernah ada academy lama yang perlu di-ignore.

Tambahkan ke `messages()`:

```php
'owner_email.required_if' => 'Email akun Owner wajib diisi.',
'owner_email.email' => 'Format email akun Owner tidak valid.',
'owner_email.unique' => 'Email sudah digunakan oleh akun lain.',

'owner_password.required_if' => 'Password akun Owner wajib diisi.',
'owner_password.min' => 'Password akun Owner minimal :min karakter.',
'owner_password.confirmed' => 'Konfirmasi password akun Owner tidak sesuai.',
```

**✅ Cek dulu**

- Submit form `academies.create` dengan toggle akun **mati** dan `owner_email`/`owner_password` kosong → **lolos** validasi (karena `required_if` cuma aktif kalau `create_account=1`).
- Submit dengan toggle **aktif** tapi `owner_email` kosong → **ditolak**, pesan "Email akun Owner wajib diisi."
- Submit dengan `owner_email` yang sudah dipakai user lain → ditolak, "Email sudah digunakan oleh akun lain."

---

## Tahap 5 — View: `create.blade.php` (toggle "Buat Akun Owner")

**Tujuan**: UI toggle identik pola dengan `resources/views/players/create.blade.php` (lihat blok "Buat Akun Player" di file itu), disesuaikan nama field.

Tambahkan blok ini di `resources/views/academies/create.blade.php`, sebelum tombol submit form:

```blade
<div class="form-group">

    <label class="form-label">
        Buat Akun Owner
    </label>

    <div x-data="{ createAccount: false }">

        <input type="hidden" name="create_account" :value="createAccount ? 1 : 0">

        <label class="flex cursor-pointer items-center">

            <input type="checkbox" class="sr-only" @change="createAccount=!createAccount">

            <div class="form-toggle" :class="createAccount && 'form-toggle-active'">
                <span class="form-toggle-dot" :class="createAccount && 'form-toggle-checked'">
                </span>
            </div>

            <span class="ml-3 text-sm text-gray-500" x-text="createAccount ? 'Aktif' : 'Nonaktif'">
            </span>

        </label>

        <div x-show="createAccount" x-transition class="mt-4 space-y-3">

            <div>
                <input type="email" name="owner_email" value="{{ old('owner_email') }}"
                    placeholder="Email akun Owner" class="form-input @error('owner_email') form-danger @enderror">

                @error('owner_email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <input type="password" name="owner_password" placeholder="Password"
                    class="form-input @error('owner_password') form-danger @enderror">

                @error('owner_password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <input type="password" name="owner_password_confirmation" placeholder="Konfirmasi Password"
                    class="form-input">
            </div>

        </div>

    </div>

</div>
```

> Ingatkan (lihat `2e`): toggle ini **cuma** mengontrol pembuatan akun. Kalau Super Admin ingin academy ini langsung bisa diakses Owner-nya, toggle **Status Academy** (field `status`, sudah ada dari `issue.md`) harus **ikut** diaktifkan — dua toggle terpisah, jangan diasumsikan salah satunya otomatis mengaktifkan yang lain.

**✅ Cek dulu**

- Buka `/academies/create` → toggle "Buat Akun Owner" nonaktif secara default, field email/password **tersembunyi**.
- Klik toggle → field email/password/konfirmasi **muncul** (transisi Alpine).
- Submit dengan toggle aktif + data lengkap → academy tersimpan, `php artisan tinker` → `Academy::latest()->first()->owner` bukan `null`.

---

## Tahap 6 — `AcademyAccountController` (baru)

**Tujuan**: sub-resource `academies/{academy}/account/*` — create/store untuk academy yang belum punya akun Owner, edit/update/password/status untuk yang sudah. Isi & struktur **disalin persis** dari `app/Http/Controllers/PlayerAccountController.php`, diganti `Player` → `Academy`, `player.id_user` → `academy.id_owner_user`, role hardcode `'Player'` → `'Owner'`.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academy\StoreAcademyAccountRequest;
use App\Http\Requests\Academy\UpdateAcademyAccountRequest;
use App\Models\Academy;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;

class AcademyAccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function create(Academy $academy)
    {
        if ($academy->id_owner_user) {
            return redirect()
                ->route('academies.show', $academy)
                ->with('error', 'Academy sudah memiliki akun Owner.');
        }

        return view('academies.account.create', [
            'title' => 'Buat Akun Owner',
            'academy' => $academy,
            'breadcrumb' => [
                ['label' => 'Manajemen Academy', 'url' => route('academies.index')],
                ['label' => $academy->name, 'url' => route('academies.show', $academy)],
                ['label' => 'Buat Akun Owner'],
            ],
        ]);
    }

    public function store(StoreAcademyAccountRequest $request, Academy $academy)
    {
        try {

            if ($academy->id_owner_user) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', 'Academy sudah memiliki akun Owner.');
            }

            DB::transaction(function () use ($request, $academy) {

                $user = $this->accountService->create([
                    'id_academy' => $academy->id_academy,
                    'name' => $academy->name,
                    'email' => $request->email,
                    'password' => $request->password,
                ], 'Owner');

                $academy->update([
                    'id_owner_user' => $user->id_user,
                ]);
            });

            return redirect()
                ->route('academies.show', $academy)
                ->with('success', 'Akun Owner berhasil dibuat.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal membuat akun Owner');
        }
    }

    public function edit(Academy $academy)
    {
        if (!$academy->owner) {
            return redirect()
                ->route('academies.show', $academy)
                ->with('error', 'Academy belum memiliki akun Owner.');
        }

        return view('academies.account.edit', [
            'title' => 'Edit Akun Owner',
            'academy' => $academy,
            'user' => $academy->owner,
            'breadcrumb' => [
                ['label' => 'Manajemen Academy', 'url' => route('academies.index')],
                ['label' => $academy->name, 'url' => route('academies.show', $academy)],
                ['label' => 'Edit Akun Owner'],
            ],
        ]);
    }

    public function update(UpdateAcademyAccountRequest $request, Academy $academy)
    {
        try {

            if (!$academy->owner) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', 'Academy belum memiliki akun Owner.');
            }

            $this->accountService->update(
                $academy->owner,
                $request->validated()
            );

            return redirect()
                ->route('academies.show', $academy)
                ->with('success', 'Akun Owner berhasil diperbarui.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal update akun Owner');
        }
    }

    public function password(Academy $academy)
    {
        try {

            if (!$academy->owner) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', 'Academy belum memiliki akun Owner.');
            }

            $newPassword = $this->accountService->generatePassword();

            $this->accountService->resetPassword(
                $academy->owner,
                $newPassword
            );

            return redirect()
                ->route('academies.show', $academy)
                ->with('success', 'Password berhasil direset. Password baru: ' . $newPassword);

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal reset password', 'academies.show', [$academy]);
        }
    }

    public function status(Academy $academy)
    {
        try {

            if (!$academy->owner) {
                return redirect()
                    ->route('academies.show', $academy)
                    ->with('error', 'Academy belum memiliki akun Owner.');
            }

            $status = !$academy->owner->status;

            $this->accountService->changeStatus(
                $academy->owner,
                $status
            );

            return redirect()
                ->route('academies.show', $academy)
                ->with(
                    'success',
                    $status
                        ? 'Akun Owner berhasil diaktifkan.'
                        : 'Akun Owner berhasil dinonaktifkan.'
                );

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal mengubah status akun Owner', 'academies.show', [$academy]);
        }
    }
}
```

> Kenapa cek `$academy->id_owner_user` di `create()`/`store()` tapi `!$academy->owner` (relasi, bukan kolom) di `edit()`/`update()`/`password()`/`status()`? Konsisten dengan pola `PlayerAccountController` (`$player->id_user` vs `!$player->user`) — cek kolom cukup untuk "sudah ada atau belum" (tidak perlu query tambahan), sedangkan aksi lain butuh **objek** `User`-nya, jadi pakai relasi (`$academy->owner` — Eloquent otomatis lazy-load sekali, di-reuse untuk sisa method).

**✅ Cek dulu** — tunda sampai Tahap 8 (route) selesai, tidak bisa dites lewat browser sebelum route ada. Cek sintaks dulu dengan:

```bash
php artisan route:list --name=academies.account
# harus error "route not defined" -- normal, lanjut ke Tahap 7 & 8 dulu
```

---

## Tahap 7 — Form Request baru: `StoreAcademyAccountRequest` & `UpdateAcademyAccountRequest`

**Tujuan**: validasi identik pola `StorePlayerAccountRequest`/`UpdatePlayerAccountRequest`.

`app/Http/Requests/Academy/StoreAcademyAccountRequest.php`:

```php
<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email akun Owner wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',

            'password.required' => 'Password akun Owner wajib diisi.',
            'password.min' => 'Password minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ];
    }
}
```

`app/Http/Requests/Academy/UpdateAcademyAccountRequest.php`:

```php
<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->academy->id_owner_user, 'id_user'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama akun wajib diisi.',
            'name.max' => 'Nama maksimal :max karakter.',

            'email.required' => 'Email akun wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan akun lain.',
        ];
    }
}
```

> `$this->academy` di `UpdateAcademyAccountRequest` tersedia otomatis lewat route model binding — nama variabel di closure/route **wajib** `{academy}` (bukan `{id}` atau nama lain), sama seperti `$this->player` di `UpdatePlayerAccountRequest` bergantung pada parameter route `{player}`.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\Http\Requests\Academy\StoreAcademyAccountRequest)->rules();
(new \App\Http\Requests\Academy\UpdateAcademyAccountRequest)->rules();
// keduanya tidak boleh error saat dipanggil
```

---

## Tahap 8 — Route

**Tujuan**: nested route `academies/{academy}/account/*`, digerbang `academy.update` (lihat [2d](#2d-kenapa-permission-academyupdate-bukan-user-atau-permission-baru)).

Tambahkan di `routes/web.php`, **tepat setelah** blok `Route::resource('academies', ...)` yang sudah ada (lihat `issue.md` Tahap 6):

```php
use App\Http\Controllers\AcademyAccountController;

// ...

/*
|--------------------------------------------------------------------------
| Academy Owner Account Management
|--------------------------------------------------------------------------
| Sub-resource dari academies.* -- SENGAJA pakai permission academy.update
| (bukan user.* seperti Player Account), karena Academy Management memang
| tidak pernah didelegasikan ke role manapun selain Super Admin. Lihat
| issue2.md Bagian 2d.
*/
Route::prefix('academies/{academy}/account')
    ->name('academies.account.')
    ->middleware('permission:academy.update')
    ->group(function () {

        Route::get('/create', [AcademyAccountController::class, 'create'])->name('create');
        Route::post('/', [AcademyAccountController::class, 'store'])->name('store');

        Route::get('/edit', [AcademyAccountController::class, 'edit'])->name('edit');
        Route::put('/', [AcademyAccountController::class, 'update'])->name('update');
        Route::patch('/status', [AcademyAccountController::class, 'status'])->name('status');
        Route::patch('/password', [AcademyAccountController::class, 'password'])->name('password');

    });
```

> Beda dengan Player Account yang membagi dua middleware group (`user.create` untuk create/store, `user.update` untuk sisanya), di sini **cukup satu** middleware `academy.update` untuk seluruh sub-resource — karena permission-nya memang cuma satu (`academy.update`), tidak ada pemisahan create-vs-update seperti Player.

**✅ Cek dulu**

```bash
php artisan route:list --name=academies.account
```

- Harus muncul 6 route: `create`, `store`, `edit`, `update`, `status`, `password`.
- Login sebagai role academy biasa (**bukan** Super Admin), coba akses `academies/{id}/account/create` langsung lewat URL → **403**.

---

## Tahap 9 — View: `show.blade.php` + `academies/account/create.blade.php` + `academies/account/edit.blade.php`

### 9a. `academies/show.blade.php`

Tambahkan `<x-account.dropdown>` di `card-actions` (lihat baris `card-actions` yang sudah ada di file ini, berisi tombol Kembali/Edit), setelah tombol Edit:

```blade
<x-account.dropdown :model="$academy" :user="$academy->owner" route-create="academies.account.create"
    route-edit="academies.account.edit" route-password="academies.account.password"
    route-status="academies.account.status" />
```

> Komponen ini **generik** — sudah dipakai persis sama di `players/show.blade.php`. Kalau `$academy->owner` null, komponen otomatis merender link "Buat Account" ke `academies.account.create`; kalau sudah ada, merender dropdown Edit/Reset Password/Aktifkan-Nonaktifkan.

### 9b. `app/Http/Controllers/AcademyController.php` — eager load `owner`

Ubah method `show()` supaya tidak N+1 (lihat `docs/query-performance.md`):

```php
public function show(Academy $academy)
{
    $academy->load('owner');

    return view('academies.show', [
        // ...tidak ada perubahan lain, isi array tetap sama
    ]);
}
```

### 9c. `resources/views/academies/account/create.blade.php` — file baru

Salin persis struktur `resources/views/players/account/create.blade.php`, ganti teks & route:

```blade
@extends('layouts.app', ['page' => 'academies'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">
                    Buat Akun Owner
                </h3>

                <p class="card-description">
                    Membuat akun login untuk <strong>{{ $academy->name }}</strong>.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('academies.show', $academy) }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('academies.account.store', $academy) }}" method="POST">
            @csrf

            <div class="p-5">

                <div class="form-group">
                    <label class="form-label">
                        Email <span class="text-error-500">*</span>
                    </label>

                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-input @error('email') form-danger @enderror">

                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Password <span class="text-error-500">*</span>
                    </label>

                    <input type="password" name="password" class="form-input @error('password') form-danger @enderror">

                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Konfirmasi Password <span class="text-error-500">*</span>
                    </label>

                    <input type="password" name="password_confirmation" class="form-input">
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-5">
                    <a href="{{ route('academies.show', $academy) }}" class="btn btn-secondary">
                        Batal
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Buat Akun
                    </button>
                </div>

            </div>

        </form>

    </div>

@endsection
```

### 9d. `resources/views/academies/account/edit.blade.php` — file baru

Salin persis struktur `resources/views/players/account/edit.blade.php`, ganti teks & route (`academies.account.update`, `academies.show`, `$academy->name` sebagai ganti `$player->name`).

**✅ Cek dulu**

- Buka Detail Academy (`academies.show`) untuk academy **tanpa** akun Owner → tombol/dropdown menampilkan "Buat Account", link ke `academies.account.create`.
- Isi form, submit → redirect ke `academies.show` dengan pesan sukses, dropdown sekarang berubah jadi menu Edit/Reset Password/Nonaktifkan.
- Klik "Reset Password" → password baru ditampilkan lewat flash message, cek `php artisan tinker` bisa login dengan password baru itu (`Hash::check()`).
- Klik "Nonaktifkan" → `$academy->owner->status` jadi `false`, coba login pakai akun itu → ditolak "Akun Anda sedang dinonaktifkan."

---

## Tahap 10 — Test

**Tujuan**: `tests/Feature/AcademyAccountTest.php` — pastikan seluruh alur (create-with-account, create-without-account, validasi, permission gate, edit/reset/status) berjalan sesuai desain.

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

class AcademyAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeSuperAdmin(): User
    {
        foreach (['academy.view', 'academy.create', 'academy.update', 'academy.delete'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

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

    protected function baseAcademyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Academy Tes',
            'code' => 'TES' . fake()->unique()->numberBetween(100, 999),
            'phone' => '081234567890',
            'email' => 'academy@tes.com',
            'address' => 'Jl. Tes',
            'tagline' => 'Tagline Tes',
            'status' => true,
            'subscription_type' => 'monthly',
            'subscription_fee' => 100000,
            'subscription_started_at' => now()->toDateString(),
            'subscription_ends_at' => now()->addMonth()->toDateString(),
        ], $overrides);
    }

    public function test_super_admin_bisa_buat_academy_sekaligus_akun_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $response->assertRedirect(route('academies.index'));

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertNotNull($academy->id_owner_user);
        $this->assertSame('owner@tes.com', $academy->owner->email);
        $this->assertTrue($academy->owner->hasRole('Owner'));
        $this->assertTrue($academy->owner->status);
    }

    public function test_academy_tanpa_toggle_create_account_tidak_membuat_user(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload(['create_account' => 0]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertNull($academy->id_owner_user);
    }

    public function test_toggle_aktif_tapi_owner_email_kosong_ditolak(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $response->assertSessionHasErrors('owner_email');
        $this->assertDatabaseMissing('academies', ['code' => $payload['code']]);
    }

    public function test_owner_email_tidak_bentrok_dengan_email_kontak_academy(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'email' => 'kontak@academytes.com',
            'create_account' => 1,
            'owner_email' => 'owner@academytes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertSame('kontak@academytes.com', $academy->email);
        $this->assertSame('owner@academytes.com', $academy->owner->email);
    }

    public function test_role_academy_biasa_ditolak_403_akses_route_account(): void
    {
        $academy = Academy::factory()->create();

        Permission::firstOrCreate(['name' => 'player.view', 'guard_name' => 'web']);
        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Staff', 'guard_name' => 'web']);
        $role->syncPermissions(['player.view']);

        $staff = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $staff->assignRole($role);

        $response = $this->actingAs($staff)->get(route('academies.account.create', $academy));

        $response->assertForbidden();
    }

    public function test_super_admin_bisa_reset_password_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password-lama',
            'owner_password_confirmation' => 'password-lama',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();
        $oldHash = $academy->owner->password;

        $this->actingAs($superAdmin)->patch(route('academies.account.password', $academy));

        $this->assertNotSame($oldHash, $academy->owner->fresh()->password);
    }

    public function test_super_admin_bisa_nonaktifkan_akun_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertTrue($academy->owner->status);

        $this->actingAs($superAdmin)->patch(route('academies.account.status', $academy));

        $this->assertFalse($academy->owner->fresh()->status);
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=AcademyAccountTest
```

Ketujuh test harus **pass**. Kalau `test_owner_email_tidak_bentrok_dengan_email_kontak_academy` gagal dengan salah satu email ketimpa yang lain, cek lagi Tahap 4–5 — kemungkinan field masih dinamai `email` polos, bukan `owner_email`.

---

## Tahap 11 — Dokumentasi

**Tujuan**: `docs/permission-reference.md` mencatat sub-module baru ini, supaya keputusan "kenapa `academy.update`, bukan permission baru" tidak hilang saat module berikutnya dikerjakan.

Tambahkan sub-section baru **tepat setelah** section "Module: Academy Management" (dan sebelum "Module: Academy Profile"):

```markdown
### Sub-module: Academy Account (login Owner)

Nested di `academies/{academy}/account/*`. Sama seperti *Sub-module: Player Account*, ini **membuat/mengelola record `User`** (login Owner), bukan data `Academy` itu sendiri — tapi **beda** dari Player Account soal permission: di sini **sengaja tetap** `academy.update`, bukan permission `user.*` atau permission baru.

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `academy.update` | Buat akun Owner (kalau belum ada), edit akun, reset password, aktif/nonaktifkan akun Owner | `academies.account.create`, `academies.account.store`, `academies.account.edit`, `academies.account.update`, `academies.account.status`, `academies.account.password` (route middleware) + `<x-account.dropdown>` di `academies.show` |

Kenapa **tidak** dipisah seperti Player Account (`user.create`/`user.update` terpisah dari `player.*`): pemisahan itu berguna di Player karena role macam Coach/Staff **memang** didelegasikan `player.update` tapi belum tentu boleh membuat akun login player. Di Academy, tidak ada role manapun (termasuk Owner) yang pernah punya `academy.*` sama sekali (lihat *Module: Academy Management* → `4.4` di `issue.md`) — jadi menambah permission terpisah untuk sub-resource-nya tidak memberi pemisahan hak akses yang nyata, cuma menambah satu baris permission yang tidak pernah dipakai membedakan siapa boleh apa.

Akun Owner yang dibuat lewat sub-module ini otomatis diberi role **Owner** (hardcode di `AcademyManagementService::create()` dan `AcademyAccountController::store()`) — role lain **tidak bisa** dipilih lewat jalur ini. Kalau Owner butuh akun tambahan dengan role lain (Coach, Staff, dst), itu tetap lewat jalur biasa (Role Management + pembuatan akun oleh Owner sendiri di academy-nya, di luar scope brief ini).
```

Perbarui juga baris ringkasan status implementasi di bagian bawah dokumen (cari kalimat "Role, Permission, Player Management, ... Academy Management, dan Academy Profile sudah digerbang penuh") supaya menyebut Academy Account juga.

**✅ Cek dulu**: baca ulang section barunya sekali lagi, pastikan tidak menyiratkan bahwa Academy Account punya permission sendiri yang berbeda dari Academy Management — intinya justru sebaliknya: sengaja dibuat **memakai** permission yang sama.

---

## 4. Alasan Teknis

### 4.1. Kenapa field akun dinamai `owner_email` / `owner_password`, bukan `email` / `password`

Lihat [2b](#2b-kenapa-field-akun-dinamai-owner_email--owner_password-bukan-email--password). Ringkas: `AcademyFormRequest` sudah punya `email` untuk kontak academy sejak `issue.md`. Reuse nama akan membuat satu `<input>` menimpa makna yang lain di form yang sama — beda dengan Player yang form Tambah Player-nya memang tidak punya field `email` bawaan apapun, jadi aman pakai nama polos di sana.

### 4.2. Kenapa permission Academy Account pakai `academy.update`, bukan `user.*` atau permission baru

Lihat [2d](#2d-kenapa-permission-academyupdate-bukan-user-atau-permission-baru). Ringkas: `user.*` sudah bermakna spesifik di Player Account (Owner boleh kelola akun player-nya sendiri) dan **sudah** default dimiliki role Owner — dipakai ulang di sini berarti Owner akan lolos middleware ke rute pembuatan/pengubahan akun Owner academy manapun. Permission baru juga tidak menambah manfaat karena Academy Management memang selalu Super-Admin-exclusive, tidak pernah ada role kedua yang perlu dibedakan aksesnya.

### 4.3. Kenapa membuat akun Owner tidak otomatis berarti bisa login

`LoginRequest::authenticate()` (lihat `app/Http/Requests/Auth/LoginRequest.php` baris ~100 dan ~152) mengecek **dua** kondisi berurutan sebelum sesi login diizinkan untuk user non-Super-Admin: `$user->status` (akun) dan `$academy->status` (academy tempat user itu berada). `AccountService::create()` selalu men-set `status => true` untuk akun baru, jadi kondisi pertama otomatis lolos. Tapi `AcademyManagementService::create()` men-set `status = $data['status'] ?? false` — **default `false`** kalau Super Admin tidak eksplisit centang toggle Aktif di form. Kalau brief ini diam-diam memaksa `status` academy jadi `true` setiap kali `create_account` dicentang, itu mengambil keputusan produk (kapan academy dianggap "resmi aktif") atas nama Super Admin tanpa diminta — dua toggle ini sengaja dibiarkan independen, Super Admin yang memutuskan keduanya secara eksplisit setiap kali menambah academy baru.

### 4.4. Kenapa `id_owner_user` di `academies`, bukan dicari lewat role

Lihat [2a](#2a-kenapa-perlu-kolom-baru-id_owner_user-di-academies). Ringkas: role Owner bisa lebih dari satu per academy (Super Admin bebas menambah lewat Role Management), jadi "akun Owner utama yang terhubung ke academy ini" butuh penanda eksplisit, bukan disimpulkan dari role. Pola ini identik dengan `players.id_user`.

---

## 5. Development Checklist

Sebelum brief ini dinyatakan selesai, cocokkan dengan checklist `docs/module-standard.md`:

- [ ] Migration: `id_owner_user` nullable, FK ke `users.id_user`, `nullOnDelete()`.
- [ ] Model: `Academy::owner()` (`belongsTo`), fillable bertambah `id_owner_user`, **tidak ada** logic baru di Model.
- [ ] Service: `AcademyManagementService::create()` membuat akun Owner dalam **satu** `DB::transaction()` yang sama dengan `Academy::create()`, urutan `createDefaultRoles()` → akun Owner.
- [ ] Form Request: `create_account`, `owner_email`, `owner_password` — nama field **tidak** bentrok dengan `email` kontak academy.
- [ ] View create: toggle "Buat Akun Owner" dengan field tersembunyi/tampil via Alpine, mengikuti pola `players/create.blade.php`.
- [ ] `AcademyAccountController`: 6 method (create/store/edit/update/status/password), semuanya cek keberadaan akun sebelum aksi (redirect + flash error kalau belum/sudah ada).
- [ ] Form Request baru: `StoreAcademyAccountRequest`, `UpdateAcademyAccountRequest` — validasi identik pola Player Account.
- [ ] Route: nested `academies/{academy}/account/*`, **satu** middleware `permission:academy.update` untuk seluruh 6 route.
- [ ] View `show.blade.php`: `<x-account.dropdown>` terpasang, `AcademyController::show()` eager-load `owner`.
- [ ] View baru: `academies/account/create.blade.php`, `academies/account/edit.blade.php`.
- [ ] Test: 7 skenario `AcademyAccountTest` pass.
- [ ] `docs/permission-reference.md`: sub-section "Sub-module: Academy Account" ditambahkan, menjelaskan kenapa **tidak** pakai permission terpisah (beda dari Player Account).
- [ ] Manual: login sebagai role academy biasa, akses `academies/{id}/account/create` langsung lewat URL → 403.
- [ ] Manual: buat academy dengan toggle akun **aktif** tapi toggle status academy **nonaktif** → coba login pakai akun Owner baru → ditolak "Academy sedang tidak aktif." (bukti dua toggle memang independen).

## Summary

Brief ini menambahkan kemampuan bagi Super Admin untuk langsung membuatkan akun login Owner saat menambah academy baru — meniru persis pola `create_account` yang sudah ada di form Tambah Player, plus sub-resource `academies/{academy}/account/*` untuk membuat/mengelola akun itu belakangan (mirip `players/{player}/account/*`). Relasi academy-ke-akun-Owner disimpan lewat kolom baru `id_owner_user` (FK eksplisit, bukan dicari lewat role, karena satu academy bisa punya lebih dari satu user berrole Owner). Dua gotcha utama yang membedakan brief ini dari sekadar "copy-paste Player Account": nama field form (`owner_email`/`owner_password`, bukan `email`/`password` polos, karena `Academy` sudah punya field kontak `email` sendiri), dan pilihan permission (`academy.update`, bukan `user.*`, karena Academy Management tidak pernah didelegasikan ke role manapun sehingga pemisahan permission ala Player Account tidak memberi manfaat nyata di sini). Membuat akun Owner **tidak** otomatis membuatnya bisa login — `LoginRequest` tetap mensyaratkan academy itu sendiri berstatus aktif, dan brief ini sengaja tidak memaksa status itu ikut berubah hanya karena akun dibuat.
