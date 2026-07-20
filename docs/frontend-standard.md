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
- [Urutan & Pengelompokan Field Form (Create/Edit)](#urutan--pengelompokan-field-form-createedit)
- [Reusable View dengan Data Dinamis](#reusable-view-dengan-data-dinamis)
- [Theming Per-Academy (CSS Custom Property Override)](#theming-per-academy-css-custom-property-override)
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

## Urutan & Pengelompokan Field Form (Create/Edit)

### Masalah

Form create/edit di FAOSBall ditulis tanpa urutan atau pengelompokan field yang konsisten — tiap module punya urutannya sendiri, kadang bahkan tidak konsisten antara create dan edit di module yang sama. Beberapa contoh nyata yang sudah terjadi:

- **`academies/create.blade.php` & `academies/edit.blade.php`**: field `Kode Academy` diselipkan di antara `Tagline` dan `Nomor Telepon`, padahal `Kode` secara logis adalah identitas record (sejenis dengan `Nama`), bukan atribut deskriptif. Tidak ada pemisah visual apa pun antar kelompok field.
- **`players/create.blade.php` vs `players/edit.blade.php`**: field `Posisi Utama`/`Posisi Kedua` ada di **kolom kiri** pada form create, tapi pindah ke **kolom kanan** pada form edit — dua tampilan dari entity yang sama, urutan berbeda. Ini bikin user yang sudah familiar dengan create harus mencari ulang letak field yang sama di edit.
- **Duplikasi markup upload**: komponen `<x-logo-upload-field>` sudah ada dan dipakai dengan benar oleh `academies/create`, `academies/edit`, `academy-profile/edit` — tapi `players/create` dan `players/edit` masing-masing hand-roll ulang markup upload foto yang hampir identik (termasuk SVG icon dan logic Alpine-nya), bukan reuse komponen yang sudah ada.
- **Asterisk & pesan error tidak konsisten**: field yang jadi wajib secara kondisional (misal `Email`/`Password` saat toggle "Buat Akun" aktif) tidak diberi tanda `*`, dan field konfirmasi password (`players/account/create`, `academies/account/create`) tidak punya slot `@error` sama sekali walau field password utamanya punya.
- **Tombol submit tidak seragam**: sebagian besar form create pakai `Reset` + submit, sebagian besar edit pakai `Batal` (link) + submit — tapi `academy-profile/edit` dan form Breeze (`profile/edit`) cuma punya satu tombol submit tanpa `Reset`/`Batal` sama sekali.

Akibatnya form terasa berantakan — field yang berhubungan (nama & kode, posisi & tipe/kategori) terpisah jauh, dan pengalaman create vs edit tidak konsisten.

### Solusi: Taksonomi Urutan Field

Field pada form create/edit **wajib** dikelompokkan dan diurutkan berdasarkan kategori berikut, top-to-bottom (atau kiri-ke-kanan kalau dipecah dua kolom — lihat [Pembagian Kolom](#pembagian-kolom) di bawah). Field dalam satu kategori harus berdekatan, tidak boleh disisipi field dari kategori lain:

1. **Konteks/Scope** — field yang menentukan cakupan record, misal select Academy untuk Super Admin. Selalu paling atas, karena field lain di bawahnya secara implisit bergantung pada scope ini.
2. **Identitas Utama** — `Nama`, `Kode`/`Slug`, dan field lain yang jadi "penanda" record. Kalau ada field kode/slug yang berasal dari nama, **taruh langsung bersebelahan dengan Nama**, jangan dipisah field lain (lihat contoh kasus `Kode Academy` di atas).
3. **Klasifikasi/Relasi Wajib** — select/dropdown yang menentukan "jenis" record (`Type`, `Kategori`, `Posisi`, dst). Field-field ini saling terkait secara semantik (biasanya membentuk funnel: pilih tipe → pilih kategori → pilih posisi) dan harus berkelompok, tidak boleh dipisah field bio/kontak.
4. **Informasi Kontak** — `Telepon`, `Email`, `Alamat`.
5. **Atribut Deskriptif/Opsional** — `Tagline`, `Deskripsi`, `Catatan`, dan atribut sekunder lain (`Nationality`, `Kaki Dominan`, dst) yang tidak menentukan identitas maupun klasifikasi record.
6. **Media/Upload** — logo/foto. Kalau perlu upload dengan preview/crop, **pakai `<x-logo-upload-field>` yang sudah ada**, jangan hand-roll markup baru (lihat [Reusable View](#reusable-view-dengan-data-dinamis) untuk alasan yang sama soal duplikasi).
7. **Status/Toggle aktif-nonaktif** — selalu diletakkan di posisi yang sama tiap module (langsung sebelum kategori 8 atau tombol submit), jangan diselipkan di tengah-tengah field lain.
8. **Section Terpisah** — kelompok field yang merepresentasikan sub-entitas/concern yang benar-benar berbeda dari record utama (info langganan, matrix permission, kredensial akun baru). Selalu paling bawah, sebelum tombol submit — lihat aturan pembungkusan di bawah.

**Wajib konsisten antara create dan edit di module yang sama** — urutan kategori, isi tiap kolom, dan penempatan tiap field harus identik (kecuali field yang secara fungsional memang hanya ada di salah satu, misal toggle "Buat Akun" cuma relevan di create).

### Pembagian Kolom

Kalau form dipecah dua kolom (`form-row`), pembagiannya ikut taksonomi di atas: **kolom kiri = kategori 1–4** (yang mendefinisikan "record ini record apa": scope, identitas, klasifikasi, kontak), **kolom kanan = kategori 5–8** (deskriptif, media, status, section terpisah). Field numerik pendek yang benar-benar sepasang (`Umur Minimal`+`Umur Maksimal`, `Tinggi`+`Berat`) boleh digabung jadi grid 2 kolom kecil (`form-row grid-cols-2`) **di dalam** kolom yang sesuai — tapi jangan gabungkan dua field cuma karena sama-sama pendek kalau tidak ada hubungan logis (mis. `Kode` + `Urutan` di `player-positions/create` dipasangkan hanya karena sama-sama field pendek, padahal `Kode` itu identitas dan `Urutan` itu atribut layout — ini bukan pola yang direkomendasikan, meski sudah ada).

### Kapan Membungkus Field jadi Section (`rounded-xl border` + `section-title`)

Bungkus sekelompok field jadi section berjudul (`<div class="rounded-xl border ..."><h4 class="section-title">...</h4>...</div>`) **hanya** kalau field-field itu representasi sub-entitas/concern yang secara konsep berbeda dari record utama — bukan sekadar 2-3 field yang related. Contoh yang sudah benar dan jadi acuan:

- `academies/edit.blade.php` — "Informasi Langganan" (subscription adalah concern terpisah dari profil academy itu sendiri).
- `roles/create.blade.php` / `roles/edit.blade.php` — "Hak Akses" (permission matrix adalah concern terpisah dari data role itu sendiri).

Untuk grouping ringan (identitas, kontak, dst) **tidak perlu** dibungkus section — cukup urutan yang benar sudah menyampaikan pengelompokan. Membungkus semua kelompok jadi section akan membuat form penuh kotak bersarang dan justru menambah noise visual.

### Tombol Submit: Reset vs Batal

- **Form Create** pada module dengan halaman index/list → `<button type="reset">Reset</button>` + tombol submit primary. Reset masuk akal karena form masih kosong/baru diisi.
- **Form Edit** pada module dengan halaman index/list → `<a href="...">Batal</a>` (link kembali ke index/show) + tombol submit primary. **Jangan** pakai `type="reset"` di form edit — reset akan mengembalikan ke value kosong bukan ke data lama, yang membingungkan.
- **Form self-service tanpa halaman index** (`profile/edit`, `academy-profile/edit` — user mengedit data miliknya sendiri, tidak ada "list" untuk kembali) → submit-only dapat diterima, tidak perlu dipaksakan punya Reset/Batal.

### Kelengkapan Asterisk & Slot Error

- Field yang wajib **secara kondisional** (misal `Email`/`Password` yang muncul saat toggle "Buat Akun" dinyalakan) tetap wajib diberi tanda `<span class="text-error-500">*</span>`, sama seperti field yang selalu wajib — jangan dianggap opsional secara visual hanya karena validasinya bersyarat.
- Setiap input yang divalidasi lewat Form Request wajib punya slot `@error` pendampingnya — termasuk field konfirmasi password, yang di beberapa form saat ini (`players/account/create`, `academies/account/create`) belum punya.

### Kapan Pola Ini Wajib Dipakai

Wajib untuk **semua form create/edit baru**, tanpa kecuali. Untuk form existing yang menyimpang dari taksonomi ini (lihat daftar di [Masalah](#masalah) di atas), jangan langsung di-refactor massal — diskusikan dulu dengan user, karena mengubah urutan field pada form yang sudah dipakai sehari-hari bisa mengejutkan tanpa pemberitahuan (lihat Aturan Utama di `CLAUDE.md`).

---

## Reusable View dengan Data Dinamis

Kalau sebuah partial/view perlu menampilkan data yang dihitung sendiri (query database, statistik, dsb), gunakan **class-based Blade Component** (`App\View\Components\Xxx`), bukan View Composer.

Contoh yang sudah ada: `App\View\Components\Alert`, `App\View\Components\Breadcrumb`, `App\View\Components\AuthSidebar`, `App\View\Components\AcademyLogo`, `App\View\Components\LogoUploadField`. Pola bakunya:

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

## Theming Per-Academy (CSS Custom Property Override)

FAOSBall mendukung 1 warna utama (`primary_color`) per academy yang menggantikan token `--color-brand-*` default. Mekanismenya:

1. Tailwind v4 compile utility (`bg-brand-500`, dst) selalu mengacu ke `var(--color-brand-500)`, tidak pernah membakar nilai hex langsung — jadi override cukup lewat CSS custom property, tidak perlu rebuild asset per tenant.
2. Override dicetak lewat Blade Component (`<x-academy-theme />`, lihat `App\View\Components\AcademyTheme`) sebagai `<style>:root{...}</style>` **tanpa** `@layer` apa pun — style unlayered otomatis menang lawan Tailwind punya `@theme` (yang di-compile ke dalam `@layer theme`), terlepas dari urutan penempatan di `<head>`.
3. Generate ramp 12 shade dari 1 warna dasar ada di `App\Support\ColorRamp` — class murni, tidak bergantung Laravel, gampang di-unit-test.

**Kapan pola ini dipakai lagi**: kalau ada kebutuhan theming per-tenant lain (secondary color, dst), reuse `ColorRamp` dan pola Component yang sama — jangan bikin mekanisme baru dari nol.

---

## Development Rules

Gunakan:

- `@utility` untuk pola yang berulang atau string Tailwind panjang.
- Token dari `variables.css` daripada hardcode warna/shadow/z-index.
- Cek dulu apakah class yang dibutuhkan sudah ada sebelum bikin baru (terutama di `components.css` - card/btn/badge/form/table/table-card/modal/avatar/dropdown sudah lengkap).
- Class-based Blade Component untuk view yang butuh data dinamis/hitung sendiri, bukan View Composer.
- Dual-render Table (desktop) + Card List (mobile/tablet) untuk semua halaman index/list module (lihat [Tabel Responsif](#tabel-responsif-table-desktop--card-list-mobiletablet)).
- `<x-table.tabs>`/`<x-table.toolbar>` untuk search/filter/sort di halaman index/list yang datanya berpotensi banyak baris, dengan state lewat query string (GET) dan business logic filter di Service (lihat [Tabs Status + Toolbar Filter/Search](#tabs-status--toolbar-filtersearch)).
- Taksonomi urutan field (Scope → Identitas → Klasifikasi → Kontak → Deskriptif → Media → Status → Section Terpisah) untuk semua form create/edit baru, konsisten antara create dan edit di module yang sama (lihat [Urutan & Pengelompokan Field Form](#urutan--pengelompokan-field-form-createedit)).

Hindari:

- Membungkus varian breakpoint/dark ke `@utility` untuk properti yang juga di-toggle dinamis lewat class Tailwind polos di elemen yang sama (lihat [Gotcha](#gotcha-varian-breakpoint-vs-toggle-dinamis)).
- Hardcode warna/shadow yang sebenarnya sudah ada tokennya.
- Membuat `@utility` baru untuk toggle dinamis yang sepele (single property, dipakai sekali).
- `View::composer()` untuk mengikat data ke partial — pakai Blade Component (lihat [Reusable View dengan Data Dinamis](#reusable-view-dengan-data-dinamis)).
- Halaman index/list baru yang hanya mengandalkan tabel dengan scroll horizontal tanpa Card List di mobile/tablet.
- Field form yang saling berhubungan (identitas & kode, klasifikasi & relasi) dipisah oleh field dari kategori lain, atau urutan/kolom field yang berbeda antara create dan edit di module yang sama.
- Hand-roll ulang markup upload/komponen form yang sudah ada (`<x-logo-upload-field>`, dst) — reuse komponen yang sudah ada.
- Membungkus setiap kelompok kecil field (2-3 field) jadi section berjudul — section hanya untuk sub-entitas/concern yang benar-benar terpisah (subscription, permission matrix, dst).

---

## Summary

FAOSBall menggunakan Tailwind CSS v4 dengan pendekatan CSS-first (`@theme`, `@utility`). String Tailwind yang berulang atau panjang diekstrak jadi `@utility` reusable di `utilities.css` (pola sidebar/menu) atau `components.css` (komponen UI umum), sedangkan toggle dinamis Alpine yang sepele dibiarkan inline. Yang paling penting: variant breakpoint/dark untuk properti yang juga dikendalikan toggle dinamis di elemen yang sama wajib tetap jadi class Tailwind langsung, tidak boleh dibungkus ke `@utility`, karena urutan compile-nya tidak dijamin benar. Untuk halaman index/list module, tabel wajib didampingi Card List responsif (`table-card-list`) supaya data tidak kepotong saat diakses lewat tablet/smartphone di lapangan (lihat [Tabel Responsif](#tabel-responsif-table-desktop--card-list-mobiletablet)). Untuk form create/edit, field wajib diurutkan dan dikelompokkan berdasarkan taksonomi Scope → Identitas → Klasifikasi → Kontak → Deskriptif → Media → Status → Section Terpisah, konsisten antara create dan edit di module yang sama, dengan section berjudul hanya dipakai untuk sub-entitas yang benar-benar terpisah dari record utama (lihat [Urutan & Pengelompokan Field Form](#urutan--pengelompokan-field-form-createedit)).
