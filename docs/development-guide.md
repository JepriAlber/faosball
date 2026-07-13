# Development Guide

## Overview

Dokumen ini menjelaskan standar pengembangan module pada FAOSBall.

Seluruh module baru wajib mengikuti alur pengembangan yang sama agar struktur project tetap konsisten dan mudah dipelihara.

---

## Table of Contents

- [Development Flow](#development-flow)
- [Step 1 - Database](#step-1---database)
- [Step 2 - Model](#step-2---model)
- [Step 3 - Form Request](#step-3---form-request)
- [Step 4 - Service](#step-4---service)
- [Step 5 - Controller](#step-5---controller)
- [Step 6 - View](#step-6---view)
- [Step 7 - Route](#step-7---route)
- [Step 8 - Permission](#step-8---permission)
- [Step 9 - Menu](#step-9---menu)
- [Development Checklist](#development-checklist)
- [Summary](#summary)

---

## Development Flow

Seluruh module dikembangkan menggunakan alur berikut.

```text
Migration
    │
    ▼
Model
    │
    ▼
Form Request
    │
    ▼
Service
    │
    ▼
Controller
    │
    ▼
Route
    │
    ▼
View
    │
    ▼
Permission
    │
    ▼
Menu
```

Seluruh module wajib mengikuti urutan tersebut.

---

## Step 1 - Database

Buat migration sesuai kebutuhan module.

Ketentuan:

- Gunakan UUID sebagai Primary Key.
- Tambahkan `id_academy` pada seluruh tabel tenant.
- Gunakan foreign key sesuai relasi.
- Gunakan Laravel Migration.

Contoh:

```text
players

id_player
id_academy
id_user
...
```

---

## Step 2 - Model

Buat Model pada folder:

```text
app/Models
```

Model hanya digunakan untuk:

- Fillable
- Relationship
- Scope
- Cast
- UUID Generation

Business logic tidak ditempatkan pada Model.

Untuk model tenant, gunakan:

```php
use BelongsToAcademy;
```

---

## Step 3 - Form Request

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

Controller tidak melakukan validasi secara langsung.

---

## Step 4 - Service

Seluruh business logic ditempatkan pada Service.

Lokasi:

```text
app/Services
```

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

```text
Player
        │
        ▼
AccountService
```

---

## Step 5 - Controller

Controller hanya menerima request dan memanggil Service.

Seluruh method yang mengubah data (`store`, `update`, `destroy`) wajib membungkus pemanggilan Service dengan `try/catch`. Proses yang berhasil menampilkan flash message `success`. Exception dari Service ditangani lewat helper `handleException()` yang tersedia di base `Controller` (`app/Http/Controllers/Controller.php`), yang bertugas mencatat exception ke log sekaligus menampilkan flash message `error`.

Contoh:

```php
protected PlayerService $playerService;

public function __construct(PlayerService $playerService)
{
    $this->playerService = $playerService;
}

public function store(StorePlayerRequest $request)
{
    try {

        $this->playerService->create(
            $request->validated()
        );

        return redirect()
            ->route('players.index')
            ->with('success', 'Player berhasil dibuat.');

    } catch (\Exception $e) {

        return $this->handleException($e, 'Gagal membuat player');

    }
}

public function destroy(Player $player)
{
    try {

        $this->playerService->delete($player);

        return redirect()
            ->route('players.index')
            ->with('success', 'Player berhasil dihapus.');

    } catch (\Exception $e) {

        return $this->handleException($e, 'Gagal menghapus player', 'players.index');

    }
}
```

### `handleException()`

```php
protected function handleException(
    \Exception $e,
    string $message,
    ?string $route = null,
    array $routeParameters = []
): RedirectResponse
```

| Parameter | Kegunaan |
|-----------|----------|
| `$e` | Exception yang ditangkap dari Service. |
| `$message` | Pesan konteks yang ditampilkan ke user, digabung dengan `$e->getMessage()`. |
| `$route` | Nama route tujuan redirect. Kosongkan (`null`) untuk `store`/`update` agar kembali ke halaman sebelumnya dengan `back()->withInput()`. Isi untuk `destroy` atau aksi lain yang tidak punya form untuk dipertahankan. |
| `$routeParameters` | Parameter route, mis. `[$player]` untuk route yang butuh model binding. |

`handleException()` selalu mencatat exception ke log (`Log::error()`) sebelum menampilkan flash message, sehingga error tetap terlacak di `storage/logs/laravel.log` walau exception ditangkap secara lokal di Controller. Jangan menulis `try/catch` yang menangani exception secara manual di luar helper ini.

> **Debugging**: User hanya melihat pesan singkat ("Gagal membuat player: ...") di flash message, bukan detail teknis (stack trace, query SQL, dll). Setiap kegagalan yang melewati `handleException()` selalu tercatat lengkap di `storage/logs/laravel.log`. Kalau ada laporan "gagal simpan/gagal hapus" dari user, cek log ini dulu sebelum menelusuri kode.

Controller tidak menangani:

- Business Logic
- Upload File
- Transaction
- Assign Role

---

## Step 6 - View

Seluruh View ditempatkan pada:

```text
resources/views
```

Gunakan folder berdasarkan nama module.

Contoh:

```text
resources/views
├── academies
├── players
├── roles
└── permissions
```

Tampilkan menu atau aksi menggunakan permission.

Contoh:

```blade
@can('player.create')

@endcan
```

### Tampilan Mobile & Tablet

Sebagian besar pengguna FAOSBall adalah orang lapangan (Coach, Academy Admin) yang lebih sering mengakses sistem lewat HP/tablet, bukan laptop. Karena itu, setiap View wajib nyaman dipakai di layar kecil, bukan cuma di desktop.

> Untuk konvensi penulisan class CSS/Tailwind itu sendiri (kapan bikin `@utility` baru, jebakan varian breakpoint vs toggle dinamis), lihat `docs/frontend-standard.md`.

Ketentuan:

- Uji tampilan minimal di 3 breakpoint: mobile (`< 640px`), tablet (`md`), dan desktop (`lg` ke atas). Jangan hanya mengecek di layar desktop lalu asumsikan otomatis rapi di HP.
- Untuk konten yang dikelompokkan dalam tab (mis. detail Player), gunakan class `tabs`/`tab`/`tab-active`/`tab-panel` yang sudah tersedia di `resources/css/theme/components.css` — class ini sudah scrollable secara horizontal (`overflow-x-auto`) sehingga tab tetap bisa diakses di layar sempit tanpa terpotong.
- Tombol aksi utama (Simpan, Edit, Hapus) harus tetap mudah dijangkau ibu jari di layar HP — hindari tombol kecil atau menumpuk terlalu rapat.
- Tabel dengan banyak kolom wajib dibungkus `table-wrapper` (scroll horizontal), jangan biarkan tabel memaksa halaman melebar (overflow ke luar layar).
- Gunakan grid responsif Tailwind (`grid-cols-1 md:grid-cols-2 lg:grid-cols-3`, dst) agar layout otomatis menumpuk jadi satu kolom di layar kecil.

---

## Step 7 - Route

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

## Step 8 - Permission

Buat permission menggunakan format:

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

Role dan Permission dibuat melalui:

```text
RolePermissionSeeder
```

Role diberikan menggunakan:

```php
$user->assignRole($role);
```

melalui `AccountService`.

---

## Step 9 - Menu

Seluruh menu mengikuti permission user.

Contoh:

```blade
@can('player.view')

<li>Players</li>

@endcan
```

Jangan menyembunyikan menu hanya menggunakan CSS.

---

## Development Checklist

Sebelum module dinyatakan selesai, pastikan seluruh poin berikut telah dipenuhi.

### Database

- UUID digunakan sebagai Primary Key.
- `id_academy` ditambahkan pada tabel tenant.

### Model

- Relationship dibuat.
- Fillable ditentukan.
- UUID Generation tersedia.
- Menggunakan `BelongsToAcademy` jika merupakan model tenant.

### Validation

- Menggunakan Form Request.

### Service

- Business logic berada pada Service.
- Menggunakan Transaction apabila diperlukan.
- Menggunakan `AccountService` jika membuat akun.

### Controller

- Controller tetap tipis.
- Tidak terdapat business logic.

### Route

- Menggunakan Resource Naming.
- Menggunakan Permission Middleware.

### View

- Menggunakan folder module.
- Menggunakan Blade Permission Directive.
- Tampilan sudah dicek di breakpoint mobile dan tablet, bukan cuma desktop.
- Tab (jika ada) tetap dapat diakses/di-scroll dengan nyaman di layar sempit.
- Tabel lebar dibungkus `table-wrapper` agar tidak overflow di HP.

### Multi-Tenant

- Menggunakan `AcademyService`.
- Menggunakan `AcademyScope`.
- Tidak menggunakan `Auth::user()->id_academy` secara langsung.

### Authorization

- Permission mengikuti format `module.action`.
- Menu mengikuti permission.
- Route dilindungi middleware permission.

---

## Summary

Seluruh module pada FAOSBall dikembangkan menggunakan alur yang sama, mulai dari Migration hingga Menu. Dengan mengikuti panduan ini, setiap module akan memiliki struktur yang konsisten, mendukung arsitektur multi-tenant, serta mengikuti standar authentication dan authorization yang telah ditetapkan.