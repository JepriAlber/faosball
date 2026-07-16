# Brief: Module Player Position (Master Posisi Pemain — Data Global)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu. Terutama `docs/multi-tenancy.md`, `docs/permission-reference.md`, `docs/development-guide.md`, dan `docs/frontend-standard.md`.
>
> ## ⚠️ Urutan pengerjaan
>
> Brief ini **tidak punya ketergantungan teknis** ke `issue.md` (Player Type) maupun `issue2.md` (Player Category) — migration-nya sengaja tidak memakai `->after()` supaya bisa jalan sendiri.
>
> **Tapi jangan dikerjakan paralel dengan keduanya.** Tahap 10 menyentuh file yang sama persis: `PlayerController`, `StorePlayerRequest`, `UpdatePlayerRequest`, `PlayerService`, dan `players/{create,edit,index,show}.blade.php`. Kalau dikerjakan bersamaan, konflik merge-nya pasti dan melelahkan.
>
> **Urutan yang disarankan**: `issue.md` → `issue2.md` → `issue3.md` (ini).

> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 12 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> Kalau cuma mau eksekusi, cukup baca Bagian 0–3 lalu langsung ke Tahap 1. Bagian 4 (alasan teknis) boleh dibaca belakangan, **tapi aturannya tetap tidak boleh dilanggar**.

---

## 0. Aturan Emas

Sebelas larangan ini bukan preferensi gaya. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| **Memakai `BelongsToAcademy` / `AcademyScope` / kolom `id_academy`** | Module ini **GLOBAL**, bukan tenant. Beda total dengan PlayerType & PlayerCategory. | [4.1](#41-kenapa-module-ini-global-padahal-namanya-mirip-playertype--playercategory) |
| **`extends FaosModel`** | `FaosModel` memaksa `BelongsToAcademy`. Pakai `extends Model` biasa. | [4.1](#41-kenapa-module-ini-global-padahal-namanya-mirip-playertype--playercategory) |
| Cek pemakaian posisi **hanya** lewat `primaryPlayers()` | Posisi bisa dipakai sebagai **posisi kedua** juga → lolos terhapus | [4.2](#42-posisi-dipakai-di-dua-kolom--cek-dua-duanya) |
| `withCount`/guard hapus **tanpa** `withoutGlobalScopes()` | `Player` punya `AcademyScope` → hitungan cuma lihat academy si pemanggil | [4.3](#43-kenapa-withoutglobalscopes-wajib-di-hitungan--guard-hapus) |
| `unique(['id_academy', 'name'])` | Tidak ada `id_academy` di sini. Unique-nya **global** pada `code` & `name`. | [Tahap 1](#tahap-1--migration) |
| `Rule::exists(...)->where('id_academy', ...)` di validasi Player | Kolomnya tidak ada → SQL error. Posisi berlaku lintas academy. | [4.1](#41-kenapa-module-ini-global-padahal-namanya-mirip-playertype--playercategory) |
| `cascadeOnDelete` pada FK `players.id_primary_position` | Hapus 1 posisi → **seluruh player** posisi itu ikut terhapus, di **semua** academy | [4.4](#44-cascadeondelete--hapus-posisi-hapus-semua-playernya) |
| Menambahkan `player_position.*` ke `role_templates` | CRUD-nya **khusus Super Admin** | [Tahap 5](#tahap-5--permission) |
| Menamai kolom `group` | `GROUP` adalah reserved word SQL | [Tahap 1](#tahap-1--migration) |
| Bikin Policy / template per-academy di `config/faos.php` | Tidak relevan untuk data global | [5](#5-keputusan-arsitektur) |
| Membuat folder `lang/` | Pesan Indonesia di-hardcode | `docs/coding-standard.md` |

---

## 1. Tujuan

Saat ini posisi pemain adalah **teks bebas**:

```php
// database/migrations/..._create_players_table.php  (SEKARANG)
$table->string('primary_position', 20);               // "Striker"? "striker"? "ST"? "Penyerang"?
$table->string('secondary_position', 20)->nullable();
```

```blade
{{-- players/create.blade.php (SEKARANG) --}}
<input type="text" name="primary_position" placeholder="Contoh: Forward, Midfielder">
```

Akibatnya: satu akademi menulis "Striker", akademi lain "ST", coach lain "Penyerang" — tiga nilai untuk hal yang sama. Laporan, filter, dan penyusunan tim jadi mustahil dibuat.

Yang kita tuju: **satu master posisi global** yang dipakai bersama seluruh akademi.

```text
                 MASTER POSISI (GLOBAL — 1 tabel untuk semua academy)
                 ├── GK   Goalkeeper          [Goalkeeper]
                 ├── CB   Center Back         [Defender]
                 ├── CM   Center Midfielder   [Midfielder]
                 └── ST   Striker             [Forward]
                        ▲              ▲                ▲
                        │              │                │
                 Academy A       Academy B        Academy C
                  (pakai)         (pakai)          (pakai)

                 CRUD-nya: HANYA Super Admin
```

**Aturan inti**: Data posisi **dibaca semua academy**, tapi **hanya Super Admin yang boleh menambah/mengubah/menghapusnya**.

---

## 2. Cara Kerja Solusi

Baca bagian ini sampai paham. **Kalau kamu baru selesai mengerjakan `issue.md` / `issue2.md`, bagian ini yang paling penting** — karena namanya mirip, tapi sifatnya kebalikan.

### 2a. ⛔ Ini GLOBAL, bukan tenant — beda total dengan PlayerType & PlayerCategory

| Hal | PlayerType & PlayerCategory | **PlayerPosition (module ini)** |
|-----|----------------------------|--------------------------------|
| Sifat data | **Tenant** — tiap academy punya sendiri | **Global** — satu daftar untuk semua |
| Kolom `id_academy` | ✅ Ada, wajib | ❌ **TIDAK ADA** |
| `extends` | `FaosModel` (bawa `BelongsToAcademy`) | **`Model` biasa** |
| `AcademyScope` | ✅ Otomatis dari trait | ❌ **Tidak ada** |
| Unique | `(id_academy, name)` — composite | **`code` & `name`** — global, masing-masing |
| Isolasi academy lain | 404 lewat global scope | **Tidak relevan** — semua boleh baca |
| Siapa yang CRUD | Owner academy masing-masing | **Hanya Super Admin** |
| Default academy baru | Template di `config/faos.php` | ❌ **Tidak ada** — pakai Seeder global |
| Butuh `AcademyManagementService`? | ✅ Ya | ❌ **Tidak** |
| Menu sidebar | Football Academy | **Administration → Master** |

> **Bahaya terbesar di brief ini**: kamu (atau AI) mengerjakan ini setelah dua module tenant yang polanya sudah hafal, lalu refleks menambahkan `id_academy` + `BelongsToAcademy` "karena begitu polanya". Jangan. Kalau tanganmu mulai mengetik `use BelongsToAcademy`, berhenti dan baca [4.1](#41-kenapa-module-ini-global-padahal-namanya-mirip-playertype--playercategory).

### 2b. Bentuk datanya

Tabel `player_positions`:

| code | name | position_group | sort_order | status |
|------|------|----------------|-----------|--------|
| GK | Goalkeeper | Goalkeeper | 1 | true |
| CB | Center Back | Defender | 11 | true |
| CM | Center Midfielder | Midfielder | 21 | true |
| ST | Striker | Forward | 34 | true |

- **`code`** — singkatan standar sepak bola (GK, CB, ST). Dipakai di kolom tabel Player yang sempit.
- **`position_group`** — pengelompokan (Goalkeeper / Defender / Midfielder / Forward). Dipakai untuk `<optgroup>` di dropdown.
- **`sort_order`** — urutan tampil. Wajib, karena urutan alfabetis menghasilkan *Defender, Forward, Goalkeeper, Midfielder* — bukan urutan sepak bola (GK → DEF → MID → FWD).

Tabel `players` **berubah**:

```text
- primary_position    VARCHAR(20)      ← DIHAPUS
- secondary_position  VARCHAR(20)      ← DIHAPUS
+ id_primary_position    UUID nullable  → player_positions   (WAJIB di form)
+ id_secondary_position  UUID nullable  → player_positions   (opsional)
```

### 2c. Satu posisi dipakai di DUA kolom

Ini yang bikin module ini bukan sekadar salinan PlayerType:

```text
                     players
                  ┌───────────────────────┐
player_positions  │ id_primary_position   │  ← "ST" dipakai di sini
   "ST" ──────────┤                       │
                  │ id_secondary_position │  ← ...DAN bisa di sini juga
                  └───────────────────────┘
```

Artinya `PlayerPosition` punya **dua** relasi `hasMany` ke `Player`, dan setiap pengecekan "posisi ini masih dipakai tidak?" **wajib memeriksa dua-duanya**. Lihat [4.2](#42-posisi-dipakai-di-dua-kolom--cek-dua-duanya).

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_create_player_positions_table.php` | 🆕 Baru | 1 |
| `database/migrations/…_replace_position_columns_on_players_table.php` | 🆕 Baru | 1 |
| `app/Models/PlayerPosition.php` | 🆕 Baru | 2 |
| `app/Models/Player.php` | ✏️ Ubah fillable + relasi | 2 |
| `database/seeders/PlayerPositionSeeder.php` | 🆕 Baru | 3 |
| `database/seeders/DatabaseSeeder.php` | ✏️ Tambah 1 baris | 3 |
| `app/Services/PlayerPositionService.php` | 🆕 Baru | 4 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah 4 permission | 5 |
| `app/Support/PermissionPresenter.php` | ✏️ Tambah 1 baris ke `$modules` | 5 |
| `app/Http/Requests/PlayerPosition/PlayerPositionFormRequest.php` | 🆕 Baru | 6 |
| `app/Http/Controllers/PlayerPositionController.php` | 🆕 Baru | 7 |
| `routes/web.php` | ✏️ Tambah resource `player-positions` | 7 |
| `resources/views/player-positions/{index,create,edit}.blade.php` | 🆕 Baru | 8 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Tambah menu-item "Master" | 9 |
| `app/Services/PlayerService.php` | ✏️ Simpan FK posisi | 10 |
| `app/Http/Requests/Players/{Store,Update}PlayerRequest.php` | ✏️ Ganti validasi posisi | 10 |
| `app/Http/Controllers/PlayerController.php` | ✏️ Kirim `$playerPositions` | 10 |
| `resources/views/players/{index,create,edit,show}.blade.php` | ✏️ Ubah | 10 |
| `database/factories/PlayerPositionFactory.php` | 🆕 Baru | 11 |
| `tests/Feature/PlayerPositionTest.php` | 🆕 Baru | 11 |
| `docs/permission-reference.md`, `docs/multi-tenancy.md` | ✏️ Ubah | 12 |
| **`config/faos.php`** | 🚫 **Jangan sentuh** — tidak ada template per-academy | — |
| **`app/Services/AcademyManagementService.php`** | 🚫 **Jangan sentuh** — tidak ada default per-academy | — |
| **`app/Traits/BelongsToAcademy.php`**, **`app/Scopes/AcademyScope.php`** | 🚫 **Jangan sentuh** | — |
| **`app/Models/FaosModel.php`** | 🚫 **Jangan sentuh** | — |

> Empat baris terakhir bukan basa-basi. Kalau kamu merasa perlu menyentuh salah satunya, berarti kamu sedang memperlakukan module ini sebagai tenant — dan itu salah. Baca [4.1](#41-kenapa-module-ini-global-padahal-namanya-mirip-playertype--playercategory).

---

## Tahap 1 — Migration

```bash
php artisan make:migration create_player_positions_table
php artisan make:migration replace_position_columns_on_players_table
```

### 1a. `…_create_player_positions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_positions', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_player_position')->primary();

            /*
            |--------------------------------------------------------------------------
            | TIDAK ADA id_academy -- INI DISENGAJA
            |--------------------------------------------------------------------------
            | Master posisi bersifat GLOBAL: satu daftar dipakai bersama seluruh
            | academy, persis seperti tabel `permissions`. Berbeda dengan
            | player_types & player_categories yang justru punya id_academy.
            |
            | Kalau kamu tergoda menambahkan id_academy di sini, baca dulu
            | Bagian 4.1 di issue3.md.
            */

            /*
            |--------------------------------------------------------------------------
            | Position Information
            |--------------------------------------------------------------------------
            */

            // Singkatan standar sepak bola: GK, CB, LB, CM, ST, dst.
            $table->string('code', 10)->unique();

            $table->string('name', 50)->unique();

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Grouping
            |--------------------------------------------------------------------------
            | Kelompok posisi: Goalkeeper / Defender / Midfielder / Forward.
            | Dipakai sebagai <optgroup> di dropdown form Player.
            |
            | Namanya "position_group", BUKAN "group" -- GROUP adalah reserved
            | word SQL. Laravel memang meng-escape-nya otomatis, tapi begitu ada
            | yang menulis raw query / orderByRaw, kolom bernama `group` langsung
            | jadi sumber error yang membingungkan. Tidak sepadan.
            */
            $table->string('position_group', 50);

            /*
            |--------------------------------------------------------------------------
            | Sort Order
            |--------------------------------------------------------------------------
            | WAJIB ada. Urutan alfabetis menghasilkan:
            |   Defender, Forward, Goalkeeper, Midfielder
            | Padahal urutan sepak bola yang benar:
            |   Goalkeeper -> Defender -> Midfielder -> Forward
            */
            $table->unsignedSmallInteger('sort_order')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | Posisi nonaktif tidak muncul di dropdown Player baru, tapi player
            | lama yang memakainya tetap utuh.
            */
            $table->boolean('status')->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            | Dipakai untuk pengurutan & pengelompokan dropdown.
            */
            $table->index(['position_group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_positions');
    }
};
```

> `code` dan `name` di-`unique()` **masing-masing secara global** — bukan composite `(id_academy, name)` seperti dua module sebelumnya. Tidak boleh ada dua "ST" atau dua "Striker" di seluruh sistem. Itu memang gunanya master data.

### 1b. `…_replace_position_columns_on_players_table.php`

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

            // Nullable di level DATABASE, tapi id_primary_position WAJIB di
            // Form Request. Player lama (posisinya masih teks bebas) tidak punya
            // FK ini, dan migration TIDAK menebak-nebak pemetaannya.
            // Lihat Bagian 4.5.
            //
            // Sengaja TANPA ->after(): supaya migration ini tidak bergantung
            // pada issue.md / issue2.md. Urutan kolom tidak berpengaruh apa pun
            // secara fungsional.
            $table->uuid('id_primary_position')->nullable();
            $table->uuid('id_secondary_position')->nullable();

            $table->index('id_primary_position');
            $table->index('id_secondary_position');

            // nullOnDelete, BUKAN cascadeOnDelete. Lihat Bagian 4.4.
            $table->foreign('id_primary_position')
                ->references('id_player_position')
                ->on('player_positions')
                ->nullOnDelete();

            $table->foreign('id_secondary_position')
                ->references('id_player_position')
                ->on('player_positions')
                ->nullOnDelete();
        });

        Schema::table('players', function (Blueprint $table) {
            // Kolom teks bebas lama dibuang -- satu sumber kebenaran.
            // Dipisah ke Schema::table() kedua supaya urutannya jelas: kolom
            // baru ada dulu, baru yang lama dibuang.
            $table->dropColumn(['primary_position', 'secondary_position']);
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('primary_position', 20)->nullable();
            $table->string('secondary_position', 20)->nullable();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['id_primary_position']);
            $table->dropForeign(['id_secondary_position']);
            $table->dropIndex(['id_primary_position']);
            $table->dropIndex(['id_secondary_position']);
            $table->dropColumn(['id_primary_position', 'id_secondary_position']);
        });
    }
};
```

> `down()` mengembalikan `primary_position` sebagai **nullable**, padahal aslinya `NOT NULL`. Itu disengaja: rollback tidak mungkin mengarang isi teks yang sudah dibuang, dan `NOT NULL` tanpa default akan menggagalkan rollback di tabel yang sudah ada isinya.

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table player_positions
php artisan db:table players
```

- `player_positions`: **tidak boleh** ada kolom `id_academy`. Kalau ada, hapus dan ulangi.
- `players`: `primary_position` & `secondary_position` sudah **hilang**; `id_primary_position` & `id_secondary_position` ada dengan FK **`on delete set null`** — kalau tertulis `cascade`, **ULANGI**.

---

## Tahap 2 — Model

### 2a. `app/Models/PlayerPosition.php` — file baru

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Master posisi pemain -- DATA GLOBAL.
 *
 * PERHATIKAN: model ini sengaja `extends Model`, BUKAN `extends FaosModel`.
 *
 * FaosModel memaksa trait BelongsToAcademy (AcademyScope + isi id_academy saat
 * creating). Itu benar untuk PlayerType & PlayerCategory yang memang milik
 * masing-masing academy, tapi SALAH untuk master global seperti ini: tabelnya
 * tidak punya kolom id_academy sama sekali, sehingga AcademyScope akan
 * menghasilkan SQL error "column not found" pada setiap query dari user academy.
 *
 * Konsekuensinya, UUID generation yang biasanya datang dari FaosModel harus
 * ditulis sendiri di boot() di bawah.
 *
 * Lihat issue3.md Bagian 4.1.
 */
class PlayerPosition extends Model
{
    use HasFactory;

    protected $table = 'player_positions';
    protected $primaryKey = 'id_player_position';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'description',
        'position_group',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            if (empty($model->id_player_position)) {
                $model->id_player_position = (string) Str::uuid();
            }
        });
    }

    /**
     * Player yang memakai posisi ini sebagai POSISI UTAMA.
     */
    public function primaryPlayers(): HasMany
    {
        return $this->hasMany(Player::class, 'id_primary_position', 'id_player_position');
    }

    /**
     * Player yang memakai posisi ini sebagai POSISI KEDUA.
     *
     * Relasi kedua ini bukan pelengkap -- tanpa memeriksanya, posisi yang cuma
     * dipakai sebagai posisi kedua akan lolos dihapus. Lihat Bagian 4.2.
     */
    public function secondaryPlayers(): HasMany
    {
        return $this->hasMany(Player::class, 'id_secondary_position', 'id_player_position');
    }
}
```

### 2b. `app/Models/Player.php` — ubah

**Hapus** dari `$fillable`:

```php
'primary_position',      // ← HAPUS, kolomnya sudah tidak ada
'secondary_position',    // ← HAPUS, kolomnya sudah tidak ada
```

**Tambah** ke `$fillable`:

```php
'id_primary_position',
'id_secondary_position',
```

**Tambah** relasi (di bawah relasi `user()`):

```php
/*
|--------------------------------------------------------------------------
| Relationship Player Position
|--------------------------------------------------------------------------
*/
public function primaryPosition()
{
    return $this->belongsTo(
        PlayerPosition::class,
        'id_primary_position',
        'id_player_position'
    );
}

public function secondaryPosition()
{
    return $this->belongsTo(
        PlayerPosition::class,
        'id_secondary_position',
        'id_player_position'
    );
}
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
// Model TIDAK boleh punya AcademyScope:
(new \App\Models\PlayerPosition)->getGlobalScopes();
// harus: []   <- kalau berisi AcademyScope, kamu memakai FaosModel. ULANGI.

(new \App\Models\PlayerPosition)->getKeyName();   // "id_player_position"
```

---

## Tahap 3 — Seeder Master Posisi

**Tujuan**: master posisi terisi saat `migrate:fresh --seed`.

Ini pengganti "template per-academy" milik dua module sebelumnya. Karena datanya global, ia di-seed **sekali** untuk seluruh sistem — persis seperti `permissions`, **bukan** disalin ke tiap academy.

### 3a. `database/seeders/PlayerPositionSeeder.php` — file baru

```bash
php artisan make:seeder PlayerPositionSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\PlayerPosition;
use Illuminate\Database\Seeder;

class PlayerPositionSeeder extends Seeder
{
    /**
     * Master posisi pemain -- data GLOBAL, dipakai seluruh academy.
     *
     * sort_order sengaja diberi jarak (1, 10-15, 20-24, 30-34) supaya posisi
     * baru bisa disisipkan di tengah tanpa menomori ulang semuanya.
     */
    public function run(): void
    {
        $positions = [

            // Goalkeeper
            ['code' => 'GK',  'name' => 'Goalkeeper',           'position_group' => 'Goalkeeper', 'sort_order' => 1,  'description' => 'Penjaga gawang.'],

            // Defender
            ['code' => 'SW',  'name' => 'Sweeper',              'position_group' => 'Defender',   'sort_order' => 10, 'description' => 'Bek penyapu di belakang bek tengah.'],
            ['code' => 'CB',  'name' => 'Center Back',          'position_group' => 'Defender',   'sort_order' => 11, 'description' => 'Bek tengah.'],
            ['code' => 'LB',  'name' => 'Left Back',            'position_group' => 'Defender',   'sort_order' => 12, 'description' => 'Bek kiri.'],
            ['code' => 'RB',  'name' => 'Right Back',           'position_group' => 'Defender',   'sort_order' => 13, 'description' => 'Bek kanan.'],
            ['code' => 'LWB', 'name' => 'Left Wing Back',       'position_group' => 'Defender',   'sort_order' => 14, 'description' => 'Bek sayap kiri yang aktif menyerang.'],
            ['code' => 'RWB', 'name' => 'Right Wing Back',      'position_group' => 'Defender',   'sort_order' => 15, 'description' => 'Bek sayap kanan yang aktif menyerang.'],

            // Midfielder
            ['code' => 'CDM', 'name' => 'Defensive Midfielder', 'position_group' => 'Midfielder', 'sort_order' => 20, 'description' => 'Gelandang bertahan.'],
            ['code' => 'CM',  'name' => 'Center Midfielder',    'position_group' => 'Midfielder', 'sort_order' => 21, 'description' => 'Gelandang tengah.'],
            ['code' => 'CAM', 'name' => 'Attacking Midfielder', 'position_group' => 'Midfielder', 'sort_order' => 22, 'description' => 'Gelandang serang.'],
            ['code' => 'LM',  'name' => 'Left Midfielder',      'position_group' => 'Midfielder', 'sort_order' => 23, 'description' => 'Gelandang kiri.'],
            ['code' => 'RM',  'name' => 'Right Midfielder',     'position_group' => 'Midfielder', 'sort_order' => 24, 'description' => 'Gelandang kanan.'],

            // Forward
            ['code' => 'LW',  'name' => 'Left Winger',          'position_group' => 'Forward',    'sort_order' => 30, 'description' => 'Penyerang sayap kiri.'],
            ['code' => 'RW',  'name' => 'Right Winger',         'position_group' => 'Forward',    'sort_order' => 31, 'description' => 'Penyerang sayap kanan.'],
            ['code' => 'SS',  'name' => 'Second Striker',       'position_group' => 'Forward',    'sort_order' => 32, 'description' => 'Penyerang bayangan.'],
            ['code' => 'CF',  'name' => 'Center Forward',       'position_group' => 'Forward',    'sort_order' => 33, 'description' => 'Penyerang tengah.'],
            ['code' => 'ST',  'name' => 'Striker',              'position_group' => 'Forward',    'sort_order' => 34, 'description' => 'Penyerang utama.'],

        ];

        foreach ($positions as $position) {

            PlayerPosition::firstOrCreate(
                ['code' => $position['code']],
                $position
            );
        }
    }
}
```

> `firstOrCreate(['code' => ...])` bikin seeder ini **idempoten** — aman dijalankan berulang tanpa menggandakan data, dan tidak menimpa perubahan yang sudah dibuat Super Admin lewat UI.

### 3b. `database/seeders/DatabaseSeeder.php` — tambah 1 baris

```php
$this->call([
    RolePermissionSeeder::class,
    PlayerPositionSeeder::class,
]);
```

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
php artisan tinker
```

```php
\App\Models\PlayerPosition::count();   // 17

// Urutan HARUS urutan sepak bola, bukan alfabetis:
\App\Models\PlayerPosition::orderBy('sort_order')->pluck('code')->take(4);
// ["GK","SW","CB","LB"]   <- kalau keluar ["CAM","CB","CDM","CF"], sort_order-nya tidak dipakai
```

---

## Tahap 4 — `PlayerPositionService`

`app/Services/PlayerPositionService.php` — **file baru**.

Perhatikan: **tidak ada `AcademyService` di constructor**, dan **tidak ada `resolveAcademyId()`** — dua hal yang justru wajib ada di `PlayerTypeService`/`PlayerCategoryService`. Data ini global, tidak punya pemilik academy.

```php
<?php

namespace App\Services;

use App\Models\PlayerPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlayerPositionService
{
    /*
    |--------------------------------------------------------------------------
    | Catatan: Service ini SENGAJA tidak menerima AcademyService.
    |--------------------------------------------------------------------------
    | Master posisi bersifat global -- tidak ada "posisi milik academy X".
    | Kalau kamu merasa butuh AcademyService di sini, berarti ada yang salah.
    */

    /**
     * Daftar posisi untuk halaman index (Super Admin).
     *
     * withoutGlobalScopes() pada hitungan player WAJIB: Player memakai
     * AcademyScope, sedangkan posisi ini dipakai lintas academy. Angka
     * "dipakai N player" pada master global harus berarti N player di
     * SELURUH sistem. Lihat Bagian 4.3.
     */
    public function paginate(?int $perPage = null)
    {
        return PlayerPosition::query()
            ->withCount([
                'primaryPlayers' => fn ($query) => $query->withoutGlobalScopes(),
                'secondaryPlayers' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar posisi untuk dropdown di form Player.
     *
     * Tidak ada parameter academy -- posisi berlaku untuk seluruh academy.
     *
     * $includeId & $includeSecondId dipakai form EDIT: posisi yang sedang
     * dipakai player tetap ikut walau sudah dinonaktifkan, supaya nilainya
     * tidak hilang saat disimpan.
     */
    public function selectable(?string $includeId = null, ?string $includeSecondId = null): Collection
    {
        return PlayerPosition::query()
            ->where(function ($query) use ($includeId, $includeSecondId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_player_position', $includeId);
                }

                if ($includeSecondId) {
                    $query->orWhere('id_player_position', $includeSecondId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Daftar position_group yang sudah dipakai, untuk opsi datalist di form.
     *
     * Meniru pola PermissionService::existingModules() yang sudah ada.
     */
    public function existingGroups(): array
    {
        return PlayerPosition::query()
            ->select('position_group')
            ->distinct()
            ->orderBy('position_group')
            ->pluck('position_group')
            ->all();
    }

    public function create(array $data): PlayerPosition
    {
        return DB::transaction(function () use ($data) {

            return PlayerPosition::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'position_group' => $data['position_group'],
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(PlayerPosition $playerPosition, array $data): PlayerPosition
    {
        return DB::transaction(function () use ($playerPosition, $data) {

            $playerPosition->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'position_group' => $data['position_group'],
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

            return $playerPosition;
        });
    }

    public function delete(PlayerPosition $playerPosition): bool
    {
        return DB::transaction(function () use ($playerPosition) {

            // WAJIB cek DUA-DUANYA: posisi bisa dipakai sebagai posisi utama
            // ATAU posisi kedua. Kalau cuma primaryPlayers() yang dicek, posisi
            // yang hanya dipakai sebagai posisi kedua akan lolos dihapus, dan
            // player-player itu diam-diam kehilangan posisi keduanya.
            // Lihat Bagian 4.2.
            //
            // withoutGlobalScopes() juga wajib -- lihat Bagian 4.3.
            $dipakai = $playerPosition->primaryPlayers()->withoutGlobalScopes()->exists()
                || $playerPosition->secondaryPlayers()->withoutGlobalScopes()->exists();

            if ($dipakai) {
                throw new \Exception('Posisi masih digunakan oleh player, tidak dapat dihapus. Nonaktifkan posisi ini kalau sudah tidak dipakai.');
            }

            return $playerPosition->delete();
        });
    }
}
```

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Services\PlayerPositionService::class)` tidak error.

---

## Tahap 5 — Permission

### 5a. `database/seeders/RolePermissionSeeder.php`

Di array `$permissions`, tambahkan **tepat setelah blok `// Player`**:

```php
            // Player Position (master global, Super Admin only)
            'player_position.view',
            'player_position.create',
            'player_position.update',
            'player_position.delete',
```

### 5b. `config/faos.php` → **JANGAN diubah**

⛔ **Jangan tambahkan `player_position.*` ke `role_templates` manapun — termasuk Owner.**

Ini beda dengan PlayerType & PlayerCategory. Master posisi dipakai bersama seluruh academy, jadi kalau satu Owner boleh mengubahnya, ia mengubah data akademi lain juga.

Cara kerjanya sudah otomatis, tanpa kode tambahan:

- Super Admin dapat seluruh permission lewat `$superAdmin->syncPermissions(Permission::all())` di seeder, **dan** lolos apa pun lewat `Gate::before()`.
- Role academy tidak pernah diberi `player_position.*`, jadi middleware `permission:player_position.view` menolak mereka → **403**.

Polanya sama persis dengan `permission.*` dan `academy.*` yang juga sengaja tidak ada di `role_templates`.

> **Yang tetap bisa dilakukan semua orang**: memilih posisi saat menambah/mengubah Player. Dropdown-nya diisi Service, bukan digerbang `player_position.view` — sama seperti PlayerType & PlayerCategory.

### 5c. `app/Support/PermissionPresenter.php`

Satu baris, di array `$modules` milik `moduleLabel()`:

```php
            'player_position' => 'Player Position',
```

> Tanpa baris ini pun tampilannya sudah benar (fallback `Str::headline()` mengubah `player_position` → "Player Position"). Ditambahkan supaya `$modules` tetap jadi daftar lengkap module yang dikenal sistem.

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
php artisan tinker
```

```php
\App\Support\PermissionPresenter::label('player_position.view');   // "Lihat Player Position"

// Super Admin boleh:
\App\Models\User::where('email','superadmin@faosball.com')->first()->can('player_position.create');   // true

// Owner academy TIDAK boleh:
\App\Models\User::where('email','owner@faosacademy.com')->first()->can('player_position.create');     // false
```

> Kalau Owner mengembalikan `true`, berarti `player_position.*` tidak sengaja masuk ke `role_templates`. Cabut.

---

## Tahap 6 — `PlayerPositionFormRequest`

`app/Http/Requests/PlayerPosition/PlayerPositionFormRequest.php` — **file baru**.

Perhatikan: **tidak ada rule `id_academy`** sama sekali — beda dengan `PlayerTypeFormRequest`/`PlayerCategoryFormRequest`.

```php
<?php

namespace App\Http\Requests\PlayerPosition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerPositionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kode posisi dinormalkan jadi huruf besar SEBELUM validasi jalan.
     *
     * Tanpa ini, "st" lolos rule unique walau "ST" sudah ada -- lalu Service
     * menyimpannya dan menabrak unique index di level database, yang muncul
     * sebagai error SQL mentah, bukan pesan validasi yang rapi.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('player_position')?->id_player_position;

        return [
            /*
            | Tidak ada rule 'id_academy' di sini -- master posisi global, tidak
            | punya pemilik academy. Kalau kamu menambahkannya, berarti kamu
            | memperlakukan module ini sebagai tenant. Lihat Bagian 4.1.
            */

            // unique GLOBAL, bukan composite dengan id_academy.
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('player_positions', 'code')->ignore($id, 'id_player_position'),
            ],

            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('player_positions', 'name')->ignore($id, 'id_player_position'),
            ],

            'description' => ['nullable', 'string'],

            'position_group' => ['required', 'string', 'max:50'],

            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],

            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode posisi wajib diisi.',
            'code.max' => 'Kode posisi maksimal :max karakter.',
            'code.unique' => 'Kode posisi sudah digunakan.',

            'name.required' => 'Nama posisi wajib diisi.',
            'name.max' => 'Nama posisi maksimal :max karakter.',
            'name.unique' => 'Nama posisi sudah digunakan.',

            'description.string' => 'Deskripsi harus berupa teks.',

            'position_group.required' => 'Kelompok posisi wajib diisi.',
            'position_group.max' => 'Kelompok posisi maksimal :max karakter.',

            'sort_order.required' => 'Urutan wajib diisi.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
            'sort_order.min' => 'Urutan tidak valid.',
            'sort_order.max' => 'Urutan maksimal :max.',

            'status.required' => 'Status wajib ditentukan.',
            'status.boolean' => 'Status tidak valid.',
        ];
    }
}
```

> `->ignore($id, 'id_player_position')` — parameter kedua **wajib**, primary key tabel ini bukan `id`.
> Nama parameter route dari `Route::resource('player-positions', ...)` adalah `player_position`.

---

## Tahap 7 — Controller + Route

### 7a. `app/Http/Controllers/PlayerPositionController.php` — file baru

Strukturnya seperti `PlayerTypeController`, **tanpa** `AcademyService`, **tanpa** `$isSuperAdmin`, **tanpa** dropdown academy.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerPosition\PlayerPositionFormRequest;
use App\Models\PlayerPosition;
use App\Services\PlayerPositionService;

class PlayerPositionController extends Controller
{
    protected PlayerPositionService $playerPositionService;

    public function __construct(PlayerPositionService $playerPositionService)
    {
        $this->playerPositionService = $playerPositionService;
    }

    public function index()
    {
        return view('player-positions.index', [
            'title' => 'Master Posisi Pemain',
            'breadcrumb' => [
                ['label' => 'Master'],
                ['label' => 'Posisi Pemain'],
            ],
            'playerPositions' => $this->playerPositionService->paginate(),
        ]);
    }

    public function create()
    {
        return view('player-positions.create', [
            'title' => 'Tambah Posisi Pemain',
            'breadcrumb' => [
                ['label' => 'Posisi Pemain', 'url' => route('player-positions.index')],
                ['label' => 'Tambah Posisi'],
            ],
            'existingGroups' => $this->playerPositionService->existingGroups(),
        ]);
    }

    public function store(PlayerPositionFormRequest $request)
    {
        try {

            $this->playerPositionService->create($request->validated());

            return redirect()
                ->route('player-positions.index')
                ->with('success', 'Posisi pemain berhasil ditambahkan.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menambahkan posisi pemain');
        }
    }

    public function edit(PlayerPosition $playerPosition)
    {
        return view('player-positions.edit', [
            'title' => 'Edit Posisi Pemain',
            'breadcrumb' => [
                ['label' => 'Posisi Pemain', 'url' => route('player-positions.index')],
                ['label' => 'Edit Posisi'],
            ],
            'playerPosition' => $playerPosition,
            'existingGroups' => $this->playerPositionService->existingGroups(),
        ]);
    }

    public function update(PlayerPositionFormRequest $request, PlayerPosition $playerPosition)
    {
        try {

            $this->playerPositionService->update($playerPosition, $request->validated());

            return redirect()
                ->route('player-positions.index')
                ->with('success', 'Posisi pemain berhasil diperbarui.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal memperbarui posisi pemain');
        }
    }

    public function destroy(PlayerPosition $playerPosition)
    {
        try {

            $this->playerPositionService->delete($playerPosition);

            return redirect()
                ->route('player-positions.index')
                ->with('success', 'Posisi pemain berhasil dihapus.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menghapus posisi pemain', 'player-positions.index');
        }
    }
}
```

> **Tidak ada Policy dan tidak ada `authorize()`** — tapi alasannya **berbeda** dengan PlayerType/PlayerCategory. Di sana, global scope yang membuat academy lain dapat 404. Di sini, tidak ada isolasi academy sama sekali karena datanya memang global; yang menjaga adalah **middleware permission** yang cuma dipunyai Super Admin. Test nomor 2 di Tahap 11 membuktikannya.

### 7b. `routes/web.php`

Tambahkan **di dekat blok `roles`/`permissions`** (bukan di dekat `players`) — module ini bagian dari Administration:

```php
    /*
    |--------------------------------------------------------------------------
    | Master: Player Position (global, Super Admin only)
    |--------------------------------------------------------------------------
    */
    Route::resource('player-positions', PlayerPositionController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:player_position.view')
        ->middlewareFor(['create', 'store'], 'permission:player_position.create')
        ->middlewareFor(['edit', 'update'], 'permission:player_position.update')
        ->middlewareFor('destroy', 'permission:player_position.delete');
```

Tambah import:

```php
use App\Http\Controllers\PlayerPositionController;
```

**✅ Cek dulu**

```bash
php artisan route:list --name=player-positions
```

6 route dengan middleware `permission:player_position.*` yang sesuai. **Tidak boleh** ada `player-positions.show`.

---

## Tahap 8 — View Player Position

`resources/views/player-positions/`. Tiru `resources/views/player-types/`, termasuk **tabel + Card List responsif** (`docs/frontend-standard.md`).

**Yang dihapus** dibanding player-types: seluruh blok `@if ($isSuperAdmin)` untuk kolom/dropdown Academy — halaman ini memang cuma bisa dibuka Super Admin, dan datanya tidak punya academy.

### 8a. `index.blade.php`

Kolom: **Kode** · **Nama** (+ deskripsi) · **Kelompok** · **Urutan** · **Status** · **Dipakai** · **Aksi**.

```blade
{{-- Kode --}}
<span class="badge badge-primary">{{ $playerPosition->code }}</span>

{{-- Kelompok --}}
<span class="badge badge-secondary">{{ $playerPosition->position_group }}</span>

{{-- Urutan --}}
<span class="table-text">{{ $playerPosition->sort_order }}</span>

{{-- Status --}}
@if ($playerPosition->status)
    <span class="badge badge-success">Aktif</span>
@else
    <span class="badge badge-danger">Nonaktif</span>
@endif

{{-- Dipakai: JUMLAHKAN dua-duanya (posisi utama + posisi kedua) --}}
<span class="table-text">
    {{ $playerPosition->primary_players_count + $playerPosition->secondary_players_count }} Player
</span>
<span class="table-subtitle">
    {{ $playerPosition->primary_players_count }} utama &middot;
    {{ $playerPosition->secondary_players_count }} kedua
</span>

{{-- Delete: kunci kalau masih dipakai, cek DUA-DUANYA --}}
@can('player_position.delete')
    <x-button.delete :action="route('player-positions.destroy', $playerPosition)" :name="$playerPosition->name"
        :disabled="$playerPosition->primary_players_count + $playerPosition->secondary_players_count > 0"
        reason="Posisi masih digunakan oleh player, tidak dapat dihapus." />
@endcan
```

> `:disabled` **wajib menjumlahkan dua-duanya**. Kalau cuma `primary_players_count > 0`, tombol hapus tetap aktif untuk posisi yang dipakai sebagai posisi kedua — user mengklik, lalu ditolak Service. Bukan bug fatal, tapi UI yang berbohong.

### 8b. `create.blade.php` & `edit.blade.php`

Field: **Kode** · **Urutan** · **Nama** · **Kelompok** (datalist) · **Deskripsi** · **Status** (toggle).

Kelompok memakai `<datalist>` — meniru field "Module" di `resources/views/permissions/create.blade.php` yang sudah ada. Super Admin bisa mengetik kelompok baru, tapi dibantu daftar yang sudah ada supaya tidak muncul "Defender" dan "defender" sebagai dua kelompok berbeda:

```blade
<div class="form-row grid-cols-2">

    <div class="form-group">
        <label class="form-label">
            Kode <span class="text-error-500">*</span>
        </label>

        <input type="text" name="code" value="{{ old('code') }}" placeholder="Contoh: CB" maxlength="10"
            class="form-input @error('code') form-danger @enderror" required>

        <p class="form-helper">Singkatan standar sepak bola. Otomatis jadi huruf besar.</p>

        @error('code')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label">
            Urutan <span class="text-error-500">*</span>
        </label>

        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="9999"
            class="form-input @error('sort_order') form-danger @enderror" required>

        <p class="form-helper">Makin kecil makin atas. Kiper 1, bek 10-an, gelandang 20-an, penyerang 30-an.</p>

        @error('sort_order')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

</div>

<div class="form-group">
    <label class="form-label">
        Kelompok <span class="text-error-500">*</span>
    </label>

    <input type="text" name="position_group" value="{{ old('position_group') }}" list="position-group-options"
        placeholder="Contoh: Defender" class="form-input @error('position_group') form-danger @enderror" required>

    <datalist id="position-group-options">
        @foreach ($existingGroups as $group)
            <option value="{{ $group }}"></option>
        @endforeach
    </datalist>

    <p class="form-helper">Dipakai untuk mengelompokkan pilihan posisi di form Player.</p>

    @error('position_group')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

Toggle `status`: tiru pola hidden input + Alpine dari `player-types` (**jangan** checkbox polos — checkbox yang tidak dicentang tidak dikirim browser, sehingga rule `required` gagal).

Di `edit.blade.php`: `old('code', $playerPosition->code)`, `old('sort_order', $playerPosition->sort_order)`, dst.

**✅ Cek dulu**: login Super Admin → `/player-positions` menampilkan 17 posisi **urut GK → SW → CB → …**, bukan alfabetis. Cek di 375px: berubah jadi Card List.

---

## Tahap 9 — Menu Sidebar

**Tujuan**: menu **Master → Posisi Pemain** muncul di grup **Administration**.

`resources/views/partials/sidebar.blade.php`. Struktur yang sudah ada:

```text
@if (isSuperAdmin())
    <h3 class="menu-group-heading">Administration</h3>   ← group title
    <li> Administration (dropdown) → Roles, Permissions, Academy </li>
@endif
```

Tambahkan menu-item **Master** sebagai **saudara** dari menu-item "Administration" — di dalam `@if (isSuperAdmin())` yang sama, **tepat sebelum** `@endif` penutupnya:

```blade
                        {{-- ===== Menu Item: Master (dengan dropdown) ===== --}}
                        @php
                            $masterRoutes = ['player-positions.*'];

                            $isMasterActive = false;

                            foreach ($masterRoutes as $route) {
                                if (Route::is($route)) {
                                    $isMasterActive = true;
                                    break;
                                }
                            }
                        @endphp

                        <li x-data="{ open: {{ $isMasterActive ? 'true' : 'false' }} }">

                            <a href="#" @click.prevent="open=!open" class="menu-item group"
                                :class="open ? 'menu-item-active' : 'menu-item-inactive'">

                                <svg :class="open ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M4 4H10V10H4V4ZM14 4H20V10H14V4ZM4 14H10V20H4V14ZM14 14H20V20H14V14Z"
                                        fill="currentColor" />
                                </svg>

                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    Master
                                </span>

                                <svg class="menu-item-arrow transition-transform duration-200"
                                    :class="[
                                        open ?
                                        'menu-item-arrow-active rotate-180' :
                                        'menu-item-arrow-inactive',
                                        sidebarToggle ? 'lg:hidden' : ''
                                    ]"
                                    width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                            </a>

                            <div x-show="open" x-collapse class="overflow-hidden">

                                <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">

                                    {{-- Posisi Pemain --}}
                                    @can('player_position.view')
                                        <li>
                                            <a href="{{ route('player-positions.index') }}"
                                                class="menu-dropdown-item group"
                                                :class="{{ Route::is('player-positions.*') ? 'true' : 'false' }}
                                                    ?
                                                    'menu-dropdown-item-active' :
                                                    'menu-dropdown-item-inactive'">
                                                Posisi Pemain
                                            </a>
                                        </li>
                                    @endcan

                                </ul>

                            </div>

                        </li>
                        {{-- ===== END: Master ===== --}}
```

> Menu ini sudah berada di dalam `@if (isSuperAdmin())`, jadi `@can('player_position.view')` terlihat mubazir. Tetap dipasang karena `docs/module-standard.md` mewajibkannya, dan supaya kalau suatu saat grup Administration dibuka untuk role lain, menu ini tidak ikut bocor.
>
> Grup **Master** sengaja dibuat sebagai menu tersendiri, bukan disisipkan ke dropdown "Administration" yang sudah ada — supaya master data berikutnya (Nationality, Preferred Foot, dst) tinggal ditambahkan ke `$masterRoutes` + satu `<li>`, tanpa mengubah struktur lagi.

**✅ Cek dulu**: login Super Admin → sidebar menampilkan grup **Administration** berisi **dua** menu: "Administration" dan "Master". Buka Master → "Posisi Pemain". Login Owner → **tidak ada** grup Administration sama sekali.

---

## Tahap 10 — Integrasi ke Module Player

**Tujuan**: input teks bebas posisi diganti dropdown dari master.

### 10a. `StorePlayerRequest`

**Hapus** rule lama:

```php
'primary_position' => ['required', 'string', 'max:20'],       // ← HAPUS
'secondary_position' => ['nullable', 'string', 'max:20'],     // ← HAPUS
```

**Ganti** dengan:

```php
            /*
            | PERHATIKAN: TIDAK ada where('id_academy', ...) di sini -- beda
            | dengan rule id_player_type / id_player_category kalau issue.md &
            | issue2.md sudah dikerjakan. Master posisi global; tabelnya tidak
            | punya kolom id_academy, jadi menambahkannya akan menghasilkan SQL
            | error "column not found". Lihat Bagian 4.1.
            */
            'id_primary_position' => [
                'required',
                'uuid',
                Rule::exists('player_positions', 'id_player_position')
                    ->where(fn ($query) => $query->where('status', true)),
            ],

            // different: posisi kedua tidak boleh sama dengan posisi utama.
            // 'nullable' membuat seluruh rule di bawahnya dilewati kalau field
            // ini memang dikosongkan.
            'id_secondary_position' => [
                'nullable',
                'uuid',
                'different:id_primary_position',
                Rule::exists('player_positions', 'id_player_position')
                    ->where(fn ($query) => $query->where('status', true)),
            ],
```

Pastikan `use Illuminate\Validation\Rule;` sudah ada di atas.

**Ganti** pesan lama (`primary_position.*` / `secondary_position.*`) dengan:

```php
            'id_primary_position.required' => 'Posisi utama wajib dipilih.',
            'id_primary_position.uuid' => 'Posisi utama tidak valid.',
            'id_primary_position.exists' => 'Posisi utama tidak ditemukan.',

            'id_secondary_position.uuid' => 'Posisi kedua tidak valid.',
            'id_secondary_position.exists' => 'Posisi kedua tidak ditemukan.',
            'id_secondary_position.different' => 'Posisi kedua tidak boleh sama dengan posisi utama.',
```

### 10b. `UpdatePlayerRequest`

Sama dengan 10a, dengan **satu perbedaan**: buang filter `status`, supaya player yang posisinya sudah dinonaktifkan tetap bisa disimpan tanpa dipaksa ganti posisi.

```php
            'id_primary_position' => [
                'required',
                'uuid',
                'exists:player_positions,id_player_position',
            ],

            'id_secondary_position' => [
                'nullable',
                'uuid',
                'different:id_primary_position',
                'exists:player_positions,id_player_position',
            ],
```

Pesan `messages()`: sama dengan 10a.

### 10c. `PlayerService`

Di `create()` **dan** `update()`, **ganti** dua baris lama:

```php
'primary_position' => $data['primary_position'],                    // ← HAPUS
'secondary_position' => $data['secondary_position'] ?? null,        // ← HAPUS
```

menjadi:

```php
'id_primary_position' => $data['id_primary_position'],
'id_secondary_position' => $data['id_secondary_position'] ?? null,
```

### 10d. `PlayerController`

Tambah import + dependency:

```php
use App\Services\PlayerPositionService;
```

Tambahkan `PlayerPositionService $playerPositionService` ke constructor (di samping dependency yang sudah ada), lalu:

`index()` — eager load supaya tidak N+1 (`docs/query-performance.md`):

```php
'players' => Player::with(['primaryPosition', 'secondaryPosition'])->latest()->paginate(10),
```

> Kalau `issue.md`/`issue2.md` sudah dikerjakan, **gabungkan** dengan relasi yang sudah ada, jangan ditimpa:
> `->with(['playerType', 'playerCategory', 'primaryPosition', 'secondaryPosition'])`

`create()` — tambahkan:

```php
'playerPositions' => $this->playerPositionService->selectable(),
```

`edit()` — tambahkan. Dua argumen supaya posisi yang sudah dinonaktifkan tapi masih dipakai player ini tetap muncul di dropdown:

```php
'playerPositions' => $this->playerPositionService->selectable(
    $player->id_primary_position,
    $player->id_secondary_position
),
```

`show()` — tambahkan ke `load()`:

```php
'primaryPosition',
'secondaryPosition',
```

### 10e. `players/create.blade.php` & `players/edit.blade.php`

**Hapus** dua input teks lama:

```blade
{{-- HAPUS SEMUANYA --}}
<input type="text" name="primary_position" placeholder="Contoh: Forward, Midfielder" ...>
<input type="text" name="secondary_position" ...>
```

**Ganti** dengan dua dropdown ber-`<optgroup>`. Posisi bersifat global, jadi **tidak butuh filter Alpine** seperti Type/Category — cukup `@foreach` biasa:

```blade
<div class="form-group">
    <label class="form-label">
        Posisi Utama <span class="text-error-500">*</span>
    </label>

    <select name="id_primary_position" class="form-select @error('id_primary_position') form-danger @enderror"
        required>
        <option value="">Pilih Posisi Utama</option>
        @foreach ($playerPositions->groupBy('position_group') as $group => $positions)
            <optgroup label="{{ $group }}">
                @foreach ($positions as $position)
                    <option value="{{ $position->id_player_position }}" @selected(old('id_primary_position') === $position->id_player_position)>
                        {{ $position->code }} — {{ $position->name }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    @error('id_primary_position')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">Posisi Kedua</label>

    <select name="id_secondary_position" class="form-select @error('id_secondary_position') form-danger @enderror">
        <option value="">Tidak ada</option>
        @foreach ($playerPositions->groupBy('position_group') as $group => $positions)
            <optgroup label="{{ $group }}">
                @foreach ($positions as $position)
                    <option value="{{ $position->id_player_position }}" @selected(old('id_secondary_position') === $position->id_player_position)>
                        {{ $position->code }} — {{ $position->name }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    <p class="form-helper">Opsional. Tidak boleh sama dengan posisi utama.</p>

    @error('id_secondary_position')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

Di `edit.blade.php`: `old('id_primary_position', $player->id_primary_position)`, dan sama untuk yang kedua.

> `$playerPositions->groupBy('position_group')` di Blade **bukan** business logic — itu murni pengelompokan tampilan, dan `groupBy()` pada Collection memang disediakan Laravel untuk keperluan ini. Urutan kelompoknya sudah benar karena Service mengurutkan `sort_order` **sebelum** `groupBy()`, dan `groupBy()` mempertahankan urutan aslinya.

### 10f. `players/index.blade.php`

Ganti isi kolom **Posisi** yang lama (`$player->primary_position` / `$player->secondary_position`) — di **kedua** representasi.

Tabel `<tbody>`:

```blade
<td class="table-cell">
    @if ($player->primaryPosition)
        <span class="badge badge-primary">{{ $player->primaryPosition->code }}</span>
    @else
        <span class="table-subtitle">-</span>
    @endif

    @if ($player->secondaryPosition)
        <span class="badge badge-secondary">{{ $player->secondaryPosition->code }}</span>
    @endif
</td>
```

Card List — field "Posisi" di `table-card-body`:

```blade
<div class="table-card-field">
    <span class="table-card-label">Posisi</span>
    <div class="flex flex-wrap gap-1">
        @if ($player->primaryPosition)
            <span class="badge badge-primary">{{ $player->primaryPosition->code }}</span>
        @else
            <span class="table-subtitle">-</span>
        @endif

        @if ($player->secondaryPosition)
            <span class="badge badge-secondary">{{ $player->secondaryPosition->code }}</span>
        @endif
    </div>
</div>
```

> `@if` **wajib** — player lama FK-nya `NULL`. Dan inilah gunanya `code`: kolom sempit cukup menampilkan `[CB] [LB]`, bukan "Center Back, Left Back" yang memaksa tabel melebar.

### 10g. `players/show.blade.php`

Di tab **Fisik & Posisi**, ganti blok `{{ $player->primary_position ?? '-' }}` dan `{{ $player->secondary_position ?? '-' }}`:

```blade
<div>
    <span class="block mb-1 text-xs text-gray-400">Posisi Utama</span>
    <span class="table-text">
        @if ($player->primaryPosition)
            {{ $player->primaryPosition->code }} — {{ $player->primaryPosition->name }}
        @else
            -
        @endif
    </span>
</div>

<div>
    <span class="block mb-1 text-xs text-gray-400">Posisi Kedua</span>
    <span class="table-text">
        @if ($player->secondaryPosition)
            {{ $player->secondaryPosition->code }} — {{ $player->secondaryPosition->name }}
        @else
            -
        @endif
    </span>
</div>
```

Cek juga header halaman — ada `{{ $player->primary_position ?? 'Player' }}` di bawah nama pemain. Ganti jadi `{{ $player->primaryPosition->name ?? 'Player' }}`.

> **Wajib**: cari sisa kolom lama di seluruh kode sebelum lanjut.
> ```bash
> grep -rn "primary_position\|secondary_position" resources/views/ app/
> ```
> Yang tersisa **hanya boleh** `id_primary_position` / `id_secondary_position`. Kolom teks lamanya sudah tidak ada di database — setiap sisa pemakaiannya akan jadi error saat halaman dibuka.

**✅ Cek dulu**

1. Login Owner → `/players/create` → dropdown **Posisi Utama** berisi optgroup Goalkeeper/Defender/Midfielder/Forward, urut sepak bola.
2. Pilih posisi kedua **sama** dengan posisi utama → ditolak "Posisi kedua tidak boleh sama dengan posisi utama."
3. Simpan tanpa posisi kedua → berhasil (opsional).
4. `/players` → kolom Posisi menampilkan badge kode (`CB`, `LB`). Cek juga di 375px.

---

## Tahap 11 — Test

### 11a. `database/factories/PlayerPositionFactory.php` — file baru

```php
<?php

namespace Database\Factories;

use App\Models\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlayerPosition>
 */
class PlayerPositionFactory extends Factory
{
    protected $model = PlayerPosition::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(3)),
            'name' => 'Position ' . fake()->unique()->word(),
            'description' => fake()->sentence(),
            'position_group' => fake()->randomElement(['Goalkeeper', 'Defender', 'Midfielder', 'Forward']),
            'sort_order' => fake()->numberBetween(1, 99),
            'status' => true,
        ];
    }
}
```

### 11b. `tests/Feature/PlayerPositionTest.php` — file baru

Wajib menulis **9 skenario**. Nomor **1, 2, dan 5** yang paling penting — ketiganya menjaga sifat khas module ini.

| # | Skenario | Assert kunci |
|---|----------|--------------|
| 1 | **Master posisi GLOBAL** | `selectable()` mengembalikan daftar **identik** saat `actingAs($ownerA)` maupun `actingAs($ownerB)` — tidak ada penyaringan academy |
| 2 | **Owner academy TIDAK bisa CRUD** | `actingAs($owner)->get(route('player-positions.index'))` → `assertForbidden()` (**403**, bukan 404 — ini dari middleware permission, bukan scope) |
| 3 | Super Admin bisa membuka index | `actingAs($superAdmin)->get(...)` → `assertOk()` |
| 4 | Posisi dipakai sebagai **posisi utama** tidak bisa dihapus | `expectException(\Exception::class)` |
| 5 | **Posisi dipakai sebagai POSISI KEDUA tidak bisa dihapus** | `expectException(\Exception::class)` — inilah yang jebol kalau `secondaryPlayers()` lupa dicek. Lihat 4.2 |
| 6 | Player academy manapun boleh memakai posisi manapun | Owner A & Owner B sama-sama berhasil membuat player dengan posisi yang **sama persis** |
| 7 | Posisi kedua tidak boleh sama dengan posisi utama | `assertSessionHasErrors('id_secondary_position')` |
| 8 | Posisi utama wajib, posisi kedua opsional | Tanpa `id_primary_position` → error; tanpa `id_secondary_position` → berhasil |
| 9 | `code` unik & otomatis huruf besar | POST `code` = `"cb"` saat `"CB"` sudah ada → `assertSessionHasErrors('code')` |

Kerangka untuk skenario 1, 2, 5 (sisanya tiru pola yang sama):

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerPosition;
use App\Models\Role;
use App\Models\User;
use App\Services\PlayerPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerPositionTest extends TestCase
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

    /**
     * INI SIFAT UTAMA MODULE INI: datanya GLOBAL.
     */
    public function test_master_posisi_sama_untuk_semua_academy(): void
    {
        PlayerPosition::factory()->create(['code' => 'GK', 'name' => 'Goalkeeper']);
        PlayerPosition::factory()->create(['code' => 'ST', 'name' => 'Striker']);

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $ownerA = $this->makeAcademyUser($academyA, ['player.view']);
        $ownerB = $this->makeAcademyUser($academyB, ['player.view']);

        $service = app(PlayerPositionService::class);

        $this->actingAs($ownerA);
        $daftarA = $service->selectable()->pluck('code')->sort()->values()->all();

        $this->actingAs($ownerB);
        $daftarB = $service->selectable()->pluck('code')->sort()->values()->all();

        // Dua academy berbeda HARUS melihat daftar yang sama persis.
        $this->assertSame(['GK', 'ST'], $daftarA);
        $this->assertSame($daftarA, $daftarB);
    }

    /**
     * CRUD master posisi khusus Super Admin.
     */
    public function test_owner_academy_tidak_bisa_membuka_master_posisi(): void
    {
        $academy = Academy::factory()->create();

        // Owner ini sengaja diberi permission player.* selengkap-lengkapnya,
        // untuk membuktikan yang menolak adalah player_position.view yang memang
        // tidak dia punya -- bukan sekadar "tidak punya izin apa-apa".
        $owner = $this->makeAcademyUser($academy, [
            'player.view', 'player.create', 'player.update', 'player.delete',
        ]);

        $this->actingAs($owner)
            ->get(route('player-positions.index'))
            ->assertForbidden();
    }

    /**
     * MENGUNCI BUG YANG PALING GAMPANG TERLEWAT. Lihat Bagian 4.2.
     */
    public function test_posisi_yang_dipakai_sebagai_posisi_kedua_tidak_bisa_dihapus(): void
    {
        $academy = Academy::factory()->create();

        $utama = PlayerPosition::factory()->create(['code' => 'ST', 'name' => 'Striker']);
        $kedua = PlayerPosition::factory()->create(['code' => 'CAM', 'name' => 'Attacking Midfielder']);

        Player::withoutGlobalScopes()->create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST00001',
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'id_primary_position' => $utama->id_player_position,
            'id_secondary_position' => $kedua->id_player_position,   // ← cuma dipakai sebagai posisi KEDUA
        ]);

        $this->expectException(\Exception::class);

        // Kalau delete() cuma mengecek primaryPlayers(), baris ini LOLOS
        // dan test-nya merah -- persis yang kita mau.
        app(PlayerPositionService::class)->delete($kedua);
    }
}
```

> Di test nomor 5, `Player::withoutGlobalScopes()->create(...)` dipakai supaya pembuatan player tidak bergantung pada user yang login. Kalau `issue.md`/`issue2.md` sudah dikerjakan, tambahkan `id_player_type` & `id_player_category` ke array itu sesuai kolom yang wajib.

**✅ Cek dulu**

```bash
php artisan test --filter=PlayerPositionTest
php artisan test --filter=RoleAcademyTest
```

Kalau `issue.md`/`issue2.md` sudah dikerjakan, jalankan juga `--filter=PlayerTypeTest` dan `--filter=PlayerCategoryTest`. Semuanya harus hijau.

> Catatan: `php artisan test` polos akan menunjukkan **7 test Breeze lama yang memang sudah merah sejak sebelum brief ini** (`AuthenticationTest`, `RegistrationTest`, `ExampleTest`, `ProfileTest`, `EmailVerificationTest`). Bukan ulahmu — penyebabnya `User` memakai primary key `id_user` + SoftDeletes yang bentrok dengan asumsi test bawaan Breeze. Jangan diperbaiki di brief ini.

---

## Tahap 12 — Update `docs/`

### 12a. `docs/permission-reference.md`

1. Tambah section **Module: Player Position (Master Global)**, status **✅ Implemented**:

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `player_position.view` | Lihat master posisi | `player-positions.index` + menu sidebar |
| `player_position.create` | Tambah posisi baru | `player-positions.create`, `player-positions.store` |
| `player_position.update` | Ubah kode/nama/kelompok/urutan/status | `player-positions.edit`, `player-positions.update` |
| `player_position.delete` | Hapus posisi (kalau tidak dipakai player manapun) | `player-positions.destroy` |

Catatan yang wajib ikut:

- Ini **master data global** — tidak punya `id_academy`, dibaca seluruh academy, **CRUD-nya khusus Super Admin**.
- `player_position.*` **sengaja tidak ada di `role_templates` manapun**. Itu yang membuatnya Super-Admin-only, sama seperti `permission.*` dan `academy.*`.
- Akses dari role academy ditolak dengan **403** (dari middleware permission), **bukan 404** seperti module tenant.
- `player_position.view` **tidak** dibutuhkan untuk memilih posisi saat menambah Player.

2. Di section **Module: Player**, tambahkan bahwa `players.id_primary_position` wajib diisi saat create, dan `id_secondary_position` opsional (tidak boleh sama dengan posisi utama).

3. Di tabel **Role Template Default per Academy Baru**, tambahkan keterangan `player_position.*` → **tidak diberikan ke role manapun** (Super Admin only).

### 12b. `docs/multi-tenancy.md`

Di section **Tenant Identifier**, pada daftar tabel global (yang berisi `academies`, `permissions`, dst), **tambahkan `player_positions`** dengan keterangan:

> `player_positions` — master posisi pemain. Global (tanpa `id_academy`), dibaca seluruh academy, CRUD khusus Super Admin. Bandingkan dengan `player_types` & `player_categories` yang justru tabel tenant.

Ini penting supaya dokumen itu tetap jadi rujukan yang benar soal mana tabel tenant dan mana yang global.

---

## 4. Kenapa Begini? (alasan teknis)

### 4.1 Kenapa module ini GLOBAL, padahal namanya mirip PlayerType & PlayerCategory?

Yang menentukan tenant/global bukan namanya, tapi **siapa pemilik maknanya**.

| | Pemilik makna | Contoh |
|---|---|---|
| `PlayerType` | **Academy** | Academy A menagih "Reguler", Academy B menyebutnya "Siswa Tetap" dengan aturan berbeda. Maknanya milik masing-masing. |
| `PlayerCategory` | **Academy** | Academy A: U-12 = 10–12 th. Academy B: U-12 = 11–12 th. Maknanya milik masing-masing. |
| **`PlayerPosition`** | **Sepak bola** | "Center Back" artinya sama di seluruh dunia. Tidak ada academy yang berhak mendefinisikan ulang apa itu bek tengah. |

Kalau posisi dibuat per-academy, yang terjadi: Academy A menulis "Striker", Academy B "ST", Academy C "Penyerang" — dan laporan lintas academy, perbandingan pemain, atau pencarian "semua bek tengah di seluruh sistem" jadi mustahil. Itu justru masalah yang mau diselesaikan module ini.

**Akibat teknis kalau salah**: menambahkan `use BelongsToAcademy` atau `extends FaosModel` ke `PlayerPosition` membuat `AcademyScope` menyisipkan `WHERE player_positions.id_academy = ?` ke **setiap** query. Kolom itu tidak ada → SQL error di setiap halaman yang menyentuh posisi, untuk setiap user academy. Dan kalau kamu "memperbaikinya" dengan menambahkan kolom `id_academy`, kamu sudah membatalkan seluruh tujuan module ini.

### 4.2 Posisi dipakai di DUA kolom — cek dua-duanya

`players` punya **dua** FK ke `player_positions`: `id_primary_position` dan `id_secondary_position`.

Guard hapus yang cuma memeriksa satu:

```php
// ❌ SALAH -- kelihatan benar, dan lolos test kalau testnya cuma pakai posisi utama
if ($playerPosition->primaryPlayers()->exists()) {
    throw new \Exception('...');
}
```

**Akibatnya**: posisi "CAM" yang dipakai 40 pemain **sebagai posisi kedua** (dan tidak dipakai siapa pun sebagai posisi utama) akan **lolos dihapus**. FK `nullOnDelete` lalu menyapu bersih: 40 pemain diam-diam kehilangan posisi kedua mereka — tanpa error, tanpa peringatan, tanpa cara mengembalikannya.

Ini kelas bug yang tidak akan ketahuan berbulan-bulan. Test nomor 5 di Tahap 11 dibuat khusus untuk menangkapnya.

### 4.3 Kenapa `withoutGlobalScopes()` wajib di hitungan & guard hapus

`PlayerPosition` memang tidak punya `AcademyScope` — tapi `Player` **punya**.

Jadi `$playerPosition->primaryPlayers()->exists()` menjalankan query ke tabel `players`, dan scope-nya **ikut jalan**: hasilnya cuma menghitung player dari academy si pemanggil.

Untuk Super Admin, `isSuperAdmin()` mengembalikan `true` sehingga scope tidak memfilter apa-apa — jadi "kebetulan" hasilnya benar. Tapi menggantungkan **integritas data global** pada "kebetulan siapa yang sedang login" adalah bom waktu: begitu ada job, command artisan, atau fitur baru yang memanggil Service ini dalam konteks user academy, guard-nya diam-diam jadi bolong.

`withoutGlobalScopes()` membuat pengecekannya menjawab pertanyaan yang benar: *"apakah posisi ini dipakai player **manapun di sistem**?"* — bukan *"apakah dipakai player di academy saya?"*.

Alasan yang sama berlaku untuk `withCount()` di halaman index: angka "Dipakai 40 Player" pada master global harus berarti 40 player di seluruh sistem.

### 4.4 `cascadeOnDelete` = hapus posisi, hapus semua playernya

Sama seperti `issue.md` Bagian 4.1 dan `issue2.md` Bagian 4.4 — tapi **skalanya lebih besar**. Karena ini master global, `cascadeOnDelete` pada `id_primary_position` berarti menghapus posisi "ST" akan menghapus seluruh pemain berposisi striker **di semua academy sekaligus**.

`nullOnDelete` memastikan yang hilang cuma label posisinya. Guard di Service mencegahnya sejak awal.

### 4.5 Kenapa kolom teks lama dibuang, dan FK-nya nullable

**Kenapa dibuang**: menyimpan `primary_position` (teks) dan `id_primary_position` (FK) sekaligus berarti dua sumber kebenaran untuk satu hal. Setiap query, laporan, dan view berikutnya harus memutuskan mana yang dipercaya — dan begitu keduanya tidak sinkron, tidak ada yang tahu mana yang benar.

**Kenapa tidak di-backfill lewat migration**: memetakan teks bebas ("Striker", "striker", "ST", "Penyerang", "penyerang tengah") ke master posisi adalah **tebakan**, dan migration bukan tempat menulis data bisnis hasil tebakan. Realistisnya juga tidak ada yang hilang: seeder tidak membuat player sama sekali, dan `migrate:fresh --seed` sudah jadi kebiasaan di project ini — yang terhapus cuma data dev.

**Kenapa FK-nya nullable padahal wajib di form**: sama seperti `id_player_type` di `issue.md` Bagian 4.5. Kolom `NOT NULL` akan menggagalkan migration di database yang sudah punya player. Dengan nullable + `required` di Form Request: migration mulus, player baru wajib punya posisi, player lama tampil "-" dan dipaksa memilih saat pertama kali di-edit.

Yang wajib diingat: **relasi `primaryPosition` bisa `null`** — setiap Blade yang mengaksesnya wajib dijaga `@if`.

---

## 5. Keputusan Arsitektur

Keputusan berikut sudah dibahas dengan pemilik produk. Kalau mau mengubahnya, **diskusikan dulu**.

| Pilihan | Keputusan | Alasan |
|---------|-----------|--------|
| Nama module | **PlayerPosition** | Konsisten dengan PlayerType/PlayerCategory, dan menyisakan nama "Position" untuk jabatan staff/coach (Head Coach, Asisten, Fisioterapis) yang kemungkinan besar dibutuhkan nanti. |
| Sifat data | **Global**, tanpa `id_academy` | Makna posisi sepak bola tidak dimiliki academy manapun (4.1). |
| Siapa yang CRUD | **Hanya Super Admin** | Data dipakai bersama; kalau satu Owner boleh mengubah, ia mengubah data academy lain juga. |
| Cara menggerbang | **Permission `player_position.*` yang tidak diberikan ke `role_templates`** | Pola yang sudah ada & terbukti (sama seperti `permission.*` dan `academy.*`). Tidak perlu mekanisme baru. |
| Struktur | **`code` + `position_group` + `sort_order`** | Sepak bola memang pakai singkatan & pengelompokan. Tanpa `sort_order`, urutannya alfabetis — tidak masuk akal untuk sepak bola. |
| Nama kolom kelompok | **`position_group`**, bukan `group` | `GROUP` reserved word SQL. Laravel meng-escape otomatis, tapi raw query pertama yang ditulis orang akan langsung pecah. |
| Kolom teks lama | **Dibuang** | Dua sumber kebenaran untuk satu hal adalah sumber bug menahun (4.5). |
| Backfill data lama | **Tidak dilakukan** | Memetakan teks bebas ke master adalah tebakan; migration bukan tempatnya (4.5). |
| Posisi kedua | **Opsional, tidak boleh sama dengan posisi utama** | Pemain boleh punya satu posisi saja. Tapi "posisi kedua = posisi utama" tidak berarti apa-apa. |
| Template per-academy | **Tidak ada** | Data global di-seed sekali, tidak disalin ke tiap academy. Karena itu `config/faos.php` & `AcademyManagementService` tidak disentuh sama sekali. |
| Policy | **Tidak dibuat** | Tidak ada isolasi per-baris yang perlu dijaga — middleware permission sudah cukup. |
| Halaman `show` | **Tidak dibuat** | Seluruh info muat di index. Konsisten dengan PlayerType & PlayerCategory. |

---

## 6. Definition of Done

- [ ] `php artisan migrate:fresh --seed` bersih tanpa error, dan `player_positions` terisi **17 posisi**.
- [ ] Tabel `player_positions` **tidak punya** kolom `id_academy`.
- [ ] `PlayerPosition` **tidak** punya global scope (`getGlobalScopes()` mengembalikan `[]`).
- [ ] Kolom `primary_position` & `secondary_position` sudah **hilang** dari tabel `players`.
- [ ] FK `players.id_primary_position` & `id_secondary_position` = **`on delete set null`**, bukan cascade.
- [ ] Daftar posisi tampil **urut sepak bola** (GK → DEF → MID → FWD), bukan alfabetis.
- [ ] Owner Academy A dan Owner Academy B melihat **daftar posisi yang sama persis** — terbukti lewat test.
- [ ] Owner academy membuka `/player-positions` → **403** — terbukti lewat test.
- [ ] Super Admin bisa CRUD master posisi lewat menu **Administration → Master → Posisi Pemain**.
- [ ] **Posisi yang cuma dipakai sebagai posisi kedua tidak bisa dihapus** — terbukti lewat test.
- [ ] Posisi kedua tidak boleh sama dengan posisi utama.
- [ ] Posisi utama wajib, posisi kedua opsional.
- [ ] `code` unik dan otomatis huruf besar ("cb" ditolak kalau "CB" sudah ada).
- [ ] Player lama (FK posisi `NULL`) tidak bikin error 500 di index/show.
- [ ] `grep -rn "primary_position\|secondary_position" resources/views/ app/` — tidak ada sisa kolom teks lama.
- [ ] `php artisan test --filter=PlayerPositionTest` dan `--filter=RoleAcademyTest` hijau.
- [ ] Controller tetap tipis, business logic di Service, validasi di Form Request.
- [ ] Pesan user-facing Bahasa Indonesia, hardcoded, tanpa folder `lang/`.
- [ ] Halaman index punya Card List responsif; sudah dicek di 375px / tablet / desktop.
- [ ] `docs/permission-reference.md` dan `docs/multi-tenancy.md` sudah diperbarui.

---

## 7. Urutan Commit

| # | Isi | Tahap |
|---|-----|-------|
| 1 | Migration `player_positions` + ganti kolom posisi di `players` | 1 |
| 2 | Model `PlayerPosition` + relasi di `Player` | 2 |
| 3 | `PlayerPositionSeeder` + `DatabaseSeeder` | 3 |
| 4 | `PlayerPositionService` | 4 |
| 5 | Permission (seeder + `PermissionPresenter`) | 5 |
| 6 | `PlayerPositionFormRequest` + Controller + Route + View + menu Master | 6, 7, 8, 9 |
| 7 | Integrasi ke module Player | 10 |
| 8 | Factory + Test | 11 |
| 9 | Update `docs/` | 12 |

Kalau perilaku permission terasa aneh saat manual testing:

```bash
php artisan permission:cache-reset
php artisan config:clear
php artisan view:clear
```

---

## 8. Hasil Akhir

Setelah module ini, posisi pemain berhenti jadi teks bebas dan menjadi **master global yang dipakai bersama seluruh academy** — dengan singkatan standar sepak bola, pengelompokan yang benar, dan urutan yang masuk akal. Super Admin memegang kendali penuh atas daftarnya; academy tinggal memakai.

Ini juga membuka menu **Master** di Administration — tempat master data global berikutnya (Nationality, Preferred Foot, Jenis Cedera, dst) tinggal ditambahkan tanpa mengubah struktur menu lagi.

Bersama dua module sebelumnya, Player sekarang punya tiga dimensi yang masing-masing pemiliknya jelas:

```text
  Player Type      -> milik ACADEMY      (Reguler/Beasiswa/Trial)  -> dipakai module Payment
  Player Category  -> milik ACADEMY      (U-12/U-15/U-17)          -> dipakai module Template Latihan
  Player Position  -> milik SEPAK BOLA   (GK/CB/CM/ST)             -> dipakai penyusunan tim & laporan
```

Yang milik academy: academy yang menentukan. Yang milik sepak bola: Super Admin yang menjaga, semua memakai.
