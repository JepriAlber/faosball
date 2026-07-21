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
- [Support Structure](#support-structure)
- [Request Structure](#request-structure)
- [Controller Standard](#controller-standard)
- [Service Standard](#service-standard)
- [Model Standard](#model-standard)
- [Bahasa Pesan (User-Facing Messages) & Multi-Language](#bahasa-pesan-user-facing-messages--multi-language)
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

## Support Structure

Class murni yang **tidak** bergantung Laravel (tidak ada Model/Eloquent/Facade/DB, tidak butuh boot framework untuk di-test) ditempatkan di:

```text
app/Support
```

Contoh:

```text
app/Support
└── ColorRamp.php
```

Bedanya dengan `app/Services`: Service selalu mengandung *business logic* domain FAOSBall (butuh Model/DB/Auth, hanya masuk akal dites lewat `RefreshDatabase`/HTTP). Class di `app/Support` adalah fungsi/algoritma generik (mis. transformasi warna, format angka) yang tidak tahu apa-apa soal Academy/Player/dst — bisa di-unit-test dengan `PHPUnit\Framework\TestCase` biasa (`tests/Unit`), tanpa `RefreshDatabase`.

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

## Bahasa Pesan (User-Facing Messages) & Multi-Language

FAOSBall mendukung 2 bahasa: **Bahasa Indonesia (default)** dan **English**. Mekanismenya:

1. **String Indonesia yang sudah ada di kode TETAP ditulis apa adanya** -- cuma dibungkus `__(...)`. Ini translasi berbasis string/JSON (`lang/en.json`), BUKAN key-based (`__('messages.key')`). Tidak ada `lang/id.json` -- kalau locale aktif `id` dan tidak ada entry, `__()` mengembalikan string aslinya apa adanya.

    ```php
    'name.required' => __('Nama academy wajib diisi.'),
    ```

    ```blade
    <h3 class="card-title">{{ __('Academy List') }}</h3>
    ```

2. **Setiap string baru yang dibungkus `__()` WAJIB ditambahkan entry-nya ke `lang/en.json`** di PR yang sama -- termasuk string yang kebetulan sama di kedua bahasa (supaya jelas sudah direview, bukan kelewat).

3. **Preferensi bahasa** disimpan di `users.locale` (user login) atau `session('locale')` (guest), diresolusi oleh `App\Http\Middleware\SetLocale` di setiap request. Jangan taruh logic ini di Controller/View manapun.

4. **Controller bawaan Laravel Breeze** (`PasswordResetLinkController`, `NewPasswordController`, dst) memakai mekanisme **berbeda** -- key-based bawaan Laravel (`lang/{locale}/auth.php`, `lang/{locale}/passwords.php`), BUKAN JSON. Sudah tersedia untuk `id` dan `en` (lihat `issue7.md` Tahap 10) -- tidak perlu override manual lagi. **Catatan**: controller Breeze di codebase ini sebagian masih memakai string Indonesia literal (workaround dari standar lama, sebelum folder `lang/` ada) -- ini masih valid untuk locale `id` dan boleh dibiarkan; migrasi ke `__('auth.failed')` dkk untuk flow tersebut adalah pekerjaan terpisah, bukan otomatis ikut terbenahi.

5. **`lang/{locale}/validation.php` tetap ada** (`lang/en/validation.php` bawaan Laravel + `lang/id/validation.php` terjemahannya) -- **bukan** dipakai oleh Form Request FAOSBall sendiri (yang selalu eksplisit lewat `messages()`, lihat *Model Standard*/*Request Structure* di atas), tapi sebagai fallback untuk `$request->validate([...])` bawaan controller Breeze (`ProfileController`, `RegisteredUserController`, dst) yang tidak selalu diberi custom message. **Jangan dihapus** -- pernah dicoba dihapus bersamaan dengan mengubah `APP_LOCALE`/`APP_FALLBACK_LOCALE` ke `id`, akibatnya validasi generik Breeze yang tidak di-custom message justru menampilkan key mentah (`validation.unique`) alih-alih teks yang bisa dibaca, karena tidak ada file `validation.php` yang bisa di-resolve untuk locale `id` maupun fallback-nya.

Module baru **wajib** ikut pola ini sejak awal dibuat -- jangan menulis string Indonesia hardcode tanpa `__()` lagi.

---

## Summary

FAOSBall menggunakan standar penulisan kode yang konsisten untuk seluruh module. Penamaan class, method, variable, route, dan struktur folder mengikuti konvensi yang sama sehingga kode lebih mudah dipahami, dipelihara, dan dikembangkan.