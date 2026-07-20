# Frontend Standard

## Overview

Dokumen ini menjelaskan standar penulisan CSS/Tailwind dan Blade view pada FAOSBall. Berbeda dengan dokumen lain yang fokus ke backend, dokumen ini fokus ke konvensi frontend: struktur file CSS, kapan menulis Tailwind inline vs kapan membuat class reusable, dan jebakan teknis yang pernah terjadi.

---

## Table of Contents

- [Struktur File CSS](#struktur-file-css)
- [Tailwind CSS v4 (CSS-first)](#tailwind-css-v4-css-first)
- [Kapan Membuat @utility Baru](#kapan-membuat-utility-baru)
- [Gotcha: Varian Breakpoint vs Toggle Dinamis](#gotcha-varian-breakpoint-vs-toggle-dinamis)
- [Konsistensi Warna & Token](#konsistensi-warna--token)
- [Tabel Responsif: Table Desktop + Card List Mobile/Tablet](#tabel-responsif-table-desktop--card-list-mobiletablet)
- [Tabs Status + Toolbar Filter/Search](#tabs-status--toolbar-filtersearch)
- [Reusable View dengan Data Dinamis](#reusable-view-dengan-data-dinamis)
- [Development Rules](#development-rules)
- [Summary](#summary)

---

## Struktur File CSS

```text
resources/css/
├── app.css              # entry point, import semuanya
└── theme/
    ├── variables.css     # @theme - design token (warna, breakpoint, shadow, z-index)
    ├── base.css          # reset elemen dasar (body, heading, input, dll)
    ├── utilities.css     # @utility - komponen sidebar/menu, scrollbar
    ├── components.css    # @utility - card, btn, form, badge, table, modal,
    │                     #            avatar, dropdown, header, layout
    └── vendor.css        # override style third-party (ApexCharts, Flatpickr, dll)
```

`app.css` meng-import kelimanya secara berurutan (`variables → base → utilities → components → vendor`). Urutan ini penting: `variables.css` mendefinisikan token yang dipakai semua file setelahnya.

---

## Tailwind CSS v4 (CSS-first)

FAOSBall pakai Tailwind v4 dengan config berbasis CSS (bukan `tailwind.config.js`).

- `@theme { ... }` di `variables.css` — definisi token (`--color-brand-500`, `--breakpoint-xsm`, `--z-index-9999`, dst). Tailwind otomatis membuat utility class dari token ini (`--color-brand-500` → class `bg-brand-500`/`text-brand-500`/dst).
- `@utility nama-class { @apply ...; }` — cara resmi Tailwind v4 untuk bikin custom utility yang tetap ikut sistem variant (`dark:`, `hover:`, `lg:`, dst), dipakai di `utilities.css` dan `components.css`.

Sebelum menambah `@theme` token baru, cek dulu `variables.css` — kemungkinan besar warna/shadow/spacing yang dibutuhkan sudah ada.

---

## Kapan Membuat @utility Baru

Gunakan aturan berikut saat menulis Blade view:

1. **String Tailwind yang berulang identik di 2+ tempat** (mis. class card-header yang sama persis di beberapa file) → wajib jadi `@utility` baru di `components.css`/`utilities.css`.
2. **String Tailwind yang panjang tapi cuma dipakai sekali** → tetap sebaiknya diberi nama (`@utility`) kalau lebih dari ~4-5 token, supaya blade tidak penuh soup class. Contoh: `.sidebar-header { @apply flex items-center gap-2 pt-8 pb-7; }`.
3. **Toggle dinamis Alpine yang sepele** (mis. `:class="open ? 'rotate-180' : ''"`, `:class="sidebarToggle ? 'lg:hidden' : ''"`) → **biarkan inline**, tidak perlu dibuatkan class baru. Ini bukan soup, ini state yang memang harus reaktif.
4. Class baru untuk pola sidebar/menu/dropdown-navigasi → `utilities.css`. Class baru untuk komponen UI umum (card, button, badge, avatar, header, layout) → `components.css`.

---

## Gotcha: Varian Breakpoint vs Toggle Dinamis

**Aturan wajib**: kalau sebuah elemen sudah di-toggle dinamis oleh Alpine memakai class Tailwind polos untuk properti tertentu (paling sering `display`, lewat `hidden`/`flex`/`block`), maka **varian breakpoint (`lg:`, `md:`, dst) atau `dark:` untuk properti yang sama tidak boleh dibungkus ke dalam custom `@utility`** — harus tetap ditulis sebagai class Tailwind langsung di Blade.

**Kenapa**: Tailwind menjamin urutan compile yang benar antar variant HANYA untuk utility yang dipakai langsung (ke-scan dari isi file lewat `@source`). Kalau sebuah variant (mis. `lg:flex`) di-`@apply` di dalam custom utility, Tailwind v4 menaruh rule tersebut pada posisi yang tidak dijamin lebih akhir dibanding rule polos (`hidden`/`flex`) yang dipakai terpisah di elemen yang sama. Kalau posisinya salah, class yang seharusnya menang (misalnya `lg:flex` di desktop) bisa kalah oleh `.hidden` yang muncul belakangan di file CSS hasil build — padahal secara logika harusnya menang.

**Contoh nyata (pernah kejadian)**: `app-header-actionbar` awalnya di-`@apply` termasuk `lg:flex lg:justify-end lg:px-0 lg:shadow-none`, padahal elemen yang sama di-toggle `:class="menuToggle ? 'flex' : 'hidden'"`. Akibatnya di desktop, action bar (dark mode/notifikasi/user) hilang total saat halaman pertama dimuat, karena `.hidden` menang cascade. Perbaikannya: keluarkan `lg:flex lg:justify-end lg:px-0 lg:shadow-none` dari `@utility`, taruh sebagai class Tailwind langsung berdampingan dengan `:class` binding-nya.

Contoh yang benar (lihat `resources/css/theme/components.css`, komentar di atas `.app-header-actionbar` dan `.sidebar-overlay`):

```php
<div :class="menuToggle ? 'flex' : 'hidden'"
    class="app-header-actionbar lg:flex lg:justify-end lg:px-0 lg:shadow-none">
```

Sebelum membuat `@utility` baru, selalu cek: apakah elemen ini juga punya `:class` dinamis dari Alpine yang menyentuh properti CSS yang sama (`display`, `justify-content`, dst)? Kalau ya, jangan bungkus varian tersebut ke `@utility`.

---

## Konsistensi Warna & Token

- Jangan hardcode nilai warna/shadow/z-index kalau token-nya sudah ada di `variables.css` (mis. pakai `z-9` bukan `z-[9]`, pakai `--color-gray-700` bukan `#344054`).
- Kalau butuh nilai arbitrary yang genuinely spesifik untuk satu komponen (mis. lebar sidebar `w-[290px]`, tinggi panel notifikasi `h-[480px]`), itu wajar dan tidak perlu dipaksakan jadi token baru — bukan semua angka harus jadi design token, hanya yang benar-benar dipakai berulang lintas komponen.

---

## Tabel Responsif: Table Desktop + Card List Mobile/Tablet

### Masalah

Tabel data-dense pada halaman index/list module (players, academies, roles, permissions, dst) memakai `table-wrapper` yang scroll horizontal (`overflow-x-auto`) karena `table` punya `min-w-[1000px]`. Di desktop ini bagus, tapi di tablet/smartphone — yang justru jadi alat kerja utama coach dan staff di lapangan — kolom kanan (Status, Aksi) kepotong dari layar pertama dan user harus geser horizontal untuk melihatnya. UX-nya buruk untuk device yang paling sering dipakai user sistem ini.

### Solusi wajib: dual-render Table (desktop) + Card List (mobile/tablet)

Setiap halaman index/list module yang menampilkan data dalam tabel wajib punya **dua representasi data yang di-render sekaligus oleh server**, ditoggle tampil/sembunyi lewat breakpoint `lg` (1024px) — bukan JavaScript, murni CSS:

1. **Table** (`table-wrapper` > `table`) — tampil hanya di `lg:` ke atas. `table-wrapper` sudah `hidden lg:block` secara default di `components.css`, tidak perlu ditambah apa pun lagi di Blade.
2. **Card List** (`table-card-list`) — tampil di bawah `lg` (utility-nya sudah include `lg:hidden`), satu `table-card` per baris data.

Class yang tersedia (didefinisikan di `resources/css/theme/components.css`, section "Table Card"):

- `table-card-list` — wrapper `flex flex-col gap-3`, otomatis `lg:hidden`.
- `table-card` — 1 card per baris data (border + rounded, senada dengan `card`).
- `table-card-header` — `flex justify-between`, biasanya avatar+judul di kiri (representasi kolom "Info X") dan badge status di kanan (representasi kolom "Status").
- `table-card-body` — grid 2 kolom untuk field-field sekunder (representasi kolom-kolom lain seperti Profil, Posisi, Kontak, Tagline, dst).
- `table-card-field` + `table-card-label` — satu field: label kecil di atas (`table-card-label`) + value (pakai `table-text`/`table-subtitle`/`badge` seperti biasa).
- `table-card-actions` — footer tombol aksi, pakai ulang komponen/icon yang sama persis dengan kolom Aksi di tabel (`btn-icon-*`, `<x-button.delete>`, `@can` guard yang sama).

### Pola implementasi

Card List ditulis sebagai `@forelse` kedua yang looping data yang sama, diletakkan tepat setelah `</div>` penutup `table-wrapper` dan sebelum `table-footer`/pagination. Aturan pentingnya:

- Isi card **wajib merepresentasikan seluruh kolom tabel** — jangan buang informasi hanya karena dipindah ke card, karena tujuan pola ini justru supaya user mobile/tablet tidak kehilangan data yang tersedia di desktop.
- Empty-state wajib digandakan versi card-nya juga (`<div class="table-card"><div class="empty-state">...</div></div>` di dalam `@empty`), supaya tetap tampil saat data kosong di layar kecil — bukan cuma kosong tanpa keterangan.
- Guard permission (`@can`) dan disabled-state (mis. tombol delete yang di-disable karena masih dipakai relasi lain) harus identik antara versi table dan versi card.

Contoh lengkap: `resources/views/players/index.blade.php`, `academies/index.blade.php`, `permissions/index.blade.php`, `roles/index.blade.php`.

### Kenapa dual-render, bukan sekadar sembunyi kolom (`hidden md:table-cell`)

Alternatif yang lebih murah adalah menyembunyikan kolom sekunder di breakpoint kecil sambil tetap pakai tabel. Ini **tidak dipakai** karena user tetap kehilangan info (harus buka halaman detail buat lihat kolom yang disembunyikan), dan tombol aksi tetap berdesakan di lebar tablet. Card List memberi kepadatan informasi setara tabel tapi dengan hierarki visual yang lebih jelas di layar sempit.

### Kapan pola ini wajib dipakai

Wajib untuk semua halaman index/list module (baru maupun refactor) yang menampilkan data lewat `table-wrapper` + `table`. Kalau ada tabel index yang sangat sederhana (≤3 kolom pendek, tidak ada kolom yang berpotensi kepotong di layar kecil) dan terasa berlebihan untuk dibuatkan Card List, diskusikan dulu dengan user sebelum melewati pola ini — jangan asumsikan boleh dilewati begitu saja (lihat Aturan Utama di `CLAUDE.md`).

---

## Tabs Status + Toolbar Filter/Search

### Masalah

Halaman index/list yang datanya sudah banyak (mis. Players) tidak punya cara apapun untuk mencari satu baris tertentu selain scroll manual atau membolak-balik halaman pagination — makin banyak data, makin sulit dipakai, terutama di HP/tablet yang jadi alat kerja utama user (lihat `docs/development-guide.md`).

### Solusi: dua Blade Component reusable + pagination custom

Implementasi pertama ada di `resources/views/players/index.blade.php` — jadikan ini contoh acuan saat menambahkan pola yang sama ke module lain.

**1. `<x-table.tabs>`** (`resources/views/components/table/tabs.blade.php`) — baris tab status dengan angka (count) di tiap tab, dibangun di atas class `tab`/`tab-active` yang sudah ada (dipakai juga oleh tab konten di `players/show.blade.php`), bukan class baru.

```blade
<x-table.tabs route="players.index" :active="$filters['status'] ?? ''" :tabs="[
    '' => ['label' => 'Semua', 'count' => $allCount],
    'active' => ['label' => 'Aktif', 'count' => $statusCounts['active']],
    ...
]" />
```

Tab kosong (`''`) selalu berarti "tanpa filter status". Angka tiap tab **wajib** ikut menghormati filter lain yang sedang aktif (search/type/dst) tapi **tidak** menghormati status tab itu sendiri — supaya angka di tab lain tidak berubah cuma karena user sedang berada di satu tab tertentu. Lihat `PlayerService::statusCounts()` (parameter `includeStatus: false` di `applyFilters()`).

**2. `<x-table.toolbar>`** (`resources/views/components/table/toolbar.blade.php`) — satu `<form method="GET">` berisi input search + tombol "Filter & Sort" yang membuka `dropdown-menu` (class yang sudah ada). Field filter/sort di dalam dropdown **beda-beda tiap module** — komponen ini cuma menyediakan mekanisme search + dropdown-nya lewat slot, bukan field-nya:

```blade
<x-table.toolbar route="players.index" :filters="$filters" placeholder="Cari nama, nickname, atau kode player...">
    <div class="form-group">
        <label class="form-label">Urutkan</label>
        <select name="sort" class="form-select"> ... </select>
    </div>
    {{-- field filter khusus module lain (Type, Category, dst) --}}
</x-table.toolbar>
```

**3. Query string sebagai satu-satunya sumber state.** Tidak ada state filter di Alpine/JS — semuanya lewat `GET` + query string (`?search=...&status=...&sort=...`), supaya URL bisa di-share/refresh/bookmark dan tombol Back browser tetap benar. Konsekuensinya:
- Controller membaca filter lewat `$request->only([...])` lalu `array_filter()` supaya value kosong (mis. `<option value="">Semua Type</option>`) tidak ikut nyangkut di query string.
- Business logic filter (search/where/sort) **wajib** di Service, bukan Controller — ikuti pola `PlayerService::applyFilters()`/`paginate()`/`statusCounts()`, konsisten dengan Thin Controller di `docs/architecture.md`.
- `{{ $x->withQueryString()->links() }}` — **wajib**, kalau tidak filter yang aktif hilang saat pindah halaman pagination.

**4. Pagination custom global.** `resources/views/vendor/pagination/faos.blade.php` didaftarkan lewat `Paginator::defaultView()` di `AppServiceProvider::boot()`, jadi otomatis dipakai **seluruh** halaman index yang memanggil `->links()` tanpa perlu disebut satu-satu — module baru tidak perlu melakukan apapun untuk mendapat tampilan pagination ini.

### Kapan pola ini dipakai

Tabs status masuk akal untuk module yang punya kolom status/state dengan sedikit nilai tetap (Player: `active/inactive/graduated/left`). Kalau sebuah module tidak punya kolom seperti itu, cukup pasang `<x-table.toolbar>` saja tanpa `<x-table.tabs>`. Search & filter dropdown (`<x-table.toolbar>`) berlaku untuk semua module dengan data yang berpotensi banyak baris — untuk module master kecil (≤ 1 halaman pagination biasanya) boleh dilewati, diskusikan dulu dengan user kalau ragu.

---

## Reusable View dengan Data Dinamis

Kalau sebuah partial/view perlu menampilkan data yang dihitung sendiri (query database, statistik, dsb), gunakan **class-based Blade Component** (`App\View\Components\Xxx`), bukan View Composer.

Contoh yang sudah ada: `App\View\Components\Alert`, `App\View\Components\Breadcrumb`, `App\View\Components\AuthSidebar`, `App\View\Components\AcademyLogo`. Pola bakunya:

```php
class AuthSidebar extends Component
{
    public int $totalActivePlayers;

    public function __construct(AuthStatsService $authStatsService)
    {
        $this->totalActivePlayers = $authStatsService->snapshot()['totalActivePlayers'];
    }

    public function render(): View
    {
        return view('components.auth-sidebar');
    }
}
```

Dipakai di Blade sebagai `<x-auth-sidebar />`. View-nya taruh di `resources/views/components/`, business logic/query tetap di Service (constructor Component cuma manggil Service, sama seperti Controller).

**Kenapa bukan View Composer** (`View::composer(...)` di `AppServiceProvider`): View Composer memang bisa mengikat data ke view tanpa mengubah titik pemanggilan (`@include(...)` tetap sama), tapi asal data jadi tidak terlihat dari sisi pemanggil maupun dari file view itu sendiri — orang harus tahu dulu ada wiring tersembunyi di `AppServiceProvider` untuk nemuin sumbernya. Blade Component lebih eksplisit: tinggal buka file component-nya, langsung ketemu.

---

## Development Rules

Gunakan:

- `@utility` untuk pola yang berulang atau string Tailwind panjang.
- Token dari `variables.css` daripada hardcode warna/shadow/z-index.
- Cek dulu apakah class yang dibutuhkan sudah ada sebelum bikin baru (terutama di `components.css` - card/btn/badge/form/table/table-card/modal/avatar/dropdown sudah lengkap).
- Class-based Blade Component untuk view yang butuh data dinamis/hitung sendiri, bukan View Composer.
- Dual-render Table (desktop) + Card List (mobile/tablet) untuk semua halaman index/list module (lihat [Tabel Responsif](#tabel-responsif-table-desktop--card-list-mobiletablet)).
- `<x-table.tabs>`/`<x-table.toolbar>` untuk search/filter/sort di halaman index/list yang datanya berpotensi banyak baris, dengan state lewat query string (GET) dan business logic filter di Service (lihat [Tabs Status + Toolbar Filter/Search](#tabs-status--toolbar-filtersearch)).

Hindari:

- Membungkus varian breakpoint/dark ke `@utility` untuk properti yang juga di-toggle dinamis lewat class Tailwind polos di elemen yang sama (lihat [Gotcha](#gotcha-varian-breakpoint-vs-toggle-dinamis)).
- Hardcode warna/shadow yang sebenarnya sudah ada tokennya.
- Membuat `@utility` baru untuk toggle dinamis yang sepele (single property, dipakai sekali).
- `View::composer()` untuk mengikat data ke partial — pakai Blade Component (lihat [Reusable View dengan Data Dinamis](#reusable-view-dengan-data-dinamis)).
- Halaman index/list baru yang hanya mengandalkan tabel dengan scroll horizontal tanpa Card List di mobile/tablet.

---

## Summary

FAOSBall menggunakan Tailwind CSS v4 dengan pendekatan CSS-first (`@theme`, `@utility`). String Tailwind yang berulang atau panjang diekstrak jadi `@utility` reusable di `utilities.css` (pola sidebar/menu) atau `components.css` (komponen UI umum), sedangkan toggle dinamis Alpine yang sepele dibiarkan inline. Yang paling penting: variant breakpoint/dark untuk properti yang juga dikendalikan toggle dinamis di elemen yang sama wajib tetap jadi class Tailwind langsung, tidak boleh dibungkus ke `@utility`, karena urutan compile-nya tidak dijamin benar. Untuk halaman index/list module, tabel wajib didampingi Card List responsif (`table-card-list`) supaya data tidak kepotong saat diakses lewat tablet/smartphone di lapangan (lihat [Tabel Responsif](#tabel-responsif-table-desktop--card-list-mobiletablet)).
