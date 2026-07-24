# Brief: Cascading Dropdown Academy-Scoped + Tab Teams di Player + Rebuild Team Show

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Module Team/Season/Team Staff Position (`issue16.md`), indikator mismatch Player Category (`issue17.md`) **wajib sudah selesai**. Baca dulu `issue18.md` (catatan review lengkap + keputusan yang sudah disepakati untuk brief ini) **dan** `docs/frontend-standard.md` (*Tabel Responsif*, *Tabs Status*, *Reusable View dengan Data Dinamis*, *Urutan & Pengelompokan Field Form*) — **wajib**, jangan mulai coding sebelum baca keduanya. Modul referensi paling mirip: `resources/views/players/create.blade.php` (pola Alpine `x-data` di form, walau untuk kasus BEDA — baca Bagian 2a kenapa brief ini TIDAK meniru pola itu), `resources/views/employment-contracts/index.blade.php` (pola Table+Card List untuk sub-resource roster), `resources/js/components/currency-input.js` + `app/View/Components/CurrencyInput.php` (pola Alpine helper module + class-based Component).
> **Bukan module baru** — ini kumpulan perbaikan UI/UX + 1 bug lintas-module, hasil review manual di `issue18.md`. **Cara pakai brief ini**: Kerjakan Tahap 1 → 12 berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: (a) Cascading dropdown academy-scoped (AJAX) untuk `Team`/`Staff`/`Staff Position` — supaya dropdown Season/Player Category/Employment Type/Staff Position/Role ikut ter-filter begitu Super Admin memilih Academy di form create; (b) Tab baru "Teams" di `players/show.blade.php`; (c) Rebuild `teams/show.blade.php` supaya UI/UX lebih baik dan benar-benar responsif di device kecil. **Bukan scope**: module Player (sudah benar, pakai pola client-side filter berbeda — lihat Bagian 2a), form **edit** manapun (tidak ambigu, `id_academy` sudah tetap), module lain di luar 3 yang disebutkan.

---

## Progress Implementasi

> Dicentang begitu tahap selesai dikerjakan **dan** lolos blok ✅ Cek dulu masing-masing. Update checklist ini tiap kali sebuah tahap selesai.

- [x] Tahap 1 — Alpine helper `academyCascade()` (`resources/js/components/academy-cascade.js`) + registrasi di `app.js` (npm run build sukses tanpa error)
- [x] Tahap 2 — Team: Route `teams.cascade-options` + `TeamController::cascadeOptions()` + update `create()` (lint OK, route:list konfirmasi urutan benar sebelum teams.show)
- [x] Tahap 3 — Team: Wire `teams/create.blade.php` (blade compile OK; verifikasi HTTP endpoint penuh menyusul di Tahap 11)
- [x] Tahap 4 — Staff: Route `staff.cascade-options` + `StaffController::cascadeOptions()` + update `create()` (lint OK, route:list konfirmasi urutan benar sebelum staff.show)
- [x] Tahap 5 — Staff: Wire `staff/create.blade.php` (blade compile OK)
- [x] Tahap 6 — Staff Position: Route `staff-positions.cascade-options` + `StaffPositionController::cascadeOptions()` + update `create()` (lint OK, route terdaftar)
- [x] Tahap 7 — Staff Position: Wire `staff-positions/create.blade.php` (blade compile OK; branch @else user academy biasa terverifikasi tidak berubah)
- [x] Tahap 8 — Player Show: Tab "Teams" (lint+compile OK, nesting div terverifikasi benar; verifikasi HTTP penuh di Tahap 11)
- [x] Tahap 9 — Team Show: Rebuild penuh (UI/UX + responsif) (lint+compile OK; verifikasi tinker: availablePlayers exclude anggota aktif lolos; verifikasi HTTP+table-card-list penuh di Tahap 11)
- [x] Tahap 10 — Multi-Language (lang/en.json valid, tanpa duplikat; 8 string baru dari Tahap 1-9 ditambahkan; string pre-existing di luar scope seperti "Kewarganegaraan"/"Edit Staff" sengaja TIDAK disentuh)
- [x] Tahap 11 — Tests (3 test cascade+responsive baru di TeamTest, 2 di StaffTest, 2 di StaffPositionTest, PlayerTest.php baru dengan 2 test -- semua lulus; full suite 185 test, 178 passed, baseline 5 failure + 2 error Breeze tidak bertambah)
- [x] Tahap 12 — Dokumentasi (2 pola baru ditambah ke docs/frontend-standard.md + TOC + Development Rules + Summary; docs/permission-reference.md: 3 catatan endpoint cascade-options ditambah di section Team/Staff/Staff Position; route:list cross-check cocok)

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Tiru pola `players/create.blade.php` (embed semua data + filter client-side) untuk Team/Staff/Staff Position | **Sudah didiskusikan & diputuskan** (`issue18.md` Temuan 3-5) — brief ini **sengaja** pakai cascading AJAX walau Player pakai pola client-side filter. Dua pola beda ini **boleh** hidup berdampingan; jangan "menyeragamkan" Team/Staff/Staff Position ke pola Player tanpa didiskusikan ulang | [Bagian 2a](#2a-kenapa-ajax-bukan-client-side-filter-seperti-player) |
| Taruh route `*.cascade-options` **setelah** `Route::resource(...)` yang bersangkutan | `Route::resource('teams', ...)` mendaftarkan `GET teams/{team}` (show). Kalau `teams/cascade-options` didaftarkan belakangan, Laravel akan mencocokkan `teams/{team}` duluan dan menganggap `"cascade-options"` sebagai **UUID team** (404/error), bukan hit endpoint baru. **Wajib** didaftarkan **sebelum** `Route::resource()` modul yang sama | [Tahap 2](#tahap-2--team-route--controller-cascadeoptions--update-create), [Tahap 4](#tahap-4--staff-route--controller-cascadeoptions--update-create), [Tahap 6](#tahap-6--staff-position-route--controller-cascadeoptions--update-create) |
| Sentuh form **edit** Team/Staff/Staff Position | `id_academy` di form edit **sudah pasti** (dari record yang diedit), tidak ada ambiguitas academy mana yang dipakai — beda dari form **create** dimana Super Admin baru memilih academy di form yang sama. Cascading dropdown **hanya** relevan di create | — |
| Query Eloquent langsung di Blade (`\App\Models\Player::where(...)->get()` dst di `teams/show.blade.php`) | Ditemukan saat review (`issue18.md` Temuan 2) — melanggar Thin Controller (`docs/architecture.md`). Tahap 9 memindahkan semua query ini ke `TeamController::show()` | [Tahap 9](#tahap-9--team-show-rebuild-penuh-uiux--responsif) |
| Bungkus `table-card` dengan `table-card-list` (yang `lg:hidden`) di tab "Teams" milik `players/show.blade.php` | Tab ini ada di kolom **sempit** (bukan halaman index full-width) — `table` dengan `min-w-[1000px]` justru bikin scroll horizontal aneh di kolom sempit. Cukup pakai `table-card` **tanpa** wrapper `table-card-list`, supaya tampil identik di semua breakpoint (lihat Bagian 2c) | [Tahap 8](#tahap-8--player-show-tab-teams) |

---

## 1. Konteks & Tujuan

Brief ini menuntaskan 3 dari 5 temuan review di `issue18.md` (Temuan 1, 2, dan 3-5 — semua sudah didiskusikan & diputuskan, **tidak perlu diskusi ulang**, langsung kerjakan):

1. **Player show belum ada tab "Teams"** — relasi `Player::teamPlayers()` sudah ada sejak `issue16.md` Tahap 10, tapi tidak pernah dipakai.
2. **Team show UI/UX kurang & bug responsif nyata** — tab Players/Staff di `teams/show.blade.php` cuma pakai `table-wrapper` tanpa `table-card-list`, jadi **hilang total** di layar < 1024px.
3. **Dropdown academy-scoped tidak ikut Academy yang dipilih** — di form create `Team`/`Staff`/`Staff Position`, begitu Super Admin memilih Academy, dropdown anak (Season+Player Category / Employment Type+Staff Position / Role) tidak ikut ter-filter — Super Admin melihat opsi lintas-academy tercampur (atau di-`groupBy` tapi tetap tidak sinkron ke pilihan Academy).

## 2. Cara Kerja Solusi

### 2a. Kenapa AJAX, bukan client-side filter seperti Player

`players/create.blade.php` sudah punya solusi utk masalah sejenis: embed **seluruh** data (`@js($playerTypes)`) ke Alpine, lalu filter di getter (`availableTypes`) berdasar `academyId` yang dipilih — tanpa request baru sama sekali. Solusi ini **valid dan sengaja tidak diseragamkan** ke Team/Staff/Staff Position di brief ini — sudah didiskusikan dengan user (`issue18.md`), keputusannya tetap pakai **cascading AJAX** (fetch endpoint JSON kecil saat Academy berubah). Jangan "memperbaiki" ke pola Player tanpa diskusi ulang.

### 2b. Kontrak JSON endpoint `*.cascade-options`

Supaya Alpine helper-nya **satu** dan reusable di 3 module, endpoint tiap module **wajib** balikin bentuk JSON yang sama: object dengan key = `name` attribute target `<select>`, value = array `{value, label}`:

```json
{
    "id_season": [{"value": "uuid-season-1", "label": "2026"}],
    "id_player_category": [{"value": "uuid-category-1", "label": "U-12"}]
}
```

Endpoint **wajib** resolve `$academyId` dengan pola yang sama persis seperti `resolveAcademyId()` di Service lain (`AcademyService::isSuperAdmin()`), **BUKAN** langsung percaya `$request->query('id_academy')` mentah-mentah — supaya user academy biasa tidak bisa mengintip data academy lain lewat endpoint ini walau mengubah query string manual:

```php
$academyId = $this->academyService->isSuperAdmin()
    ? $request->query('id_academy')
    : $this->academyService->currentId();
```

### 2c. `table-card` tanpa `table-card-list` untuk panel sempit

`docs/frontend-standard.md` mewajibkan **Table (desktop) + Card List (mobile/tablet)** untuk halaman **index/list** full-width. Tab "Teams" di `players/show.blade.php` **bukan** halaman index — dia ada di kolom kiri (2/3 lebar) sebuah halaman detail. Memaksakan `table` (`min-w-[1000px]`) di kolom sempit itu bikin scroll horizontal yang aneh bahkan di desktop biasa. Solusinya: pakai `table-card` (class card individual, **tanpa** wrapper `table-card-list` yang `lg:hidden`) sebagai **satu-satunya** representasi, tampil identik di semua breakpoint — bukan dual-render. Ini pola baru, didokumentasikan di Tahap 12.

### 2d. `teams/show.blade.php`: full-width + info strip, bukan grid 2/3+1/3 ala Player

`players/show.blade.php` pakai grid `lg:grid-cols-3` (2/3 tab + 1/3 sidebar) karena kontennya banyak field pendek (label+value). `Team` show kontennya **tabel roster** (Players/Staff) yang butuh lebar penuh untuk `table-wrapper` (`min-w-[1000px]`) bekerja benar sesuai `docs/frontend-standard.md`. Kalau dipaksa ke kolom 2/3, tabel jadi scroll horizontal terus-menerus bahkan di desktop biasa. Solusi Tahap 9: tetap **full-width** (bukan grid 2 kolom), tapi tambah **info strip** (badge Season/Category/Type/Status/counts) di bawah header, dan tab Players/Staff pakai `tabs`/`table-wrapper`+`table-card-list` yang benar — reuse *pola yang benar* dari Player (avatar header, class `tabs`, Table+Card List), bukan reuse *struktur grid*-nya.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `resources/js/components/academy-cascade.js`, `resources/js/app.js` | 🆕/✏️ | 1 |
| `routes/web.php`, `app/Http/Controllers/TeamController.php` | ✏️ | 2 |
| `resources/views/teams/create.blade.php` | ✏️ | 3 |
| `routes/web.php`, `app/Http/Controllers/StaffController.php` | ✏️ | 4 |
| `resources/views/staff/create.blade.php` | ✏️ | 5 |
| `routes/web.php`, `app/Http/Controllers/StaffPositionController.php` | ✏️ | 6 |
| `resources/views/staff-positions/create.blade.php` | ✏️ | 7 |
| `app/Http/Controllers/PlayerController.php`, `resources/views/players/show.blade.php` | ✏️ | 8 |
| `app/Http/Controllers/TeamController.php`, `resources/views/teams/show.blade.php` | ✏️ | 9 |
| `lang/en.json` | ✏️ | 10 |
| `tests/Feature/TeamTest.php`, `StaffTest.php`, `StaffPositionTest.php`, `tests/Feature/PlayerTest.php` (baru) | ✏️/🆕 | 11 |
| `docs/frontend-standard.md`, `docs/permission-reference.md` | ✏️ | 12 |

---

## Tahap 1 — Alpine helper `academyCascade()`

`resources/js/components/academy-cascade.js` (🆕):

```js
/**
 * Cascading dropdown academy-scoped: dipakai di form create Team/Staff/Staff
 * Position, hanya efektif untuk Super Admin (yang punya select id_academy).
 * User academy biasa: dropdown anak sudah benar sejak render server, x-data
 * ini idle (idAcademy selalu kosong, init() tidak pernah trigger fetch).
 *
 * endpoint -- URL endpoint JSON module ybs (mis. route('teams.cascade-options')).
 * Kontrak response: { [nama_select_anak]: [{value, label}, ...], ... }
 * (issue19.md Bagian 2b). Jangan dipakai untuk form EDIT (Aturan Emas).
 */
export default (endpoint) => ({
    idAcademy: '',
    loading: false,

    // oldAcademyId/oldSelected dipanggil dari x-init -- restore state kalau
    // form ini re-render gara-gara validasi gagal (Super Admin sudah pilih
    // academy tapi ada error field lain).
    init(oldAcademyId = '', oldSelected = {}) {
        if (oldAcademyId) {
            this.idAcademy = oldAcademyId;
            this.loadOptions(oldSelected);
        }
    },

    async loadOptions(restoreSelected = {}) {
        const targets = Object.keys(restoreSelected);

        if (!this.idAcademy) {
            targets.forEach((name) => this.resetSelect(name));
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(`${endpoint}?id_academy=${this.idAcademy}`, {
                headers: { Accept: 'application/json' },
            });

            const options = await response.json();

            Object.keys(options).forEach((name) => {
                this.fillSelect(name, options[name], restoreSelected[name] ?? '');
            });
        } finally {
            this.loading = false;
        }
    },

    resetSelect(name) {
        const select = this.$el.querySelector(`[name="${name}"]`);
        if (!select) return;

        select.querySelectorAll('option:not(:first-child)').forEach((opt) => opt.remove());
        select.value = '';
    },

    fillSelect(name, options, selectedValue) {
        const select = this.$el.querySelector(`[name="${name}"]`);
        if (!select) return;

        select.querySelectorAll('option:not(:first-child)').forEach((opt) => opt.remove());

        options.forEach((opt) => {
            const el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.label;
            if (String(opt.value) === String(selectedValue)) el.selected = true;
            select.appendChild(el);
        });
    },
});
```

`resources/js/app.js` — tambah import + registrasi (pola sama `currencyInput`):

```js
import academyCascade from './components/academy-cascade';
```

```js
Alpine.data('academyCascade', academyCascade);
```

**✅ Cek dulu**: `npm run build` (atau `npm run dev` kalau pakai Vite dev server) tidak error. Buka console browser, ketik `Alpine` — pastikan tidak ada error import.

---

## Tahap 2 — Team: Route + Controller `cascadeOptions()` + update `create()`

`routes/web.php` — tambah route baru **PERSIS SEBELUM** `Route::resource('teams', ...)` (Aturan Emas — urutan wajib):

```php
    /*
    |--------------------------------------------------------------------------
    | Team Cascade Options (AJAX academy-scoped, issue19.md)
    |--------------------------------------------------------------------------
    | WAJIB didaftarkan SEBELUM Route::resource('teams', ...) di bawah ini --
    | kalau dipindah ke bawah, "cascade-options" akan ketangkep GET
    | teams/{team} (show) dan dianggap UUID team, bukan hit endpoint ini.
    */
    Route::get('teams/cascade-options', [TeamController::class, 'cascadeOptions'])
        ->name('teams.cascade-options')
        ->middleware('permission:team.create');

    Route::resource('teams', TeamController::class)
        ->middlewareFor(['index', 'show'], 'permission:team.view')
        ->middlewareFor(['create', 'store'], 'permission:team.create')
        ->middlewareFor(['edit', 'update'], 'permission:team.update')
        ->middlewareFor('destroy', 'permission:team.delete');
```

`app/Http/Controllers/TeamController.php` — tambah method `cascadeOptions()`, dan ubah `create()`:

```php
public function cascadeOptions(Request $request)
{
    $academyId = $this->academyService->isSuperAdmin()
        ? $request->query('id_academy')
        : $this->academyService->currentId();

    return response()->json([
        'id_season' => $this->seasonService->selectable($academyId)
            ->map(fn ($season) => ['value' => $season->id_season, 'label' => $season->name])
            ->values(),
        'id_player_category' => $this->playerCategoryService->selectable($academyId)
            ->map(fn ($category) => ['value' => $category->id_player_category, 'label' => $category->name])
            ->values(),
    ]);
}
```

Ubah `create()` — Super Admin sekarang dapat collection **kosong** (diisi AJAX begitu pilih Academy), user academy biasa **tidak berubah**:

```php
public function create()
{
    $academyId = $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId();

    return view('teams.create', [
        'title' => __('Tambah Team'),
        'breadcrumb' => [
            ['label' => __('Team'), 'url' => route('teams.index')],
            ['label' => __('Tambah Team')],
        ],
        'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        'academies' => $this->academyService->isSuperAdmin() ? Academy::orderBy('name')->get() : collect(),
        // Super Admin: dropdown ini KOSONG di render awal, diisi AJAX oleh
        // academyCascade() begitu Academy dipilih (issue19.md/issue18.md
        // Temuan 3-5). User academy biasa: tetap ter-scope penuh seperti
        // sebelumnya, TIDAK terpengaruh perubahan ini.
        'seasons' => $this->academyService->isSuperAdmin() ? collect() : $this->seasonService->selectable($academyId),
        'playerCategories' => $this->academyService->isSuperAdmin() ? collect() : $this->playerCategoryService->selectable($academyId),
    ]);
}
```

**✅ Cek dulu**

```bash
php -l app/Http/Controllers/TeamController.php
php artisan route:list --name=teams.cascade-options
```

Route harus muncul **sebelum** `teams.show` di urutan `route:list` (urutan pendaftaran, bukan alfabet — `route:list` biasanya menampilkan sesuai urutan definisi, pastikan tidak ada error "route not found" kalau diakses).

```bash
php artisan tinker
```

```php
// Simulasikan lewat HTTP nanti di Tahap 11 (test). Cek dulu manual:
$academy = \App\Models\Academy::factory()->create();
\App\Models\Season::factory()->create(['id_academy' => $academy->id_academy, 'name' => '2026']);
app(\App\Services\SeasonService::class)->selectable($academy->id_academy)->pluck('name'); // ["2026"]
```

---

## Tahap 3 — Team: Wire `teams/create.blade.php`

Ubah tag `<form>` (baris ~24) — tambah `x-data`/`x-init`:

```blade
<form action="{{ route('teams.store') }}" method="POST"
    x-data="academyCascade('{{ route('teams.cascade-options') }}')"
    x-init="init('{{ old('id_academy') }}', { id_season: '{{ old('id_season') }}', id_player_category: '{{ old('id_player_category') }}' })">
```

Ubah `<select name="id_academy">` (di dalam `@if ($isSuperAdmin)`) — tambah `x-model` + `@change`:

```blade
<select name="id_academy" x-model="idAcademy"
    @change="loadOptions({ id_season: '', id_player_category: '' })"
    class="form-select @error('id_academy') form-danger @enderror" required>
    <option value="">{{ __('Pilih Academy') }}</option>
    @foreach ($academies as $academy)
        <option value="{{ $academy->id_academy }}" @selected(old('id_academy') === $academy->id_academy)>
            {{ $academy->name }}
        </option>
    @endforeach
</select>
```

Ubah `<select name="id_season">` — tambah `:disabled="loading"` + helper text kalau Super Admin belum pilih Academy:

```blade
<select name="id_season" :disabled="loading"
    class="form-select @error('id_season') form-danger @enderror" required>
    <option value="">{{ __('Pilih Season') }}</option>
    @foreach ($seasons as $season)
        <option value="{{ $season->id_season }}" @selected(old('id_season') === $season->id_season)>
            {{ $season->name }}
        </option>
    @endforeach
</select>

@if ($isSuperAdmin)
    <p class="form-helper" x-show="!idAcademy" x-cloak>{{ __('Pilih Academy dulu untuk melihat pilihan Season.') }}</p>
@endif
```

Ubah `<select name="id_player_category">` — pola identik:

```blade
<select name="id_player_category" :disabled="loading"
    class="form-select @error('id_player_category') form-danger @enderror" required>
    <option value="">{{ __('Pilih Player Category') }}</option>
    @foreach ($playerCategories as $category)
        <option value="{{ $category->id_player_category }}" @selected(old('id_player_category') === $category->id_player_category)>
            {{ $category->name }}
        </option>
    @endforeach
</select>

@if ($isSuperAdmin)
    <p class="form-helper" x-show="!idAcademy" x-cloak>{{ __('Pilih Academy dulu untuk melihat pilihan Player Category.') }}</p>
@endif
```

**✅ Cek dulu**: `php -l resources/views/teams/create.blade.php` (Blade tetap valid PHP di dalam `@php`, tapi `-l` untuk file `.blade.php` cuma cek tag PHP mentahnya — validasi utama tetap lewat compile check):

```bash
php artisan tinker --execute="Illuminate\Support\Facades\Blade::compileString(file_get_contents(resource_path('views/teams/create.blade.php'))); echo 'compiled OK';"
```

Manual: login sebagai Super Admin, buka `/teams/create` — dropdown Season & Player Category **kosong** (cuma placeholder). Pilih 1 Academy — dropdown Season & Player Category **otomatis terisi** cuma milik academy itu (buka Network tab browser, harus ada request `GET /teams/cascade-options?id_academy=...`). Ganti Academy ke yang lain — dropdown ke-reset & refetch. Submit dengan Season yang valid — tersimpan normal.

---

## Tahap 4 — Staff: Route + Controller `cascadeOptions()` + update `create()`

`routes/web.php` — tambah **SEBELUM** `Route::resource('staff', ...)`:

```php
    Route::get('staff/cascade-options', [StaffController::class, 'cascadeOptions'])
        ->name('staff.cascade-options')
        ->middleware('permission:staff.create');

    Route::resource('staff', StaffController::class)
        ->middlewareFor(['index', 'show'], 'permission:staff.view')
        ->middlewareFor(['create', 'store'], 'permission:staff.create')
        ->middlewareFor(['edit', 'update'], 'permission:staff.update')
        ->middlewareFor('destroy', 'permission:staff.delete');
```

`app/Http/Controllers/StaffController.php`:

```php
public function cascadeOptions(Request $request)
{
    $academyId = $this->academyService->isSuperAdmin()
        ? $request->query('id_academy')
        : $this->academyService->currentId();

    return response()->json([
        'id_employment_type' => $this->employmentTypeService->selectable($academyId)
            ->map(fn ($type) => ['value' => $type->id_employment_type, 'label' => $type->name])
            ->values(),
        'id_staff_position' => $this->staffPositionService->selectable($academyId)
            ->map(fn ($position) => ['value' => $position->id_staff_position, 'label' => $position->name])
            ->values(),
    ]);
}
```

Ubah `create()` — sama pola Tahap 2, Super Admin dapat collection kosong:

```php
public function create()
{
    $isSuperAdmin = $this->academyService->isSuperAdmin();
    $academyId = $isSuperAdmin ? null : $this->academyService->currentId();

    return view('staff.create', [
        'title' => __('Tambah Staff'),
        'breadcrumb' => [
            ['label' => __('Staff'), 'url' => route('staff.index')],
            ['label' => __('Tambah Staff')],
        ],
        'isSuperAdmin' => $isSuperAdmin,
        'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
        // Super Admin: diisi AJAX (issue19.md Tahap 4-5). User academy biasa: tidak berubah.
        'employmentTypes' => $isSuperAdmin ? collect() : $this->employmentTypeService->selectable($academyId),
        'staffPositions' => $isSuperAdmin ? collect() : $this->staffPositionService->selectable($academyId),
        'canViewSalary' => $isSuperAdmin || auth()->user()->can('salary.view'),
    ]);
}
```

**✅ Cek dulu**: `php -l app/Http/Controllers/StaffController.php`, `php artisan route:list --name=staff.cascade-options` muncul & terdaftar sebelum `staff.show`.

---

## Tahap 5 — Staff: Wire `staff/create.blade.php`

Ubah tag `<form>` (baris ~24):

```blade
<form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data"
    x-data="academyCascade('{{ route('staff.cascade-options') }}')"
    x-init="init('{{ old('id_academy') }}', { id_employment_type: '{{ old('id_employment_type') }}', id_staff_position: '{{ old('id_staff_position') }}' })">
```

Ubah `<select name="id_academy">` (di dalam `@if ($isSuperAdmin)`):

```blade
<select name="id_academy" x-model="idAcademy"
    @change="loadOptions({ id_employment_type: '', id_staff_position: '' })"
    class="form-select @error('id_academy') form-danger @enderror" required>
    <option value="">{{ __('Pilih Academy') }}</option>
    @foreach ($academies as $academy)
        <option value="{{ $academy->id_academy }}" @selected(old('id_academy') === $academy->id_academy)>
            {{ $academy->name }}
        </option>
    @endforeach
</select>
```

Ubah `<select name="id_employment_type">`:

```blade
<select name="id_employment_type" :disabled="loading"
    class="form-select @error('id_employment_type') form-danger @enderror" required>
    <option value="">{{ __('Pilih Employment Type') }}</option>
    @foreach ($employmentTypes as $type)
        <option value="{{ $type->id_employment_type }}" @selected(old('id_employment_type') === $type->id_employment_type)>
            {{ $type->name }}
        </option>
    @endforeach
</select>

@if ($isSuperAdmin)
    <p class="form-helper" x-show="!idAcademy" x-cloak>{{ __('Pilih Academy dulu untuk melihat pilihan Employment Type.') }}</p>
@endif
```

Ubah `<select name="id_staff_position">`:

```blade
<select name="id_staff_position" :disabled="loading"
    class="form-select @error('id_staff_position') form-danger @enderror" required>
    <option value="">{{ __('Pilih Staff Position') }}</option>
    @foreach ($staffPositions as $position)
        <option value="{{ $position->id_staff_position }}" @selected(old('id_staff_position') === $position->id_staff_position)>
            {{ $position->name }}
        </option>
    @endforeach
</select>

@if ($isSuperAdmin)
    <p class="form-helper" x-show="!idAcademy" x-cloak>{{ __('Pilih Academy dulu untuk melihat pilihan Staff Position.') }}</p>
@endif
```

**✅ Cek dulu**: sama seperti Tahap 3 (compile check + manual: pilih Academy di `/staff/create`, dropdown Employment Type & Staff Position ke-refresh sesuai academy itu).

---

## Tahap 6 — Staff Position: Route + Controller `cascadeOptions()` + update `create()`

`routes/web.php` — tambah **SEBELUM** `Route::resource('staff-positions', ...)`:

```php
    Route::get('staff-positions/cascade-options', [StaffPositionController::class, 'cascadeOptions'])
        ->name('staff-positions.cascade-options')
        ->middleware('permission:staff_position.create');

    Route::resource('staff-positions', StaffPositionController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:staff_position.view')
        ->middlewareFor(['create', 'store'], 'permission:staff_position.create')
        ->middlewareFor(['edit', 'update'], 'permission:staff_position.update')
        ->middlewareFor('destroy', 'permission:staff_position.delete');
```

`app/Http/Controllers/StaffPositionController.php` — beda dari 2 module lain: `role_id` bukan lewat Service `selectable()`, tapi query `Role` langsung (pola yang sudah ada di `create()`):

```php
public function cascadeOptions(Request $request)
{
    $academyId = $this->academyService->isSuperAdmin()
        ? $request->query('id_academy')
        : $this->academyService->currentId();

    $roles = Role::where('id_academy', $academyId)
        ->orderBy('name')
        ->get()
        ->map(fn ($role) => ['value' => $role->id, 'label' => $role->name])
        ->values();

    return response()->json(['role_id' => $roles]);
}
```

Ubah `create()` — Super Admin dapat collection kosong untuk `roles` (dulunya `groupBy` academy, sekarang diisi AJAX):

```php
public function create()
{
    $isSuperAdmin = $this->academyService->isSuperAdmin();

    return view('staff-positions.create', [
        'title' => __('Tambah Staff Position'),
        'breadcrumb' => [
            ['label' => __('Staff Position'), 'url' => route('staff-positions.index')],
            ['label' => __('Tambah Staff Position')],
        ],
        'isSuperAdmin' => $isSuperAdmin,
        'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
        // Super Admin: diisi AJAX begitu Academy dipilih (issue19.md Tahap
        // 6-7) -- dulunya groupBy academy tapi tetap tidak sinkron ke
        // pilihan Academy (issue18.md Temuan 5). User academy biasa: tetap.
        'roles' => $isSuperAdmin ? collect() : Role::forCurrentAcademy()->orderBy('name')->get(),
    ]);
}
```

**✅ Cek dulu**: `php -l app/Http/Controllers/StaffPositionController.php`, `php artisan route:list --name=staff-positions.cascade-options` muncul.

---

## Tahap 7 — Staff Position: Wire `staff-positions/create.blade.php`

Ubah tag `<form>`:

```blade
<form action="{{ route('staff-positions.store') }}" method="POST"
    x-data="academyCascade('{{ route('staff-positions.cascade-options') }}')"
    x-init="init('{{ old('id_academy') }}', { role_id: '{{ old('role_id') }}' })">
```

Ubah `<select name="id_academy">` (di dalam `@if ($isSuperAdmin)`):

```blade
<select name="id_academy" x-model="idAcademy" @change="loadOptions({ role_id: '' })"
    class="form-select @error('id_academy') form-danger @enderror" required>
    ...isi @foreach tetap sama...
</select>
```

Ganti **HANYA** blok `@if ($isSuperAdmin) <select name="role_id">...optgroup...@endif` (baris ~99-111) — hapus `optgroup`, ganti jadi select polos yang diisi AJAX (blok `@else` di baris ~112-120 **TIDAK diubah sama sekali**, tetap punya cabang sendiri untuk user academy biasa):

```blade
@if ($isSuperAdmin)
    <select name="role_id" :disabled="loading" class="form-select @error('role_id') form-danger @enderror">
        <option value="">{{ __('Tidak ada / atur manual nanti') }}</option>
    </select>

    <p class="form-helper" x-show="!idAcademy" x-cloak>{{ __('Pilih Academy dulu untuk melihat pilihan Role.') }}</p>
@else
    <select name="role_id" class="form-select @error('role_id') form-danger @enderror">
        <option value="">{{ __('Tidak ada / atur manual nanti') }}</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>
                {{ $role->name }}
            </option>
        @endforeach
    </select>
@endif
```

**✅ Cek dulu**: manual, login Super Admin, buka `/staff-positions/create` — dropdown Role kosong sampai Academy dipilih, lalu terisi cuma Role academy itu (tanpa `optgroup` lagi, karena sudah 1 academy spesifik).

---

## Tahap 8 — Player Show: Tab "Teams"

`app/Http/Controllers/PlayerController.php` — tambah eager-load di `show()`:

```php
public function show(Player $player)
{
    $player->load([
        'academy',
        'playerType',
        'playerCategory',
        'primaryPosition',
        'secondaryPosition',
        'user.roles',
        // Histori keanggotaan tim (issue18.md Temuan 1) -- termasuk yang
        // sudah leave_date (histori roster), terbaru duluan.
        'teamPlayers' => fn ($query) => $query->with('team.season')->orderByDesc('join_date'),
    ]);

    return view('players.show',[
        'title'=>__('Detail Player'),
        'breadcrumb'=>[
            ['label'=>__('Players'), 'url'=>route('players.index')],
            ['label'=>__('Detail Player')]
        ],
        'player'=>$player
    ]);
}
```

`resources/views/players/show.blade.php` — tambah 1 tombol tab **setelah** tombol tab "Dokumen / File" (di dalam `<div class="tabs scrollbar-brand">`):

```blade
<button type="button" class="focus:outline-none" @click="tab='teams'"
    :class="tab === 'teams' ? 'tab tab-active' : 'tab'">
    {{ __('Teams') }}
</button>
```

Tambah tab-panel baru **setelah** panel `tab==='dokumen'` (sebelum `</div>` penutup box tab, baris ~259):

```blade
<div x-show="tab==='teams'" x-cloak class="tab-panel">

    <div class="space-y-3">
        @forelse ($player->teamPlayers as $teamPlayer)
            <div class="table-card">
                <div class="table-card-header">
                    <div class="min-w-0">
                        @can('team.view')
                            <a href="{{ route('teams.show', $teamPlayer->team) }}" class="table-title truncate">
                                {{ $teamPlayer->team->name }}
                            </a>
                        @else
                            <span class="table-title truncate">{{ $teamPlayer->team->name }}</span>
                        @endcan
                        <span class="table-subtitle truncate">
                            {{ $teamPlayer->team->code }} &middot; {{ $teamPlayer->team->season->name }}
                        </span>
                    </div>

                    @if ($teamPlayer->isActive())
                        <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                    @else
                        <span class="badge badge-secondary shrink-0">{{ __('Keluar') }}</span>
                    @endif
                </div>

                <div class="table-card-body">
                    <div class="table-card-field">
                        <span class="table-card-label">{{ __('Nomor Punggung') }}</span>
                        <span class="table-text">{{ $teamPlayer->jersey_number }}</span>
                    </div>

                    <div class="table-card-field">
                        <span class="table-card-label">{{ __('Captain') }}</span>
                        <span class="table-text">{{ $teamPlayer->is_captain ? __('Ya') : '-' }}</span>
                    </div>

                    <div class="table-card-field">
                        <span class="table-card-label">{{ __('Bergabung') }}</span>
                        <span class="table-text">{{ $teamPlayer->join_date->format('d M Y') }}</span>
                    </div>

                    @if (! $teamPlayer->isActive())
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Keluar') }}</span>
                            <span class="table-text">{{ $teamPlayer->leave_date->format('d M Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="table-card">
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" class="mb-3 text-gray-300 dark:text-gray-700">
                        <path d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z" stroke="currentColor" stroke-width="2.5" />
                    </svg>
                    <h4 class="empty-title">{{ __('Belum menjadi anggota tim manapun.') }}</h4>
                </div>
            </div>
        @endforelse
    </div>

</div>
```

Catatan: **jangan** bungkus `<div class="space-y-3">` di atas dengan `table-card-list` — lihat Aturan Emas & Bagian 2c (panel ini sudah sempit, tidak perlu dual-render Table+CardList).

**✅ Cek dulu**: `php -l app/Http/Controllers/PlayerController.php`. Buka `/players/{player}` untuk player yang sudah pernah di-assign ke tim (pakai `TeamPlayerService::assign()` lewat tinker kalau perlu data uji) — tab "Teams" muncul, menampilkan nama tim + season + nomor punggung + status. Player yang belum pernah gabung tim manapun → tampil empty-state, bukan kosong tanpa keterangan.

---

## Tahap 9 — Team Show: Rebuild Penuh (UI/UX + Responsif)

`app/Http/Controllers/TeamController.php` — tambah `use App\Models\Player;`, `use App\Models\Staff;`, `use App\Models\TeamStaffPosition;` di atas, lalu ganti method `show()`:

```php
public function show(Team $team)
{
    $team->load(['season', 'playerCategory', 'academy']);

    return view('teams.show', [
        'title' => $team->name,
        'breadcrumb' => [
            ['label' => __('Team'), 'url' => route('teams.index')],
            ['label' => $team->name],
        ],
        'team' => $team,
        'teamPlayers' => $team->teamPlayers()->with('player')->get(),
        'teamStaff' => $team->teamStaff()->with(['staff', 'teamStaffPosition'])->get(),
        // Dipindah dari view (issue18.md Temuan 2 -- query di Blade
        // melanggar Thin Controller, docs/architecture.md). Exclude
        // player/staff yang SUDAH aktif di tim ini supaya tidak bisa
        // dipilih dobel di form "Add Player"/"Assign Staff" (TeamPlayerService/
        // TeamStaffService sudah menolaknya, ini cuma mencegah percobaan sia-sia).
        'availablePlayers' => Player::where('id_academy', $team->id_academy)
            ->whereNotIn('id_player', $team->activeTeamPlayers()->pluck('id_player'))
            ->orderBy('name')
            ->get(),
        'availableStaff' => Staff::where('id_academy', $team->id_academy)
            ->whereNotIn('id_staff', $team->activeTeamStaff()->pluck('id_staff'))
            ->orderBy('full_name')
            ->get(),
        'teamStaffPositions' => TeamStaffPosition::where('id_academy', $team->id_academy)
            ->where('status', true)
            ->orderBy('name')
            ->get(),
    ]);
}
```

`resources/views/teams/show.blade.php` — **ganti seluruh isi file** dengan:

```blade
@extends('layouts.app', ['page' => 'teams'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card" x-data="{ tab: 'players' }">

        <div class="card-header">
            <div class="flex items-center gap-4">
                <div class="avatar avatar-lg avatar-square border border-gray-100 dark:border-gray-800">
                    <span class="avatar-placeholder">{{ strtoupper(substr($team->name, 0, 2)) }}</span>
                </div>

                <div>
                    <h3 class="card-title text-xl">{{ $team->name }}</h3>
                    <p class="card-description">
                        {{ $team->code }} &middot; {{ $team->season->name }}
                        @if ($team->academy)
                            &middot; {{ $team->academy->name }}
                        @endif
                    </p>
                </div>
            </div>

            @can('team.update')
                <div class="card-actions">
                    <a href="{{ route('teams.edit', $team) }}" class="btn btn-primary">{{ __('Edit Team') }}</a>
                </div>
            @endcan
        </div>

        {{-- Info strip --}}
        <div class="grid grid-cols-2 gap-4 border-b border-gray-100 p-5 sm:grid-cols-3 lg:grid-cols-6 dark:border-gray-800">
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Player Category') }}</span>
                <span class="badge badge-secondary">{{ $team->playerCategory->name }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Team Type') }}</span>
                <span class="table-text">{{ ucfirst($team->team_type) }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Status') }}</span>
                @if ($team->status)
                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                @else
                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                @endif
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Jumlah Player Aktif') }}</span>
                <span class="table-text">{{ $teamPlayers->whereNull('leave_date')->count() }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Jumlah Staff Aktif') }}</span>
                <span class="table-text">{{ $teamStaff->whereNull('leave_date')->count() }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Dibuat') }}</span>
                <span class="table-text">{{ $team->created_at->format('d M Y') }}</span>
            </div>
        </div>

        <div class="border-b border-gray-100 px-5 dark:border-gray-800">
            <div class="tabs scrollbar-brand">
                <button type="button" class="focus:outline-none" @click="tab='players'"
                    :class="tab === 'players' ? 'tab tab-active' : 'tab'">{{ __('Players') }}</button>

                <button type="button" class="focus:outline-none" @click="tab='staff'"
                    :class="tab === 'staff' ? 'tab tab-active' : 'tab'">{{ __('Staff') }}</button>
            </div>
        </div>

        <div class="p-5">

            {{-- Players --}}
            <div x-show="tab==='players'" x-cloak class="tab-panel" x-data="{ showForm: false }">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" @click="showForm = !showForm">
                            {{ __('Add Player') }}
                        </button>
                    </div>

                    <form x-show="showForm" x-cloak action="{{ route('teams.players.store', $team) }}" method="POST"
                        class="mb-4 rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Player') }}</label>
                                <select name="id_player" class="form-select" required>
                                    <option value="">{{ __('Pilih Player') }}</option>
                                    @foreach ($availablePlayers as $player)
                                        <option value="{{ $player->id_player }}">{{ $player->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Nomor Punggung') }}</label>
                                <input type="number" name="jersey_number" min="1" max="99" class="form-input" required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                @endcan

                <div class="table-wrapper">
                    <table class="table">
                        <thead class="table-head">
                            <tr class="table-header-row">
                                <th class="table-header-cell">{{ __('Player') }}</th>
                                <th class="table-header-cell">{{ __('Nomor') }}</th>
                                <th class="table-header-cell">{{ __('Captain') }}</th>
                                <th class="table-header-cell">{{ __('Status') }}</th>
                                <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse ($teamPlayers as $teamPlayer)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $teamPlayer->player->name }}</td>
                                    <td class="table-cell">{{ $teamPlayer->jersey_number }}</td>
                                    <td class="table-cell">
                                        @if ($teamPlayer->is_captain)
                                            <span class="badge badge-primary">{{ __('Captain') }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($teamPlayer->isActive())
                                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Keluar') }} ({{ $teamPlayer->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($teamPlayer->isActive())
                                                <form action="{{ route('teams.players.leave', [$team, $teamPlayer]) }}" method="POST"
                                                    onsubmit="return confirm('{{ __('Keluarkan player ini dari tim?') }}')" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                            <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="table-empty">{{ __('Belum ada player di tim ini.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Card List (mobile & tablet) -->
                <div class="table-card-list">
                    @forelse ($teamPlayers as $teamPlayer)
                        <div class="table-card">
                            <div class="table-card-header">
                                <div class="min-w-0">
                                    <span class="table-title truncate">{{ $teamPlayer->player->name }}</span>
                                    <span class="table-subtitle">{{ __('Nomor') }} {{ $teamPlayer->jersey_number }}</span>
                                </div>

                                @if ($teamPlayer->isActive())
                                    <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-secondary shrink-0">{{ __('Keluar') }}</span>
                                @endif
                            </div>

                            <div class="table-card-body">
                                <div class="table-card-field">
                                    <span class="table-card-label">{{ __('Captain') }}</span>
                                    <span class="table-text">{{ $teamPlayer->is_captain ? __('Ya') : '-' }}</span>
                                </div>

                                @if (! $teamPlayer->isActive())
                                    <div class="table-card-field">
                                        <span class="table-card-label">{{ __('Keluar') }}</span>
                                        <span class="table-text">{{ $teamPlayer->leave_date->format('d M Y') }}</span>
                                    </div>
                                @endif
                            </div>

                            @can('team.update')
                                @if ($teamPlayer->isActive())
                                    <div class="table-card-actions">
                                        <form action="{{ route('teams.players.leave', [$team, $teamPlayer]) }}" method="POST"
                                            onsubmit="return confirm('{{ __('Keluarkan player ini dari tim?') }}')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                    <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @empty
                        <div class="table-card">
                            <div class="empty-state">
                                <h4 class="empty-title">{{ __('Belum ada player di tim ini.') }}</h4>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Staff --}}
            <div x-show="tab==='staff'" x-cloak class="tab-panel" x-data="{ showForm: false }">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" @click="showForm = !showForm">
                            {{ __('Assign Staff') }}
                        </button>
                    </div>

                    <form x-show="showForm" x-cloak action="{{ route('teams.staff.store', $team) }}" method="POST"
                        class="mb-4 rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Staff') }}</label>
                                <select name="id_staff" class="form-select" required>
                                    <option value="">{{ __('Pilih Staff') }}</option>
                                    @foreach ($availableStaff as $staff)
                                        <option value="{{ $staff->id_staff }}">{{ $staff->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Peran di Tim') }}</label>
                                <select name="id_team_staff_position" class="form-select" required>
                                    <option value="">{{ __('Pilih Peran') }}</option>
                                    @foreach ($teamStaffPositions as $position)
                                        <option value="{{ $position->id_team_staff_position }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                @endcan

                <div class="table-wrapper">
                    <table class="table">
                        <thead class="table-head">
                            <tr class="table-header-row">
                                <th class="table-header-cell">{{ __('Nama') }}</th>
                                <th class="table-header-cell">{{ __('Peran di Tim') }}</th>
                                <th class="table-header-cell">{{ __('Status') }}</th>
                                <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse ($teamStaff as $ts)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $ts->staff->full_name }}</td>
                                    <td class="table-cell">
                                        <span class="badge badge-secondary">{{ $ts->teamStaffPosition->name }}</span>
                                    </td>
                                    <td class="table-cell">
                                        @if ($ts->isActive())
                                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Keluar') }} ({{ $ts->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($ts->isActive())
                                                <form action="{{ route('teams.staff.leave', [$team, $ts]) }}" method="POST"
                                                    onsubmit="return confirm('{{ __('Keluarkan staff ini dari tim?') }}')" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                            <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="table-empty">{{ __('Belum ada staff di tim ini.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Card List (mobile & tablet) -->
                <div class="table-card-list">
                    @forelse ($teamStaff as $ts)
                        <div class="table-card">
                            <div class="table-card-header">
                                <div class="min-w-0">
                                    <span class="table-title truncate">{{ $ts->staff->full_name }}</span>
                                    <span class="table-subtitle">{{ $ts->teamStaffPosition->name }}</span>
                                </div>

                                @if ($ts->isActive())
                                    <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-secondary shrink-0">{{ __('Keluar') }}</span>
                                @endif
                            </div>

                            @if (! $ts->isActive())
                                <div class="table-card-body">
                                    <div class="table-card-field">
                                        <span class="table-card-label">{{ __('Keluar') }}</span>
                                        <span class="table-text">{{ $ts->leave_date->format('d M Y') }}</span>
                                    </div>
                                </div>
                            @endif

                            @can('team.update')
                                @if ($ts->isActive())
                                    <div class="table-card-actions">
                                        <form action="{{ route('teams.staff.leave', [$team, $ts]) }}" method="POST"
                                            onsubmit="return confirm('{{ __('Keluarkan staff ini dari tim?') }}')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                    <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @empty
                        <div class="table-card">
                            <div class="empty-state">
                                <h4 class="empty-title">{{ __('Belum ada staff di tim ini.') }}</h4>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

@endsection
```

Catatan penting:
- `x-data="{ showForm: false }"` di masing-masing tab-panel **menggantikan** `onclick`/`getElementById` versi lama (inkonsistensi kode yang ditemukan saat review, `issue18.md` Temuan 2) — sekarang konsisten Alpine seperti file lain di codebase ini.
- `$availablePlayers`/`$availableStaff`/`$teamStaffPositions` **wajib** dari Controller (Tahap 9 bagian atas), **jangan** query langsung di Blade seperti versi lama.
- Tab "Overview" **dihapus**, datanya dipindah ke info strip (selalu terlihat, tidak perlu klik tab) — lihat Bagian 2d kenapa bukan grid 2/3+1/3 ala Player.

**✅ Cek dulu**:

```bash
php -l app/Http/Controllers/TeamController.php
php artisan tinker --execute="Illuminate\Support\Facades\Blade::compileString(file_get_contents(resource_path('views/teams/show.blade.php'))); echo 'compiled OK';"
```

Manual: buka `/teams/{team}` di desktop — info strip + tab Players/Staff + tabel muncul normal. **Resize browser ke < 1024px (atau buka DevTools mobile emulation)** — tabel desktop hilang, **Card List muncul menggantikannya** dengan info yang sama lengkapnya (jangan sampai ada data yang hilang dibanding versi desktop). Tombol "Add Player"/"Assign Staff" tetap berfungsi toggle form di kedua ukuran layar.

---

## Tahap 10 — Multi-Language

Tambah ke `lang/en.json` (cek dulu key yang mungkin sudah ada seperti `Status`/`Aktif`/`Nonaktif`/`Nama`/`Simpan`/`Aksi`/`Player`/`Staff`/`Captain`/`Keluar`/`Keluarkan`/`Nomor Punggung`/`Peran di Tim`/`Add Player`/`Assign Staff`/`Player Category`/`Team Type` — **sudah ada** dari `issue16.md`/`issue17.md`, jangan duplikat):

```json
"Pilih Academy dulu untuk melihat pilihan Season.": "Select an Academy first to see Season options.",
"Pilih Academy dulu untuk melihat pilihan Player Category.": "Select an Academy first to see Player Category options.",
"Pilih Academy dulu untuk melihat pilihan Employment Type.": "Select an Academy first to see Employment Type options.",
"Pilih Academy dulu untuk melihat pilihan Staff Position.": "Select an Academy first to see Staff Position options.",
"Pilih Academy dulu untuk melihat pilihan Role.": "Select an Academy first to see Role options.",
"Teams": "Teams",
"Bergabung": "Joined",
"Ya": "Yes",
"Belum menjadi anggota tim manapun.": "Not a member of any team yet.",
"Dibuat": "Created"
```

**✅ Cek dulu**: `php -r "json_decode(file_get_contents('lang/en.json'), true) or die('invalid json');"`, cek tidak ada key duplikat:

```bash
php -r "
\$data = json_decode(file_get_contents('lang/en.json'), true);
echo count(\$data) . ' keys, ';
"
```

Buka salah satu halaman (`/players/{player}?locale=en`, `/teams/{team}?locale=en`) — tidak ada teks Indonesia yang bocor di bagian yang baru ditambahkan.

---

## Tahap 11 — Tests

### `tests/Feature/TeamTest.php` — tambah 3 method baru di akhir class:

```php
public function test_cascade_options_mengembalikan_season_dan_category_sesuai_academy_yang_diminta(): void
{
    $academyA = Academy::factory()->create();
    $academyB = Academy::factory()->create();

    Season::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Season A']);
    Season::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'Season B']);

    $superAdmin = $this->actingAsOwner($academyA);
    $superAdmin->syncRoles([]); // lepas role Owner biasa dulu

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'team.create', 'guard_name' => 'web']);
    $role = Role::create(['id_academy' => null, 'name' => 'Super Admin Test', 'guard_name' => 'web']);
    $role->syncPermissions(\Spatie\Permission\Models\Permission::where('name', 'team.create')->get());
    $superAdmin->update(['id_academy' => null]);
    $superAdmin->assignRole($role);

    $response = $this->actingAs($superAdmin)
        ->getJson(route('teams.cascade-options', ['id_academy' => $academyB->id_academy]));

    $response->assertOk();
    $names = collect($response->json('id_season'))->pluck('label');
    $this->assertContains('Season B', $names);
    $this->assertNotContains('Season A', $names);
}

public function test_cascade_options_user_academy_biasa_mengabaikan_id_academy_dari_query(): void
{
    $academyA = Academy::factory()->create();
    $academyB = Academy::factory()->create();

    Season::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Season A']);
    Season::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'Season B']);

    $owner = $this->actingAsOwner($academyA);

    $response = $this->actingAs($owner)
        ->getJson(route('teams.cascade-options', ['id_academy' => $academyB->id_academy]));

    $names = collect($response->json('id_season'))->pluck('label');
    $this->assertContains('Season A', $names);
    $this->assertNotContains('Season B', $names);
}

public function test_halaman_show_team_memuat_table_card_list_untuk_responsif(): void
{
    $academy = Academy::factory()->create();
    $team = $this->makeTeam($academy);

    $this->actingAsOwner($academy);

    $this->get(route('teams.show', $team))
        ->assertOk()
        ->assertSee('table-card-list', false);
}
```

**Catatan**: kalau `actingAsOwner()` di `TeamTest.php` (dari `issue16.md`) belum bisa dipakai untuk skenario Super Admin, buat helper baru `actingAsSuperAdmin()` (permission `team.create`, `id_academy` = `null`) daripada memodifikasi role di tengah test seperti contoh di atas — sesuaikan dengan konvensi test yang sudah ada di file ini, contoh di atas cuma ilustrasi minimal.

### `tests/Feature/StaffTest.php` — tambah 1 method serupa untuk `staff.cascade-options` (pola sama, ganti ke `EmploymentType`/`StaffPosition`, key JSON `id_employment_type`/`id_staff_position`).

### `tests/Feature/StaffPositionTest.php` — tambah 1 method serupa untuk `staff-positions.cascade-options` (key JSON `role_id`, cek `Role::where('id_academy', ...)`).

### `tests/Feature/PlayerTest.php` (🆕 — belum ada file ini sebelumnya):

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerCategory;
use App\Models\Role;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamPlayerService;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsOwner(Academy $academy): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'player.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'team.view', 'guard_name' => 'web']);

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['player.view', 'team.view'])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);
        $this->actingAs($owner);

        return $owner;
    }

    public function test_halaman_show_player_menampilkan_tab_teams(): void
    {
        $academy = Academy::factory()->create();
        $season = Season::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        $team = app(TeamService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_season' => $season->id_season,
            'id_player_category' => $category->id_player_category,
            'name' => 'U15 A',
            'team_type' => 'regular',
        ]);

        $player = Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST00001',
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);

        app(TeamPlayerService::class)->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);

        $this->actingAsOwner($academy);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('U15 A');
    }

    public function test_halaman_show_player_tanpa_tim_menampilkan_empty_state(): void
    {
        $academy = Academy::factory()->create();

        $player = Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST00002',
            'name' => 'Player Tanpa Tim',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);

        $this->actingAsOwner($academy);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('Belum menjadi anggota tim manapun.');
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=TeamTest
php artisan test --filter=StaffTest
php artisan test --filter=StaffPositionTest
php artisan test --filter=PlayerTest
php artisan test
```

Semua test baru lulus, baseline kegagalan bawaan Breeze (5 failure + 2 error) tidak bertambah.

---

## Tahap 12 — Dokumentasi

**`docs/frontend-standard.md`** — tambah section baru **setelah** *Input Nominal Rupiah* dan **sebelum** *Development Rules*:

```markdown
## Cascading Dropdown Academy-Scoped (AJAX)

### Masalah

Form create module tenant (Team, Staff, Staff Position) yang punya dropdown "anak" bergantung pada Academy (Season+Player Category, Employment Type+Staff Position, Role) tidak otomatis ter-filter begitu Super Admin memilih Academy -- dropdown anak menampilkan opsi lintas-academy tercampur sampai form disubmit (lihat `issue18.md` Temuan 3-5, `issue19.md`).

### Solusi: Alpine helper `academyCascade()` + endpoint JSON kecil per module

`resources/js/components/academy-cascade.js` (`Alpine.data('academyCascade', ...)`, didaftarkan di `app.js` pola sama `currencyInput`) -- dipasang di tag `<form>` create module ybs:

```blade
<form x-data="academyCascade('{{ route('teams.cascade-options') }}')"
    x-init="init('{{ old('id_academy') }}', { id_season: '{{ old('id_season') }}' })">
```

Kontrak JSON endpoint **wajib** sama di semua module: object dengan key = `name` attribute select target, value = array `{value, label}`. Endpoint resolve `$academyId` sama seperti `resolveAcademyId()` di Service (Super Admin pakai `$request->query('id_academy')`, user academy biasa **selalu** pakai `AcademyService::currentId()`, mengabaikan query string -- supaya tidak bisa mengintip academy lain).

### Kapan pola ini dipakai lagi

Form create baru dengan dropdown Academy + dropdown anak yang bergantung academy tsb -- reuse `academyCascade()`, endpoint baru per module ikut kontrak JSON yang sama. **Bukan** untuk form edit (`id_academy` sudah pasti, tidak ambigu). **Bukan pengganti** pola client-side filter di `players/create.blade.php` (`@js($data)` + getter Alpine) -- itu pola valid lain untuk kasus yang sama, dua-duanya boleh hidup berdampingan (`issue18.md` Temuan 3-5).

---

## `table-card` Tanpa `table-card-list` untuk Panel Sempit di Halaman Detail

### Masalah

Pola *Tabel Responsif* (Table + Card List) dirancang untuk halaman index/list **full-width**. Kalau sebuah tab/panel ada di kolom **sempit** milik halaman detail (mis. tab "Teams" di `players/show.blade.php`, kolom kiri `lg:grid-cols-3`), memaksakan `table` (`min-w-[1000px]`) bikin scroll horizontal yang tidak perlu bahkan di desktop biasa.

### Solusi: `table-card` saja, tanpa wrapper `table-card-list`

`table-card` (class individual per baris data) **tidak** punya `lg:hidden` bawaan -- hanya wrapper `table-card-list` yang punya. Untuk panel sempit, pakai `table-card` langsung di dalam `<div class="space-y-3">` biasa (tanpa `table-wrapper`/`table` sama sekali) -- tampil identik di semua breakpoint, satu-satunya representasi, bukan dual-render.

### Kapan pola ini dipakai lagi

Konten tabular apapun yang ditampilkan di dalam kolom sempit halaman detail (bukan halaman index/list full-width) -- reuse `table-card` tanpa `table-card-list`. Kalau kontennya ada di halaman index/list biasa (full-width), tetap pakai pola Table+Card List penuh (`table-wrapper`+`table` DAN `table-card-list`), jangan dicampur.
```

Update juga kalimat di section **Summary** (baris terakhir dokumen) — tambahkan kalimat singkat menyebut 2 pola baru ini.

**`docs/permission-reference.md`** — tambah 1 baris "Catatan" di section **Module: Team**, **Module: Staff** (kalau ada), dan bagian yang relevan untuk **Staff Position**, masing-masing menyebut endpoint `*.cascade-options` reuse permission `*.create` yang sudah ada (bukan permission baru):

```markdown
- Endpoint `teams.cascade-options` (dipakai AJAX cascading dropdown di form create) reuse `team.create`, bukan permission baru (`issue19.md`).
```

(Sesuaikan nama endpoint & permission untuk section Staff/Staff Position.)

**✅ Cek dulu**: baca ulang bagian yang diedit, pastikan tidak ada broken link antar section (`#anchor` di Table of Contents kalau ditambah).

---

## Ringkasan Alur Akhir

```text
Form create Team/Staff/Staff Position (Super Admin)
│
├── Pilih Academy -> Alpine academyCascade() fetch GET *.cascade-options?id_academy=...
│     -> dropdown anak (Season+Category / EmploymentType+StaffPosition / Role)
│        di-replace isinya sesuai academy itu, TANPA reload halaman
│
├── User academy biasa -> tidak terpengaruh sama sekali, dropdown anak
│     sudah benar sejak render server (seperti sebelumnya)
│
└── Validasi server (FormRequest) tetap menolak kombinasi academy yang salah
      (lapis pertahanan terakhir, tidak pernah dihapus)

Player Show (/players/{player})
│
└── Tab baru "Teams" -> daftar Team yang pernah/sedang diikuti (table-card,
      TANPA table-card-list, karena panel ini sempit)

Team Show (/teams/{team})
│
├── Info strip (Season/Category/Type/Status/counts) -- selalu terlihat,
│     menggantikan tab "Overview" yang lama
│
└── Tab Players/Staff -- table-wrapper+table (desktop) DAN table-card-list
      (mobile/tablet) berdampingan, dual-render PENUH sesuai
      docs/frontend-standard.md (bug lama: cuma table, hilang di device kecil)
```
