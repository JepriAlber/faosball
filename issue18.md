# Issue: Review UI/UX Halaman Detail (Show) + Bug Dropdown Academy-Scoped

> **Status**: 📋 Catatan review, **BELUM** jadi brief pengembangan. Didiskusikan dulu dengan user sebelum ada keputusan implementasi/urutan kerja — jangan mulai coding dari dokumen ini sebelum ada kesepakatan.
> **Konteks**: Ditemukan saat review manual setelah `issue16.md` (modul Team) dan `issue17.md` (indikator mismatch Player Category) selesai. 5 temuan: 2 soal UI/UX halaman detail (`docs/frontend-standard.md` sudah punya standar Tabel Responsif tapi belum konsisten dipakai di semua halaman show), 3 soal bug dropdown academy-scoped yang tidak mengikuti academy yang dipilih Super Admin.

---

## Temuan 1 — Halaman `players/show.blade.php` belum ada tab "Teams"

> **Keputusan**: ✅ Disepakati — **Opsi A**, tab baru "Teams" di `players/show.blade.php`. Detail cakupan tab (histori penuh vs. cuma aktif, field apa saja yang ditampilkan) masih perlu dirinci saat brief ditulis.

**Laporan user**: player yang sudah jadi anggota satu/banyak Team belum bisa dilihat dari halaman detail Player-nya sendiri.

**Verifikasi**: benar. `Player::teamPlayers()` relation **sudah ada** (ditambahkan di `issue16.md` Tahap 10, "relasi balik"), tapi `PlayerController::show()` tidak meng-eager-load atau meng-passing relasi ini ke view, dan `players/show.blade.php` tidak punya tab untuk menampilkannya sama sekali — datanya ada di database, tapi tidak ada satupun jalan untuk melihatnya dari sisi Player.

**Pertanyaan untuk didiskusikan** — bagaimana bentuk exposure-nya di UI? Beberapa opsi:

| Opsi | Deskripsi | Trade-off |
|---|---|---|
| **A. Tab baru "Teams"** (rekomendasi) | Tambah 1 tab lagi di baris tab yang sudah ada (Profil Pemain / Fisik & Posisi / Parent Information / Dokumen), isinya list Team yang pernah/sedang diikuti (nama tim, season, jersey number, status aktif/keluar, tanggal join-leave) | Konsisten dengan pola tab yang sudah ada, tidak nambah kompleksitas halaman. Historical (termasuk yang sudah `leave_date`) atau cuma yang aktif — perlu diputuskan |
| **B. Section ringkas di tab "Profil Pemain"** (badge/list kecil, bukan tab terpisah) | Taruh sebagai info tambahan di tab Profil yang sudah ada, mis. di bawah "Kategori Umur" | Lebih sederhana, tapi kalau player ikut banyak tim (reguler + turnamen + seleksi sekaligus, sesuai skenario di `issue16.md` Bagian 1) bisa jadi penuh/berantakan di satu tab yang bukan fokusnya |
| **C. Card ringkas di sidebar kanan** (pola sama "Informasi Academy"/"Informasi Account" di `players/show.blade.php`) | Cuma nampilkan Team yang **sedang aktif** (ringkas), tanpa histori lengkap | Paling ringan, tapi kalau perlu histori lengkap (roster musim lalu) tetap butuh tempat lain |

Kecenderungan saya ke **Opsi A** (tab baru), karena polanya sudah established di halaman ini (nambah 1 tab lagi tidak asing), dan Team punya cukup banyak atribut per baris (nama tim, season, jersey number, captain, status, tanggal join/leave) yang butuh ruang tabel/list sendiri — bukan sekadar badge kecil. Tapi ini keputusan UX yang saya serahkan ke kamu.

---

## Temuan 2 — Halaman `teams/show.blade.php` UI/UX kurang & tidak responsif di device kecil

> **Keputusan**: ✅ Disepakati — perbaikan diserahkan penuh ke AI agent, **wajib** berpedoman ke `docs/frontend-standard.md` (Tabel Responsif, Urutan & Pengelompokan Field, dst) dan dokumen `docs/` lain yang relevan (`docs/architecture.md`, `docs/coding-standard.md`). Reuse pola `players/show.blade.php` (grid 2/3+1/3, sidebar info, tab pakai class `tabs`) dan `teams/index.blade.php` (Table + Card List paralel) sebagai referensi, bukan mengarang pola baru.

**Laporan user**: tampilan detail Team kurang bagus, dan tidak support di device ukuran kecil.

**Verifikasi — 2 masalah konkret ditemukan** (file ini saya yang tulis di `issue16.md` Tahap 14, jadi ini murni gap dari implementasi sebelumnya, bukan salah brief):

1. **Bug responsif nyata (bukan cuma kurang bagus)**: tab "Players" (baris 112–163) dan tab "Staff" (baris 207–252) cuma pakai `<div class="table-wrapper"><table>...`, **tanpa** `table-card-list` pendamping. Sesuai `docs/frontend-standard.md` bagian *Tabel Responsif*, class `table-wrapper` = `hidden ... lg:block` — artinya di layar **di bawah 1024px** (tablet & smartphone, yang menurut dokumen ini justru alat kerja utama coach/staff di lapangan), roster Player dan Staff tim **sama sekali tidak terlihat**, bukan cuma kurang rapi. Ini pelanggaran langsung terhadap standar wajib *"Dual-render Table (desktop) + Card List (mobile/tablet)"* yang didokumentasikan di `docs/frontend-standard.md`.
2. **UI kurang dibanding referensi (`players/show.blade.php`/`academies/show`)**: halaman ini cuma 1 card datar (header + tab + isi), tidak ada avatar/visual identity di header, tidak ada sidebar info (pola "Informasi Academy"/"Informasi Sistem" di kanan yang dipakai `players/show.blade.php`), dan baris tab (`<div class="flex gap-2">`) tidak pakai utility class `tabs` yang sudah ada (yang otomatis dapat `overflow-x-auto` kalau label tab kepanjangan) — inline `<button>` manual tanpa wrapper scroll.

**Rencana perbaikan** (kalau disepakati) kemungkinan besar: reuse pola `players/show.blade.php` (grid 2/3 + 1/3, tab pakai class `tabs`/`tab`/`tab-active` yang sudah ada) untuk struktur umum, dan tambahkan `table-card-list` di tab Players & Staff mengikuti pola exact yang sudah dipakai di `teams/index.blade.php` (Table + Card List paralel, sama field, sama guard `@can`).

---

## Temuan 3, 4, 5 — Dropdown academy-scoped tidak ikut academy yang dipilih (Team, Staff, Staff Position, + Player)

> **Keputusan**:
> - **Pendekatan teknis**: ✅ Cascading dropdown via AJAX (Alpine `@change` + fetch endpoint JSON kecil per module), bukan reload halaman penuh.
> - **Cakupan reuse**: ✅ Dibangun sebagai **1 pola reusable** (Blade Component + Alpine helper generik) sejak awal, dipakai identik di semua module terdampak — bukan diperbaiki satu-satu lalu diekstrak belakangan.
> - **Cakupan module**: ✅ Audit menyeluruh sudah dilakukan ke **semua** controller yang Super-Admin-aware (11 controller dicek: `EmploymentContractController`, `EmploymentTypeController`, `PlayerCategoryController`, `PlayerController`, `PlayerTypeController`, `RoleController`, `SeasonController`, `StaffController`, `StaffPositionController`, `TeamController`, `TeamStaffPositionController`) dan semua view create/edit yang punya dropdown `id_academy` (10 view dicek). Hasil: **4 module benar-benar terdampak** — **Team** (season+category), **Staff** (employment type+staff position), **Staff Position** (role, sudah ada mitigasi parsial via `optgroup`), **Player** (player type+category, ditemukan saat audit, sebelumnya tidak dilaporkan user). Module lain (`EmploymentType`, `PlayerCategory`, `PlayerType`, `Season`, `TeamStaffPosition`, `Role`) **tidak terdampak** — field yang dibuat di form-nya sendiri adalah master data tanpa dropdown anak yang bergantung academy (atau datanya global, seperti permission di Role). `EmploymentContractController` & semua form **edit** (bukan create) tidak terdampak karena `id_academy`-nya sudah pasti dari record yang sedang diedit, bukan dipilih bebas di form yang sama.

**Laporan user**: di form create, setelah pilih Academy, dropdown Season/Player Category (Team) dan dropdown lain yang terkait academy (Staff, Staff Position) tidak mengikuti academy yang baru dipilih.

**Verifikasi — root cause sama persis di ketiga module, dan ini gap arsitektur lama, bukan regresi dari `issue16`/`issue17`**:

- Untuk user academy biasa, ini **tidak** jadi masalah — `id_academy`-nya tetap (`AcademyService::currentId()`), jadi dropdown anak (season, category, dst) sudah benar sejak awal render halaman.
- Untuk **Super Admin**, controller `create()` di ketiga module memanggil `selectable(null)` (atau setara) untuk dropdown anak — parameter `$academyId = null` berarti **seluruh** data lintas academy ditampilkan tercampur (atau di-`groupBy` academy seperti di Staff Position, tapi tetap tidak otomatis ikut pilihan `id_academy`), karena server **belum tahu** Super Admin akan pilih academy mana saat halaman pertama dirender.
- Ditemukan **tidak ada satupun** pola cascading-dropdown (AJAX/fetch saat `id_academy` berubah) di codebase ini sama sekali — jadi ini bukan bug regresi satu module, tapi **gap yang sama, sengaja/tidak sengaja diwariskan** dari StaffController → StaffPositionController → TeamController (Team Tahap 7 mengikuti pola yang sudah ada, bukan mengarang baru).
- **Kabar baiknya**: ini murni bug UX, **bukan** celah keamanan/data — validasi server (`TeamFormRequest`/dst) tetap men-scope `Rule::exists(...)->where('id_academy', $academyId resolved dari input request)`, jadi kombinasi Academy A + Season milik Academy B tetap **ditolak** saat submit. Masalahnya cuma: dropdown menampilkan pilihan yang salah/campur, dan begitu ditolak, pesan error yang muncul generic ("wajib dipilih") bukan pesan yang menjelaskan "season ini bukan milik academy yang kamu pilih" — user bingung kenapa gagal padahal sudah pilih sesuatu.
- Detail per module:
  - **Team** (`TeamController::create()`): `seasons`/`playerCategories` di-fetch dengan `$academyId = null` untuk Super Admin.
  - **Staff** (`StaffController::create()`): `employmentTypeOptions`/`staffPositionOptions` sama, `$academyId = null`.
  - **Staff Position** (`StaffPositionController::create()`): dropdown `roles` sudah ada mitigasi **parsial** — di-`groupBy` nama academy pakai `<optgroup>`, jadi Super Admin *bisa* mencari grup yang benar secara manual, tapi tetap tidak otomatis ke-filter/sinkron ke pilihan `id_academy`.

**Kemungkinan arah perbaikan** (masih perlu didiskusikan, belum diputuskan):
- Cascading dropdown via Alpine + endpoint JSON kecil (fetch season/category/dst berdasarkan `id_academy` yang dipilih) — solusi paling benar tapi butuh endpoint baru per module.
- Atau: reload halaman penuh saat `id_academy` berubah (`?id_academy=...` lalu redirect balik ke form create dengan query param, controller re-fetch dropdown sesuai academy itu) — lebih murah, tanpa AJAX, tapi UX-nya "meloncat".
- Karena pola ini dipakai berulang di banyak module (Team, Staff, Staff Position, kemungkinan juga module lain yang belum diaudit), kalau mau diperbaiki sebaiknya jadi **1 pola reusable** (component/JS helper), bukan diperbaiki satu-satu per module secara terpisah — supaya module berikutnya yang punya kasus serupa tinggal reuse, bukan mengulang gap yang sama lagi.

---

## Belum Termasuk Scope Diskusi Ini

- Belum mengaudit module lain di luar Team/Staff/Staff Position untuk pola dropdown academy-scoped yang sama (mis. apakah `PlayerController::create()` punya masalah serupa) — kalau relevan, bisa diperluas setelah arah perbaikan pola disepakati.
- Belum ada keputusan final untuk Temuan 1 (bentuk exposure Team di halaman Player) — menunggu diskusi.
- Belum ada estimasi/pemecahan jadi Tahap kerja — sengaja, sesuai permintaan user untuk diskusi dulu.
