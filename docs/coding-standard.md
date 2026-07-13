# Coding Standard

## Overview

Dokumen ini menjelaskan standar penulisan kode yang digunakan pada FAOSBall.

Seluruh module wajib mengikuti standar ini agar struktur kode tetap konsisten, mudah dibaca, dan mudah dipelihara.

---

## Table of Contents

- [Naming Convention](#naming-convention)
- [Class Naming](#class-naming)
- [Method Naming](#method-naming)
- [Variable Naming](#variable-naming)
- [Route Naming](#route-naming)
- [View Structure](#view-structure)
- [Service Structure](#service-structure)
- [Request Structure](#request-structure)
- [Controller Standard](#controller-standard)
- [Service Standard](#service-standard)
- [Model Standard](#model-standard)
- [Bahasa Pesan (User-Facing Messages)](#bahasa-pesan-user-facing-messages)
- [Summary](#summary)

---

## Naming Convention

FAOSBall menggunakan standar penamaan yang konsisten untuk seluruh project.

| Component | Convention |
|-----------|------------|
| Class | PascalCase |
| Method | camelCase |
| Variable | camelCase |
| Route | resource naming |
| View Folder | plural |
| Service | PascalCase |

---

## Class Naming

Gunakan **PascalCase** untuk seluruh class.

Contoh:

```php
PlayerService
AccountService
AcademyService
AcademyManagementService

StorePlayerRequest
UpdatePlayerRequest
```

Nama class harus menggambarkan tanggung jawabnya.

---

## Method Naming

Gunakan **camelCase**.

Contoh:

```php
create()

update()

delete()

uploadPhoto()

generatePlayerCode()
```

Nama method harus menggunakan kata kerja yang jelas.

---

## Variable Naming

Gunakan **camelCase**.

Contoh:

```php
$player

$academy

$user

$playerCode
```

Hindari singkatan yang tidak memiliki makna yang jelas.

---

## Route Naming

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

Gunakan bentuk jamak (plural) untuk nama resource.

---

## View Structure

Folder view menggunakan bentuk jamak.

Contoh:

```text
resources/views/
├── academies
├── players
├── roles
└── permissions
```

Kelompokkan view berdasarkan module.

---

## Service Structure

Seluruh business logic ditempatkan pada folder:

```text
app/Services
```

Contoh:

```text
app/Services
├── AcademyService.php
├── AcademyManagementService.php
├── AccountService.php
└── PlayerService.php
```

Satu Service hanya menangani satu domain.

---

## Request Structure

Form Request dipisahkan berdasarkan module.

Contoh:

```text
app/Http/Requests
├── Academy
│   └── AcademyFormRequest.php
│
└── Player
    ├── StorePlayerRequest.php
    ├── UpdatePlayerRequest.php
    └── StorePlayerAccountRequest.php
```

Jangan menempatkan seluruh Request dalam satu folder.

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
- Database Transaction
- Create Account
- Assign Role

---

## Service Standard

Service menjadi pusat business logic.

Service dapat menangani:

- Business Rule
- Database Transaction
- Upload File
- Delete File
- Generate Code
- Create Account
- Assign Role

Service harus dapat digunakan kembali oleh module lain apabila memiliki fungsi yang sama.

---

## Model Standard

Model hanya digunakan untuk:

- Fillable
- Relationship
- Scope
- Cast
- UUID Generation

Model tidak boleh berisi business logic.

---

## Bahasa Pesan (User-Facing Messages)

Seluruh pesan yang tampil ke user (validasi, error, status flash message) wajib Bahasa Indonesia.

FAOSBall **tidak** menggunakan sistem file terjemahan Laravel (folder `lang/`, helper `__()`/`trans()`). Pesan Indonesia ditulis langsung sebagai string literal di kode — di `messages()` milik Form Request, atau langsung di argumen `ValidationException::withMessages([...])`/`$request->validate([...], [...])` pada Controller.

Contoh (lihat `app/Http/Requests/Auth/LoginRequest.php`):

```php
public function messages(): array
{
    return [
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
    ];
}
```

```php
throw ValidationException::withMessages([
    'email' => 'Email atau password tidak valid.',
]);
```

**Perhatian khusus untuk controller bawaan Laravel Breeze** (`PasswordResetLinkController`, `NewPasswordController`, `ConfirmablePasswordController`, dsb): controller ini secara default memanggil `__($status)` atau `__('auth.password')`, yang tanpa folder `lang/` akan jatuh ke string default Laravel (Bahasa Inggris). Setiap kali membuat/menyentuh controller auth bawaan Breeze, ganti pemanggilan `__()`-nya dengan string Indonesia literal, mengikuti pola yang sama seperti `LoginRequest`. Jangan menambahkan folder `lang/` sebagai solusinya.

---

## Summary

FAOSBall menggunakan standar penulisan kode yang konsisten untuk seluruh module. Penamaan class, method, variable, route, dan struktur folder mengikuti konvensi yang sama sehingga kode lebih mudah dipahami, dipelihara, dan dikembangkan.