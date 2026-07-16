# Module Standard

## Overview

Dokumen ini menjelaskan standar yang wajib diikuti saat mengembangkan module baru pada FAOSBall.

Seluruh module harus memiliki struktur, alur, dan implementasi yang konsisten agar sistem tetap mudah dipelihara dan dikembangkan.

---

## Table of Contents

- [Module Structure](#module-structure)
- [Database Standard](#database-standard)
- [Model Standard](#model-standard)
- [Request Standard](#request-standard)
- [Service Standard](#service-standard)
- [Controller Standard](#controller-standard)
- [View Standard](#view-standard)
- [Route Standard](#route-standard)
- [Authorization Standard](#authorization-standard)
- [Multi-Tenant Standard](#multi-tenant-standard)
- [Completion Checklist](#completion-checklist)
- [Summary](#summary)

---

## Module Structure

Setiap module mengikuti struktur berikut.

```text
Module
│
├── Migration
├── Model
├── Form Request
├── Service
├── Controller
├── Views
├── Route
└── Permission
```

Struktur ini berlaku untuk seluruh module pada FAOSBall.

---

## Database Standard

Migration wajib mengikuti standar berikut.

- Menggunakan UUID sebagai Primary Key.
- Menggunakan nama Primary Key yang sesuai dengan module.
- Menambahkan `id_academy` pada seluruh tabel tenant.
- Menggunakan foreign key sesuai kebutuhan.

Contoh:

```text
players

id_player
id_academy
id_user
...
```

---

## Model Standard

Model hanya bertanggung jawab untuk:

- Fillable
- Relationship
- Scope
- Cast
- UUID Generation

Model tenant wajib menggunakan:

```php
use BelongsToAcademy;
```

Business logic tidak ditempatkan pada Model.

---

## Request Standard

Seluruh validasi menggunakan Form Request.

Lokasi:

```text
app/Http/Requests
```

Pisahkan berdasarkan module.

Contoh:

```text
Player
├── StorePlayerRequest
├── UpdatePlayerRequest
└── StorePlayerAccountRequest
```

---

## Service Standard

Business logic ditempatkan pada Service.

Service bertanggung jawab untuk:

- Business Rule
- Database Transaction
- Upload File
- Delete File
- Generate Code
- Create Account
- Assign Role

Gunakan Service yang sudah tersedia apabila memiliki fungsi yang sama.

Contoh:

- `AcademyService`
- `AcademyManagementService`
- `PlayerService`
- `AccountService`

---

## Controller Standard

Controller hanya bertanggung jawab untuk:

- Menerima request.
- Memanggil Service.
- Mengembalikan response.

Contoh:

```php
protected PlayerService $playerService;

public function __construct(PlayerService $playerService)
{
    $this->playerService = $playerService;
}
```

Controller tidak menangani:

- Business Logic
- Database Transaction
- Upload File
- Delete File
- Assign Role

---

## View Standard

View ditempatkan berdasarkan nama module.

Contoh:

```text
resources/views
├── academies
├── players
├── roles
└── permissions
```

Menu dan aksi pada halaman menggunakan permission.

Contoh:

```blade
@can('player.create')

@endcan
```

Untuk konvensi penulisan CSS/Tailwind di view (kapan bikin `@utility` baru, struktur file `resources/css/theme`), lihat `docs/frontend-standard.md`.

Halaman index/list module yang menampilkan data lewat `table-wrapper` + `table` wajib didampingi Card List responsif (`table-card-list`) supaya kolom tabel tidak kepotong di tablet/smartphone — lihat `docs/frontend-standard.md#tabel-responsif-table-desktop--card-list-mobiletablet`.

---

## Route Standard

Gunakan Resource Route Naming.

Contoh:

```text
players.index
players.create
players.store
players.show
players.edit
players.update
players.destroy
```

Seluruh route wajib menggunakan Permission Middleware.

Contoh:

```php
Route::middleware(
    'permission:player.view'
);
```

---

## Authorization Standard

Setiap module wajib memiliki permission.

Minimal:

```text
module.view
module.create
module.update
module.delete
```

Permission dibuat melalui:

- `RolePermissionSeeder`
- Module Permission (pengembangan selanjutnya)

Role diberikan melalui `AccountService`.

Setiap permission wajib benar-benar ditegakkan di kode (route `middlewareFor()`, `@can()` di Blade, `$this->authorize()` kalau ada isolasi per-baris seperti `RolePolicy`) — bukan cuma didaftarkan di seeder. Setelah module selesai, tambahkan entry-nya di `docs/permission-reference.md` supaya jelas permission apa mengunci fitur apa.

---

## Multi-Tenant Standard

Seluruh module tenant wajib:

- Memiliki field `id_academy`.
- Menggunakan `BelongsToAcademy`.
- Menggunakan `AcademyScope`.
- Menggunakan `AcademyService`.

Hindari:

```php
Auth::user()->id_academy
```

Gunakan:

```php
$this->academyService->currentId();
```

atau biarkan `BelongsToAcademy` mengisi `id_academy` secara otomatis.

---

## Completion Checklist

Sebelum module dinyatakan selesai, pastikan seluruh poin berikut telah dipenuhi.

### Database

- [ ] UUID digunakan sebagai Primary Key.
- [ ] `id_academy` ditambahkan pada tabel tenant.
- [ ] Relasi database telah dibuat.
- [ ] Index ditambahkan pada `id_academy` dan foreign key lain yang sering dipakai filter/join (lihat `docs/query-performance.md`).

### Model

- [ ] Fillable ditentukan.
- [ ] Relationship dibuat.
- [ ] UUID Generation tersedia.
- [ ] Menggunakan `BelongsToAcademy` (jika module tenant).

### Request

- [ ] Menggunakan Form Request.
- [ ] Validasi tidak dilakukan di Controller.

### Service

- [ ] Business logic berada pada Service.
- [ ] Menggunakan `DB::transaction()` jika diperlukan.
- [ ] Menggunakan `AccountService` jika membuat akun.
- [ ] Query list memakai `paginate()`, relasi yang dipakai di view di-eager-load (`with()`/`withCount()`) — lihat `docs/query-performance.md`.

### Controller

- [ ] Menggunakan Constructor Injection.
- [ ] Tidak terdapat business logic.

### View

- [ ] Menggunakan folder sesuai nama module.
- [ ] Menggunakan Blade Permission Directive.
- [ ] Halaman index/list punya Card List responsif (`table-card-list`) selain tabel, jika datanya ditampilkan dalam tabel.

### Route

- [ ] Menggunakan Resource Route Naming.
- [ ] Menggunakan Permission Middleware.

### Authorization

- [ ] Permission mengikuti format `module.action`.
- [ ] Role tidak dibuat secara manual.
- [ ] Permission tidak dibuat secara manual.
- [ ] Route pakai `middlewareFor('permission:module.action')` dan tombol/menu di Blade dibungkus `@can()` — bukan cuma terdaftar di seeder.
- [ ] Entry module ditambahkan ke `docs/permission-reference.md`.

### Multi-Tenant

- [ ] Menggunakan `AcademyService`.
- [ ] Menggunakan `AcademyScope`.
- [ ] Tidak menggunakan `Auth::user()->id_academy` secara langsung.

---

## Summary

Seluruh module pada FAOSBall wajib mengikuti standar yang sama, mulai dari struktur folder, penulisan kode, arsitektur Service Layer, implementasi Multi-Tenant, hingga Authorization. Dengan mengikuti standar ini, setiap module akan memiliki implementasi yang konsisten, mudah dipelihara, dan mudah dikembangkan seiring pertumbuhan sistem.