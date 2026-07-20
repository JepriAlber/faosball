# Referensi Permission per Module

## Overview

Dokumen ini adalah **peta module → permission** yang benar-benar dipakai di kode (route middleware, `$this->authorize()`, `@can()` di Blade) — bukan sekadar daftar nama permission di seeder. Tujuannya supaya saat customize akses role per academy lewat halaman Role Management, jelas permission mana yang benar-benar mengunci fitur mana, dan module mana yang permission-nya **belum** digerbang sama sekali (jangan dikira sudah aman padahal belum).

Sumber kebenaran (source of truth) tetap kode — `database/seeders/RolePermissionSeeder.php` untuk daftar permission, `config('faos.role_templates')` untuk default per role, `routes/web.php` + Blade view untuk penegakannya. Dokumen ini cuma ringkasannya. Kalau ada yang beda antara dokumen ini dan kode, **kode yang benar** — segera update dokumen ini.

---

## Table of Contents

- [Cara Baca Status](#cara-baca-status)
- [Module: Role Management](#module-role-management)
- [Module: Permission Management](#module-permission-management)
- [Module: Player Management](#module-player-management)
- [Module: Player Type](#module-player-type)
- [Module: Player Category](#module-player-category)
- [Module: Player Position (Master Global)](#module-player-position-master-global)
- [Module: Academy Management](#module-academy-management)
- [Permission Belum Dipakai Module Manapun](#permission-belum-dipakai-module-manapun)
- [Role Template Default per Academy Baru](#role-template-default-per-academy-baru)
- [Development Rules](#development-rules)
- [Summary](#summary)

---

## Cara Baca Status

| Status | Arti |
|--------|------|
| ✅ Implemented | Route pakai `permission:xxx` middleware **dan** tombol/menu di Blade dibungkus `@can()`. Aman diandalkan. |
| 🚨 Belum digerbang | Permission-nya **ada** di seeder/role template, tapi route & view module ini **tidak** memeriksanya sama sekali — siapapun yang login bisa akses lewat URL langsung, terlepas dari role-nya. |
| 🚧 Disiapkan | Permission ada di seeder untuk module yang **belum dibangun** (belum ada Controller/View-nya). |

---

## Module: Role Management

Status: **✅ Implemented** (route middleware + `@can()` + `RolePolicy` untuk isolasi antar academy — lihat `docs/authorization.md#role-academy-based`).

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `role.view` | Lihat daftar role & detail role | `roles.index`, `roles.show` (route middleware) + `RolePolicy` per baris |
| `role.create` | Tambah role baru | `roles.create`, `roles.store` (route middleware) |
| `role.update` | Ubah nama/permission role | `roles.edit`, `roles.update` (route middleware) + `RolePolicy` |
| `role.delete` | Hapus role (kalau tidak dipakai user manapun) | `roles.destroy` (route middleware) + `RolePolicy` |

Catatan: Role Super Admin tidak bisa diedit/dihapus siapapun (dicek eksplisit di `RoleService`, bukan lewat permission).

---

## Module: Permission Management

Status: **✅ Implemented**.

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `permission.view` | Lihat daftar & detail permission | `permissions.index`, `permissions.show` (route middleware) |
| `permission.create` | Tambah permission baru | `permissions.create`, `permissions.store` (route middleware) |
| `permission.delete` | Hapus permission (kalau tidak dipakai role manapun) | `permissions.destroy` (route middleware) |

Catatan: `permission.update` **ada** di seeder tapi sengaja tidak dipakai — Permission tidak punya fitur edit karena nama permission dipakai sebagai literal string di middleware/`@can()`/`RolePermissionSeeder`, jadi mengganti nama setelah dibuat akan membuat pengecekan akses gagal diam-diam (lihat `docs/authorization.md#future-modules`).

---

## Module: Player Management

Status: **✅ Implemented** (termasuk Card List mobile/tablet, lihat `docs/frontend-standard.md`).

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `player.view` | Lihat daftar & detail player | `players.index`, `players.show` (route middleware) + menu sidebar "Players" |
| `player.create` | Tambah player baru | `players.create`, `players.store` (route middleware) + tombol "Tambah Player" |
| `player.update` | Ubah data player | `players.edit`, `players.update` (route middleware) + tombol "Edit" |
| `player.delete` | Hapus player | `players.destroy` (route middleware) + tombol delete |

### Sub-module: Player Account (login player)

Nested di `players/{player}/account/*`. Ini **membuat/mengelola record `User`** (login), bukan data `Player` itu sendiri — jadi sengaja pakai permission `user.*`, bukan `player.*`:

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `user.create` | Buat akun login untuk player yang belum punya akun | `players.account.create`, `players.account.store` (route middleware) + tombol "Buat Akun"/"Buat Account" |
| `user.update` | Edit akun, reset password, aktif/nonaktifkan akun | `players.account.edit`, `players.account.update`, `players.account.status`, `players.account.password` (route middleware) + `<x-account.dropdown>` |

Konsekuensi: role yang punya `player.update` tapi **tidak** punya `user.create`/`user.update` (mis. `Coach`, `Staff` di template default) bisa mengelola data player tapi **tidak bisa** membuatkan/mengubah akun login player.

Catatan tambahan: `players.id_player_type` wajib diisi saat create — divalidasi di `StorePlayerRequest` (dan `UpdatePlayerRequest` saat edit), dibatasi ke type milik academy yang sama dengan player (lihat [Module: Player Type](#module-player-type)). `players.id_player_category` wajib diisi dengan cara yang sama (lihat [Module: Player Category](#module-player-category)). `players.id_primary_position` wajib diisi saat create; `id_secondary_position` opsional dan tidak boleh sama dengan posisi utama (lihat [Module: Player Position](#module-player-position-master-global)) — berbeda dengan Type/Category, validasi posisi **tidak** difilter `id_academy` karena datanya global.

---

## Module: Player Type

Status: **✅ Implemented** (termasuk Card List mobile/tablet, lihat `docs/frontend-standard.md`).

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `player_type.view` | Lihat daftar player type | `player-types.index` (route middleware) + menu sidebar |
| `player_type.create` | Tambah type baru | `player-types.create`, `player-types.store` |
| `player_type.update` | Ubah nama/deskripsi/tagihan/status type | `player-types.edit`, `player-types.update` |
| `player_type.delete` | Hapus type (kalau tidak dipakai player manapun) | `player-types.destroy` |

Catatan:

- Isolasi antar academy memakai `AcademyScope` (global scope), **bukan** Policy — akses type academy lain menghasilkan **404**, bukan 403. Ini beda dengan module Role.
- `player_type.view` **tidak** dibutuhkan untuk memilih type saat menambah Player. Dropdown di form Player diisi Service; permission ini hanya menggerbang halaman `/player-types`.
- `is_billable` adalah kontrak untuk module Payment: filter `where('is_billable', true)`, **jangan** cocokkan nama type.

---

## Module: Player Category

Status: **✅ Implemented** (termasuk Card List mobile/tablet, lihat `docs/frontend-standard.md`).

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `player_category.view` | Lihat daftar kategori umur | `player-categories.index` (route middleware) + menu sidebar |
| `player_category.create` | Tambah kategori baru | `player-categories.create`, `player-categories.store` |
| `player_category.update` | Ubah nama/rentang umur/status kategori | `player-categories.edit`, `player-categories.update` |
| `player_category.delete` | Hapus kategori (kalau tidak dipakai player manapun) | `player-categories.destroy` |

Catatan:

- Isolasi antar academy memakai `AcademyScope` (global scope), **bukan** Policy — akses kategori academy lain menghasilkan **404**, bukan 403. Sama seperti Player Type.
- `player_category.view` **tidak** dibutuhkan untuk memilih kategori saat menambah Player. Dropdown di form Player diisi Service; permission ini hanya menggerbang halaman `/player-categories`.
- `min_age`/`max_age` **hanya untuk menyarankan** kategori dari `birth_date`. Sistem **tidak pernah menolak** player yang umurnya di luar rentang — "main naik kelas" adalah hal normal di sepak bola (lihat `issue2.md` Bagian 4.2).

---

## Module: Player Position (Master Global)

Status: **✅ Implemented** (termasuk Card List mobile/tablet, lihat `docs/frontend-standard.md`).

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `player_position.view` | Lihat master posisi | `player-positions.index` (route middleware) + menu sidebar Master |
| `player_position.create` | Tambah posisi baru | `player-positions.create`, `player-positions.store` |
| `player_position.update` | Ubah kode/nama/kelompok/urutan/status | `player-positions.edit`, `player-positions.update` |
| `player_position.delete` | Hapus posisi (kalau tidak dipakai player manapun) | `player-positions.destroy` |

Catatan — module ini **berbeda total** dari Player Type & Player Category walau namanya mirip:

- Ini **master data global** — tabel `player_positions` tidak punya `id_academy`, dibaca seluruh academy, **CRUD-nya khusus Super Admin**.
- `player_position.*` **sengaja tidak ada di `role_templates` manapun** — termasuk Owner. Itu yang membuatnya Super-Admin-only, sama seperti `permission.*` dan `academy.*`.
- Akses dari role academy ditolak dengan **403** (dari middleware permission), **bukan 404** seperti module tenant (Player Type/Category) yang mengandalkan `AcademyScope`.
- `player_position.view` **tidak** dibutuhkan untuk memilih posisi saat menambah Player — dropdown diisi Service, sama seperti module tenant lainnya.
- Posisi dipakai di **dua** kolom Player (`id_primary_position` dan `id_secondary_position`), jadi guard hapus wajib memeriksa dua-duanya (lihat `issue3.md` Bagian 4.2), dan hitungan "dipakai N player" wajib `withoutGlobalScopes()` karena `Player` sendiri masih memakai `AcademyScope` (lihat `issue3.md` Bagian 4.3).

---

## Module: Academy Management

Status: **✅ Implemented**.

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `academy.view` | Lihat daftar & detail academy (termasuk data subscription) | `academies.index`, `academies.show` (route middleware) + `@can()` tombol Detail |
| `academy.create` | Tambah academy baru | `academies.create`, `academies.store` (route middleware) + `@can()` tombol "Tambah Academy" |
| `academy.update` | Ubah profil academy & data subscription | `academies.edit`, `academies.update` (route middleware) + `@can()` tombol Edit |
| `academy.delete` | Hapus academy | `academies.destroy` (route middleware) + `@can()` tombol Hapus |

Catatan:

- `academy.*` **sengaja tidak ada di `config('faos.role_templates')` manapun**, termasuk Owner — modul ini Super-Admin-only, sama seperti `player_position.*`. Akses dari role academy manapun ditolak dengan **403** (dari middleware permission), bukan 404 seperti module tenant (Player Type/Category) yang mengandalkan `AcademyScope`.
- Menu sidebar "Academy" ada di dalam grup "Administration" yang sudah dibungkus `isSuperAdmin()` — tidak dobel-digerbang `@can()` per-item seperti menu Player, konsisten dengan menu Roles/Permissions di grup yang sama.
- **Subscription** (`subscription_type`, `subscription_fee`, `subscription_started_at`, `subscription_ends_at`) adalah field deskriptif pada tabel `academies` sendiri — dikunci oleh permission yang sama dengan profil academy (`academy.update`), **tidak punya permission terpisah**. Status langganan (Aktif/Akan Berakhir/Kadaluarsa) dihitung dari `subscription_ends_at` lewat `AcademyManagementService::subscriptionStatus()`, bukan kolom tersimpan.

### Sub-module: Academy Account (login Owner)

Nested di `academies/{academy}/account/*`. Sama seperti *Sub-module: Player Account*, ini **membuat/mengelola record `User`** (login Owner), bukan data `Academy` itu sendiri — tapi **beda** dari Player Account soal permission: di sini **sengaja tetap** `academy.update`, bukan permission `user.*` atau permission baru.

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `academy.update` | Buat akun Owner (kalau belum ada), edit akun, reset password, aktif/nonaktifkan akun Owner | `academies.account.create`, `academies.account.store`, `academies.account.edit`, `academies.account.update`, `academies.account.status`, `academies.account.password` (route middleware) + `<x-account.dropdown>` di `academies.show` |

Kenapa **tidak** dipisah seperti Player Account (`user.create`/`user.update` terpisah dari `player.*`): pemisahan itu berguna di Player karena role macam Coach/Staff **memang** didelegasikan `player.update` tapi belum tentu boleh membuat akun login player. Di Academy, tidak ada role manapun (termasuk Owner) yang pernah punya `academy.*` sama sekali (lihat catatan di atas) — jadi menambah permission terpisah untuk sub-resource-nya tidak memberi pemisahan hak akses yang nyata, cuma menambah satu baris permission yang tidak pernah dipakai membedakan siapa boleh apa.

Akun Owner yang dibuat lewat sub-module ini otomatis diberi role **Owner** (hardcode di `AcademyManagementService::create()` dan `AcademyAccountController::store()`) — role lain **tidak bisa** dipilih lewat jalur ini. Relasi ke akun disimpan lewat kolom `academies.id_owner_user` (bukan dicari lewat role) — lihat `issue2.md` Bagian 2a. Membuat akun Owner **tidak otomatis** membuatnya bisa login — `LoginRequest` juga mensyaratkan `academies.status = true`, dua kondisi yang sengaja independen (lihat `issue2.md` Bagian 2e).

---

## Module: Academy Profile (Self-Service)

Status: **✅ Implemented**.

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `academy_profile.update` | Owner melihat & mengubah profil UMUM academy miliknya sendiri (nama, tagline, kontak, alamat, deskripsi, logo, warna utama) | `academy.profile.edit`, `academy.profile.update` (route middleware) + menu sidebar |

Catatan:

- **Beda total** dari `academy.*` (module *Academy Management* di atas) walau sama-sama soal data Academy:
  - `academy.*` — Super-Admin-only, CRUD *lintas seluruh academy*, termasuk field sensitif (`code`, `status`, subscription).
  - `academy_profile.update` — default diberikan ke **Owner** lewat `role_templates` (bisa didelegasikan/dicabut lewat Role Management, seperti `player_type.*`), hanya bisa mengubah *academy miliknya sendiri* (tidak menerima ID dari luar), dan field-nya dibatasi ketat ke profil umum saja.
- `code`, `status`, dan seluruh field subscription **tidak pernah** bisa diubah lewat endpoint ini, ditegakkan dua lapis: `AcademyProfileFormRequest` tidak punya rule untuk field itu, dan `AcademyManagementService::updateProfile()` membangun payload update dengan whitelist eksplisit (tidak mengoper `$data` mentah ke `Model::update()`).
- Super Admin (`id_academy = null`) tidak punya "academy sendiri", jadi mengakses halaman ini menghasilkan **404** (bukan error 500), bukan cuma diblokir permission.
- Menu sidebar "Profil Academy" digerbang `@can('academy_profile.update')` **plus** `!isSuperAdmin()` eksplisit — `Gate::before()` (lihat `docs/multi-tenancy.md` → *Gate Before*) meloloskan Super Admin dari `@can()` manapun, jadi tanpa guard tambahan ini menu akan ikut tampil untuk Super Admin walau modul ini memang tidak untuknya (sudah ada *Academy Management* untuk itu).

---

## Permission Belum Dipakai Module Manapun

Permission ini sudah ada di `RolePermissionSeeder` dan sudah masuk beberapa role template (`config('faos.role_templates')`), tapi **module/Controller/View-nya belum dibangun**, jadi belum ada yang benar-benar mengeceknya:

| Module (rencana) | Permission |
|---|---|
| Coach | `coach.view`, `coach.create`, `coach.update`, `coach.delete` |
| Team | `team.view`, `team.create`, `team.update`, `team.delete` |
| Training | `training.view`, `training.create`, `training.update`, `training.delete` |
| Attendance | `attendance.view`, `attendance.create`, `attendance.update` |
| Evaluation | `evaluation.view`, `evaluation.create`, `evaluation.update` |
| Payment | `payment.view`, `payment.create`, `payment.update`, `payment.report` |
| Report | `report.view`, `report.export` |
| Parent Portal | `child.profile.view`, `child.training.view`, `child.payment.view` |
| User Management (umum) | `user.view`, `user.delete` (yang `user.create`/`user.update` sudah dipakai lewat Player Account, lihat di atas) |

---

## Role Template Default per Academy Baru

Setiap academy baru otomatis dapat 6 role ini dari `config('faos.role_templates')` (`RoleService::createDefaultRoles()`). Ini titik awal saat customize — Owner academy bisa ubah permission tiap role lewat halaman Role Management, tabel di bawah cuma nilai default saat academy dibuat.

| Role | academy_profile.update | player.* | player_type.* | player_category.* | coach.*/team.*/training.* | attendance.*/evaluation.* | payment.*/report.* | role.* | user.* |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Owner** | ✅ | view, create, update, delete | penuh | penuh | penuh | penuh | penuh | penuh | penuh |
| **Coach** | ✗ | view | ✗ | ✗ | team.view, training penuh | penuh | ✗ | ✗ | ✗ |
| **Staff** | ✗ | view, create, update | ✗ | ✗ | coach.view, team.view, training.view | view, create, update | ✗ | ✗ | ✗ |
| **Finance** | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | penuh | ✗ | ✗ |
| **Player** | ✗ | ✗ (lihat data sendiri lewat portal, bukan `player.*`) | ✗ | ✗ | ✗ | training.view, attendance.view, evaluation.view | ✗ | ✗ | ✗ |
| **Parent** | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ (punya `child.*` sendiri) |

`player_type.*` dan `player_category.*` sengaja hanya diberikan ke **Owner** secara default — mengatur jenis/kelompok umur pemain adalah keputusan level Owner. Academy bisa memberi role lain akses ini lewat halaman Role Management kalau perlu. `academy_profile.update` mengikuti pola yang sama — default Owner saja, tapi bisa didelegasikan. Ini beda dengan `academy.*` (CRUD lintas academy, termasuk subscription) yang **tidak pernah** ada di role template manapun sama sekali, lihat [Module: Academy Management](#module-academy-management).

`player_position.*` **tidak diberikan ke role manapun**, termasuk Owner — tidak seperti `player_type.*`/`player_category.*` yang bisa didelegasikan Owner ke role lain lewat Role Management, `player_position.*` memang tidak boleh didelegasikan sama sekali karena datanya dipakai bersama seluruh academy, bukan milik satu academy.

Detail lengkap tiap role: lihat `config/faos.php` bagian `role_templates`.

---

## Development Rules

Wajib diikuti supaya dokumen ini tidak basi:

- **Setiap kali module baru menambahkan permission checking** (route `middlewareFor()`, `$this->authorize()`, atau `@can()` baru di Blade), tambahkan section baru di dokumen ini dengan format yang sama: tabel Permission → Untuk apa → Digerbang di.
- **Setiap kali permission baru ditambahkan ke `RolePermissionSeeder`** tapi module-nya belum dibangun, masukkan ke tabel [Permission Belum Dipakai Module Manapun](#permission-belum-dipakai-module-manapun) — jangan biarkan permission "menghilang" tanpa jejak di dokumentasi.
- **Kalau sebuah module ternyata permission-nya belum digerbang** (seperti kasus Academy Management sebelum ditutup), tandai status **🚨 Belum digerbang** dengan penjelasan bagian mana yang kurang (route/controller/view) — jangan didiamkan seolah sudah aman.
- Checklist `docs/module-standard.md` bagian Authorization sudah mengacu ke dokumen ini — module baru tidak dianggap selesai sebelum entry-nya ditambahkan di sini.

---

## Summary

FAOSBall punya banyak permission yang sudah disiapkan di seeder untuk pengembangan jangka panjang, tapi tidak semuanya sudah benar-benar ditegakkan di kode. Saat ini **Role, Permission, Player Management, Player Type, Player Category, Player Position, Academy Management (termasuk sub-module Academy Account), dan Academy Profile sudah digerbang penuh** (route + Blade), dan sisanya (Coach, Team, Training, Attendance, Evaluation, Payment, Report, Parent Portal) masih berupa permission yang menunggu module-nya dibangun. Dokumen ini jadi rujukan tunggal supaya saat customize role per academy, jelas fitur apa yang sebenarnya dikunci oleh permission apa — dan supaya module yang permission-nya belum digerbang tidak terlupakan.
