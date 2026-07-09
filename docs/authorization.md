# Authorization

## Overview

FAOSBall menggunakan **Spatie Laravel Permission** untuk mengelola Role dan Permission.

Authentication ditangani oleh Laravel Breeze, sedangkan authorization menggunakan kombinasi Spatie Permission, Laravel Gate, Middleware, dan Blade Directive.

---

## Table of Contents

- [Authorization Overview](#authorization-overview)
- [Authorization Flow](#authorization-flow)
- [Authentication vs Authorization](#authentication-vs-authorization)
- [System Roles](#system-roles)
- [Role Responsibility](#role-responsibility)
- [Permission Naming](#permission-naming)
- [CRUD Permission Standard](#crud-permission-standard)
- [Special Permission](#special-permission)
- [Seeder Strategy](#seeder-strategy)
- [Role Assignment](#role-assignment)
- [Account Service](#account-service)
- [Permission Checking](#permission-checking)
- [Menu Visibility](#menu-visibility)
- [Route Protection](#route-protection)
- [Controller Protection](#controller-protection)
- [Super Admin Bypass](#super-admin-bypass)
- [Administration Module](#administration-module)
- [Future Modules](#future-modules)
- [Permission Grouping](#permission-grouping)
- [Development Rules](#development-rules)
- [Summary](#summary)

---

## Authorization Overview

FAOSBall menggunakan package **Spatie Laravel Permission** untuk mengelola:

- Role
- Permission
- Role Permission Mapping
- User Role Assignment
- Authorization

Laravel Gate tetap digunakan untuk proses authorization, sedangkan seluruh data Role dan Permission dikelola oleh Spatie.

---

## Authorization Flow

Seluruh proses authorization mengikuti alur berikut.

```text
User Login
    │
    ▼
Authentication (Laravel Breeze)
    │
    ▼
User Loaded
    │
    ▼
Gate::before()
    │
    ├── Super Admin → Allow All
    │
    ▼
Spatie Permission
    │
    ▼
Permission Check
    │
    ▼
Access Granted / Access Denied
```

---

## Authentication vs Authorization

Authentication menjawab pertanyaan:

> Siapa user yang login?

Menggunakan:

- Laravel Breeze
- LoginRequest

Authorization menjawab pertanyaan:

> Apa yang boleh dilakukan user?

Menggunakan:

- Spatie Permission
- Laravel Gate
- Permission Middleware
- Blade Directive

---

## System Roles

Role bawaan FAOSBall:

- Super Admin
- Academy Owner
- Academy Admin
- Coach
- Player
- Parent

Seluruh role dibuat melalui:

```text
RolePermissionSeeder
```

---

## Role Responsibility

### Super Admin

Memiliki akses penuh terhadap sistem.

Hak akses:

- Academy Management
- Role Management
- Permission Management
- Player
- Coach
- Parent
- Payment
- Report

---

### Academy Owner

Mengelola seluruh operasional academy.

Tidak dapat mengakses academy lain.

---

### Academy Admin

Mengelola aktivitas harian academy.

---

### Coach

Dapat mengelola:

- Training
- Attendance
- Evaluation

---

### Player

Dapat melihat:

- Data pribadi
- Training
- Attendance
- Evaluation

---

### Parent

Dapat melihat:

- Progress anak
- Attendance
- Evaluation
- Payment

---

## Permission Naming

Permission menggunakan format:

```text
module.action
```

Contoh:

```text
player.view
player.create
player.update
player.delete
```

Hindari format seperti:

```text
view player
create-player
Player Create
```

Gunakan format yang konsisten pada seluruh module.

---

## CRUD Permission Standard

Setiap module minimal memiliki empat permission.

```text
module.view
module.create
module.update
module.delete
```

Contoh:

```text
coach.view
coach.create
coach.update
coach.delete
```

---

## Special Permission

Beberapa module memiliki permission tambahan sesuai kebutuhan bisnis.

Contoh:

```text
payment.report
report.export
attendance.update
evaluation.update
```

Permission tidak harus selalu mengikuti pola CRUD apabila proses bisnis membutuhkan aksi tambahan.

---

## Seeder Strategy

Seluruh data awal dibuat melalui:

```text
RolePermissionSeeder
```

Seeder ini membuat:

- Permission
- Role
- Academy Default
- Super Admin
- Academy Owner
- Academy Admin

Dengan strategi ini sistem dapat langsung digunakan setelah menjalankan:

```bash
php artisan migrate:fresh --seed
```

---

## Role Assignment

Role diberikan menggunakan method:

```php
$user->assignRole($role);
```

Seluruh proses assignment dilakukan melalui:

```text
AccountService
```

Contoh:

```php
$this->accountService->create(
    [...],
    'Player'
);
```

Module lain tidak memberikan role secara langsung.

---

## Account Service

`AccountService` menjadi pusat pengelolaan akun.

Tanggung jawabnya meliputi:

- Create User
- Hash Password
- Assign Role

Service ini juga disiapkan untuk pengembangan berikutnya seperti:

- Reset Password
- Enable / Disable User
- Change Role

Seluruh module yang membuat akun wajib menggunakan `AccountService`.

---

## Permission Checking

Permission dapat diperiksa menggunakan Controller maupun Blade.

Controller:

```php
auth()->user()->can('player.create');
```

atau

```php
Gate::allows('player.create');
```

Blade:

```blade
@can('player.create')

@endcan
```

atau

```blade
@canany([
    'player.create',
    'player.update'
])

@endcanany
```

---

## Menu Visibility

Menu ditampilkan berdasarkan permission.

Contoh:

```blade
@can('player.view')

<li>Players</li>

@endcan
```

Jika user tidak memiliki permission, menu tidak akan dirender.

---

## Route Protection

Seluruh route wajib menggunakan Permission Middleware.

Contoh:

```php
Route::middleware('permission:player.view');
```

atau

```php
Route::middleware([
    'permission:player.create'
]);
```

Route tidak boleh diakses hanya berdasarkan URL.

---

## Controller Protection

Selain middleware, Controller dapat melakukan pengecekan tambahan.

Contoh:

```php
abort_unless(
    auth()->user()->can('player.update'),
    403
);
```

Prioritas utama tetap menggunakan Permission Middleware.

---

## Super Admin Bypass

Super Admin mendapatkan akses penuh melalui `Gate::before()`.

Contoh:

```php
Gate::before(function ($user) {

    if ($user->hasRole('Super Admin')) {
        return true;
    }

});
```

Super Admin tidak memerlukan seluruh permission satu per satu.

---

## Administration Module

Menu Administration hanya dapat diakses oleh Super Admin.

Module yang tersedia:

```text
Administration
│
├── Academy Management
├── Roles
└── Permissions
```

Role selain Super Admin tidak dapat mengakses menu tersebut.

---

## Future Modules

Role Management akan menggunakan model bawaan Spatie.

Fitur yang direncanakan:

- List Role
- Create Role
- Update Role
- Delete Role
- Assign Permission

Permission Management juga menggunakan model bawaan Spatie dengan fitur:

- List Permission
- Create Permission
- Delete Permission

> Permission tidak memiliki fitur Update. Nama permission dipakai sebagai
> literal string pada Permission Middleware, Blade `@can()`, dan
> `RolePermissionSeeder`, sehingga perubahan nama setelah dibuat akan membuat
> pengecekan akses gagal secara diam-diam tanpa error yang terlihat.

---

## Permission Grouping

Permission dikelompokkan berdasarkan module.

Contoh:

```text
Player

□ View
□ Create
□ Update
□ Delete
```

```text
Coach

□ View
□ Create
□ Update
□ Delete
```

```text
Payment

□ View
□ Create
□ Update
□ Report
```

Pengelompokan ini mempermudah pengelolaan permission pada antarmuka.

---

## Development Rules

Seluruh module baru wajib mengikuti aturan berikut.

Gunakan:

- Spatie Role
- Spatie Permission
- RolePermissionSeeder
- AccountService
- Permission Middleware
- Blade Directive `@can`
- Gate::before() untuk Super Admin

Hindari:

- Membuat role secara manual di database.
- Membuat permission secara manual di database.
- Memberikan role langsung di module.
- Menampilkan menu tanpa pengecekan permission.
- Membuat route tanpa Permission Middleware.

---

## Summary

FAOSBall menggunakan Laravel Breeze untuk authentication dan Spatie Laravel Permission untuk authorization. Seluruh role dan permission dikelola secara terpusat, assignment role dilakukan melalui `AccountService`, route dilindungi menggunakan Permission Middleware, dan tampilan antarmuka mengikuti permission yang dimiliki user. Super Admin memperoleh akses penuh melalui `Gate::before()` tanpa memerlukan assignment seluruh permission secara manual.