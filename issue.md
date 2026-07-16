# Brief: Module Player Type (Reguler / Beasiswa / Trial)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu. Terutama `docs/multi-tenancy.md`, `docs/permission-reference.md`, `docs/development-guide.md`, dan `docs/frontend-standard.md`.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 12 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> Kalau cuma mau eksekusi, cukup baca Bagian 0–3 lalu langsung ke Tahap 1. Bagian 4 (alasan teknis) boleh dibaca belakangan, **tapi aturannya tetap tidak boleh dilanggar**.

---

## 0. Aturan Emas

Sepuluh larangan ini bukan preferensi gaya. Masing-masing sudah diverifikasi akan bikin bug nyata. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| `cascadeOnDelete` pada FK `players.id_player_type` | Hapus 1 type → **seluruh player** type itu ikut terhapus | [4.1](#41-cascadeondelete-pada-id_player_type--hapus-type-hapus-semua-playernya) |
| `Rule::exists('player_types', ...)` **tanpa** `where('id_academy', ...)` | Owner A bisa pasang type milik Academy B lewat POST | [4.2](#42-ruleexists-tidak-kena-academyscope) |
| Logika billing pakai `where('name', 'Reguler')` | Nama type bebas & beda-beda tiap academy → pasti pecah | [4.4](#44-kenapa-is_billable-bukan-cocok-cocokan-nama) |
| Membuat `PlayerType` dengan `id_academy` = `NULL` | **Tidak ada "type system"**. Beda dengan Role. | [2](#2-cara-kerja-solusi) |
| `unique('name')` global di tabel `player_types` | Academy B tidak bisa punya "Reguler" kalau A sudah punya | [Tahap 1](#tahap-1--migration) |
| Menghapus type yang masih dipakai player | Data player jadi kehilangan type diam-diam | [Tahap 4](#tahap-4--playertypeservice) |
| Merender field `id_academy` untuk user academy | Wajib `prohibited` — ikut pola `RoleFormRequest` | [Tahap 7](#tahap-7--playertypeformrequest) |
| Bikin `RolePolicy`-style Policy untuk PlayerType | **Tidak perlu.** Global scope sudah bikin 404. | [4.3](#43-kenapa-playertype-boleh-pakai-global-scope-padahal-role-tidak) |
| `$player->playerType->name` di Blade tanpa eager load | N+1 di halaman list | `docs/query-performance.md` |
| Membuat folder `lang/` | Pesan Indonesia di-hardcode | `docs/coding-standard.md` |

---

## 1. Tujuan

Setiap academy punya **jenis pemain** sendiri: Reguler (bayar SPP penuh), Beasiswa (dibebaskan), Trial (masa percobaan, belum ditagih).

Yang kita tuju:

```text
Academy A                         Academy B
├── Reguler   (ditagih)           ├── Reguler       (ditagih)
├── Beasiswa  (tidak ditagih)     ├── Beasiswa      (tidak ditagih)
└── Trial     (tidak ditagih)     ├── Trial         (tidak ditagih)
                                  └── Siswa Prestasi (tidak ditagih)  ← bebas bikin sendiri
```

**Aturan inti**: Setiap Player **wajib** punya satu Player Type. Setiap Player Type **wajib** milik satu Academy.

**Kegunaan utamanya** ada di module Payment nanti: menagih SPP **hanya** untuk player yang type-nya `is_billable = true`. Module ini adalah fondasinya.

---

## 2. Cara Kerja Solusi

Baca sampai paham. Kalau bagian ini tidak nyantol, sisa brief akan terasa acak.

Tabel `player_types`:

| id_player_type | id_academy | name | is_billable | status |
|----------------|-----------|------|-------------|--------|
| `uuid-t1` | `uuid-academy-A` | Reguler | `true` | `true` |
| `uuid-t2` | `uuid-academy-A` | Beasiswa | `false` | `true` |
| `uuid-t3` | `uuid-academy-B` | Reguler | `true` | `true` |

Tabel `players`:

| id_player | id_academy | name | id_player_type |
|-----------|-----------|------|----------------|
| `uuid-p1` | `uuid-academy-A` | Andi | `uuid-t1` |
| `uuid-p2` | `uuid-academy-A` | Budi | `uuid-t2` |

**Tiga hal penting yang harus nyantol:**

### 2a. Ini module tenant BIASA — beda dengan Role

`PlayerType` **memakai** `BelongsToAcademy` + `AcademyScope` seperti model tenant normal (`Player`). Role tidak boleh, PlayerType boleh. Kenapa bedanya penting → [4.3](#43-kenapa-playertype-boleh-pakai-global-scope-padahal-role-tidak).

Konsekuensinya, banyak hal jadi **gratis**:

| Kebutuhan | Ditangani oleh |
|-----------|----------------|
| Owner A cuma lihat type Academy A | `AcademyScope` (otomatis) |
| Super Admin lihat semua type | `AcademyScope` (otomatis, `isSuperAdmin()`) |
| Owner A buka `/player-types/{typeB}/edit` → **404** | Route model binding + `AcademyScope` (otomatis) |
| `id_academy` terisi otomatis saat Owner create | `BelongsToAcademy` (otomatis) |
| Super Admin create untuk academy pilihan | `BelongsToAcademy` (sudah didukung, lihat `docs/multi-tenancy.md`) |

**Jadi jangan bikin Policy.** Yang perlu ditulis manual cuma: unique per academy, guard delete, dan `is_billable`.

### 2b. Tidak ada "Type System"

Role punya `id_academy = NULL` untuk "Role System" (Super Admin). **PlayerType tidak punya konsep itu.** Setiap type wajib milik satu academy — tidak ada type global.

| Pelaku | Sumber `id_academy` |
|--------|---------------------|
| Owner / user academy | Otomatis dari academy miliknya. Field academy **tidak dirender** di form. |
| Super Admin | **Wajib** pilih dari dropdown academy. Tidak ada opsi "kosong". |

### 2c. `is_billable` adalah kontrak untuk module Payment

Nama type bebas dan beda-beda tiap academy ("Reguler", "Regular", "Siswa Tetap"). Jadi module Payment nanti **tidak boleh** mencocokkan nama. Kontraknya cuma satu:

```php
// Module Payment nanti (BUKAN bagian dari brief ini):
Player::whereHas('playerType', fn ($q) => $q->where('is_billable', true))->get();
```

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_create_player_types_table.php` | 🆕 Baru | 1 |
| `database/migrations/…_add_id_player_type_to_players_table.php` | 🆕 Baru | 1 |
| `app/Models/PlayerType.php` | 🆕 Baru | 2 |
| `app/Models/Player.php` | ✏️ Tambah fillable + relasi | 2 |
| `config/faos.php` | ✏️ Tambah `player_type_templates` | 3 |
| `app/Services/PlayerTypeService.php` | 🆕 Baru | 4 |
| `app/Services/AcademyManagementService.php` | ✏️ Ubah constructor + `create()` | 5 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah 4 permission | 6 |
| `config/faos.php` | ✏️ Tambah `player_type.*` ke `role_templates` Owner | 6 |
| `app/Support/PermissionPresenter.php` | ✏️ Tambah `moduleLabel()` | 6 |
| `app/Http/Requests/PlayerType/PlayerTypeFormRequest.php` | 🆕 Baru | 7 |
| `app/Http/Controllers/PlayerTypeController.php` | 🆕 Baru | 8 |
| `routes/web.php` | ✏️ Tambah resource `player-types` | 8 |
| `resources/views/player-types/{index,create,edit}.blade.php` | 🆕 Baru | 9 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Tambah menu | 9 |
| `app/Services/PlayerService.php` | ✏️ Simpan `id_player_type` | 10 |
| `app/Http/Requests/Players/{Store,Update}PlayerRequest.php` | ✏️ Validasi `id_player_type` | 10 |
| `app/Http/Controllers/PlayerController.php` | ✏️ Kirim `$playerTypes` ke view | 10 |
| `resources/views/players/{index,create,edit,show}.blade.php` | ✏️ Ubah | 10 |
| `database/factories/PlayerTypeFactory.php` | 🆕 Baru | 11 |
| `tests/Feature/PlayerTypeTest.php` | 🆕 Baru | 11 |
| `docs/permission-reference.md` | ✏️ Ubah | 12 |
| **`app/Traits/BelongsToAcademy.php`** | 🚫 **Jangan sentuh** | — |
| **`app/Scopes/AcademyScope.php`** | 🚫 **Jangan sentuh** | — |
| **`app/Models/Role.php`, `app/Policies/*`** | 🚫 **Jangan sentuh** | — |
| **`app/Http/Controllers/PlayerAccountController.php`** | 🚫 **Jangan sentuh** | — |
| **`database/migrations/…_create_players_table.php`** (yang lama) | 🚫 **Jangan sentuh** | — |

> `BelongsToAcademy` sudah mendukung Super Admin mengisi `id_academy` eksplisit (lihat `docs/multi-tenancy.md` → *Pengecualian: Super Admin membuat data untuk academy pilihan*). Kalau kamu merasa perlu mengubah trait itu, berarti ada yang salah di Tahap 2 atau 4.

---

## Tahap 1 — Migration

**Tujuan**: tabel `player_types` ada, dan `players` punya kolom `id_player_type`.

```bash
php artisan make:migration create_player_types_table
php artisan make:migration add_id_player_type_to_players_table
```

### 1a. `…_create_player_types_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_types', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_player_type')->primary();

            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            | TIDAK nullable. Berbeda dengan roles.id_academy, tidak ada konsep
            | "type system" -- setiap type wajib milik satu academy.
            */
            $table->uuid('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Type Information
            |--------------------------------------------------------------------------
            */
            $table->string('name', 100);

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Billing
            |--------------------------------------------------------------------------
            | Penanda apakah player dengan type ini ditagih iuran/SPP.
            | Module Payment WAJIB memfilter lewat kolom ini, bukan lewat nama type.
            */
            $table->boolean('is_billable')->default(true);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | Type nonaktif tidak muncul di dropdown Player baru, tapi player lama
            | yang sudah memakainya tetap utuh. Ini jalan keluar untuk type yang
            | sudah tidak dipakai lagi tapi tidak bisa dihapus karena masih
            | dipegang player lama.
            */
            $table->boolean('status')->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */
            $table->index('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Unique
            |--------------------------------------------------------------------------
            | Dua academy BOLEH punya type bernama sama. Satu academy TIDAK BOLEH
            | punya dua type dengan nama sama. Sama persis dengan pola
            | roles_academy_name_guard_unique.
            */
            $table->unique(['id_academy', 'name'], 'player_types_academy_name_unique');

            /*
            |--------------------------------------------------------------------------
            | Foreign Key
            |--------------------------------------------------------------------------
            */
            $table->foreign('id_academy')
                ->references('id_academy')
                ->on('academies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_types');
    }
};
```

### 1b. `…_add_id_player_type_to_players_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {

            // Nullable di level DATABASE, tapi WAJIB di Form Request.
            // Alasannya: player yang sudah ada sebelum module ini belum punya
            // type, dan kita tidak mau migration gagal / menebak-nebak type
            // mereka. Lihat Bagian 4.5.
            $table->uuid('id_player_type')
                ->nullable()
                ->after('id_user');

            $table->index('id_player_type');

            // nullOnDelete, BUKAN cascadeOnDelete.
            // cascadeOnDelete akan menghapus SELURUH PLAYER saat sebuah type
            // dihapus. Lihat Bagian 4.1.
            $table->foreign('id_player_type')
                ->references('id_player_type')
                ->on('player_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['id_player_type']);
            $table->dropIndex(['id_player_type']);
            $table->dropColumn('id_player_type');
        });
    }
};
```

> Urutan migration penting: `player_types` harus ada dulu sebelum `players` bisa membuat FK ke situ. Nama file migration sudah otomatis urut karena timestamp — pastikan kamu menjalankan `make:migration` untuk `create_player_types_table` **lebih dulu**.

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table player_types
php artisan db:table players
```

- `player_types` harus punya index `player_types_academy_name_unique`.
- `players` harus punya kolom `id_player_type` dan FK-nya **`on delete set null`** — kalau tertulis `cascade`, ULANGI. Itu bug paling berbahaya di brief ini.

---

## Tahap 2 — Model

**Tujuan**: `PlayerType` jadi model tenant normal, dan `Player` tahu relasinya.

### 2a. `app/Models/PlayerType.php` — file baru

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PlayerType extends FaosModel, jadi otomatis dapat:
 * - UUID primary key
 * - BelongsToAcademy (AcademyScope + isi id_academy saat creating)
 *
 * Berbeda dengan App\Models\Role, model ini MEMANG BOLEH pakai global scope.
 * Lihat Bagian 4.3 di issue.md.
 */
class PlayerType extends FaosModel
{
    use HasFactory;

    protected $table = 'player_types';
    protected $primaryKey = 'id_player_type';

    protected $fillable = [
        // id_academy wajib fillable supaya Super Admin bisa memilih academy.
        // Tanpa ini, nilainya didiamkan sebelum sempat dibaca BelongsToAcademy.
        'id_academy',
        'name',
        'description',
        'is_billable',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_billable' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'id_player_type', 'id_player_type');
    }
}
```

### 2b. `app/Models/Player.php` — ubah

Tambah `id_player_type` ke `$fillable` (**tepat di bawah `'id_user'`**):

```php
protected $fillable = [

    'id_academy',
    'id_user',
    'id_player_type',
    'player_code',
    // ... sisanya tetap
];
```

Tambah relasi (di bawah relasi `user()` yang sudah ada):

```php
/*
|--------------------------------------------------------------------------
| Relationship Player Type
|--------------------------------------------------------------------------
*/
public function playerType()
{
    return $this->belongsTo(
        PlayerType::class,
        'id_player_type',
        'id_player_type'
    );
}
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\Models\PlayerType)->getKeyName();
// harus: "id_player_type"

(new \App\Models\PlayerType)->getFillable();
// harus memuat "id_academy"
```

---

## Tahap 3 — Template type di `config/faos.php`

**Tujuan**: academy baru otomatis dapat type default.

Tambahkan blok ini ke `config/faos.php`, **tepat setelah** blok `'role_templates'` dan sebelum `'upload'`:

```php
/*
|--------------------------------------------------------------------------
| Player Type Template
|--------------------------------------------------------------------------
| Player Type default yang otomatis dibuat untuk setiap academy baru.
|
| is_billable = true  -> player dengan type ini ditagih iuran/SPP.
| is_billable = false -> tidak ditagih.
|
| Academy bebas menambah/mengubah type sendiri lewat menu Player Type.
| Daftar di sini hanya titik awal saat academy dibuat.
*/

'player_type_templates' => [

    'Reguler' => [
        'description' => 'Pemain reguler yang membayar iuran/SPP penuh.',
        'is_billable' => true,
    ],

    'Beasiswa' => [
        'description' => 'Pemain penerima beasiswa, dibebaskan dari iuran/SPP.',
        'is_billable' => false,
    ],

    'Trial' => [
        'description' => 'Pemain masa percobaan, belum dikenakan iuran/SPP.',
        'is_billable' => false,
    ],

],
```

**✅ Cek dulu**

```bash
php artisan config:clear
php artisan tinker
```

```php
array_keys(config('faos.player_type_templates'));
// harus: ["Reguler","Beasiswa","Trial"]

config('faos.player_type_templates.Reguler.is_billable');
// harus: true
```

---

## Tahap 4 — `PlayerTypeService`

**Tujuan**: business logic type, termasuk guard hapus dan pembuatan type default.

`app/Services/PlayerTypeService.php` — **file baru**, salin utuh:

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\PlayerType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlayerTypeService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Daftar type untuk halaman index.
     *
     * Tidak perlu filter id_academy manual: PlayerType memakai
     * BelongsToAcademy -> AcademyScope, jadi user academy otomatis hanya
     * melihat type miliknya, dan Super Admin melihat seluruhnya.
     */
    public function paginate(?int $perPage = null)
    {
        return PlayerType::with('academy')
            ->withCount('players')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar type untuk dropdown di form Player.
     *
     * $academyId null  -> seluruh academy (dipakai Super Admin di form CREATE
     *                     Player, karena academy-nya baru dipilih di form yang
     *                     sama lalu difilter di sisi Alpine).
     * $includeId       -> dipakai form EDIT Player: type yang sedang dipakai
     *                     player tetap ikut walau sudah dinonaktifkan, supaya
     *                     nilainya tidak hilang saat disimpan.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return PlayerType::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_player_type', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Tentukan id_academy untuk type baru.
     *
     * User academy : otomatis dari academy miliknya, input form DIABAIKAN.
     * Super Admin  : dari pilihan academy di form (wajib, divalidasi
     *                PlayerTypeFormRequest). Tidak ada opsi "type system".
     */
    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): PlayerType
    {
        return DB::transaction(function () use ($data) {

            return PlayerType::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_billable' => $data['is_billable'] ?? false,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(PlayerType $playerType, array $data): PlayerType
    {
        return DB::transaction(function () use ($playerType, $data) {

            // id_academy sengaja TIDAK ikut diubah.
            // Type tidak dapat berpindah academy -- player yang sudah memakainya
            // akan ikut "pindah" secara tidak sengaja.
            $playerType->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_billable' => $data['is_billable'] ?? false,
                'status' => $data['status'] ?? true,
            ]);

            return $playerType;
        });
    }

    public function delete(PlayerType $playerType): bool
    {
        return DB::transaction(function () use ($playerType) {

            // FK players.id_player_type memang nullOnDelete, tapi itu cuma
            // jaring pengaman terakhir. Kalau type dihapus begitu saja,
            // player-nya diam-diam kehilangan type. Blokir di sini.
            // Type yang sudah tidak dipakai lagi: nonaktifkan (status = false),
            // jangan dihapus.
            if ($playerType->players()->exists()) {
                throw new \Exception('Type masih digunakan oleh player, tidak dapat dihapus. Nonaktifkan type ini kalau sudah tidak dipakai.');
            }

            return $playerType->delete();
        });
    }

    /**
     * Buat type default untuk academy baru dari config('faos.player_type_templates').
     *
     * Academy di-pass eksplisit (bukan dari academy aktif) karena yang membuat
     * academy adalah Super Admin, yang id_academy-nya null.
     */
    public function createDefaultPlayerTypes(Academy $academy): void
    {
        foreach (config('faos.player_type_templates') as $name => $attributes) {

            PlayerType::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'is_billable' => $attributes['is_billable'] ?? false,
                'status' => true,
            ]);
        }
    }
}
```

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Services\PlayerTypeService::class)` tidak error.

---

## Tahap 5 — `AcademyManagementService`

**Tujuan**: bikin academy → type default langsung jadi.

Di `app/Services/AcademyManagementService.php`:

**1.** Ubah constructor yang sudah ada (jangan hapus `RoleService`-nya):

```php
protected RoleService $roleService;
protected PlayerTypeService $playerTypeService;

public function __construct(RoleService $roleService, PlayerTypeService $playerTypeService)
{
    $this->roleService = $roleService;
    $this->playerTypeService = $playerTypeService;
}
```

**2.** Di method `create()`, tambahkan **satu baris** setelah `createDefaultRoles()`:

```php
$academy = Academy::create($data);

$this->roleService->createDefaultRoles($academy);
$this->playerTypeService->createDefaultPlayerTypes($academy);

return $academy;
```

Method `create()` sudah dibungkus `DB::transaction()`, jadi kalau pembuatan type gagal, academy + role-nya ikut batal. Itu memang yang kita mau.

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Services\AcademyManagementService::class)` tidak error.

---

## Tahap 6 — Permission

**Tujuan**: permission `player_type.*` ada, masuk role Owner, dan tampil rapi di UI.

### 6a. `database/seeders/RolePermissionSeeder.php`

Di array `$permissions`, tambahkan blok ini **tepat setelah blok `// Player`**:

```php
            // Player Type
            'player_type.view',
            'player_type.create',
            'player_type.update',
            'player_type.delete',
```

Bagian lain seeder **tidak berubah**.

### 6b. `config/faos.php` → `role_templates`

Di template **`Owner`** saja, tambahkan baris ini setelah baris `player.*`:

```php
'player_type.view', 'player_type.create', 'player_type.update', 'player_type.delete',
```

> Role lain (Coach, Staff, Finance, Player, Parent) **sengaja tidak** diberi `player_type.*`. Mengatur jenis pemain + tagihan adalah keputusan level Owner. Kalau sebuah academy mau memberi Staff akses, Owner tinggal centang sendiri lewat menu Role Management — itu memang gunanya refactor Role Academy Based.
>
> **Penting**: Staff/Coach yang punya `player.create` **tetap bisa** memilih type saat menambah player, walau tidak punya `player_type.view`. Dropdown di form Player diisi oleh Service, bukan digerbang permission `player_type.view`. Permission itu hanya menggerbang halaman `/player-types`.

### 6c. `app/Support/PermissionPresenter.php`

**Masalahnya**: sekarang `label('player_type.view')` menghasilkan **"Lihat Player_type"** dan `description()` menghasilkan **"...melihat data player_type."** — dua-duanya tampil ke user di halaman Permission.

Tambah import di paling atas:

```php
use Illuminate\Support\Str;
```

Tambah method baru `moduleLabel()`:

```php
    /**
     * Label module untuk tampilan.
     *
     * Module yang belum terdaftar jatuh ke Str::headline() supaya tetap
     * terbaca ("player_type" -> "Player Type"), bukan "Player_type".
     */
    public static function moduleLabel(string $permission): string
    {
        $modules = [
            'academy' => 'Academy',
            'role' => 'Role',
            'permission' => 'Permission',
            'player' => 'Player',
            'player_type' => 'Player Type',
            'coach' => 'Coach',
            'team' => 'Team',
            'training' => 'Training',
            'attendance' => 'Attendance',
            'evaluation' => 'Evaluation',
            'payment' => 'Payment',
            'report' => 'Report',
            'user' => 'User',
        ];

        return $modules[self::module($permission)]
            ?? Str::headline(self::module($permission));
    }
```

**Ganti** method `label()` — array `$modules` yang lama di dalamnya **dihapus** (sudah pindah ke `moduleLabel()`):

```php
    public static function label(string $permission): string
    {
        $actions = [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'export' => 'Export',
            'report' => 'Laporan',
        ];

        return ($actions[self::action($permission)] ?? ucfirst(self::action($permission)))
            . ' '
            . self::moduleLabel($permission);
    }
```

**Ganti** isi `description()` — `self::module(...)` diganti `self::moduleLabel(...)`:

```php
    public static function description(string $permission): string
    {
        return match (self::action($permission)) {
            'view' => 'Mengizinkan pengguna melihat data ' . strtolower(self::moduleLabel($permission)) . '.',
            'create' => 'Mengizinkan pengguna menambahkan data ' . strtolower(self::moduleLabel($permission)) . '.',
            'update' => 'Mengizinkan pengguna mengubah data ' . strtolower(self::moduleLabel($permission)) . '.',
            'delete' => 'Mengizinkan pengguna menghapus data ' . strtolower(self::moduleLabel($permission)) . '.',
            'export' => 'Mengizinkan pengguna mengekspor data ' . strtolower(self::moduleLabel($permission)) . '.',
            'report' => 'Mengizinkan pengguna melihat laporan ' . strtolower(self::moduleLabel($permission)) . '.',
            default => $permission,
        };
    }
```

> Perubahan ini **tidak mengubah tampilan module lama** — untuk module yang sudah ada di map (`player`, `role`, dst) hasilnya sama persis seperti sebelumnya. Blok `✅ Cek dulu` di bawah membuktikannya, jangan dilewat.

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
php artisan tinker
```

```php
// Yang lama TIDAK BOLEH berubah:
\App\Support\PermissionPresenter::label('player.view');        // "Lihat Player"
\App\Support\PermissionPresenter::description('player.view');  // "Mengizinkan pengguna melihat data player."

// Yang baru harus rapi:
\App\Support\PermissionPresenter::label('player_type.view');       // "Lihat Player Type"
\App\Support\PermissionPresenter::description('player_type.view'); // "Mengizinkan pengguna melihat data player type."

// Owner FAOS Academy harus dapat permission barunya:
\App\Models\User::where('email','owner@faosacademy.com')->first()->can('player_type.view');
// harus: true

// Academy default harus otomatis dapat 3 type:
\App\Models\PlayerType::pluck('is_billable', 'name');
// harus: Reguler => true, Beasiswa => false, Trial => false
```

---

## Tahap 7 — `PlayerTypeFormRequest`

**Tujuan**: nama type unik **per academy**, dan hanya Super Admin boleh mengirim `id_academy`.

`app/Http/Requests/PlayerType/PlayerTypeFormRequest.php` — **file baru** (perhatikan foldernya: `PlayerType`, mengikuti `docs/coding-standard.md` → Request dipisah per module):

```php
<?php

namespace App\Http\Requests\PlayerType;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerTypeFormRequest extends FormRequest
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
            // Hanya Super Admin yang boleh mengirim id_academy, dan dia WAJIB
            // mengirimnya -- tidak ada "type system" tanpa academy.
            // User academy: field ini tidak dirender & ditolak kalau tetap dikirim.
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'required' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('player_types', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('player_type')?->id_player_type, 'id_player_type'),
            ],

            'description' => ['nullable', 'string'],

            'is_billable' => ['required', 'boolean'],

            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => 'Academy wajib dipilih.',
            'id_academy.prohibited' => 'Academy tidak dapat dipilih.',
            'id_academy.uuid' => 'Academy tidak valid.',
            'id_academy.exists' => 'Academy tidak ditemukan.',

            'name.required' => 'Nama type wajib diisi.',
            'name.string' => 'Nama type harus berupa teks.',
            'name.max' => 'Nama type maksimal :max karakter.',
            'name.unique' => 'Nama type sudah digunakan pada academy ini.',

            'description.string' => 'Deskripsi harus berupa teks.',

            'is_billable.required' => 'Status tagihan wajib ditentukan.',
            'is_billable.boolean' => 'Status tagihan tidak valid.',

            'status.required' => 'Status wajib ditentukan.',
            'status.boolean' => 'Status tidak valid.',
        ];
    }
}
```

Catatan:
- `->ignore(..., 'id_player_type')` — parameter kedua **wajib** karena primary key tabel ini bukan `id`. Kalau lupa, edit type akan selalu gagal validasi "nama sudah digunakan" oleh dirinya sendiri.
- `$this->route('player_type')` — nama parameter route dari `Route::resource('player-types', ...)` adalah `player_type` (Laravel mengubah `player-types` → singular → `player_type`).
- Pesan Indonesia di-hardcode. Jangan bikin folder `lang/` (`docs/coding-standard.md`).

---

## Tahap 8 — Controller + Route

**Tujuan**: CRUD type jalan dan digerbang permission.

### 8a. `app/Http/Controllers/PlayerTypeController.php` — file baru

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerType\PlayerTypeFormRequest;
use App\Models\Academy;
use App\Models\PlayerType;
use App\Services\AcademyService;
use App\Services\PlayerTypeService;

class PlayerTypeController extends Controller
{
    protected PlayerTypeService $playerTypeService;
    protected AcademyService $academyService;

    public function __construct(PlayerTypeService $playerTypeService, AcademyService $academyService)
    {
        $this->playerTypeService = $playerTypeService;
        $this->academyService = $academyService;
    }

    public function index()
    {
        return view('player-types.index', [
            'title' => 'Player Type',
            'breadcrumb' => [
                ['label' => 'Players', 'url' => route('players.index')],
                ['label' => 'Player Type'],
            ],
            'playerTypes' => $this->playerTypeService->paginate(),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        return view('player-types.create', [
            'title' => 'Tambah Player Type',
            'breadcrumb' => [
                ['label' => 'Player Type', 'url' => route('player-types.index')],
                ['label' => 'Tambah Player Type'],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(PlayerTypeFormRequest $request)
    {
        try {

            $this->playerTypeService->create($request->validated());

            return redirect()
                ->route('player-types.index')
                ->with('success', 'Player type berhasil ditambahkan.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menambahkan player type');
        }
    }

    public function edit(PlayerType $playerType)
    {
        return view('player-types.edit', [
            'title' => 'Edit Player Type',
            'breadcrumb' => [
                ['label' => 'Player Type', 'url' => route('player-types.index')],
                ['label' => 'Edit Player Type'],
            ],
            'playerType' => $playerType,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(PlayerTypeFormRequest $request, PlayerType $playerType)
    {
        try {

            $this->playerTypeService->update($playerType, $request->validated());

            return redirect()
                ->route('player-types.index')
                ->with('success', 'Player type berhasil diperbarui.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal memperbarui player type');
        }
    }

    public function destroy(PlayerType $playerType)
    {
        try {

            $this->playerTypeService->delete($playerType);

            return redirect()
                ->route('player-types.index')
                ->with('success', 'Player type berhasil dihapus.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menghapus player type', 'player-types.index');
        }
    }
}
```

> **Tidak ada `$this->authorize()` dan tidak ada Policy di sini** — dan itu memang benar. Route model binding `PlayerType $playerType` sudah kena `AcademyScope`, jadi Owner Academy A yang membuka type milik Academy B dapat **404**, bukan lolos. Lihat [4.3](#43-kenapa-playertype-boleh-pakai-global-scope-padahal-role-tidak). Test nomor 3 di Tahap 11 membuktikannya.

### 8b. `routes/web.php`

Tambahkan **tepat di bawah** blok `Route::resource('players', ...)` yang sudah ada:

```php
    /*
    |--------------------------------------------------------------------------
    | Player Type Management
    |--------------------------------------------------------------------------
    */
    Route::resource('player-types', PlayerTypeController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:player_type.view')
        ->middlewareFor(['create', 'store'], 'permission:player_type.create')
        ->middlewareFor(['edit', 'update'], 'permission:player_type.update')
        ->middlewareFor('destroy', 'permission:player_type.delete');
```

Tambah import di atas:

```php
use App\Http\Controllers\PlayerTypeController;
```

> `except(['show'])` — halaman detail tidak dibuat. Seluruh informasi type (nama, deskripsi, tagihan, status, jumlah player) sudah muat di halaman index. Ini mengikuti pola module Permission yang juga `except(['edit','update'])` untuk hal yang memang tidak dibutuhkan.

**✅ Cek dulu**

```bash
php artisan route:list --name=player-types
```

Harus muncul 6 route (`index`, `create`, `store`, `edit`, `update`, `destroy`), masing-masing dengan middleware `permission:player_type.*` yang sesuai. **Tidak boleh** ada `player-types.show`.

---

## Tahap 9 — View Player Type

`resources/views/player-types/`. Ingat `docs/development-guide.md`: **tidak ada business logic di Blade**, dan wajib nyaman di HP.

**Wajib baca dulu**: `docs/frontend-standard.md` → *Tabel Responsif: Table Desktop + Card List Mobile/Tablet*. Halaman index **wajib** punya dua representasi (tabel `lg:` ke atas + `table-card-list` di bawahnya). Contoh terdekat yang bisa kamu tiru: `resources/views/roles/index.blade.php`.

### 9a. `index.blade.php`

Kolom tabel: **Type** (nama + deskripsi) · **Academy** (Super Admin saja) · **Tagihan** · **Status** · **Player** · **Aksi**.

Potongan yang spesifik module ini (sisanya tiru `roles/index.blade.php` apa adanya):

```blade
{{-- Tagihan --}}
@if ($playerType->is_billable)
    <span class="badge badge-success">Ditagih</span>
@else
    <span class="badge badge-secondary">Tidak Ditagih</span>
@endif

{{-- Status --}}
@if ($playerType->status)
    <span class="badge badge-success">Aktif</span>
@else
    <span class="badge badge-danger">Nonaktif</span>
@endif

{{-- Academy (Super Admin saja) --}}
@if ($isSuperAdmin)
    <span class="badge badge-secondary">{{ $playerType->academy->name }}</span>
@endif

{{-- Player --}}
<span class="table-text">{{ $playerType->players_count }} Player</span>

{{-- Delete: kunci tombolnya kalau type masih dipakai --}}
@can('player_type.delete')
    <x-button.delete :action="route('player-types.destroy', $playerType)" :name="$playerType->name"
        :disabled="$playerType->players_count > 0"
        reason="Type masih digunakan oleh player, tidak dapat dihapus." />
@endcan
```

> `:disabled` + `reason` pada `<x-button.delete>` sudah tersedia (dipakai module Role/Permission). Pakai itu, jangan bikin sendiri.
>
> Jumlah `<th>` dan `<td>` harus tetap sama. Kalau `@if ($isSuperAdmin)` dipasang di salah satunya saja, tabel akan bergeser.

### 9b. `create.blade.php`

Field: **Academy** (Super Admin saja) · **Nama Type** · **Deskripsi** · **Tagihan** (toggle) · **Status** (toggle).

```blade
@if ($isSuperAdmin)
    <div class="form-group">
        <label class="form-label">
            Academy <span class="text-error-500">*</span>
        </label>

        <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror" required>
            <option value="">Pilih Academy</option>
            @foreach ($academies as $academy)
                <option value="{{ $academy->id_academy }}" @selected(old('id_academy') === $academy->id_academy)>
                    {{ $academy->name }}
                </option>
            @endforeach
        </select>

        @error('id_academy')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>
@endif
```

Untuk toggle `is_billable` dan `status`, **tiru pola toggle yang sudah ada** di `resources/views/players/create.blade.php` (bagian "Buat Akun Player") — pakai `input type="hidden"` + Alpine supaya nilainya **selalu terkirim**:

```blade
<div class="form-group" x-data="{ isBillable: {{ old('is_billable', 1) ? 'true' : 'false' }} }">

    <label class="form-label">Tagihan Iuran / SPP</label>

    <input type="hidden" name="is_billable" :value="isBillable ? 1 : 0">

    <label class="flex cursor-pointer items-center">

        <input type="checkbox" class="sr-only" :checked="isBillable" @change="isBillable = !isBillable">

        <div class="form-toggle" :class="isBillable && 'form-toggle-active'">
            <span class="form-toggle-dot" :class="isBillable && 'form-toggle-checked'"></span>
        </div>

        <span class="ml-3 text-sm text-gray-500"
            x-text="isBillable ? 'Player dengan type ini ditagih iuran/SPP' : 'Player dengan type ini tidak ditagih'">
        </span>

    </label>

    @error('is_billable')
        <span class="form-error">{{ $message }}</span>
    @enderror

</div>
```

> **Jangan** pakai `<input type="checkbox" name="is_billable">` polos. Checkbox yang tidak dicentang **tidak dikirim sama sekali** oleh browser, sehingga rule `required` akan gagal dan user tidak bisa membuat type yang tidak ditagih. Pola hidden input di atas mengirim `0`/`1` secara eksplisit.

### 9c. `edit.blade.php`

Sama dengan `create`, dengan dua perbedaan:

1. `@method('PUT')` dan `action="{{ route('player-types.update', $playerType) }}"`.
2. Academy ditampilkan **read-only** (bukan dropdown) — type tidak bisa pindah academy:

```blade
@if ($isSuperAdmin)
    <div class="form-group">
        <label class="form-label">Academy</label>
        <p class="form-input bg-gray-50 dark:bg-gray-800">
            {{ $playerType->academy->name }}
        </p>
    </div>
@endif
```

Nilai awal toggle diambil dari data: `old('is_billable', $playerType->is_billable)`.

### 9d. Menu sidebar

`resources/views/partials/sidebar.blade.php` — di dalam grup **Football Academy**, tambahkan **setelah** menu Players yang sudah ada:

```blade
{{-- Player Types --}}
@can('player_type.view')
    <li>
        <a href="{{ route('player-types.index') }}" class="menu-dropdown-item group"
            :class="{{ Route::is('player-types.*') ? 'true' : 'false' }}
                ?
                'menu-dropdown-item-active' :
                'menu-dropdown-item-inactive'">
            Player Types
        </a>
    </li>
@endcan
```

Tambahkan juga `'player-types.*'` ke array `$footballAcademyRoutes` di `@php` blok atas, supaya grup menu-nya ikut terbuka saat halaman ini aktif:

```php
$footballAcademyRoutes = ['players.*', 'player-types.*', 'training.*'];
```

**✅ Cek dulu**: login sebagai Super Admin (`superadmin@faosball.com` / `password`) → `/player-types` menampilkan 3 type FAOS Academy + kolom Academy. Cek di DevTools lebar **375px**: harus berubah jadi Card List, tabel tidak boleh memaksa halaman melebar.

---

## Tahap 10 — Integrasi ke Module Player

**Tujuan**: setiap Player wajib punya type, dan tidak bisa dipasangi type milik academy lain.

### 10a. `app/Http/Requests/Players/StorePlayerRequest.php`

Tambah import:

```php
use Illuminate\Validation\Rule;
```

Tambah rule ini di dalam `rules()`, **tepat setelah** rule `id_academy` yang sudah ada:

```php
            // Type WAJIB milik academy yang sama dengan player.
            //
            // Rule::exists() memakai query builder mentah -- AcademyScope TIDAK
            // ikut jalan di sini. Tanpa where('id_academy', ...) eksplisit,
            // Owner Academy A bisa memasang type milik Academy B lewat POST
            // yang dikarang. Lihat Bagian 4.2.
            'id_player_type' => [
                'required',
                'uuid',
                Rule::exists('player_types', 'id_player_type')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $academyId)
                        ->where('status', true)
                    ),
            ],
```

`$academyId` diambil dari variabel yang **sudah ada** di `rules()`:

```php
$academyService = app(AcademyService::class);

$academyId = $academyService->isSuperAdmin()
    ? $this->input('id_academy')
    : $academyService->currentId();
```

> Kalau `rules()` di file itu belum punya `$academyId` (cuma punya `$academyService`), tambahkan barisnya seperti di atas.

Tambah pesan di `messages()`:

```php
            'id_player_type.required' => 'Type player wajib dipilih.',
            'id_player_type.uuid' => 'Type player tidak valid.',
            'id_player_type.exists' => 'Type player tidak ditemukan pada academy ini.',
```

### 10b. `app/Http/Requests/Players/UpdatePlayerRequest.php`

Player tidak bisa pindah academy (lihat form edit), jadi academy-nya diambil dari player itu sendiri:

```php
use Illuminate\Validation\Rule;
```

```php
    public function rules(): array
    {
        return [
            // Academy player TIDAK berubah lewat form edit, jadi acuannya
            // diambil dari player yang sedang diedit.
            //
            // Sengaja TIDAK memfilter status = true: player yang type-nya sudah
            // dinonaktifkan harus tetap bisa disimpan tanpa dipaksa ganti type.
            'id_player_type' => [
                'required',
                'uuid',
                Rule::exists('player_types', 'id_player_type')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $this->route('player')->id_academy)
                    ),
            ],

            // ... rule lain yang sudah ada, biarkan
        ];
    }
```

Pesan `messages()`: sama dengan 10a.

### 10c. `app/Services/PlayerService.php`

Di method `create()`, tambahkan **satu baris** ke array `Player::create([...])`, setelah `'player_code'`:

```php
'id_player_type' => $data['id_player_type'],
```

Di method `update()`, tambahkan **satu baris** ke array `$player->update([...])`:

```php
'id_player_type' => $data['id_player_type'],
```

> Tidak perlu validasi ulang di Service — Form Request sudah memastikan type-nya milik academy yang benar. `resolveAcademy()` dan `generatePlayerCode()` **tidak berubah**.

### 10d. `app/Http/Controllers/PlayerController.php`

Tambah import + constructor injection `PlayerTypeService` (di samping `PlayerService` dan `AcademyService` yang sudah ada):

```php
use App\Services\PlayerTypeService;
```

```php
protected PlayerService $playerService;
protected AcademyService $academyService;
protected PlayerTypeService $playerTypeService;

public function __construct(
    PlayerService $playerService,
    AcademyService $academyService,
    PlayerTypeService $playerTypeService
) {
    $this->playerService = $playerService;
    $this->academyService = $academyService;
    $this->playerTypeService = $playerTypeService;
}
```

Di `index()`, tambahkan eager load supaya kolom Type tidak N+1 (`docs/query-performance.md`):

```php
'players' => Player::with('playerType')->latest()->paginate(10),
```

Di `create()`, tambahkan ke array view:

```php
// Super Admin: seluruh academy (difilter di sisi Alpine mengikuti academy
// yang dipilih). User academy: cukup type miliknya sendiri.
'playerTypes' => $this->playerTypeService->selectable(
    $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId()
),
```

Di `edit()`, tambahkan:

```php
// Academy player tidak berubah, jadi cukup type milik academy player itu.
// includeId dipakai supaya type yang sudah dinonaktifkan tetap muncul kalau
// player ini memang sedang memakainya.
'playerTypes' => $this->playerTypeService->selectable(
    $player->id_academy,
    $player->id_player_type
),
```

Di `show()`, tambahkan `playerType` ke `load()` yang sudah ada:

```php
$player->load([
    'academy',
    'playerType',
    'user.roles'
]);
```

### 10e. `resources/views/players/create.blade.php`

Ini bagian **paling rawan** di brief ini. Masalahnya: Super Admin memilih academy **di form yang sama**, jadi dropdown type harus ikut berubah mengikuti academy yang dipilih.

**Ganti** blok `@if ($isSuperAdmin)` yang berisi dropdown Academy (yang sudah ada di file itu) dengan blok ini:

```blade
@if ($isSuperAdmin)

    {{-- Academy + Type: dropdown type mengikuti academy yang dipilih --}}
    <div x-data="{
        academyId: @js(old('id_academy', '')),
        playerTypeId: @js(old('id_player_type', '')),
        types: @js($playerTypes),
        get availableTypes() {
            return this.types.filter(type => type.id_academy === this.academyId);
        },
    }">

        <div class="form-group">
            <label class="form-label">
                Academy <span class="text-error-500">*</span>
            </label>

            <select name="id_academy" x-model="academyId" @change="playerTypeId = ''"
                class="form-select @error('id_academy') form-danger @enderror" required>
                <option value="">Pilih Academy</option>
                @foreach ($academies as $academy)
                    <option value="{{ $academy->id_academy }}">{{ $academy->name }}</option>
                @endforeach
            </select>

            @error('id_academy')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">
                Type Player <span class="text-error-500">*</span>
            </label>

            <select name="id_player_type" x-model="playerTypeId"
                class="form-select @error('id_player_type') form-danger @enderror" required>

                <option value="">
                    <span x-text="academyId ? 'Pilih Type Player' : 'Pilih Academy dulu'"></span>
                </option>

                <template x-for="type in availableTypes" :key="type.id_player_type">
                    <option :value="type.id_player_type" x-text="type.name"></option>
                </template>

            </select>

            <p x-show="academyId && availableTypes.length === 0" x-cloak class="form-error">
                Academy ini belum punya type player. Buat dulu lewat menu Player Type.
            </p>

            @error('id_player_type')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

    </div>

@else

    {{-- User academy: type otomatis dibatasi academy sendiri --}}
    <div class="form-group">
        <label class="form-label">
            Type Player <span class="text-error-500">*</span>
        </label>

        <select name="id_player_type" class="form-select @error('id_player_type') form-danger @enderror" required>
            <option value="">Pilih Type Player</option>
            @foreach ($playerTypes as $type)
                <option value="{{ $type->id_player_type }}" @selected(old('id_player_type') === $type->id_player_type)>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>

        @if ($playerTypes->isEmpty())
            <span class="form-error">
                Academy ini belum punya type player. Buat dulu lewat menu Player Type.
            </span>
        @endif

        @error('id_player_type')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

@endif
```

> `@js($playerTypes)` mengubah collection Eloquent jadi JSON yang aman untuk atribut Alpine. Tiap objeknya sudah punya `id_player_type`, `id_academy`, dan `name` — tidak perlu mapping manual (dan **jangan** mapping di Blade, itu business logic).
>
> Blok "belum punya type player" itu bukan hiasan: `id_player_type` bersifat `required`, jadi tanpa type sama sekali form ini **tidak akan bisa disubmit**. Pesan itu yang mencegah user mentok tanpa tahu sebabnya.

### 10f. `resources/views/players/edit.blade.php`

Lebih sederhana — academy player tidak berubah, jadi tidak ada filter dinamis. Tambahkan **setelah** blok Academy read-only yang sudah ada:

```blade
<div class="form-group">
    <label class="form-label">
        Type Player <span class="text-error-500">*</span>
    </label>

    <select name="id_player_type" class="form-select @error('id_player_type') form-danger @enderror" required>
        <option value="">Pilih Type Player</option>
        @foreach ($playerTypes as $type)
            <option value="{{ $type->id_player_type }}" @selected(old('id_player_type', $player->id_player_type) === $type->id_player_type)>
                {{ $type->name }}@unless ($type->status) (nonaktif)@endunless
            </option>
        @endforeach
    </select>

    @error('id_player_type')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

> Player lama (dibuat sebelum module ini ada) `id_player_type`-nya `NULL`, jadi dropdown-nya terbuka di "Pilih Type Player" dan `required` memaksa user memilih saat menyimpan. Itu memang yang kita mau — backfill bertahap tanpa migration yang menebak-nebak.

### 10g. `resources/views/players/index.blade.php`

Tambah kolom **Type** antara "Posisi" dan "Status", di **kedua** representasi.

Tabel — `<thead>`:

```blade
<th class="table-header-cell">Type</th>
```

Tabel — `<tbody>`:

```blade
<td class="table-cell">
    @if ($player->playerType)
        <span class="badge {{ $player->playerType->is_billable ? 'badge-primary' : 'badge-secondary' }}">
            {{ $player->playerType->name }}
        </span>
    @else
        <span class="table-subtitle">-</span>
    @endif
</td>
```

Card List — tambah field di dalam `table-card-body`:

```blade
<div class="table-card-field">
    <span class="table-card-label">Type</span>
    @if ($player->playerType)
        <span class="badge {{ $player->playerType->is_billable ? 'badge-primary' : 'badge-secondary' }} w-fit">
            {{ $player->playerType->name }}
        </span>
    @else
        <span class="table-subtitle">-</span>
    @endif
</div>
```

> `@if ($player->playerType)` wajib — player lama `id_player_type`-nya `NULL`, dan `$player->playerType->name` pada relasi null akan **error 500**.

### 10h. `resources/views/players/show.blade.php`

Di panel **Informasi Academy** (sidebar kanan), tambahkan setelah blok "Academy":

```blade
<div>
    <span class="mb-1 block text-xs text-gray-400">
        Type Player
    </span>

    @if ($player->playerType)
        <span class="badge {{ $player->playerType->is_billable ? 'badge-primary' : 'badge-secondary' }}">
            {{ $player->playerType->name }}
        </span>
    @else
        <span class="table-text">-</span>
    @endif
</div>
```

**✅ Cek dulu**

1. Login Owner (`owner@faosacademy.com` / `password`) → `/players/create` → dropdown **Type Player** muncul berisi Reguler/Beasiswa/Trial, **tanpa** dropdown Academy.
2. Login Super Admin → `/players/create` → pilih Academy → dropdown Type **ikut berubah** dan kosong sebelum academy dipilih.
3. Simpan player tanpa memilih type → harus muncul error "Type player wajib dipilih."
4. Buka `/players` → kolom Type tampil. Cek juga di 375px (Card List).

---

## Tahap 11 — Test

**Tujuan**: mengunci tujuan module supaya tidak rusak diam-diam nanti.

Test memakai SQLite in-memory (lihat `phpunit.xml`). Contoh terdekat yang bisa ditiru: `tests/Feature/RoleAcademyTest.php`.

### 11a. `database/factories/PlayerTypeFactory.php` — file baru

```php
<?php

namespace Database\Factories;

use App\Models\PlayerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerType>
 */
class PlayerTypeFactory extends Factory
{
    protected $model = PlayerType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'is_billable' => true,
            'status' => true,
        ];
    }
}
```

> `id_academy` sengaja **tidak** diisi factory — harus selalu di-pass eksplisit oleh test (`PlayerType::factory()->create(['id_academy' => $academy->id_academy])`), supaya tidak ada test yang diam-diam bikin type di academy yang salah.

### 11b. `tests/Feature/PlayerTypeTest.php` — file baru

Wajib menulis **8 skenario** ini. Nomor 6 dan 7 adalah yang paling penting — itu yang menjaga batas antar academy.

| # | Skenario | Assert kunci |
|---|----------|--------------|
| 1 | Dua academy boleh punya type dengan nama sama | `PlayerType` "Reguler" di Academy A & B → `assertSame(2, ...)`, tidak ada exception |
| 2 | Satu academy tidak boleh punya dua type dengan nama sama | POST `player-types.store` "Reguler" 2× sebagai Owner → `assertSessionHasErrors('name')` |
| 3 | Isolasi URL | `actingAs($ownerA)->get(route('player-types.edit', $typeB))` → `assertNotFound()` (404, **bukan** 403 — ini dari `AcademyScope`, lihat 4.3). Owner A wajib punya `player_type.update` supaya yang diuji benar-benar scope-nya, bukan middleware. |
| 4 | Isolasi daftar | `actingAs($ownerA)` → `PlayerTypeService::paginate()` tidak memuat type Academy B |
| 5 | Type yang dipakai player tidak bisa dihapus | `PlayerTypeService::delete($type)` saat ada player → `expectException(\Exception::class)` |
| 6 | **Create player dengan type academy lain ditolak** | `actingAs($ownerA)->post(route('players.store'), [... 'id_player_type' => $typeB->id_player_type])` → `assertSessionHasErrors('id_player_type')`, dan pastikan player-nya **tidak** tercipta |
| 7 | **Super Admin: type harus milik academy yang dipilih** | Super Admin POST `id_academy` = A tapi `id_player_type` milik B → `assertSessionHasErrors('id_player_type')` |
| 8 | Academy baru dapat type default lengkap | `AcademyManagementService::create()` → 3 type sesuai `config('faos.player_type_templates')`, **beserta `is_billable`-nya** (mengunci risiko salah ketik di config) |

Kerangka awal (skenario 1 & 6) — lanjutkan sisanya dengan pola yang sama:

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang dibuat
        // di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeType(Academy $academy, string $name, bool $isBillable = true): PlayerType
    {
        return PlayerType::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => $name,
            'is_billable' => $isBillable,
        ]);
    }

    protected function makeUser(Academy $academy, array $permissions): User
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

    public function test_dua_academy_boleh_punya_type_dengan_nama_sama(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $this->makeType($academyA, 'Reguler');
        $this->makeType($academyB, 'Reguler');

        $this->assertSame(2, PlayerType::withoutGlobalScopes()->where('name', 'Reguler')->count());
    }

    /**
     * INI BATAS KEAMANAN UTAMA MODULE INI.
     */
    public function test_create_player_dengan_type_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $typeB = $this->makeType($academyB, 'Reguler');

        $ownerA = $this->makeUser($academyA, ['player.view', 'player.create']);

        $this->actingAs($ownerA)
            ->post(route('players.store'), [
                'id_player_type' => $typeB->id_player_type,   // ← type academy lain
                'name' => 'Player Curang',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
                'primary_position' => 'ST',
            ])
            ->assertSessionHasErrors('id_player_type');

        $this->assertSame(0, Player::withoutGlobalScopes()->count());
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=PlayerTypeTest
php artisan test --filter=RoleAcademyTest
```

Dua-duanya harus hijau. `RoleAcademyTest` wajib ikut dijalankan karena Tahap 5 & 6 menyentuh `AcademyManagementService` dan `PermissionPresenter` yang dipakai bersama.

> Catatan: `php artisan test` polos akan menunjukkan **7 test Breeze lama yang memang sudah merah sebelum brief ini** (`AuthenticationTest`, `RegistrationTest`, `ExampleTest`, `ProfileTest`, `EmailVerificationTest`). Itu **bukan** ulahmu — penyebabnya `User` memakai primary key `id_user` + SoftDeletes yang bentrok dengan asumsi test bawaan Breeze. Jangan diperbaiki di brief ini, jangan pula dijadikan alasan menunda.

---

## Tahap 12 — Update `docs/`

Wajib, sesuai aturan di `CLAUDE.md` dan checklist di `docs/module-standard.md`.

`docs/permission-reference.md`:

1. Tambah section baru **Module: Player Type** (tiru format section "Module: Player"), status **✅ Implemented**:

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `player_type.view` | Lihat daftar player type | `player-types.index` (route middleware) + menu sidebar |
| `player_type.create` | Tambah type baru | `player-types.create`, `player-types.store` |
| `player_type.update` | Ubah nama/deskripsi/tagihan/status type | `player-types.edit`, `player-types.update` |
| `player_type.delete` | Hapus type (kalau tidak dipakai player manapun) | `player-types.destroy` |

Sertakan juga catatan ini di section tersebut:

- Isolasi antar academy memakai `AcademyScope` (global scope), **bukan** Policy — akses type academy lain menghasilkan **404**, bukan 403. Ini beda dengan module Role.
- `player_type.view` **tidak** dibutuhkan untuk memilih type saat menambah Player. Dropdown di form Player diisi Service; permission ini hanya menggerbang halaman `/player-types`.
- `is_billable` adalah kontrak untuk module Payment: filter `where('is_billable', true)`, **jangan** cocokkan nama type.

2. Di section **Module: Player** yang sudah ada, tambahkan catatan bahwa `players.id_player_type` wajib diisi saat create (divalidasi di `StorePlayerRequest`, dibatasi ke academy yang sama).

3. Di tabel **Role Template Default per Academy Baru**, tambahkan kolom/keterangan `player_type.*` → hanya **Owner** yang dapat secara default.

4. Hapus `player_type.*` dari daftar *"Permission Belum Dipakai Module Manapun"* kalau kamu sempat menaruhnya di sana.

---

## 4. Kenapa Begini? (alasan teknis)

Bagian ini menjelaskan Aturan Emas di Bagian 0.

### 4.1 `cascadeOnDelete` pada `id_player_type` = hapus type, hapus semua playernya

Kalau FK ditulis:

```php
$table->foreign('id_player_type')->references('id_player_type')->on('player_types')->cascadeOnDelete();  // ❌
```

maka satu klik "Hapus type Reguler" akan **menghapus seluruh player Reguler** di academy itu — beserta akun login mereka (FK `players.id_user` sudah `nullOnDelete`, tapi player-nya sendiri lenyap). Ini kehilangan data permanen yang tidak akan langsung ketahuan.

`nullOnDelete` memastikan yang hilang cuma *label type*-nya, bukan playernya. Dan `PlayerTypeService::delete()` (Tahap 4) memblokir kejadian itu sejak awal, sehingga `nullOnDelete` cuma jaring pengaman lapis kedua.

### 4.2 `Rule::exists` tidak kena `AcademyScope`

`AcademyScope` adalah **Eloquent global scope** — ia hanya jalan untuk query lewat model (`PlayerType::where(...)`).

`Rule::exists('player_types', 'id_player_type')` memakai **query builder mentah** (`DB::table('player_types')`), sehingga global scope **tidak ikut**.

**Akibat kalau `where('id_academy', ...)` tidak ditulis eksplisit**: Owner Academy A cukup mengarang POST dengan `id_player_type` milik Academy B, dan validasi akan **lolos**. Player Academy A pun tersambung ke type Academy B — batas tenant jebol lewat pintu belakang, tanpa error apapun.

Ini bukan hipotesis: pola yang sama sudah pernah jadi lubang nyata di `AccountService::assignRole()` (lihat riwayat refactor Role).

→ Test nomor 6 & 7 di Tahap 11 mengunci ini.

### 4.3 Kenapa PlayerType BOLEH pakai global scope, padahal Role tidak?

Ini pertanyaan yang **pasti** muncul kalau kamu baca brief Role sebelumnya (ada di git, commit `a4939ad`).

Role dilarang pakai global scope karena alasan yang sangat spesifik: `PermissionRegistrar` milik Spatie menjalankan `Permission::with('roles')->get()` lalu menyimpannya ke **satu cache key bersama untuk seluruh tenant**. Global scope pada `Role` akan ikut memfilter query itu, sehingga isi cache tergantung academy siapa yang kebetulan memicu rebuild — bug acak yang nyaris mustahil dilacak.

**`PlayerType` tidak punya masalah itu sama sekali**: tidak ada package yang meng-cache `PlayerType` lintas tenant. Ia model bisnis FAOSBall biasa, persis seperti `Player`.

Jadi: pakai `BelongsToAcademy` seperti biasa, **jangan** bikin Policy, **jangan** tiru pola `forCurrentAcademy()` milik Role. Meniru pola Role di sini justru menambah kerumitan tanpa alasan.

### 4.4 Kenapa `is_billable`, bukan cocok-cocokan nama

Type dibuat **per academy** dan namanya bebas. Academy A menulis "Reguler", Academy B menulis "Regular", Academy C menulis "Siswa Tetap" — semuanya sah.

Kalau module Payment nanti menulis `where('name', 'Reguler')`:

- Academy B & C tidak akan pernah tertagih (diam-diam, tanpa error).
- Begitu Academy A rename type-nya, tagihan mereka ikut berhenti.

`is_billable` memindahkan keputusan itu ke **data**, bukan ke string di kode. Owner academy yang menentukan sendiri type mana yang ditagih, lewat toggle di form.

### 4.5 Kenapa `id_player_type` nullable di DB tapi `required` di Form Request

Kalau kolomnya dibuat `NOT NULL`, migration akan **gagal** di database manapun yang sudah punya player (semua player lama tidak punya type). Alternatifnya: migration menebak-nebak type untuk mereka — menulis data bisnis lewat migration, yang tidak boleh.

Dengan nullable + `required` di Form Request:

- Migration jalan mulus di database manapun.
- Semua player **baru** wajib punya type.
- Player lama tampil "-" di list, dan otomatis dipaksa memilih type saat pertama kali di-edit.

Yang wajib diingat: **relasi `playerType` bisa `null`**. Setiap Blade yang mengaksesnya wajib dijaga `@if ($player->playerType)` — lihat Tahap 10g.

---

## 5. Keputusan Arsitektur

Keputusan berikut sudah dibahas dengan pemilik produk. Kalau mau mengubahnya, **diskusikan dulu** — jangan diam-diam dibalik.

| Pilihan | Keputusan | Alasan |
|---------|-----------|--------|
| Nama module | **Player Type** | Sesuai istilah tim. `PlayerCategory` sengaja **dihindari** karena di akademi bola "category" umumnya berarti kelompok umur (U-12, U-15) — nama itu disisakan untuk module kelompok umur nanti. |
| Penanda tagihan | **Kolom `is_billable` sejak awal** | Tanpa ini module Payment terpaksa cocok-cocokan nama type, yang pasti pecah lintas academy (4.4). |
| Type default academy baru | **Dari `config('faos.player_type_templates')`** | Ikut pola `role_templates` yang sudah terbukti. Tanpa ini, academy baru tidak bisa menambah player sama sekali (karena type wajib) sampai Owner bikin type manual — dead-end. |
| Isolasi antar academy | **`BelongsToAcademy` + `AcademyScope`**, tanpa Policy | PlayerType tidak punya masalah cache Spatie seperti Role (4.3). Route model binding + global scope sudah menghasilkan 404. |
| Type nonaktif | **Kolom `status`** | Type yang masih dipakai player **tidak bisa dihapus**. Tanpa `status`, type yang sudah tidak ditawarkan lagi akan nyangkut selamanya di dropdown player baru. |
| Pindah academy | **Tidak bisa**, baik type maupun player | Type pindah academy = seluruh player yang memakainya ikut "pindah" secara tidak sengaja. Sama seperti keputusan di module Role. |
| Halaman `show` type | **Tidak dibuat** | Seluruh info type muat di index. Ikut pola module Permission yang juga memangkas route yang tidak dibutuhkan. |

---

## 6. Definition of Done

- [ ] `php artisan migrate:fresh --seed` bersih tanpa error.
- [ ] FK `players.id_player_type` = **`on delete set null`** (cek `php artisan db:table players`), **bukan** cascade.
- [ ] Tabel `player_types` punya unique `(id_academy, name)`.
- [ ] Dua academy bisa punya type `Reguler` masing-masing — terbukti lewat test.
- [ ] Owner hanya melihat & mengelola type academy sendiri; akses lintas academy → **404**.
- [ ] Super Admin melihat seluruh type dan wajib memilih academy saat create.
- [ ] Academy baru otomatis dapat 3 type default lengkap dengan `is_billable`-nya.
- [ ] Create Player wajib pilih type, dan type milik academy lain **ditolak** — terbukti lewat test, bukan asumsi.
- [ ] Type yang masih dipakai player tidak bisa dihapus (tombol delete disabled + guard di Service).
- [ ] Player lama (`id_player_type` = NULL) tidak bikin error 500 di halaman index/show.
- [ ] `php artisan test --filter=PlayerTypeTest` dan `--filter=RoleAcademyTest` hijau.
- [ ] `PermissionPresenter` menampilkan "Player Type", bukan "Player_type" — dan tampilan module lama tidak berubah.
- [ ] Controller tetap tipis, business logic di Service, validasi di Form Request.
- [ ] Pesan user-facing Bahasa Indonesia, hardcoded, tanpa folder `lang/`.
- [ ] Halaman index punya Card List responsif; sudah dicek di 375px / tablet / desktop.
- [ ] `docs/permission-reference.md` sudah diperbarui.

---

## 7. Urutan Commit

Kerjakan berurutan, jangan digabung — tiap commit harus bisa di-review sendiri.

| # | Isi | Tahap |
|---|-----|-------|
| 1 | Migration `player_types` + kolom `id_player_type` | 1 |
| 2 | Model `PlayerType` + relasi di `Player` + `player_type_templates` | 2, 3 |
| 3 | `PlayerTypeService` + `AcademyManagementService` | 4, 5 |
| 4 | Permission (seeder, `role_templates`, `PermissionPresenter`) | 6 |
| 5 | `PlayerTypeFormRequest` + Controller + Route + View + menu | 7, 8, 9 |
| 6 | Integrasi ke module Player | 10 |
| 7 | Factory + Test | 11 |
| 8 | Update `docs/` | 12 |

Kalau perilaku permission terasa aneh saat manual testing:

```bash
php artisan permission:cache-reset
php artisan config:clear
php artisan view:clear
```

---

## 8. Hasil Akhir

Setelah module ini, setiap academy punya daftar jenis pemainnya sendiri, bebas menambah type di luar 3 default, dan menentukan sendiri type mana yang ditagih iuran. Setiap player wajib punya type, dan type dari academy lain tidak akan pernah bisa menempel padanya.

Ini fondasi langsung untuk module **Payment**: menagih SPP tinggal menyaring `is_billable = true`, tanpa perlu tahu satupun nama type. Module Team dan Report nanti juga bisa memakai dimensi yang sama untuk mengelompokkan pemain.
