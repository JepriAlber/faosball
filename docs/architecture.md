# Software Architecture

## Overview

Dokumen ini menjelaskan arsitektur dasar yang digunakan pada FAOSBall.

Seluruh module yang dikembangkan wajib mengikuti arsitektur ini agar kode tetap konsisten, mudah dipelihara, dan mudah dikembangkan.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Architecture Principles](#architecture-principles)
- [Service Layer](#service-layer)
- [Thin Controller](#thin-controller)
- [Service Responsibility](#service-responsibility)
- [Dependency Injection](#dependency-injection)
- [UUID Standard](#uuid-standard)
- [Database Transaction](#database-transaction)
- [File Storage](#file-storage)
- [Model Responsibility](#model-responsibility)
- [Project Structure](#project-structure)
- [Development Principles](#development-principles)
- [Summary](#summary)

---

## Architecture Overview

FAOSBall menerapkan **Service Layer Architecture** untuk memisahkan business logic dari Controller dan Model.

Setiap request diproses dengan alur berikut.

```text
Request
    │
    ▼
Controller
    │
    ▼
Service
    │
    ▼
Model
    │
    ▼
Database
```

Business logic selalu ditempatkan pada Service.

---

## Architecture Principles

Seluruh module wajib mengikuti prinsip berikut.

- Service Layer Pattern
- Thin Controller
- Single Responsibility Principle (SRP)
- Reusable Service
- Dependency Injection

Prinsip tersebut menjadi standar pengembangan pada seluruh module FAOSBall.

---

## Service Layer

Business logic tidak ditempatkan pada Controller maupun Model.

Controller bertanggung jawab untuk:

- Menerima request.
- Memanggil Service.
- Mengembalikan response.

Contoh alur:

```text
PlayerController
        │
        ▼
PlayerService
        │
        ▼
Player
```

Seluruh proses bisnis seperti upload file, transaksi database, pembuatan akun, dan validasi business rule dilakukan pada Service.

---

## Thin Controller

Controller dibuat sesingkat mungkin.

Contoh implementasi:

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
```

Method yang mengubah data (`store`, `update`, `destroy`) wajib membungkus pemanggilan Service dengan `try/catch`, menampilkan flash message `success` saat berhasil, dan melempar exception ke helper `handleException()` (tersedia di base `Controller`) saat gagal. Lihat `docs/development-guide.md` bagian Controller untuk detail parameter `handleException()`.

Controller tidak menangani:

- Business Logic
- Upload File
- Delete File
- Generate Code
- Database Transaction
- Create Account
- Assign Role

Seluruh proses tersebut dipindahkan ke Service.

---

## Service Responsibility

Setiap Service memiliki satu tanggung jawab utama.

| Service | Responsibility |
|----------|----------------|
| AcademyService | Academy Context |
| AcademyManagementService | Academy Management |
| PlayerService | Player Management |
| AccountService | User Account Management |

Setiap Service hanya menangani domain yang menjadi tanggung jawabnya.

---

## Reusable Service

Logic yang digunakan oleh beberapa module ditempatkan pada Service yang dapat digunakan kembali.

Contoh:

```text
Player
      │
      ▼
AccountService

Coach
      │
      ▼
AccountService

Parent
      │
      ▼
AccountService

Academy (Owner)
      │
      ▼
AccountService
```

Seluruh proses pembuatan akun menggunakan `AccountService` agar implementasi tetap konsisten dan tidak terjadi duplikasi kode.

**Khusus Academy Owner** (`issue13.md`): pembuatan akunnya **selalu diikuti** pembuatan 1 baris `Staff` lewat `StaffService::createForOwner()`, dipanggil tepat setelah `AccountService::create()` — baik dari toggle "Buat Akun Owner" di `AcademyManagementService::create()` maupun dari halaman standalone `AcademyAccountController::store()`. Ini karena Owner secara bisnis memang bagian dari staff academy-nya sendiri, bukan entitas terpisah. Employment Type & Staff Position kontrak pertamanya di-default otomatis ke `"Permanent"`/`"Academy Director"` (template yang sudah dibuat tiap Academy baru) — form Owner tidak pernah menanyakan dropdown itu. Kalau butuh membaca kode Owner ini di halaman Staff biasa (index/show/edit) tapi bingung kenapa dia tidak pernah dibuat lewat form Staff, inilah sebabnya.

---

## Dependency Injection

Seluruh Service dipanggil menggunakan Constructor Injection.

Contoh:

```php
protected PlayerService $playerService;

public function __construct(PlayerService $playerService)
{
    $this->playerService = $playerService;
}
```

Hindari membuat instance Service secara langsung.

```php
new PlayerService();
```

Laravel akan mengelola proses dependency injection melalui Service Container.

---

## UUID Standard

Seluruh tabel utama menggunakan UUID sebagai Primary Key.

| Table | Primary Key |
|---------|-------------|
| academies | id_academy |
| users | id_user |
| players | id_player |

UUID dibuat secara otomatis ketika proses `creating`.

---

## Database Transaction

Proses yang melibatkan lebih dari satu operasi database wajib menggunakan transaction.

Contoh:

```text
Create Player
      │
      ▼
Upload Photo
      │
      ▼
Create User
      │
      ▼
Assign Role
      │
      ▼
Update Player
```

Implementasi:

```php
DB::transaction(function () {

    // Process

});
```

Jika salah satu proses gagal, seluruh perubahan akan dibatalkan sehingga tidak ada data yang tersimpan dalam kondisi tidak lengkap.

---

## File Storage

Seluruh upload file menggunakan Laravel Storage.

Contoh:

```php
$file->storeAs(
    'players/photo',
    $filename,
    'public'
);
```

Penghapusan file dilakukan menggunakan Storage.

```php
if (Storage::disk('public')->exists($path)) {
    Storage::disk('public')->delete($path);
}
```

Format nama file:

```text
CODE-UUID.extension
```

Contoh:

```text
PLAYER001-550e8400-e29b-41d4-a716-446655440000.jpg
```

### File Storage Privat (Document)

Untuk file yang TIDAK boleh diakses publik (dokumen pribadi: ijazah, akte, KTP, dst -- beda dari foto profil/logo yang memang publik), pakai `App\Models\Document` (polymorphic) + `App\Services\DocumentService::upload()`/`delete()`, disk `local` (root `storage/app/private`, TIDAK di-symlink), bukan disk `public`. Akses file WAJIB lewat route `documents.show` yang mengecek `App\Policies\DocumentPolicy` dulu (`Storage::disk('local')->response(...)`), tidak pernah lewat URL statis. Lihat `issue15.md` untuk detail lengkap & `<x-document-manager>` sebagai UI reusable-nya.

---

## Model Responsibility

Model hanya digunakan untuk:

- Relationship
- Scope
- Cast
- Fillable
- UUID Generation

Business logic tidak ditempatkan pada Model.

Seluruh proses berikut berada pada Service:

- Upload File
- Delete File
- Generate Code
- Create Account
- Assign Role
- Database Transaction
- Business Rule Validation

Karena query (termasuk eager loading, pagination) dibentuk di Service, standar performa query (N+1, `with()`/`withCount()`, index database) wajib diikuti di layer ini — lihat `docs/query-performance.md`.

---

## Project Structure

Struktur folder utama FAOSBall.

```text
app
├── Http
│   ├── Controllers
│   └── Requests
├── Models
├── Providers
├── Scopes
├── Services
├── Traits
└── View
```

---

## Development Principles

Seluruh module wajib mengikuti aturan berikut.

1. Controller harus tetap tipis.
2. Business logic berada pada Service.
3. Validasi menggunakan Form Request.
4. Model hanya menangani data dan relasi.
5. Gunakan `DB::transaction()` untuk proses yang melibatkan lebih dari satu operasi database.
6. Upload file menggunakan Laravel Storage.
7. UUID digunakan sebagai Primary Key.
8. Gunakan penamaan class, method, route, dan folder secara konsisten.
9. Hindari duplikasi kode (DRY).
10. Seluruh module mengikuti pola arsitektur yang sama.

---

## Summary

FAOSBall menggunakan Service Layer sebagai pusat business logic. Controller hanya menangani request dan response, Model hanya merepresentasikan data, sedangkan seluruh proses bisnis ditempatkan pada Service. Dengan pola ini, setiap module memiliki struktur yang konsisten, mudah dipelihara, dan mudah dikembangkan.