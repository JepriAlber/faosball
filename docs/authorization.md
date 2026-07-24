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
- [Role Academy Based](#role-academy-based)
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

Hanya **satu** role yang bersifat system (`id_academy = NULL`):

- Super Admin

Seluruh role lain (Owner, Coach, Staff, Finance, Player, Parent, dst) adalah **role academy** — masing-masing academy punya baris `roles` sendiri, bukan dibagi bersama. Lihat [Role Academy Based](#role-academy-based).

Role Super Admin dibuat melalui `RolePermissionSeeder`. Role academy dibuat otomatis oleh `RoleService::createDefaultRoles()` setiap kali academy baru dibuat.

---

## Role Academy Based

Sejak refactor Role & Permission menjadi Academy Based, **Permission tetap global** (fitur sistem, satu baris dipakai bersama seluruh tenant), tapi **Role menjadi milik masing-masing academy**.

### Aturan `id_academy` pada Role

| Nilai `id_academy` | Arti | Siapa yang mengelola |
|---------------------|------|----------------------|
| `NULL` | Role System (cuma "Super Admin") | Hanya Super Admin |
| UUID academy | Role milik academy tersebut | Owner academy itu + Super Admin |

Dua academy **boleh** punya role dengan nama sama (mis. sama-sama punya `Owner`), karena constraint unique-nya adalah `(id_academy, name, guard_name)`, bukan `(name, guard_name)` saja.

### Kenapa dua role bernama sama bisa punya permission berbeda

Spatie mencocokkan permission lewat **primary key baris role**, bukan lewat nama. Owner Academy A dan Owner Academy B adalah dua baris berbeda di tabel `roles` (id berbeda), masing-masing dengan kombinasi permission sendiri di `role_has_permissions`. Karena itu permission `staff.view` bisa ada di baris Owner Academy B tapi tidak di baris Owner Academy A, walau nama role-nya sama persis.

### Role Template

Role default academy baru berasal dari `config('faos.role_templates')`, bukan record database:

```text
Owner, Coach, Staff, Finance, Player, Parent
```

Tiap key adalah nama role, isinya daftar nama permission yang otomatis di-`syncPermissions()` saat academy dibuat (`RoleService::createDefaultRoles()`, dipanggil dari `AcademyManagementService::create()`). Permission di template **wajib** sudah terdaftar di `RolePermissionSeeder` — kalau salah ketik nama permission di config, permission itu hilang diam-diam dari role (`Permission::whereIn()` melewati nama yang tidak ditemukan tanpa error).

> `Player` dan `Parent` wajib selalu ada di template — `PlayerService` dan `PlayerAccountController` memanggil `AccountService::create([...], 'Player')`, yang gagal kalau academy tidak punya role bernama `Player`.

### Isolasi akses: Scope Lokal + Policy, bukan Global Scope

`App\Models\Role` **tidak** memakai `BelongsToAcademy`/`AcademyScope` seperti model tenant lain — global scope pada Role akan meracuni cache permission Spatie yang dipakai bersama seluruh tenant. Detail lengkap alasannya ada di `docs/multi-tenancy.md#role-tenant-tanpa-belongstoacademy`.

Sebagai gantinya, isolasi akses Role memakai dua lapis:

- **`Role::forCurrentAcademy()`** — local scope, dipanggil eksplisit oleh `RoleService::paginate()`. Membatasi daftar role yang tampil di halaman index sesuai academy user (Super Admin melihat semua).
- **`App\Policies\RolePolicy`** — mencegah akses langsung lewat URL (mis. `/roles/{id}` milik academy lain), karena Permission Middleware (`role.view`, dst) cuma memeriksa hak akses fitur, bukan kepemilikan baris data. `RoleController` memanggil `$this->authorize()` di `show`, `edit`, `update`, `destroy`.

### Assign role antar academy

`AccountService::assignRole()` menolak assignment kalau role yang diberikan bukan milik academy user (`Role tidak berasal dari academy yang sama dengan user.`). Saat resolve role dari nama (string), pencarian selalu disertai `id_academy` user — **jangan** pakai `Role::findByName()` langsung, karena method itu mengambil baris pertama yang cocok tanpa peduli academy, berpotensi memberi permission milik academy lain.

---

## Role Responsibility

> Section ini menjelaskan **desain** role template (`config('faos.role_templates')`) — permission untuk Training/Attendance/Evaluation/Payment/Report sudah di-seed sejak awal sebagai persiapan, tapi module-nya **belum dibangun** (belum ada Controller/View, lihat Roadmap di `README.md`). Untuk peta permission yang **benar-benar** ditegakkan di kode saat ini (route middleware/`@can()`), rujuk `docs/permission-reference.md` — dokumen itu yang selalu jadi sumber kebenaran terkini, section ini cuma gambaran arah desain per role.

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

### Owner

Mengelola seluruh operasional academy sendiri, termasuk Role Management academy-nya (`role.*`).

Tidak dapat mengakses academy lain.

---

### Coach

Dapat mengelola:

- Training
- Attendance
- Evaluation

---

### Staff

Mengelola aktivitas harian academy: data player, jadwal training, dan attendance.

---

### Finance

Mengelola pembayaran dan laporan keuangan academy.

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

- Permission (global)
- Role Super Admin (`id_academy = null`)
- Academy Default (FAOS Academy)
- Role academy default untuk Academy Default, lewat `RoleService::createDefaultRoles()` (Owner, Coach, Staff, Finance, Player, Parent)
- User Super Admin FAOSBall
- User Owner FAOS Academy (role `Owner`)
- User Admin FAOS Academy (role `Staff`)

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

Saat role diberikan lewat nama (string), `AccountService` otomatis resolve ke baris `Role` milik academy user yang bersangkutan (lihat [Role Academy Based](#role-academy-based)) — bukan baris pertama yang cocok namanya.

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

Menu group "Administrasi" di sidebar **bukan** Super-Admin-only secara keseluruhan — pengecualiannya adalah **Roles**, yang juga bisa diakses Owner (permission `role.*` ada di `role_templates` Owner, dibatasi ke academy-nya sendiri lewat `Role::scopeForCurrentAcademy()`). **Academy Management**, **Permissions**, dan **Master** (mis. Player Position) tetap murni Super-Admin-only.

```text
Administrasi
│
├── Academy Management   -- Super Admin only
├── Roles                -- Super Admin ATAU user dengan permission role.view (termasuk Owner)
├── Permissions          -- Super Admin only
└── Master               -- Super Admin only (mis. Player Position)
```

Heading group "Administrasi" sendiri digerbang `isSuperAdmin() || can('role.view')` (lihat `resources/views/partials/sidebar.blade.php`), supaya Owner tetap melihat groupnya demi mengakses Roles, tapi item Academy Management/Permissions/Master di dalamnya tetap dibungkus `@can()`/pengecekan `isSuperAdmin()` masing-masing sehingga tidak ikut kebuka untuk Owner. Dropdown "Master" sendiri digerbang di level dropdown (bukan cuma item di dalamnya), supaya Owner yang lolos gate "Administrasi" lewat `role.view` tidak melihat dropdown yang bisa diklik tapi kosong isinya.

---

## Future Modules

Role Management sudah berjalan, menggunakan `App\Models\Role` (Spatie Role + `id_academy`, lihat [Role Academy Based](#role-academy-based)) — bukan model bawaan Spatie langsung.

Fitur yang tersedia:

- List Role (terisolasi per academy, Super Admin melihat semua)
- Create Role (Super Admin bisa memilih academy pemilik, user academy otomatis dari academy sendiri)
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

- `App\Models\Role` (Spatie Role dengan `id_academy`, jangan import `Spatie\Permission\Models\Role` langsung di kode aplikasi)
- Spatie Permission (tetap global, tidak berubah)
- `config('faos.role_templates')` untuk role default academy baru
- `RolePolicy` untuk proteksi akses per-baris (bukan cuma Permission Middleware)
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

FAOSBall menggunakan Laravel Breeze untuk authentication dan Spatie Laravel Permission untuk authorization. Permission tetap global, sedangkan Role bersifat academy based — setiap academy punya baris role sendiri (lihat [Role Academy Based](#role-academy-based)), diisolasi lewat local scope `Role::forCurrentAcademy()` dan `RolePolicy`, bukan global scope. Assignment role dilakukan melalui `AccountService` yang selalu resolve ke role milik academy user, route dilindungi Permission Middleware, dan tampilan antarmuka mengikuti permission yang dimiliki user. Super Admin memperoleh akses penuh melalui `Gate::before()` tanpa memerlukan assignment seluruh permission secara manual.