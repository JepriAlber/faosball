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

---

## Module: Academy Management

Status: **🚨 Belum digerbang.**

Permission `academy.view`, `academy.create`, `academy.update`, `academy.delete` **sudah ada** di `RolePermissionSeeder` dan di template role `Owner`, tapi:

- `routes/web.php` — `Route::resource('academies', AcademyController::class)` **tidak** punya `middlewareFor()`.
- `AcademyController` — tidak ada `$this->authorize()`.
- `resources/views/academies/*.blade.php` — tidak ada `@can()`.

Praktiknya: menu "Academy Management" cuma **kelihatan** oleh Super Admin (dibungkus `isSuperAdmin()` di sidebar), tapi kalau ada user non-Super-Admin yang tahu URL-nya (`/academies`, `/academies/create`, dst), route-nya **tetap bisa diakses** — cuma dilindungi middleware `auth` biasa, bukan permission. Ini beda dari Player/Role/Permission yang sudah digerbang penuh.

Kalau mau ditutup dengan pola yang sama seperti Player (Tahap terakhir kemarin), tinggal bilang.

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

| Role | player.* | coach.*/team.*/training.* | attendance.*/evaluation.* | payment.*/report.* | role.* | user.* |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Owner** | view, create, update, delete | penuh | penuh | penuh | penuh | penuh |
| **Coach** | view | team.view, training penuh | penuh | ✗ | ✗ | ✗ |
| **Staff** | view, create, update | coach.view, team.view, training.view | view, create, update | ✗ | ✗ | ✗ |
| **Finance** | ✗ | ✗ | ✗ | penuh | ✗ | ✗ |
| **Player** | ✗ (lihat data sendiri lewat portal, bukan `player.*`) | ✗ | training.view, attendance.view, evaluation.view | ✗ | ✗ | ✗ |
| **Parent** | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ (punya `child.*` sendiri) |

Detail lengkap tiap role: lihat `config/faos.php` bagian `role_templates`.

---

## Development Rules

Wajib diikuti supaya dokumen ini tidak basi:

- **Setiap kali module baru menambahkan permission checking** (route `middlewareFor()`, `$this->authorize()`, atau `@can()` baru di Blade), tambahkan section baru di dokumen ini dengan format yang sama: tabel Permission → Untuk apa → Digerbang di.
- **Setiap kali permission baru ditambahkan ke `RolePermissionSeeder`** tapi module-nya belum dibangun, masukkan ke tabel [Permission Belum Dipakai Module Manapun](#permission-belum-dipakai-module-manapun) — jangan biarkan permission "menghilang" tanpa jejak di dokumentasi.
- **Kalau sebuah module ternyata permission-nya belum digerbang** (seperti Academy Management saat ini), tandai status **🚨 Belum digerbang** dengan penjelasan bagian mana yang kurang (route/controller/view) — jangan didiamkan seolah sudah aman.
- Checklist `docs/module-standard.md` bagian Authorization sudah mengacu ke dokumen ini — module baru tidak dianggap selesai sebelum entry-nya ditambahkan di sini.

---

## Summary

FAOSBall punya banyak permission yang sudah disiapkan di seeder untuk pengembangan jangka panjang, tapi tidak semuanya sudah benar-benar ditegakkan di kode. Saat ini **Role, Permission, dan Player Management sudah digerbang penuh** (route + Blade), **Academy Management punya permission tapi belum digerbang sama sekali**, dan sisanya (Coach, Team, Training, Attendance, Evaluation, Payment, Report, Parent Portal) masih berupa permission yang menunggu module-nya dibangun. Dokumen ini jadi rujukan tunggal supaya saat customize role per academy, jelas fitur apa yang sebenarnya dikunci oleh permission apa — dan supaya module yang permission-nya belum digerbang tidak terlupakan.
