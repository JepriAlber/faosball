# Brief: Module Player Category (Kelompok Umur U-12 / U-15 / U-17)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu. Terutama `docs/multi-tenancy.md`, `docs/permission-reference.md`, `docs/development-guide.md`, dan `docs/frontend-standard.md`.
>
> ## ⛔ WAJIB: `issue.md` (Module Player Type) HARUS SELESAI & MERGED DULU
>
> Brief ini **tidak bisa dikerjakan paralel** dengan `issue.md`. Alasannya konkret, bukan formalitas:
>
> - Migration Tahap 1 memakai `->after('id_player_type')` — kolom itu dibuat oleh `issue.md`.
> - Tahap 10 **menulis ulang** blok Alpine di `players/create.blade.php` yang dibuat `issue.md`. Kalau dua brief dikerjakan bersamaan, blok itu pasti konflik dan salah satunya akan hilang diam-diam.
> - Tahap 6 mengandalkan `PermissionPresenter::moduleLabel()` yang dibuat `issue.md`.
>
> Kalau `issue.md` belum selesai, **berhenti di sini.**

> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 12 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> Kalau cuma mau eksekusi, cukup baca Bagian 0–3 lalu langsung ke Tahap 1. Bagian 4 (alasan teknis) boleh dibaca belakangan, **tapi aturannya tetap tidak boleh dilanggar**.

---

## 0. Aturan Emas

Sebelas larangan ini bukan preferensi gaya. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| **Menolak player yang umurnya di luar rentang kategori** | "Main naik kelas" itu normal di sepak bola. Validasi keras = sistem menolak kenyataan. | [4.2](#42-kenapa-umur-tidak-divalidasi-keras-terhadap-kategori) |
| Menghitung kategori on-the-fly tanpa menyimpannya | Kategori berubah sendiri saat ulang tahun, riwayat hilang | [4.1](#41-kenapa-kategori-disimpan-bukan-dihitung-terus-dari-birth_date) |
| `->orderBy()` lupa di query saran kategori | Rentang tumpang tindih → saran beda-beda tiap request | [4.3](#43-kenapa-saran-kategori-wajib-orderby) |
| `cascadeOnDelete` pada FK `players.id_player_category` | Hapus 1 kategori → **seluruh player** kategori itu ikut terhapus | [4.4](#44-cascadeondelete--hapus-kategori-hapus-semua-playernya) |
| `Rule::exists('player_categories', ...)` **tanpa** `where('id_academy', ...)` | Owner A bisa pasang kategori milik Academy B lewat POST | [4.5](#45-ruleexists-tidak-kena-academyscope) |
| Lupa validasi `max_age >= min_age` | Rentang terbalik tidak pernah cocok siapa pun, diam-diam | [Tahap 7](#tahap-7--playercategoryformrequest) |
| Membuat `PlayerCategory` dengan `id_academy` = `NULL` | **Tidak ada "kategori system"**, sama seperti PlayerType | [2](#2-cara-kerja-solusi) |
| `unique('name')` global di tabel `player_categories` | Academy B tidak bisa punya "U-12" kalau A sudah punya | [Tahap 1](#tahap-1--migration) |
| Bikin Policy untuk PlayerCategory | **Tidak perlu.** Global scope sudah bikin 404. | `issue.md` Bagian 4.3 |
| `$player->playerCategory->name` di Blade tanpa `@if` | Player lama `id_player_category`-nya `NULL` → error 500 | [4.6](#46-kenapa-id_player_category-nullable-di-db-tapi-required-di-form-request) |
| Membuat folder `lang/` | Pesan Indonesia di-hardcode | `docs/coding-standard.md` |

---

## 1. Tujuan

Setiap academy mengelompokkan pemainnya berdasarkan **umur**: U-12, U-15, U-17.

```text
Academy A                          Academy B
├── U-12  (10-12 th)               ├── U-10  (8-10 th)     ← bebas bikin sendiri
├── U-15  (13-15 th)               ├── U-12  (11-12 th)    ← rentang boleh beda
└── U-17  (16-17 th)               └── U-15  (13-15 th)
```

**Aturan inti**: Setiap Player **wajib** punya satu Player Category. Setiap Player Category **wajib** milik satu Academy.

**Kegunaan utamanya** ada di module **Template Latihan** nanti: coach membuat template latihan per kelompok umur (latihan U-12 beda dengan U-17). Module ini adalah fondasinya.

---

## 2. Cara Kerja Solusi

Baca sampai paham. Kalau bagian ini tidak nyantol, sisa brief akan terasa acak.

### 2a. 80% module ini SAMA PERSIS dengan Player Type

`PlayerCategory` adalah kembaran struktural `PlayerType` (yang kamu baru selesaikan di `issue.md`):

| Hal | Sama dengan PlayerType? |
|-----|------------------------|
| `extends FaosModel` (UUID + `BelongsToAcademy` + `AcademyScope`) | ✅ Sama persis |
| `id_academy` wajib, tidak ada "kategori system" | ✅ Sama persis |
| Unique `(id_academy, name)` | ✅ Sama persis |
| Isolasi lewat global scope → 404, **tanpa Policy** | ✅ Sama persis |
| Guard: tidak bisa dihapus kalau masih dipakai player | ✅ Sama persis |
| Kolom `status` untuk menonaktifkan | ✅ Sama persis |
| Template default academy baru dari `config/faos.php` | ✅ Sama persis |
| FK `nullOnDelete` di `players` | ✅ Sama persis |

**Karena itu**: setiap kali brief ini bilang *"tiru module Player Type"*, buka file `player-types` / `PlayerTypeService` / `PlayerTypeFormRequest` yang **sudah ada**, dan tiru apa adanya. Jangan mengarang pola baru.

### 2b. 20% sisanya yang BEDA — dan ini inti brief

| Beda | PlayerType | PlayerCategory |
|------|-----------|----------------|
| Kolom khas | `is_billable` (boolean) | **`min_age` + `max_age`** (integer) |
| Asal nilai | Murni keputusan manusia | **Turunan dari `birth_date`** — sistem bisa menyarankan |
| Urutan tampil | `latest()` | **`orderBy('min_age')`** — U-12 harus tampil sebelum U-17, bukan urut tanggal dibuat |
| Bantuan di form | Tidak ada | **Saran otomatis dari umur**, boleh ditimpa coach |

### 2c. Kategori DISIMPAN, saran hanya bantuan

Ini keputusan paling penting di module ini:

```text
birth_date: 2015-05-16
     │
     ▼  (Service / Alpine menghitung: umur 11)
     │
     ▼  (cocokkan ke min_age <= 11 <= max_age)
SARAN: U-12
     │
     ▼
Coach boleh TERIMA (U-12) atau TIMPA (mis. pilih U-15 untuk pemain berbakat)
     │
     ▼
players.id_player_category  ← YANG DISIMPAN adalah pilihan coach, bukan hasil hitungan
```

Sistem **tidak pernah** memaksa. Kalau coach memilih kategori yang tidak cocok dengan umur, sistem cuma **memberi tahu**, tidak menolak. Lihat [4.2](#42-kenapa-umur-tidak-divalidasi-keras-terhadap-kategori).

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_create_player_categories_table.php` | 🆕 Baru | 1 |
| `database/migrations/…_add_id_player_category_to_players_table.php` | 🆕 Baru | 1 |
| `app/Models/PlayerCategory.php` | 🆕 Baru | 2 |
| `app/Models/Player.php` | ✏️ Tambah fillable + relasi | 2 |
| `config/faos.php` | ✏️ Tambah `player_category_templates` | 3 |
| `app/Services/PlayerCategoryService.php` | 🆕 Baru | 4 |
| `app/Services/AcademyManagementService.php` | ✏️ Tambah 1 dependency + 1 baris | 5 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah 4 permission | 6 |
| `config/faos.php` | ✏️ Tambah `player_category.*` ke `role_templates` Owner | 6 |
| `app/Support/PermissionPresenter.php` | ✏️ Tambah 1 baris ke `$modules` | 6 |
| `app/Http/Requests/PlayerCategory/PlayerCategoryFormRequest.php` | 🆕 Baru | 7 |
| `app/Http/Controllers/PlayerCategoryController.php` | 🆕 Baru | 8 |
| `routes/web.php` | ✏️ Tambah resource `player-categories` | 8 |
| `resources/views/player-categories/{index,create,edit}.blade.php` | 🆕 Baru | 9 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Tambah menu | 9 |
| `app/Services/PlayerService.php` | ✏️ Simpan `id_player_category` | 10 |
| `app/Http/Requests/Players/{Store,Update}PlayerRequest.php` | ✏️ Validasi `id_player_category` | 10 |
| `app/Http/Controllers/PlayerController.php` | ✏️ Kirim `$playerCategories` + `$suggestedCategory` | 10 |
| `resources/views/players/{index,create,edit,show}.blade.php` | ✏️ Ubah | 10 |
| `database/factories/PlayerCategoryFactory.php` | 🆕 Baru | 11 |
| `tests/Feature/PlayerCategoryTest.php` | 🆕 Baru | 11 |
| `docs/permission-reference.md` | ✏️ Ubah | 12 |
| **`app/Traits/BelongsToAcademy.php`**, **`app/Scopes/AcademyScope.php`** | 🚫 **Jangan sentuh** | — |
| **`app/Models/PlayerType.php`**, **`app/Services/PlayerTypeService.php`** | 🚫 **Jangan sentuh** | — |
| **`resources/views/player-types/*`** | 🚫 **Jangan sentuh** | — |
| **`app/Http/Controllers/PlayerAccountController.php`** | 🚫 **Jangan sentuh** | — |

---

## Tahap 1 — Migration

**Tujuan**: tabel `player_categories` ada, dan `players` punya kolom `id_player_category`.

```bash
php artisan make:migration create_player_categories_table
php artisan make:migration add_id_player_category_to_players_table
```

### 1a. `…_create_player_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_categories', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_player_category')->primary();

            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            | TIDAK nullable. Tidak ada konsep "kategori system" -- setiap kategori
            | wajib milik satu academy, sama seperti player_types.
            */
            $table->uuid('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Category Information
            |--------------------------------------------------------------------------
            */
            $table->string('name', 50);

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Rentang Umur (inklusif)
            |--------------------------------------------------------------------------
            | Dipakai HANYA untuk MENYARANKAN kategori saat menambah player.
            | Bukan aturan yang memaksa: pemain berbakat boleh "main naik kelas"
            | ke kategori yang umurnya di luar rentang ini. Lihat Bagian 4.2.
            |
            | Academy bebas menentukan rentangnya sendiri lewat form.
            */
            $table->unsignedTinyInteger('min_age');
            $table->unsignedTinyInteger('max_age');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | Kategori nonaktif tidak muncul di dropdown Player baru, tapi player
            | lama yang sudah memakainya tetap utuh.
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
            | Dua academy BOLEH punya "U-12" masing-masing (dengan rentang umur
            | yang boleh berbeda pula). Satu academy TIDAK BOLEH punya dua "U-12".
            */
            $table->unique(['id_academy', 'name'], 'player_categories_academy_name_unique');

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
        Schema::dropIfExists('player_categories');
    }
};
```

> `unsignedTinyInteger` menampung 0–255 — lebih dari cukup untuk umur, dan otomatis menolak umur negatif di level database.

### 1b. `…_add_id_player_category_to_players_table.php`

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
            // Player lama (sebelum module ini) belum punya kategori.
            // Lihat Bagian 4.6.
            //
            // after('id_player_type') -> kolom itu dibuat oleh issue.md.
            // Kalau baris ini error "column not found", berarti issue.md
            // belum dikerjakan. Berhenti, selesaikan issue.md dulu.
            $table->uuid('id_player_category')
                ->nullable()
                ->after('id_player_type');

            $table->index('id_player_category');

            // nullOnDelete, BUKAN cascadeOnDelete. Lihat Bagian 4.4.
            $table->foreign('id_player_category')
                ->references('id_player_category')
                ->on('player_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['id_player_category']);
            $table->dropIndex(['id_player_category']);
            $table->dropColumn('id_player_category');
        });
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table player_categories
php artisan db:table players
```

- `player_categories` harus punya index `player_categories_academy_name_unique`.
- `players` harus punya `id_player_category` dengan FK **`on delete set null`** — kalau tertulis `cascade`, **ULANGI**.

---

## Tahap 2 — Model

### 2a. `app/Models/PlayerCategory.php` — file baru

Tiru `app/Models/PlayerType.php` (sudah ada), ganti bagian yang khas:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PlayerCategory extends FaosModel, jadi otomatis dapat:
 * - UUID primary key
 * - BelongsToAcademy (AcademyScope + isi id_academy saat creating)
 *
 * Sama seperti PlayerType, model ini MEMANG BOLEH pakai global scope --
 * larangan global scope hanya berlaku untuk App\Models\Role, karena alasan
 * cache Spatie. Lihat issue.md Bagian 4.3.
 */
class PlayerCategory extends FaosModel
{
    use HasFactory;

    protected $table = 'player_categories';
    protected $primaryKey = 'id_player_category';

    protected $fillable = [
        // id_academy wajib fillable supaya Super Admin bisa memilih academy.
        'id_academy',
        'name',
        'description',
        'min_age',
        'max_age',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'id_player_category', 'id_player_category');
    }
}
```

> `'min_age' => 'integer'` bukan hiasan. Tanpa cast, nilainya bisa terbaca sebagai **string** `"10"` di JSON yang dikirim ke Alpine, lalu `age >= "10"` di JS jadi perbandingan yang membingungkan. Cast ini yang bikin saran kategori di Tahap 10 bekerja benar.

### 2b. `app/Models/Player.php` — ubah

Tambah ke `$fillable`, **tepat di bawah `'id_player_type'`**:

```php
'id_player_category',
```

Tambah relasi di bawah relasi `playerType()`:

```php
/*
|--------------------------------------------------------------------------
| Relationship Player Category
|--------------------------------------------------------------------------
*/
public function playerCategory()
{
    return $this->belongsTo(
        PlayerCategory::class,
        'id_player_category',
        'id_player_category'
    );
}
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\Models\PlayerCategory)->getKeyName();   // "id_player_category"
in_array('id_academy', (new \App\Models\PlayerCategory)->getFillable());  // true
```

---

## Tahap 3 — Template kategori di `config/faos.php`

Tambahkan **tepat setelah** blok `'player_type_templates'`:

```php
/*
|--------------------------------------------------------------------------
| Player Category Template
|--------------------------------------------------------------------------
| Kelompok umur default yang otomatis dibuat untuk setiap academy baru.
|
| min_age & max_age bersifat INKLUSIF, dan hanya dipakai untuk MENYARANKAN
| kategori saat menambah player -- bukan aturan yang memaksa. Pemain boleh
| ditempatkan di kategori yang umurnya di luar rentang ("main naik kelas").
|
| Academy bebas menambah/mengubah kategori & rentangnya lewat menu
| Player Category. Daftar di sini hanya titik awal saat academy dibuat.
*/

'player_category_templates' => [

    'U-12' => [
        'description' => 'Kelompok umur di bawah 12 tahun.',
        'min_age' => 10,
        'max_age' => 12,
    ],

    'U-15' => [
        'description' => 'Kelompok umur di bawah 15 tahun.',
        'min_age' => 13,
        'max_age' => 15,
    ],

    'U-17' => [
        'description' => 'Kelompok umur di bawah 17 tahun.',
        'min_age' => 16,
        'max_age' => 17,
    ],

],
```

> ⚠️ **Rentang di atas adalah usulan awal yang masuk akal, BELUM dikonfirmasi ke pemilik produk.** Istilah "U-12" secara harfiah berarti *under 12* (≤ 11 tahun), tapi banyak akademi memakainya sebagai label kelompok yang berisi anak **sampai** 12 tahun. Tanyakan dulu ke pemilik produk kalau ragu — angkanya gampang diubah di config ini, dan academy juga bisa mengubahnya sendiri lewat form.

**✅ Cek dulu**

```bash
php artisan config:clear
php artisan tinker
```

```php
array_keys(config('faos.player_category_templates'));   // ["U-12","U-15","U-17"]
config('faos.player_category_templates.U-12.max_age');  // 12
```

---

## Tahap 4 — `PlayerCategoryService`

`app/Services/PlayerCategoryService.php` — **file baru**. Strukturnya meniru `PlayerTypeService`, dengan satu method tambahan yang khas module ini: `suggestFor()`.

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\PlayerCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlayerCategoryService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Daftar kategori untuk halaman index.
     *
     * Tidak perlu filter id_academy manual: PlayerCategory memakai
     * BelongsToAcademy -> AcademyScope.
     *
     * orderBy('min_age') -- BUKAN latest(). Kelompok umur harus tampil urut
     * umur (U-12, U-15, U-17), bukan urut tanggal dibuat.
     */
    public function paginate(?int $perPage = null)
    {
        return PlayerCategory::with('academy')
            ->withCount('players')
            ->orderBy('min_age')
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar kategori untuk dropdown di form Player.
     *
     * $academyId null  -> seluruh academy (Super Admin di form CREATE Player,
     *                     karena academy-nya baru dipilih di form yang sama).
     * $includeId       -> form EDIT Player: kategori yang sedang dipakai player
     *                     tetap ikut walau sudah dinonaktifkan.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return PlayerCategory::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_player_category', $includeId);
                }
            })
            ->orderBy('min_age')
            ->get();
    }

    /**
     * Saran kategori berdasarkan umur pemain.
     *
     * INI HANYA SARAN. Hasilnya dipakai untuk mengisi dropdown di form,
     * dan coach bebas menimpanya. Tidak ada satupun tempat yang boleh
     * MEMAKSA player memakai hasil method ini. Lihat Bagian 4.2.
     *
     * orderBy('min_age') WAJIB: kalau academy membuat rentang yang tumpang
     * tindih (mis. U-12 = 10-12 dan U-13 = 12-13), umur 12 cocok ke dua-duanya.
     * Tanpa orderBy, database bebas mengembalikan yang mana saja -- sarannya
     * jadi berubah-ubah tanpa sebab. Lihat Bagian 4.3.
     */
    public function suggestFor(Carbon|string|null $birthDate, string $academyId): ?PlayerCategory
    {
        if (! $birthDate) {
            return null;
        }

        $age = Carbon::parse($birthDate)->age;

        return PlayerCategory::query()
            ->where('id_academy', $academyId)
            ->where('status', true)
            ->where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->orderBy('min_age')
            ->first();
    }

    /**
     * Tentukan id_academy untuk kategori baru.
     *
     * User academy : otomatis dari academy miliknya, input form DIABAIKAN.
     * Super Admin  : dari pilihan academy di form (wajib).
     */
    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): PlayerCategory
    {
        return DB::transaction(function () use ($data) {

            return PlayerCategory::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(PlayerCategory $playerCategory, array $data): PlayerCategory
    {
        return DB::transaction(function () use ($playerCategory, $data) {

            // id_academy sengaja TIDAK ikut diubah.
            // Kategori tidak dapat berpindah academy -- player yang memakainya
            // akan ikut "pindah" secara tidak sengaja.
            $playerCategory->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'status' => $data['status'] ?? true,
            ]);

            return $playerCategory;
        });
    }

    public function delete(PlayerCategory $playerCategory): bool
    {
        return DB::transaction(function () use ($playerCategory) {

            // FK players.id_player_category memang nullOnDelete, tapi itu cuma
            // jaring pengaman terakhir. Kalau kategori dihapus begitu saja,
            // player-nya diam-diam kehilangan kelompok umur.
            // Kategori yang sudah tidak dipakai: nonaktifkan, jangan dihapus.
            if ($playerCategory->players()->exists()) {
                throw new \Exception('Kategori masih digunakan oleh player, tidak dapat dihapus. Nonaktifkan kategori ini kalau sudah tidak dipakai.');
            }

            return $playerCategory->delete();
        });
    }

    /**
     * Buat kategori default untuk academy baru dari
     * config('faos.player_category_templates').
     */
    public function createDefaultPlayerCategories(Academy $academy): void
    {
        foreach (config('faos.player_category_templates') as $name => $attributes) {

            PlayerCategory::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'min_age' => $attributes['min_age'],
                'max_age' => $attributes['max_age'],
                'status' => true,
            ]);
        }
    }
}
```

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Services\PlayerCategoryService::class)` tidak error.

---

## Tahap 5 — `AcademyManagementService`

Di `app/Services/AcademyManagementService.php`, **tambahkan** dependency ketiga (jangan hapus dua yang sudah ada):

```php
protected RoleService $roleService;
protected PlayerTypeService $playerTypeService;
protected PlayerCategoryService $playerCategoryService;

public function __construct(
    RoleService $roleService,
    PlayerTypeService $playerTypeService,
    PlayerCategoryService $playerCategoryService
) {
    $this->roleService = $roleService;
    $this->playerTypeService = $playerTypeService;
    $this->playerCategoryService = $playerCategoryService;
}
```

Di method `create()`, tambahkan **satu baris**:

```php
$academy = Academy::create($data);

$this->roleService->createDefaultRoles($academy);
$this->playerTypeService->createDefaultPlayerTypes($academy);
$this->playerCategoryService->createDefaultPlayerCategories($academy);

return $academy;
```

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Services\AcademyManagementService::class)` tidak error.

---

## Tahap 6 — Permission

### 6a. `database/seeders/RolePermissionSeeder.php`

Di array `$permissions`, tambahkan **tepat setelah blok `// Player Type`**:

```php
            // Player Category
            'player_category.view',
            'player_category.create',
            'player_category.update',
            'player_category.delete',
```

### 6b. `config/faos.php` → `role_templates`

Di template **`Owner`** saja, setelah baris `player_type.*`:

```php
'player_category.view', 'player_category.create', 'player_category.update', 'player_category.delete',
```

> Sama seperti Player Type: role lain sengaja tidak diberi. Coach/Staff **tetap bisa** memilih kategori saat menambah player tanpa permission ini — dropdown diisi Service, permission hanya menggerbang halaman `/player-categories`.

### 6c. `app/Support/PermissionPresenter.php`

Cuma **satu baris**. Di dalam array `$modules` milik `moduleLabel()` (dibuat di `issue.md`), tambahkan setelah `'player_type'`:

```php
            'player_category' => 'Player Category',
```

> Sebenarnya tanpa baris ini pun tampilannya sudah benar — `moduleLabel()` punya fallback `Str::headline()` yang mengubah `player_category` → "Player Category". Baris ini ditambahkan supaya array `$modules` tetap jadi daftar lengkap module yang dikenal sistem, bukan karena ada yang rusak.

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
php artisan tinker
```

```php
\App\Support\PermissionPresenter::label('player_category.view');
// "Lihat Player Category"

\App\Models\User::where('email','owner@faosacademy.com')->first()->can('player_category.view');
// true

\App\Models\PlayerCategory::orderBy('min_age')->pluck('name');
// ["U-12","U-15","U-17"]  <- urut umur, bukan urut dibuat
```

---

## Tahap 7 — `PlayerCategoryFormRequest`

`app/Http/Requests/PlayerCategory/PlayerCategoryFormRequest.php` — **file baru**. Tiru `PlayerTypeFormRequest`, ganti bagian `is_billable` dengan `min_age`/`max_age`:

```php
<?php

namespace App\Http\Requests\PlayerCategory;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerCategoryFormRequest extends FormRequest
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
                Rule::unique('player_categories', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('player_category')?->id_player_category, 'id_player_category'),
            ],

            'description' => ['nullable', 'string'],

            'min_age' => ['required', 'integer', 'min:0', 'max:99'],

            // gte:min_age -- WAJIB. Rentang terbalik (min 15, max 12) tidak akan
            // pernah cocok dengan umur siapa pun, dan kegagalannya diam-diam:
            // kategori itu sekadar tidak pernah tersaran, tanpa error apapun.
            'max_age' => ['required', 'integer', 'min:0', 'max:99', 'gte:min_age'],

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

            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal :max karakter.',
            'name.unique' => 'Nama kategori sudah digunakan pada academy ini.',

            'description.string' => 'Deskripsi harus berupa teks.',

            'min_age.required' => 'Umur minimal wajib diisi.',
            'min_age.integer' => 'Umur minimal harus berupa angka.',
            'min_age.min' => 'Umur minimal tidak valid.',
            'min_age.max' => 'Umur minimal maksimal :max tahun.',

            'max_age.required' => 'Umur maksimal wajib diisi.',
            'max_age.integer' => 'Umur maksimal harus berupa angka.',
            'max_age.min' => 'Umur maksimal tidak valid.',
            'max_age.max' => 'Umur maksimal maksimal :max tahun.',
            'max_age.gte' => 'Umur maksimal tidak boleh lebih kecil dari umur minimal.',

            'status.required' => 'Status wajib ditentukan.',
            'status.boolean' => 'Status tidak valid.',
        ];
    }
}
```

Catatan:
- `->ignore(..., 'id_player_category')` — parameter kedua **wajib**, primary key tabel ini bukan `id`.
- Nama parameter route dari `Route::resource('player-categories', ...)` adalah `player_category`.

---

## Tahap 8 — Controller + Route

### 8a. `app/Http/Controllers/PlayerCategoryController.php` — file baru

Tiru `PlayerTypeController` **apa adanya**, ganti seluruh `PlayerType` → `PlayerCategory`, `player-types` → `player-categories`, `playerType` → `playerCategory`, dan teks flash message:

- `'Player category berhasil ditambahkan.'`
- `'Player category berhasil diperbarui.'`
- `'Player category berhasil dihapus.'`
- `'Gagal menambahkan player category'` / `'Gagal memperbarui player category'` / `'Gagal menghapus player category'`

Judul & breadcrumb:

```php
'title' => 'Player Category',
'breadcrumb' => [
    ['label' => 'Players', 'url' => route('players.index')],
    ['label' => 'Player Category'],
],
```

> Sama seperti PlayerType: **tidak ada `$this->authorize()` dan tidak ada Policy**. Route model binding `PlayerCategory $playerCategory` sudah kena `AcademyScope` → academy lain dapat **404**. Test nomor 2 di Tahap 11 membuktikannya.

### 8b. `routes/web.php`

Tambahkan **tepat di bawah** blok `Route::resource('player-types', ...)`:

```php
    /*
    |--------------------------------------------------------------------------
    | Player Category Management
    |--------------------------------------------------------------------------
    */
    Route::resource('player-categories', PlayerCategoryController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:player_category.view')
        ->middlewareFor(['create', 'store'], 'permission:player_category.create')
        ->middlewareFor(['edit', 'update'], 'permission:player_category.update')
        ->middlewareFor('destroy', 'permission:player_category.delete');
```

Tambah import:

```php
use App\Http\Controllers\PlayerCategoryController;
```

**✅ Cek dulu**

```bash
php artisan route:list --name=player-categories
```

6 route, masing-masing dengan middleware `permission:player_category.*` yang sesuai. **Tidak boleh** ada `player-categories.show`.

---

## Tahap 9 — View Player Category

`resources/views/player-categories/`. **Tiru `resources/views/player-types/`** (sudah ada) — struktur tabel + Card List responsif wajib ikut (`docs/frontend-standard.md`).

### 9a. `index.blade.php`

Kolom: **Kategori** (nama + deskripsi) · **Academy** (Super Admin saja) · **Rentang Umur** · **Status** · **Player** · **Aksi**.

Yang khas module ini — ganti kolom "Tagihan" milik player-types dengan:

```blade
{{-- Rentang Umur --}}
<span class="table-text">
    {{ $playerCategory->min_age }}–{{ $playerCategory->max_age }} tahun
</span>
```

Sisanya (Status, Player count, tombol Aksi, tombol delete dengan `:disabled`) sama persis dengan `player-types/index.blade.php`.

### 9b. `create.blade.php` & `edit.blade.php`

Tiru `player-types/create.blade.php` & `edit.blade.php`. **Ganti** blok toggle `is_billable` dengan dua input umur:

```blade
<div class="form-row grid-cols-2">

    <div class="form-group">
        <label class="form-label">
            Umur Minimal <span class="text-error-500">*</span>
        </label>

        <input type="number" name="min_age" value="{{ old('min_age') }}" min="0" max="99"
            class="form-input @error('min_age') form-danger @enderror" required>

        @error('min_age')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label">
            Umur Maksimal <span class="text-error-500">*</span>
        </label>

        <input type="number" name="max_age" value="{{ old('max_age') }}" min="0" max="99"
            class="form-input @error('max_age') form-danger @enderror" required>

        @error('max_age')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

</div>

<p class="form-helper">
    Rentang ini dipakai untuk <strong>menyarankan</strong> kategori saat menambah player,
    berdasarkan tanggal lahirnya. Pemain tetap boleh ditempatkan di kategori yang
    umurnya di luar rentang ini.
</p>
```

Di `edit.blade.php`, nilai awalnya `old('min_age', $playerCategory->min_age)` dan `old('max_age', $playerCategory->max_age)`.

Toggle `status` tetap ada, tiru dari `player-types` (pola hidden input + Alpine — **jangan** checkbox polos, lihat catatan di `issue.md` Tahap 9b).

### 9c. Menu sidebar

`resources/views/partials/sidebar.blade.php` — di grup **Football Academy**, **setelah** menu Player Types:

```blade
{{-- Player Categories --}}
@can('player_category.view')
    <li>
        <a href="{{ route('player-categories.index') }}" class="menu-dropdown-item group"
            :class="{{ Route::is('player-categories.*') ? 'true' : 'false' }}
                ?
                'menu-dropdown-item-active' :
                'menu-dropdown-item-inactive'">
            Player Categories
        </a>
    </li>
@endcan
```

Tambahkan `'player-categories.*'` ke `$footballAcademyRoutes`:

```php
$footballAcademyRoutes = ['players.*', 'player-types.*', 'player-categories.*', 'training.*'];
```

**✅ Cek dulu**: login Super Admin → `/player-categories` menampilkan U-12, U-15, U-17 **urut umur**. Cek di DevTools 375px: berubah jadi Card List.

---

## Tahap 10 — Integrasi ke Module Player

**Tujuan**: setiap player wajib punya kategori, disarankan otomatis dari umur, tapi tetap bisa ditimpa coach.

### 10a. `StorePlayerRequest`

Tambah rule **tepat setelah** rule `id_player_type` yang sudah ada:

```php
            // Kategori WAJIB milik academy yang sama dengan player.
            // Rule::exists() TIDAK kena AcademyScope -- where('id_academy')
            // eksplisit di bawah ini yang menjaga batas antar academy.
            // Lihat Bagian 4.5.
            //
            // Sengaja TIDAK ada validasi "umur harus cocok dengan rentang
            // kategori". Itu disengaja, bukan kelupaan. Lihat Bagian 4.2.
            'id_player_category' => [
                'required',
                'uuid',
                Rule::exists('player_categories', 'id_player_category')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $academyId)
                        ->where('status', true)
                    ),
            ],
```

`messages()`:

```php
            'id_player_category.required' => 'Kategori umur wajib dipilih.',
            'id_player_category.uuid' => 'Kategori umur tidak valid.',
            'id_player_category.exists' => 'Kategori umur tidak ditemukan pada academy ini.',
```

### 10b. `UpdatePlayerRequest`

```php
            // Sengaja TIDAK memfilter status = true: player yang kategorinya
            // sudah dinonaktifkan harus tetap bisa disimpan.
            'id_player_category' => [
                'required',
                'uuid',
                Rule::exists('player_categories', 'id_player_category')
                    ->where(fn ($query) => $query
                        ->where('id_academy', $this->route('player')->id_academy)
                    ),
            ],
```

`messages()`: sama dengan 10a.

### 10c. `PlayerService`

Di `create()` dan `update()`, tambahkan **satu baris** ke array (setelah `'id_player_type'`):

```php
'id_player_category' => $data['id_player_category'],
```

### 10d. `PlayerController`

Tambah import + dependency keempat:

```php
use App\Services\PlayerCategoryService;
```

```php
public function __construct(
    PlayerService $playerService,
    AcademyService $academyService,
    PlayerTypeService $playerTypeService,
    PlayerCategoryService $playerCategoryService
) {
    // ... assign semuanya
}
```

`index()` — eager load supaya tidak N+1 (`docs/query-performance.md`):

```php
'players' => Player::with(['playerType', 'playerCategory'])->latest()->paginate(10),
```

`create()` — tambahkan:

```php
'playerCategories' => $this->playerCategoryService->selectable(
    $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId()
),
```

`edit()` — tambahkan dua hal. Perhatikan `suggestedCategory`: di form edit, `birth_date` sudah diketahui server, jadi sarannya dihitung di **Service**, bukan di JS:

```php
'playerCategories' => $this->playerCategoryService->selectable(
    $player->id_academy,
    $player->id_player_category
),

// Saran kategori untuk player ini. Berguna terutama untuk player lama yang
// id_player_category-nya masih NULL.
'suggestedCategory' => $this->playerCategoryService->suggestFor(
    $player->birth_date,
    $player->id_academy
),
```

`show()` — tambahkan `'playerCategory'` ke `load()`:

```php
$player->load([
    'academy',
    'playerType',
    'playerCategory',
    'user.roles'
]);
```

### 10e. `players/create.blade.php` — bagian tersulit

`issue.md` membuat blok `x-data` pada sebuah `<div>` yang membungkus Academy + Type, plus blok `@else` terpisah untuk user academy. **Blok itu diganti total** di sini, karena sekarang saran kategori butuh `birth_date` yang letaknya di luar `<div>` tersebut.

**Langkah 1** — pindahkan `x-data` ke tag `<form>`, dan **hapus** `x-data` lama yang ada di `<div>` pembungkus Academy+Type:

```blade
<form action="{{ route('players.store') }}" method="POST" enctype="multipart/form-data"
    x-data="{
        isSuperAdmin: @js($isSuperAdmin),
        academyId: @js(old('id_academy', '')),
        birthDate: @js(old('birth_date', '')),
        playerTypeId: @js(old('id_player_type', '')),
        playerCategoryId: @js(old('id_player_category', '')),
        types: @js($playerTypes),
        categories: @js($playerCategories),

        // Super Admin: saring sesuai academy yang dipilih di form ini.
        // User academy: Controller sudah menyaringnya, pakai apa adanya.
        get availableTypes() {
            return this.isSuperAdmin
                ? this.types.filter(type => type.id_academy === this.academyId)
                : this.types;
        },

        get availableCategories() {
            return this.isSuperAdmin
                ? this.categories.filter(category => category.id_academy === this.academyId)
                : this.categories;
        },

        get age() {
            if (! this.birthDate) return null;
            const birth = new Date(this.birthDate);
            if (isNaN(birth)) return null;
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        },

        get suggestedCategory() {
            if (this.age === null) return null;
            return this.availableCategories.find(
                category => this.age >= category.min_age && this.age <= category.max_age
            ) ?? null;
        },

        get selectedCategory() {
            return this.categories.find(
                category => category.id_player_category === this.playerCategoryId
            ) ?? null;
        },

        // Peringatan lunak, TIDAK memblokir simpan. Lihat Bagian 4.2.
        get ageOutsideRange() {
            const category = this.selectedCategory;
            if (! category || this.age === null) return false;
            return this.age < category.min_age || this.age > category.max_age;
        },

        applySuggestion() {
            this.playerCategoryId = this.suggestedCategory
                ? this.suggestedCategory.id_player_category
                : '';
        },
    }">
```

**Langkah 2** — input `birth_date` yang sudah ada, tambahkan `x-model` + auto-saran. Sarannya **hanya** terpasang otomatis kalau coach belum memilih apa pun, supaya pilihan manualnya tidak tertimpa saat ia membetulkan tanggal lahir:

```blade
<input type="date" name="birth_date" x-model="birthDate"
    @change="if (! playerCategoryId) applySuggestion()"
    class="form-input @error('birth_date') form-danger @enderror">
```

**Langkah 3** — dropdown Academy (Super Admin saja). Reset Type **dan** Kategori saat academy berganti:

```blade
@if ($isSuperAdmin)
    <div class="form-group">
        <label class="form-label">
            Academy <span class="text-error-500">*</span>
        </label>

        <select name="id_academy" x-model="academyId"
            @change="playerTypeId = ''; playerCategoryId = ''"
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
@endif
```

**Langkah 4** — dropdown Type. Sekarang **satu blok untuk semua** (tidak ada lagi `@if/@else` seperti di `issue.md`), karena `availableTypes` sudah menangani kedua kasus:

```blade
<div class="form-group">
    <label class="form-label">
        Type Player <span class="text-error-500">*</span>
    </label>

    <select name="id_player_type" x-model="playerTypeId"
        class="form-select @error('id_player_type') form-danger @enderror" required>
        <option value="">Pilih Type Player</option>
        <template x-for="type in availableTypes" :key="type.id_player_type">
            <option :value="type.id_player_type" x-text="type.name"></option>
        </template>
    </select>

    <p x-show="isSuperAdmin && academyId && availableTypes.length === 0" x-cloak class="form-error">
        Academy ini belum punya type player. Buat dulu lewat menu Player Type.
    </p>

    @error('id_player_type')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

**Langkah 5** — dropdown Kategori, lengkap dengan saran dan peringatan lunak:

```blade
<div class="form-group">
    <label class="form-label">
        Kategori Umur <span class="text-error-500">*</span>
    </label>

    <select name="id_player_category" x-model="playerCategoryId"
        class="form-select @error('id_player_category') form-danger @enderror" required>
        <option value="">Pilih Kategori Umur</option>
        <template x-for="category in availableCategories" :key="category.id_player_category">
            <option :value="category.id_player_category"
                x-text="`${category.name} (${category.min_age}-${category.max_age} th)`"></option>
        </template>
    </select>

    {{-- Saran: tampil hanya kalau ada saran DAN belum dipilih --}}
    <p x-show="suggestedCategory && suggestedCategory.id_player_category !== playerCategoryId"
        x-cloak class="form-helper">
        Saran untuk umur <span x-text="age"></span> tahun:
        <button type="button" class="link-primary font-medium" @click="applySuggestion()">
            <span x-text="suggestedCategory?.name"></span> — pakai saran ini
        </button>
    </p>

    {{-- Peringatan LUNAK: memberi tahu, tidak memblokir. Lihat Bagian 4.2. --}}
    <p x-show="ageOutsideRange" x-cloak class="form-helper text-warning-500">
        Umur pemain (<span x-text="age"></span> th) di luar rentang kategori ini
        (<span x-text="selectedCategory?.min_age"></span>–<span x-text="selectedCategory?.max_age"></span> th).
        Ini diperbolehkan — pastikan memang disengaja.
    </p>

    <p x-show="isSuperAdmin && academyId && availableCategories.length === 0" x-cloak class="form-error">
        Academy ini belum punya kategori umur. Buat dulu lewat menu Player Category.
    </p>

    @error('id_player_category')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

> **Kenapa `@js($playerCategories)` cukup, tanpa mapping manual?** Collection Eloquent ter-serialize jadi JSON lengkap dengan `id_player_category`, `id_academy`, `name`, `min_age`, `max_age`. Cast `'min_age' => 'integer'` di Tahap 2 yang memastikan nilainya angka, bukan string — tanpa itu, `age >= category.min_age` di JS jadi perbandingan string yang menyesatkan.
>
> **Jangan** melakukan mapping/filter di Blade dengan `@php` — itu business logic di Blade (`docs/development-guide.md`).

### 10f. `players/edit.blade.php`

Jauh lebih sederhana — academy player tidak berubah, dan sarannya sudah dihitung server. Tambahkan **setelah** dropdown Type Player yang sudah ada:

```blade
<div class="form-group">
    <label class="form-label">
        Kategori Umur <span class="text-error-500">*</span>
    </label>

    <select name="id_player_category" class="form-select @error('id_player_category') form-danger @enderror" required>
        <option value="">Pilih Kategori Umur</option>
        @foreach ($playerCategories as $category)
            <option value="{{ $category->id_player_category }}" @selected(old('id_player_category', $player->id_player_category) === $category->id_player_category)>
                {{ $category->name }} ({{ $category->min_age }}-{{ $category->max_age }} th)@unless ($category->status) — nonaktif @endunless
            </option>
        @endforeach
    </select>

    @if ($suggestedCategory && $suggestedCategory->id_player_category !== $player->id_player_category)
        <p class="form-helper">
            Saran berdasarkan umur pemain: <strong>{{ $suggestedCategory->name }}</strong>
        </p>
    @endif

    @error('id_player_category')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

### 10g. `players/index.blade.php`

Tambah kolom **Kategori** setelah kolom **Type**, di **kedua** representasi.

`<thead>`:

```blade
<th class="table-header-cell">Kategori</th>
```

`<tbody>`:

```blade
<td class="table-cell">
    @if ($player->playerCategory)
        <span class="badge badge-secondary">{{ $player->playerCategory->name }}</span>
    @else
        <span class="table-subtitle">-</span>
    @endif
</td>
```

Card List — tambah field di `table-card-body`:

```blade
<div class="table-card-field">
    <span class="table-card-label">Kategori</span>
    @if ($player->playerCategory)
        <span class="badge badge-secondary w-fit">{{ $player->playerCategory->name }}</span>
    @else
        <span class="table-subtitle">-</span>
    @endif
</div>
```

> `@if ($player->playerCategory)` **wajib** — player lama `id_player_category`-nya `NULL`.

### 10h. `players/show.blade.php`

Di panel **Informasi Academy**, setelah blok "Type Player":

```blade
<div>
    <span class="mb-1 block text-xs text-gray-400">
        Kategori Umur
    </span>

    @if ($player->playerCategory)
        <span class="badge badge-secondary">
            {{ $player->playerCategory->name }}
            ({{ $player->playerCategory->min_age }}-{{ $player->playerCategory->max_age }} th)
        </span>
    @else
        <span class="table-text">-</span>
    @endif
</div>
```

**✅ Cek dulu**

1. Login Owner → `/players/create` → isi Tanggal Lahir dengan tanggal ~11 tahun lalu → dropdown **Kategori Umur** otomatis terisi **U-12**.
2. Ganti kategori manual ke **U-17** → muncul peringatan kuning "di luar rentang", tapi **tetap bisa disimpan**. Kalau ditolak, ULANGI — lihat Bagian 4.2.
3. Login Super Admin → `/players/create` → ganti Academy → dropdown Type **dan** Kategori ikut berubah.
4. `/players` → kolom Kategori tampil, cek juga di 375px.

---

## Tahap 11 — Test

### 11a. `database/factories/PlayerCategoryFactory.php` — file baru

```php
<?php

namespace Database\Factories;

use App\Models\PlayerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerCategory>
 */
class PlayerCategoryFactory extends Factory
{
    protected $model = PlayerCategory::class;

    public function definition(): array
    {
        return [
            'name' => 'U-' . fake()->unique()->numberBetween(8, 21),
            'description' => fake()->sentence(),
            'min_age' => 10,
            'max_age' => 12,
            'status' => true,
        ];
    }
}
```

> `id_academy` sengaja **tidak** diisi factory — wajib di-pass eksplisit tiap test.

### 11b. `tests/Feature/PlayerCategoryTest.php` — file baru

Tiru struktur `tests/Feature/PlayerTypeTest.php` (sudah ada). Wajib menulis **9 skenario**:

| # | Skenario | Assert kunci |
|---|----------|--------------|
| 1 | Dua academy boleh punya kategori dengan nama sama | "U-12" di A & B → `assertSame(2, ...)` tanpa exception |
| 2 | Isolasi URL | `actingAs($ownerA)->get(route('player-categories.edit', $categoryB))` → `assertNotFound()` (**404**, bukan 403) |
| 3 | Kategori yang dipakai player tidak bisa dihapus | `PlayerCategoryService::delete()` → `expectException(\Exception::class)` |
| 4 | **Create player dengan kategori academy lain ditolak** | `assertSessionHasErrors('id_player_category')` + player **tidak** tercipta |
| 5 | Academy baru dapat 3 kategori default lengkap `min_age`/`max_age` | Bandingkan dengan `config('faos.player_category_templates')` |
| 6 | `suggestFor()` benar sesuai umur, dan `null` kalau tidak ada yang cocok | Umur 11 → U-12; umur 30 → `assertNull()` |
| 7 | `suggestFor()` deterministik saat rentang tumpang tindih | Dua kategori yang sama-sama memuat umur 12 → yang `min_age` terkecil yang menang, **konsisten** tiap dipanggil |
| 8 | **"Main naik kelas" TIDAK ditolak** | Player umur 11 disimpan ke kategori U-17 (16–17) → `assertSessionHasNoErrors()` + player tercipta |
| 9 | `max_age < min_age` ditolak | POST kategori dengan min 15 / max 12 → `assertSessionHasErrors('max_age')` |

Kerangka untuk skenario 6, 7, 8 (sisanya tiru `PlayerTypeTest`):

```php
    /**
     * Saran kategori dari umur -- ini fitur khas module ini.
     */
    public function test_suggest_for_mengembalikan_kategori_sesuai_umur(): void
    {
        $academy = Academy::factory()->create();

        $u12 = PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-12', 'min_age' => 10, 'max_age' => 12,
        ]);

        PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-15', 'min_age' => 13, 'max_age' => 15,
        ]);

        $service = app(\App\Services\PlayerCategoryService::class);

        // Umur 11 -> U-12
        $birthDate = Carbon::now()->subYears(11)->subMonths(2);
        $this->assertSame(
            $u12->id_player_category,
            $service->suggestFor($birthDate, $academy->id_academy)?->id_player_category
        );

        // Umur 30 -> tidak ada yang cocok
        $this->assertNull(
            $service->suggestFor(Carbon::now()->subYears(30), $academy->id_academy)
        );

        // Tanpa tanggal lahir -> tidak menebak
        $this->assertNull($service->suggestFor(null, $academy->id_academy));
    }

    /**
     * Mengunci orderBy('min_age') di suggestFor(). Lihat Bagian 4.3.
     */
    public function test_suggest_for_deterministik_saat_rentang_tumpang_tindih(): void
    {
        $academy = Academy::factory()->create();

        // Sengaja dibuat TERBALIK urutan insert-nya, supaya kalau orderBy
        // hilang, test ini gampang merah.
        PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-13', 'min_age' => 12, 'max_age' => 13,
        ]);

        $u12 = PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-12', 'min_age' => 10, 'max_age' => 12,
        ]);

        $service = app(\App\Services\PlayerCategoryService::class);
        $birthDate = Carbon::now()->subYears(12)->subMonths(1);   // umur 12 -> cocok DUA-DUANYA

        // min_age terkecil yang menang, dan hasilnya konsisten tiap dipanggil.
        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(
                $u12->id_player_category,
                $service->suggestFor($birthDate, $academy->id_academy)?->id_player_category
            );
        }
    }

    /**
     * INI MENGUNCI KEPUTUSAN PALING PENTING DI MODULE INI.
     * Pemain berbakat boleh "main naik kelas". Lihat Bagian 4.2.
     */
    public function test_player_boleh_ditempatkan_di_kategori_di_luar_umurnya(): void
    {
        $academy = Academy::factory()->create();

        $type = PlayerType::factory()->create(['id_academy' => $academy->id_academy]);

        $u17 = PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-17', 'min_age' => 16, 'max_age' => 17,
        ]);

        $owner = $this->makeUser($academy, ['player.view', 'player.create']);

        $this->actingAs($owner)
            ->post(route('players.store'), [
                'id_player_type' => $type->id_player_type,
                'id_player_category' => $u17->id_player_category,   // umur 11 -> kategori U-17
                'name' => 'Pemain Berbakat',
                'birth_date' => Carbon::now()->subYears(11)->format('Y-m-d'),
                'gender' => 'male',
                'primary_position' => 'ST',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('players.index'));

        $this->assertSame(
            $u17->id_player_category,
            Player::withoutGlobalScopes()->where('name', 'Pemain Berbakat')->first()->id_player_category
        );
    }
```

**✅ Cek dulu**

```bash
php artisan test --filter=PlayerCategoryTest
php artisan test --filter=PlayerTypeTest
php artisan test --filter=RoleAcademyTest
```

Ketiganya harus hijau. `PlayerTypeTest` & `RoleAcademyTest` wajib ikut dijalankan karena Tahap 5 & 10 menyentuh `AcademyManagementService`, `PlayerService`, dan form Player yang dipakai bersama.

> Catatan: `php artisan test` polos akan menunjukkan **7 test Breeze lama yang memang sudah merah sejak sebelum brief ini** (`AuthenticationTest`, `RegistrationTest`, `ExampleTest`, `ProfileTest`, `EmailVerificationTest`). Itu bukan ulahmu — penyebabnya `User` memakai primary key `id_user` + SoftDeletes yang bentrok dengan asumsi test bawaan Breeze. Jangan diperbaiki di brief ini.

---

## Tahap 12 — Update `docs/`

`docs/permission-reference.md`:

1. Tambah section **Module: Player Category** (tiru format section "Module: Player Type"), status **✅ Implemented**:

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `player_category.view` | Lihat daftar kategori umur | `player-categories.index` + menu sidebar |
| `player_category.create` | Tambah kategori baru | `player-categories.create`, `player-categories.store` |
| `player_category.update` | Ubah nama/rentang umur/status | `player-categories.edit`, `player-categories.update` |
| `player_category.delete` | Hapus kategori (kalau tidak dipakai player) | `player-categories.destroy` |

Sertakan catatan:

- Isolasi antar academy memakai `AcademyScope`, **bukan** Policy → akses academy lain menghasilkan **404**.
- `player_category.view` **tidak** dibutuhkan untuk memilih kategori saat menambah Player.
- `min_age`/`max_age` **hanya untuk menyarankan** kategori dari `birth_date`. Sistem **tidak pernah menolak** player yang umurnya di luar rentang — "main naik kelas" adalah hal normal di sepak bola.

2. Di section **Module: Player**, tambahkan bahwa `players.id_player_category` wajib diisi saat create.

3. Di tabel **Role Template Default per Academy Baru**, tambahkan `player_category.*` → hanya **Owner**.

---

## 4. Kenapa Begini? (alasan teknis)

### 4.1 Kenapa kategori DISIMPAN, bukan dihitung terus dari `birth_date`

Menghitung kategori on-the-fly (`umur → cocokkan rentang`) kelihatannya lebih "pintar": tidak pernah basi, tidak perlu kolom. Tapi konsekuensinya berat:

- **Kategori berubah sendiri saat ulang tahun.** Anak yang latihan di U-12 tiba-tiba pindah U-15 di tengah musim, hanya karena hari ini ulang tahunnya. Roster latihan berubah tanpa ada yang mengubahnya.
- **"Main naik kelas" jadi mustahil.** Pemain berbakat umur 11 yang ikut U-15 tidak bisa direpresentasikan sama sekali.
- **Riwayat hilang.** "Latihan bulan lalu dia ikut kelompok apa?" tidak akan pernah bisa dijawab.
- **Query jadi mahal.** Module Template Latihan nanti butuh "semua player U-12" — dengan kolom FK itu index lookup biasa; tanpa kolom, jadi `whereBetween` pada hasil hitungan tanggal yang tidak bisa di-index.

Dengan menyimpan pilihan: umur cuma **menyarankan**, manusia yang **memutuskan**, dan keputusannya stabil sampai ada yang sengaja mengubahnya.

### 4.2 Kenapa umur TIDAK divalidasi keras terhadap kategori

Godaan terbesar saat mengerjakan brief ini adalah menambahkan validasi:

```php
// ❌ JANGAN PERNAH
'id_player_category' => [..., new AgeMatchesCategory($birthDate)],
```

Di sepak bola, **memainkan pemain di kelompok umur lebih tua adalah praktik normal dan penting** — namanya "playing up". Pemain berbakat umur 11 rutin dilatih bersama U-15 supaya berkembang. Akademi yang serius pasti melakukannya.

Kalau sistem menolak itu, yang terjadi bukan "data jadi rapi", tapi:
- Coach mengarang tanggal lahir supaya lolos validasi → **data lahir jadi sampah**, dan itu jauh lebih merusak daripada kategori yang "tidak cocok umur".
- Atau coach berhenti memakai sistem untuk kasus itu, dan datanya hilang sama sekali.

Karena itu: `min_age`/`max_age` hanya menyarankan, dan UI cuma **memberi tahu** ("umur di luar rentang, pastikan disengaja"). Test nomor 8 di Tahap 11 mengunci perilaku ini supaya tidak ada yang "memperbaikinya" jadi validasi keras di kemudian hari.

### 4.3 Kenapa saran kategori wajib `orderBy`

Academy bebas menentukan rentang, dan tidak ada yang melarang rentangnya tumpang tindih:

```text
U-12 : 10 - 12
U-13 : 12 - 13     ← umur 12 cocok ke DUA-DUANYA
```

Tanpa `orderBy`, SQL **tidak menjamin** baris mana yang dikembalikan `first()`. Hasilnya: saran untuk anak umur 12 bisa "U-12" hari ini dan "U-13" besok, tanpa ada data yang berubah. Bug seperti ini nyaris mustahil dilacak karena tidak bisa direproduksi.

`orderBy('min_age')` membuatnya deterministik: yang rentangnya mulai paling awal yang menang. Test nomor 7 menguncinya.

> Rentang tumpang tindih sengaja **tidak** dilarang — ada academy yang memang butuh (mis. U-13 dan U-15 yang sengaja beririsan). Yang kita jamin cuma: sarannya konsisten.

### 4.4 `cascadeOnDelete` = hapus kategori, hapus semua playernya

Persis seperti di `issue.md` Bagian 4.1. Kalau FK ditulis `cascadeOnDelete`, satu klik "Hapus kategori U-12" akan **menghapus seluruh player U-12**. `nullOnDelete` memastikan yang hilang cuma label kategorinya. Guard di `PlayerCategoryService::delete()` mencegahnya sejak awal.

### 4.5 `Rule::exists` tidak kena `AcademyScope`

Persis seperti di `issue.md` Bagian 4.2. `AcademyScope` adalah Eloquent global scope; `Rule::exists()` memakai query builder mentah, jadi scope-nya **tidak ikut**. Tanpa `where('id_academy', ...)` eksplisit, Owner Academy A bisa memasang kategori milik Academy B lewat POST karangan, dan validasi **lolos tanpa error**. Test nomor 4 menguncinya.

### 4.6 Kenapa `id_player_category` nullable di DB tapi `required` di Form Request

Persis seperti `id_player_type` di `issue.md` Bagian 4.5. Kolom `NOT NULL` akan membuat migration gagal di database yang sudah punya player, atau memaksa migration menebak-nebak kategori mereka.

Dengan nullable + `required` di Form Request: migration mulus, player baru wajib punya kategori, player lama tampil "-" dan otomatis dipaksa memilih saat pertama kali di-edit (dibantu `$suggestedCategory` di form edit).

Yang wajib diingat: **relasi `playerCategory` bisa `null`** — setiap Blade yang mengaksesnya wajib dijaga `@if`.

### 4.7 Kenapa umur dihitung di dua tempat (Service dan Alpine)?

Ini duplikasi yang **disengaja**, bukan kelalaian:

| Tempat | Dipakai di | Kenapa tidak bisa yang satunya |
|--------|-----------|-------------------------------|
| `PlayerCategoryService::suggestFor()` (PHP) | Form **edit**, dan module Template Latihan nanti | Ini versi yang **diuji** (test 6 & 7) dan jadi acuan |
| Alpine `get age()` / `get suggestedCategory()` (JS) | Form **create** | Server belum tahu `birth_date`-nya — user baru mengetiknya di form yang sama, tanpa reload |

Yang penting: **aturannya sendiri tidak diduplikasi**. Aturan "umur berapa masuk kategori mana" hidup di kolom `min_age`/`max_age` di database. PHP dan JS sama-sama cuma **mencocokkan** ke data yang sama. Kalau academy mengubah rentangnya, dua-duanya otomatis ikut — tidak ada yang perlu diubah di kode.

---

## 5. Keputusan Arsitektur

Keputusan berikut sudah dibahas dengan pemilik produk. Kalau mau mengubahnya, **diskusikan dulu**.

| Pilihan | Keputusan | Alasan |
|---------|-----------|--------|
| Nama module | **Player Category** | Nama ini sengaja **disisakan** untuk kelompok umur saat module Player Type dibuat, justru karena "category" di akademi bola umumnya berarti kelompok umur. |
| Cara penentuan kategori | **Disimpan + disarankan otomatis, boleh ditimpa** | Menghitung terus dari `birth_date` bikin kategori berubah saat ulang tahun & menghapus kemungkinan "main naik kelas" (4.1). |
| Dasar rentang | **Umur saat ini (`min_age`/`max_age`)** | Project belum punya konsep "musim". Alternatifnya (tahun lahir, standar kompetisi resmi) mengharuskan angka digeser manual tiap musim. Bisa ditinjau ulang kalau module Season/Musim dibuat. |
| Validasi umur vs kategori | **Tidak ada validasi keras**, cuma peringatan lunak | "Main naik kelas" itu normal. Validasi keras membuat coach mengarang tanggal lahir — merusak data yang jauh lebih penting (4.2). |
| Rentang tumpang tindih | **Diizinkan**, tapi sarannya deterministik (`orderBy('min_age')`) | Ada academy yang memang butuh rentang beririsan. Yang dijamin cuma konsistensi saran (4.3). |
| Urutan tampil | **`orderBy('min_age')`**, bukan `latest()` | Kelompok umur punya urutan alami (U-12 → U-17). Urut tanggal dibuat tidak berarti apa-apa bagi user. |
| Isolasi antar academy | **`AcademyScope`**, tanpa Policy | Sama seperti PlayerType. Larangan global scope hanya berlaku untuk `Role` (cache Spatie). |
| Pindah academy | **Tidak bisa**, baik kategori maupun player | Konsisten dengan Role & PlayerType. |
| Halaman `show` kategori | **Tidak dibuat** | Seluruh info muat di index. Konsisten dengan PlayerType. |
| Fitur "naik kelas massal" tiap tahun | **Di luar cakupan brief ini** | Butuh konsep musim/angkatan. Diskusikan terpisah saat dibutuhkan. |

---

## 6. Definition of Done

- [ ] `php artisan migrate:fresh --seed` bersih tanpa error.
- [ ] FK `players.id_player_category` = **`on delete set null`**, bukan cascade.
- [ ] Tabel `player_categories` punya unique `(id_academy, name)`.
- [ ] Dua academy bisa punya "U-12" masing-masing, dengan rentang umur yang boleh berbeda.
- [ ] Owner hanya melihat & mengelola kategori academy sendiri; akses lintas academy → **404**.
- [ ] Super Admin melihat seluruh kategori dan wajib memilih academy saat create.
- [ ] Academy baru otomatis dapat U-12/U-15/U-17 lengkap dengan `min_age`/`max_age`-nya.
- [ ] Daftar kategori tampil **urut umur**, bukan urut tanggal dibuat.
- [ ] Isi tanggal lahir di form create Player → kategori otomatis tersaran, dan **bisa ditimpa**.
- [ ] **Player umur 11 bisa disimpan ke kategori U-17** (cuma diberi peringatan, tidak ditolak) — terbukti lewat test.
- [ ] `suggestFor()` deterministik saat rentang tumpang tindih — terbukti lewat test.
- [ ] `max_age < min_age` ditolak validasi.
- [ ] Kategori yang masih dipakai player tidak bisa dihapus.
- [ ] Player lama (`id_player_category` = NULL) tidak bikin error 500 di index/show.
- [ ] `php artisan test --filter=PlayerCategoryTest`, `--filter=PlayerTypeTest`, `--filter=RoleAcademyTest` hijau.
- [ ] Controller tetap tipis, business logic di Service, validasi di Form Request.
- [ ] Pesan user-facing Bahasa Indonesia, hardcoded, tanpa folder `lang/`.
- [ ] Halaman index punya Card List responsif; sudah dicek di 375px / tablet / desktop.
- [ ] `docs/permission-reference.md` sudah diperbarui.

---

## 7. Urutan Commit

| # | Isi | Tahap |
|---|-----|-------|
| 1 | Migration `player_categories` + kolom `id_player_category` | 1 |
| 2 | Model `PlayerCategory` + relasi di `Player` + `player_category_templates` | 2, 3 |
| 3 | `PlayerCategoryService` + `AcademyManagementService` | 4, 5 |
| 4 | Permission (seeder, `role_templates`, `PermissionPresenter`) | 6 |
| 5 | `PlayerCategoryFormRequest` + Controller + Route + View + menu | 7, 8, 9 |
| 6 | Integrasi ke module Player (termasuk saran kategori di form) | 10 |
| 7 | Factory + Test | 11 |
| 8 | Update `docs/` | 12 |

Kalau perilaku terasa aneh saat manual testing:

```bash
php artisan permission:cache-reset
php artisan config:clear
php artisan view:clear
```

---

## 8. Hasil Akhir

Setelah module ini, setiap academy punya kelompok umurnya sendiri dengan rentang yang mereka tentukan sendiri. Setiap player punya kategori yang **disarankan sistem dari tanggal lahirnya**, tapi **diputuskan coach** — termasuk menaikkan pemain berbakat ke kelompok yang lebih tua.

Ini fondasi langsung untuk module **Template Latihan**: coach tinggal membuat template per kategori, dan "siapa saja pesertanya" cukup satu lookup FK (`players.id_player_category`), bukan hitung-hitungan umur yang berubah tiap hari.

Bersama Player Type (`issue.md`), Player sekarang punya dua dimensi pengelompokan yang saling tegak lurus dan keduanya per-academy:

```text
                 U-12          U-15          U-17
  Reguler      ditagih       ditagih       ditagih
  Beasiswa       —             —             —
  Trial          —             —             —
```

Module Payment memakai sumbu **Type** (`is_billable`), module Template Latihan memakai sumbu **Category**. Keduanya tidak saling mengganggu.
