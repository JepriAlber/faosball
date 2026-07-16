# Multi-Tenant Architecture

## Overview

FAOSBall menggunakan arsitektur **Single Database Multi-Tenant**.

Satu aplikasi melayani banyak academy menggunakan satu database. Seluruh data tenant dipisahkan menggunakan `id_academy` sehingga setiap academy hanya dapat mengakses datanya sendiri.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Tenant Identifier](#tenant-identifier)
- [User Type](#user-type)
- [Super Admin](#super-admin)
- [Academy Context](#academy-context)
- [Academy Service](#academy-service)
- [Academy Scope](#academy-scope)
- [BelongsToAcademy Trait](#belongstoacademy-trait)
- [Role: Tenant Tanpa BelongsToAcademy](#role-tenant-tanpa-belongstoacademy)
- [Academy Exception](#academy-exception)
- [Login Flow](#login-flow)
- [Login Validation](#login-validation)
- [Gate Before](#gate-before)
- [Data Visibility](#data-visibility)
- [Development Rules](#development-rules)
- [Summary](#summary)

---

## Architecture Overview

FAOSBall menggunakan satu aplikasi dan satu database untuk melayani banyak academy.

Seluruh data tenant berada pada database yang sama.

```text
FAOSBall
│
├── Academy A
│   ├── Players
│   ├── Coaches
│   ├── Parents
│   └── Training
│
├── Academy B
│   ├── Players
│   ├── Coaches
│   ├── Parents
│   └── Training
│
└── Academy C
    ├── Players
    ├── Coaches
    ├── Parents
    └── Training
```

Perbedaan setiap tenant ditentukan oleh nilai `id_academy`.

---

## Tenant Identifier

Seluruh tabel bisnis wajib memiliki field:

```text
id_academy
```

Contoh:

| Table | Tenant Column |
|--------|---------------|
| users | id_academy |
| players | id_academy |
| coaches | id_academy |
| parents | id_academy |
| roles | id_academy (nullable, lihat [Role: Tenant Tanpa BelongsToAcademy](#role-tenant-tanpa-belongstoacademy)) |

Tabel global tidak menggunakan `id_academy`.

Contoh:

- academies
- permissions
- role_has_permissions
- model_has_roles
- model_has_permissions

---

## User Type

FAOSBall memiliki dua jenis user.

### System User

Digunakan oleh sistem FAOSBall.

Karakteristik:

- `id_academy = NULL`
- Memiliki akses ke seluruh academy.

Role:

- Super Admin

---

### Tenant User

Digunakan oleh academy.

Karakteristik:

- `id_academy != NULL`
- Hanya dapat mengakses data academy sendiri.

Contoh role (dari `config('faos.role_templates')`, dibuat otomatis untuk tiap academy baru):

- Owner
- Coach
- Staff
- Finance
- Player
- Parent

---

## Super Admin

Super Admin merupakan pemilik sistem FAOSBall.

Super Admin dapat:

- Mengelola seluruh academy.
- Mengelola seluruh player.
- Mengelola seluruh coach.
- Mengelola seluruh parent.
- Mengelola seluruh payment.
- Mengelola role.
- Mengelola permission.

Super Admin tidak dibatasi oleh `AcademyScope`.

---

## Academy Context

Informasi academy yang sedang aktif dikelola oleh `AcademyService`.

Method yang tersedia:

| Method | Description |
|---------|-------------|
| current() | Mengambil academy aktif |
| currentId() | Mengambil UUID academy aktif |
| isSuperAdmin() | Mengecek apakah user adalah Super Admin |

Seluruh module menggunakan `AcademyService` untuk mendapatkan konteks academy.

---

## Academy Service

`AcademyService` menjadi pusat informasi tenant.

Contoh penggunaan:

```php
$this->academyService->current();

$this->academyService->currentId();

$this->academyService->isSuperAdmin();
```

Hindari mengambil academy langsung menggunakan:

```php
Auth::user()->id_academy;
```

---

## Academy Scope

FAOSBall menggunakan Global Scope bernama `AcademyScope`.

Scope ini memfilter seluruh query secara otomatis berdasarkan academy yang sedang aktif.

Contoh:

```php
Player::all();
```

akan otomatis menjadi:

```sql
SELECT *
FROM players
WHERE id_academy = CURRENT_ACADEMY
```

Developer tidak perlu lagi menambahkan filter `where('id_academy', ...)` pada setiap query.

---

## BelongsToAcademy Trait

Seluruh model tenant menggunakan trait:

```php
use BelongsToAcademy;
```

Trait memiliki dua tanggung jawab.

### Menambahkan AcademyScope

Seluruh query otomatis difilter berdasarkan academy aktif.

### Mengisi id_academy

Saat proses `creating`, `id_academy` akan diisi secara otomatis.

Contoh:

```php
Player::create([
    'name' => 'Andi'
]);
```

akan menjadi:

```php
Player::create([
    'id_academy' => CURRENT_ACADEMY,
    'name' => 'Andi'
]);
```

Developer tidak perlu mengisi `id_academy` secara manual.

---

## Role: Tenant Tanpa BelongsToAcademy

`App\Models\Role` (extends `Spatie\Permission\Models\Role`) adalah tabel tenant — punya `id_academy` — tapi **sengaja tidak** memakai trait `BelongsToAcademy` maupun `AcademyScope`. Ini satu-satunya pengecualian pada aturan "seluruh model tenant memakai `BelongsToAcademy`" di atas.

**Kenapa dikecualikan:**

1. **Global scope meracuni cache Spatie lintas tenant.** `PermissionRegistrar` menjalankan `Permission::with('roles')->get()` untuk membangun peta permission, lalu menyimpannya ke **satu cache key bersama** untuk seluruh tenant (`spatie.permission.cache`). Kalau `Role` dipasangi global scope, eager-load `with('roles')` ikut terfilter oleh academy siapa pun yang kebetulan memicu rebuild cache saat itu — role milik academy lain "hilang" dari cache sampai di-flush, dan bug-nya terlihat acak karena bergantung akademi mana yang terakhir memicu rebuild.
2. **`BelongsToAcademy` melempar exception saat `id_academy` null.** Trait ini selalu mengisi `id_academy` dari user yang login dan menolak kalau `currentId()` null — padahal Super Admin (`id_academy = null`) justru harus bisa membuat Role System maupun role untuk academy manapun.

**Solusi yang dipakai sebagai gantinya:**

- **Local scope** `Role::forCurrentAcademy()` — dipanggil eksplisit oleh `RoleService::paginate()`, bukan otomatis seperti global scope. Super Admin melihat seluruh role; user academy hanya melihat role academy-nya.
- **`RolePolicy`** — mencegah akses lintas academy lewat URL langsung (mis. `Owner` Academy A membuka `/roles/{id}` milik Academy B), karena middleware permission (`role.view`) cuma memeriksa hak akses fitur, bukan kepemilikan baris data.
- **`id_academy` diisi eksplisit oleh Service** (`RoleService::resolveAcademyId()`, `RoleService::createDefaultRoles()`), bukan otomatis lewat trait.

**Cara kerja isolasi permission per academy.** Permission tetap **global** (satu baris permission dipakai bersama seluruh sistem). Yang membedakan Academy A dan Academy B adalah **baris `roles`**: masing-masing academy punya baris `Owner` sendiri (id berbeda) dengan kombinasi permission masing-masing di `role_has_permissions`. Spatie mencocokkan permission lewat **primary key role**, bukan nama, jadi dua academy boleh punya role bernama sama tanpa saling memengaruhi hak aksesnya. Detail lengkap ada di komentar `App\Models\Role` dan `App\Policies\RolePolicy`.

---

## Academy Exception

Apabila academy tidak ditemukan ketika membuat data tenant, proses akan dihentikan dengan exception.

Hal ini mencegah data tanpa academy tersimpan ke database.

Model `Academy` tidak menggunakan trait `BelongsToAcademy` karena academy merupakan root tenant.

---

## Login Flow

Setelah user berhasil login menggunakan Laravel Breeze, FAOSBall melakukan validasi tenant.

Alur login:

```text
Email
    │
    ▼
Password
    │
    ▼
Authentication
    │
    ▼
User Status
    │
    ▼
Role
    │
    ▼
Academy
    │
    ▼
Academy Status
    │
    ▼
Dashboard
```

---

## Login Validation

Selain proses authentication, sistem melakukan validasi berikut.

- Status user harus aktif.
- Super Admin langsung diberikan akses.
- User academy wajib memiliki `id_academy`.
- Academy harus tersedia.
- Academy harus berstatus aktif.

Apabila salah satu validasi gagal, proses login dibatalkan.

---

## Gate Before

FAOSBall menggunakan `Gate::before()` untuk memberikan akses penuh kepada Super Admin.

Contoh:

```php
Gate::before(function ($user) {
    if ($user->hasRole('Super Admin')) {
        return true;
    }
});
```

Seluruh user selain Super Admin tetap mengikuti pemeriksaan permission.

---

## Data Visibility

| Role | Academy Sendiri | Academy Lain |
|------|:---------------:|:------------:|
| Super Admin | ✓ | ✓ |
| Owner | ✓ | ✗ |
| Coach | ✓ | ✗ |
| Staff | ✓ | ✗ |
| Finance | ✓ | ✗ |
| Player | ✓ | ✗ |
| Parent | ✓ | ✗ |

---

## Development Rules

Seluruh module baru wajib mengikuti aturan berikut.

### Gunakan

- `BelongsToAcademy`
- `AcademyScope`
- `AcademyService`
- `id_academy`

### Hindari

```php
Player::where(
    'id_academy',
    Auth::user()->id_academy
);
```

dan

```php
$idAcademy = Auth::user()->id_academy;
```

Gunakan:

```php
$this->academyService->currentId();
```

atau biarkan `BelongsToAcademy` mengisi `id_academy` secara otomatis.

---

## Summary

FAOSBall menerapkan arsitektur Single Database Multi-Tenant dengan `id_academy` sebagai pemisah data antar academy. `AcademyScope` memfilter seluruh query secara otomatis, `BelongsToAcademy` mengisi `id_academy` saat membuat data baru, dan `AcademyService` menjadi pusat informasi tenant. Dengan pendekatan ini, seluruh module mengikuti mekanisme multi-tenant yang konsisten tanpa perlu menangani isolasi data secara manual. `Role` adalah satu-satunya pengecualian: tetap tenant (punya `id_academy`), tapi memakai local scope + Policy, bukan `BelongsToAcademy`/`AcademyScope`, karena global scope pada `Role` akan meracuni cache Spatie lintas tenant (lihat [Role: Tenant Tanpa BelongsToAcademy](#role-tenant-tanpa-belongstoacademy)).