# Brief: Indikator Mismatch Player Category di Halaman Index Players

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Module Player + Player Category (`app/Models/PlayerCategory.php`, `PlayerCategoryService`) **wajib sudah ada**. Baca `docs/permission-reference.md` bagian *Module: Player Category*. Modul referensi paling mirip: `PlayerCategoryService::suggestFor()` (logic saran kategori dari `birth_date` yang **sudah ada** dan dipakai di halaman Edit Player) — brief ini cuma memperluas *exposure*-nya, bukan bikin logic baru dari nol.
> **Bukan module baru** — ini enhancement kecil ke halaman index Player yang sudah ada (`resources/views/players/index.blade.php`).
> **Cara pakai brief ini**: Kerjakan Tahap 1 → 4 berurutan. Tiap tahap ada blok **✅ Cek dulu**.
> **Scope**: Halaman index Players menampilkan indikator visual (badge kecil) kalau kategori umur yang tersimpan di seorang player **berbeda** dari kategori yang seharusnya berdasarkan `birth_date` hari ini (mis. anak yang umurnya sudah lewat dari rentang kategori U-12 tapi masih tercatat U-12). **Murni informasional** — TIDAK mengubah data otomatis, admin yang putuskan mau update atau biarkan (konsisten dengan filosofi `suggestFor()` yang sudah ada: "ini hanya saran, main naik kelas itu wajar"). **Bukan scope**: scheduled job/cron auto-update kategori (project ini belum punya infrastruktur `Schedule::command()` sama sekali), notifikasi/email ke admin, mengubah halaman Edit Player yang sudah menampilkan saran serupa.

---

## Progress Implementasi

> Dicentang begitu tahap selesai dikerjakan **dan** lolos blok ✅ Cek dulu masing-masing. Update checklist ini tiap kali sebuah tahap selesai, supaya siapapun yang melanjutkan brief ini (termasuk sesi/agent lain) tahu persis titik berhentinya tanpa perlu re-check semua dari awal.

- [x] Tahap 1 — `PlayerCategoryService::suggestFromCollection()` (lint OK; verifikasi tinker: umur 14→U-15, umur 11→U-12, birth_date null→null, umur tanpa kategori cocok→null -- semua lolos)
- [x] Tahap 2 — Wire ke `players/index.blade.php` (blade compile OK; verifikasi HTTP nyata lewat 2 test baru di Tahap 4 -- badge muncul saat mismatch umur, tidak muncul saat kategori sudah sesuai)
- [x] Tahap 3 — Multi-Language (lang/en.json valid JSON tanpa duplikat; "Kategori" sudah ada sebelumnya, "Saran"/"Saran berdasarkan umur saat ini" ditambah & diverifikasi resolve ke "Suggested"/"Suggestion based on current age" saat locale=en)
- [x] Tahap 4 — Tests (2 test baru di `PlayerCategoryTest` lulus; full suite 176 test, 169 passed, baseline 5 failure + 2 error bawaan Breeze tidak bertambah)

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Panggil `PlayerCategoryService::suggestFor()` (yang query DB) di dalam loop `@foreach`/`@forelse` daftar player | Halaman index menampilkan banyak baris sekaligus (pagination) — memanggil method yang query DB per baris adalah N+1 query, melanggar `docs/query-performance.md`. WAJIB pakai versi in-memory (`suggestFromCollection()`, Tahap 1) yang beroperasi di atas collection kategori yang **sudah** di-fetch sekali untuk dropdown filter (`$playerCategoryOptions`), bukan query baru per baris | [Tahap 1](#tahap-1--playercategoryservicesuggestfromcollection) |
| Lupa filter `$playerCategoryOptions` per `id_academy` player sebelum mencari saran | Untuk Super Admin, `$playerCategoryOptions` berisi kategori dari **seluruh academy tercampur** (lihat `PlayerController::index()` yang passing `selectable(null)` kalau Super Admin). Kalau tidak difilter dulu ke `id_academy` milik player yang sedang diproses, saran bisa "nyasar" pakai rentang umur kategori academy lain | [Tahap 2](#tahap-2--wire-ke-playersindexbladephp) |
| Auto-update `id_player_category` player begitu terdeteksi mismatch | Ini **cuma indikator**, bukan koreksi otomatis — sama seperti `suggestFor()` yang sudah ada, keputusan mengubah kategori tetap di tangan admin/coach (main naik kelas itu skenario yang sengaja didukung, bukan bug) | [Tahap 2](#tahap-2--wire-ke-playersindexbladephp) |
| Bikin scheduled job/command Artisan untuk cek mismatch berkala | Project ini belum punya infrastruktur `Schedule::command()` sama sekali (lihat catatan scope di `issue12.md`/`issue15.md`) — indikator cukup dihitung on-the-fly saat halaman index dibuka, tidak perlu infrastruktur baru untuk masalah yang berubah pelan (ulang tahun setahun sekali) | — |

---

## 1. Konteks & Tujuan

`PlayerCategoryService::suggestFor($birthDate, $academyId)` sudah ada dan dipakai di halaman **Edit Player** untuk menyarankan kategori berdasarkan umur (dihitung live dari `birth_date`, bukan snapshot). Masalahnya: saran ini cuma kelihatan kalau admin **membuka** halaman Edit satu player tertentu — tidak ada cara melihat sekilas "player mana saja yang kategorinya sudah tidak sesuai umur" tanpa membuka satu-satu.

Brief ini menambahkan badge kecil di halaman **index** Players (tempat admin biasanya browsing banyak player sekaligus) yang muncul di baris player mana pun yang kategorinya tidak lagi cocok dengan umurnya hari ini — reuse logic yang sama persis, cuma dipindah exposure-nya, tanpa infrastruktur baru.

## 2. Cara Kerja Solusi

### 2a. In-memory, bukan query per baris

Halaman index sudah meng-*eager-load* & fetch `$playerCategoryOptions` sekali (dipakai dropdown filter kategori). Brief ini menambah `PlayerCategoryService::suggestFromCollection()` — versi `suggestFor()` yang menerima collection kategori yang **sudah ada di memori**, bukan query baru. Dipanggil sekali per baris di view, tapi tidak pernah menyentuh database lagi.

### 2b. Filter per `id_academy` dulu (kasus Super Admin)

`$playerCategoryOptions` bisa berisi kategori lintas-academy (Super Admin). Sebelum mencari saran untuk 1 player, collection itu difilter dulu ke `id_academy` milik player tersebut (`$playerCategoryOptions->where('id_academy', $player->id_academy)`), baru dicari kategori yang `min_age`/`max_age`-nya cocok dengan umur player.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `app/Services/PlayerCategoryService.php` | ✏️ Tambah `suggestFromCollection()` | 1 |
| `resources/views/players/index.blade.php` | ✏️ Tambah badge mismatch di Table & Card List | 2 |
| `lang/en.json` | ✏️ Entry baru | 3 |
| `tests/Feature/PlayerCategoryTest.php` | ✏️ Tambah 2 test baru | 4 |

---

## Tahap 1 — `PlayerCategoryService::suggestFromCollection()`

`app/Services/PlayerCategoryService.php` — tambah method baru **setelah** `suggestFor()`:

```php
/**
 * Versi in-memory dari suggestFor() -- beroperasi di atas collection
 * kategori yang SUDAH di-fetch (mis. $playerCategoryOptions di halaman
 * index Players), TIDAK query database lagi. Wajib dipakai kalau
 * pemanggilnya di dalam loop banyak baris (issue17.md Aturan Emas --
 * cegah N+1, lihat docs/query-performance.md).
 *
 * $categories HARUS sudah difilter ke id_academy yang benar oleh
 * pemanggil sebelum dipassing ke sini -- method ini tidak melakukan
 * filter academy sendiri.
 */
public function suggestFromCollection(Collection $categories, Carbon|string|null $birthDate): ?PlayerCategory
{
    if (! $birthDate) {
        return null;
    }

    $age = Carbon::parse($birthDate)->age;

    return $categories
        ->where('status', true)
        ->filter(fn (PlayerCategory $category) => $category->min_age <= $age && $category->max_age >= $age)
        ->sortBy('min_age')
        ->first();
}
```

**✅ Cek dulu**

```bash
php -l app/Services/PlayerCategoryService.php
```

```php
php artisan tinker
$academy = \App\Models\Academy::factory()->create();
$u12 = \App\Models\PlayerCategory::factory()->create(['id_academy' => $academy->id_academy, 'name' => 'U-12', 'min_age' => 10, 'max_age' => 12]);
$u15 = \App\Models\PlayerCategory::factory()->create(['id_academy' => $academy->id_academy, 'name' => 'U-15', 'min_age' => 13, 'max_age' => 15]);
$categories = \App\Models\PlayerCategory::where('id_academy', $academy->id_academy)->get();

// Umur 14 -- harus dapat U-15
app(\App\Services\PlayerCategoryService::class)->suggestFromCollection($categories, now()->subYears(14))->name; // "U-15"
```

---

## Tahap 2 — Wire ke `players/index.blade.php`

Tambah closure **setelah** blok `@php ... @endphp` yang sudah ada di baris ~34-37:

```blade
@php
    $allCount = array_sum($statusCounts);
    $hasActiveFilters = !empty($filters);

    // Saran kategori in-memory (TIDAK query DB per baris, issue17.md
    // Aturan Emas) -- dipakai buat badge "saran kategori" kalau beda
    // dari yang tersimpan.
    $suggestedCategoryFor = function ($player) use ($playerCategoryOptions) {

        $academyCategories = $playerCategoryOptions->where('id_academy', $player->id_academy);

        return app(\App\Services\PlayerCategoryService::class)
            ->suggestFromCollection($academyCategories, $player->birth_date);
    };
@endphp
```

Ganti blok badge kategori di **Table** (baris ~173-179):

```blade
<td class="table-cell">
    @if ($player->playerCategory)
        <span class="badge badge-secondary">{{ $player->playerCategory->name }}</span>

        @php $suggested = $suggestedCategoryFor($player); @endphp
        @if ($suggested && $suggested->id_player_category !== $player->id_player_category)
            <span class="table-subtitle" title="{{ __('Saran berdasarkan umur saat ini') }}">
                ⚠ {{ __('Saran') }}: {{ $suggested->name }}
            </span>
        @endif
    @else
        <span class="table-subtitle">-</span>
    @endif
</td>
```

Ganti blok badge kategori di **Card List** (baris ~374-380):

```blade
<div class="table-card-field">
    <span class="table-card-label">{{ __('Kategori') }}</span>
    @if ($player->playerCategory)
        <span class="badge badge-secondary w-fit">{{ $player->playerCategory->name }}</span>

        @php $suggested = $suggestedCategoryFor($player); @endphp
        @if ($suggested && $suggested->id_player_category !== $player->id_player_category)
            <span class="table-subtitle" title="{{ __('Saran berdasarkan umur saat ini') }}">
                ⚠ {{ __('Saran') }}: {{ $suggested->name }}
            </span>
        @endif
    @else
        <span class="table-subtitle">-</span>
    @endif
</div>
```

Catatan: kalau `$suggested` `null` (tidak ada kategori aktif yang rentang umurnya cocok sama sekali), badge peringatan **tidak** ditampilkan — itu skenario lain (academy belum punya kategori untuk rentang umur itu), di luar scope brief ini.

**✅ Cek dulu**: buat 1 Academy, 1 Player Category "U-12" (`min_age: 10, max_age: 12`), 1 Player dengan `birth_date` yang umurnya 12 tahun dan `id_player_category` = U-12 tadi → **tidak ada** badge peringatan (cocok). Ubah `birth_date` player itu supaya umurnya jadi 15 tahun (lewat Edit Player, kategori dibiarkan tetap U-12) → buka index, badge peringatan **"⚠ Saran: ..."** muncul (kalau ada kategori U-15 yang mencakup umur 15) di baris player itu, baik di tampilan desktop (table) maupun mobile (card list, resize browser < 1024px).

---

## Tahap 3 — Multi-Language

Tambah ke `lang/en.json` (cek dulu key `Saran`/`Kategori` mungkin belum ada):

```json
"Saran": "Suggested",
"Saran berdasarkan umur saat ini": "Suggestion based on current age"
```

**✅ Cek dulu**: `php -r "json_decode(file_get_contents('lang/en.json'), true) or die('invalid json');"`, buka `/players?locale=en` — badge "⚠ Suggested: ..." muncul dalam Bahasa Inggris kalau ada mismatch.

---

## Tahap 4 — Tests

`tests/Feature/PlayerCategoryTest.php` — tambah 1 helper + 2 method test baru di akhir class. Semua import yang dibutuhkan (`Permission`, `PermissionRegistrar`, `Role`, `User`) **sudah ada** di file ini, tidak perlu tambah `use` baru:

```php
protected function actingAsPlayerViewer(Academy $academy): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'player.view', 'guard_name' => 'web']);

    $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::where('name', 'player.view')->get());

    $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
    $user->assignRole($role);

    $this->actingAs($user);

    return $user;
}

public function test_index_players_menampilkan_saran_kategori_saat_mismatch(): void
{
    $academy = Academy::factory()->create();

    $categoryU12 = PlayerCategory::factory()->create([
        'id_academy' => $academy->id_academy, 'name' => 'U-12', 'min_age' => 10, 'max_age' => 12,
    ]);

    PlayerCategory::factory()->create([
        'id_academy' => $academy->id_academy, 'name' => 'U-15', 'min_age' => 13, 'max_age' => 15,
    ]);

    Player::create([
        'id_academy' => $academy->id_academy,
        'id_player_category' => $categoryU12->id_player_category,
        'player_code' => 'TEST00001',
        'name' => 'Test Player',
        'birth_date' => now()->subYears(14)->format('Y-m-d'), // umur 14, tersimpan U-12 -- MISMATCH
        'gender' => 'male',
    ]);

    $this->actingAsPlayerViewer($academy);

    $this->get(route('players.index'))
        ->assertOk()
        ->assertSee('Saran');
}

public function test_index_players_tidak_menampilkan_saran_kalau_kategori_sudah_sesuai(): void
{
    $academy = Academy::factory()->create();

    $categoryU12 = PlayerCategory::factory()->create([
        'id_academy' => $academy->id_academy, 'name' => 'U-12', 'min_age' => 10, 'max_age' => 12,
    ]);

    Player::create([
        'id_academy' => $academy->id_academy,
        'id_player_category' => $categoryU12->id_player_category,
        'player_code' => 'TEST00002',
        'name' => 'Test Player Cocok',
        'birth_date' => now()->subYears(11)->format('Y-m-d'), // umur 11, cocok U-12
        'gender' => 'male',
    ]);

    $this->actingAsPlayerViewer($academy);

    $this->get(route('players.index'))
        ->assertOk()
        ->assertDontSee('Saran');
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=PlayerCategoryTest
php artisan test
```

Semua test baru lulus, baseline kegagalan bawaan Breeze (5 failure + 2 error) tidak bertambah.

---

## Ringkasan Alur Akhir

```text
GET /players (index)
│
├── PlayerController::index() -- TIDAK BERUBAH, playerCategoryOptions sudah di-fetch
│
└── View: @foreach player
      │
      └── $suggestedCategoryFor($player)
            ├── filter playerCategoryOptions ke id_academy player ini (in-memory)
            └── PlayerCategoryService::suggestFromCollection() (in-memory, TIDAK query DB)
                  │
                  └── beda dari $player->id_player_category? -> tampilkan badge "⚠ Saran: ..."
                      (TIDAK auto-update apapun, murni informasional)
```
