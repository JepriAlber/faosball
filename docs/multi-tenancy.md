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

Tabel global tidak menggunakan `id_academy`.

Contoh:

- academies
- roles
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

Contoh role:

- Academy Owner
- Academy Admin
- Coach
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
| Academy Owner | ✓ | ✗ |
| Academy Admin | ✓ | ✗ |
| Coach | ✓ | ✗ |
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

FAOSBall menerapkan arsitektur Single Database Multi-Tenant dengan `id_academy` sebagai pemisah data antar academy. `AcademyScope` memfilter seluruh query secara otomatis, `BelongsToAcademy` mengisi `id_academy` saat membuat data baru, dan `AcademyService` menjadi pusat informasi tenant. Dengan pendekatan ini, seluruh module mengikuti mekanisme multi-tenant yang konsisten tanpa perlu menangani isolasi data secara manual.