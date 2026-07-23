# ⚽ FAOSBall

> **FAOSBall (Football Academy Operating System)** adalah sistem ERP (Enterprise Resource Planning) yang dirancang untuk membantu pengelolaan operasional akademi sepak bola. FAOSBall dibangun menggunakan **Laravel 13** dengan arsitektur **Single Database Multi-Tenant**, sehingga satu aplikasi dapat digunakan oleh banyak academy tanpa mencampurkan data antar academy.

---

## Features

FAOSBall menyediakan berbagai modul untuk mendukung operasional akademi sepak bola, antara lain:

- Academy Management
- Player Management
- Coach Management
- Parent Management
- Staff Management
- Team Management
- Training Management
- Attendance Management
- Evaluation Management
- Payment Management
- Reporting
- Role & Permission Management

---

## Technology Stack

> Versi di bawah adalah versi yang **benar-benar terpasang** (dari `composer.lock`/`package-lock.json`), bukan cuma batas constraint di `composer.json`/`package.json`. Update tabel ini setiap kali dependency inti ditambah/di-upgrade, supaya jadi rujukan cepat tanpa perlu buka lock file.

| Layer | Component | Technology | Version |
|-------|-----------|------------|---------|
| Runtime | Bahasa | PHP | `^8.3` (terpasang 8.4.20) |
| Backend | Framework | Laravel | `^13.8` (terpasang 13.15.0) |
| Backend | Authentication scaffolding | Laravel Breeze | 2.4.2 |
| Backend | Authorization (role & permission) | Spatie Laravel Permission | 8.0.0 |
| Backend | Image processing (server-side resize, driver GD) | Intervention Image | `^4.2` (terpasang 4.2.0) |
| Backend | REPL/tinker | Laravel Tinker | 3.0.2 |
| Backend (dev only) | Query debugging | Laravel Debugbar (barryvdh) | 4.4.0 |
| Database | Produksi | MySQL | — |
| Database | Automated testing (`phpunit.xml`) | SQLite (in-memory) | — |
| Storage | File upload (logo, dsb) | Laravel Storage (disk `public`) | — |
| Frontend | Build tool | Vite | 8.1.0 (`laravel-vite-plugin` 3.1.0) |
| Frontend | CSS framework | Tailwind CSS | 4.3.1 |
| Frontend | Admin template | TailAdmin | — |
| Frontend | Reactivity/interaktivitas | Alpine.js | 3.15.12 |
| Frontend | Crop gambar sebelum upload (client-side) | Cropper.js | 1.6.2 |
| Tooling | Version control | Git | — |

---

## Core Architecture

FAOSBall dibangun menggunakan beberapa prinsip utama.

- Service Layer Architecture
- Thin Controller
- Dependency Injection
- Single Responsibility Principle (SRP)
- Reusable Service
- UUID Primary Key
- Single Database Multi-Tenant
- Laravel Storage
- Database Transaction
- DRY (Don't Repeat Yourself)

Business logic ditempatkan pada **Service Layer**, sedangkan Controller hanya bertugas menerima request, memanggil Service, dan mengembalikan response.

---

## Installation

Clone repository.

```bash
git clone <repository-url>
```

Masuk ke folder project.

```bash
cd faosball
```

Install dependency.

```bash
composer install
npm install
```

Salin file environment.

```bash
cp .env.example .env
```

Generate application key.

```bash
php artisan key:generate
```

Konfigurasi database pada file `.env`, kemudian jalankan migration dan seeder.

```bash
php artisan migrate --seed
```

Build asset frontend.

```bash
npm run dev
```

Jalankan aplikasi.

```bash
php artisan serve
```

---

## Documentation

Dokumentasi lengkap tersedia pada folder **docs/**.

| Document | Description |
|----------|-------------|
| docs/setup.md | Installation & Project Setup |
| docs/architecture.md | Software Architecture |
| docs/coding-standard.md | Coding Standard |
| docs/multi-tenancy.md | Multi-Tenant Architecture |
| docs/authorization.md | Authorization Architecture |
| docs/development-guide.md | Development Guide |
| docs/module-standard.md | Module Standard |
| docs/frontend-standard.md | Frontend Standard (CSS/Tailwind/Blade) |
| docs/query-performance.md | Query & Database Performance (N+1, Eager Loading, Index) |
| docs/permission-reference.md | Referensi Permission per Module (yang sudah digerbang & yang belum) |

---

## Project Structure

```text
app/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Models/
├── Providers/
├── Scopes/
├── Services/
├── Support/
├── Traits/
└── View/
    └── Components/

docs/

resources/

routes/
```

---

## Development Principles

Seluruh module pada FAOSBall wajib mengikuti standar yang telah ditetapkan.

- Business logic berada pada Service.
- Controller tetap tipis (Thin Controller).
- Validasi menggunakan Form Request.
- Model hanya menangani data dan relasi.
- Seluruh proses kompleks menggunakan `DB::transaction()`.
- Upload file menggunakan Laravel Storage.
- Seluruh data tenant mengikuti arsitektur Multi-Tenant.
- Seluruh authorization menggunakan Spatie Laravel Permission.
- Seluruh module mengikuti Coding Standard dan Module Standard yang telah ditetapkan.

---

## Roadmap

Status pengembangan module.

- [x] Authentication
- [x] Academy Management
- [x] Player Management
- [ ] Coach Management
- [ ] Parent Management
- [x] Staff Management
- [x] Team Management
- [ ] Training Management
- [ ] Attendance Management
- [ ] Evaluation Management
- [ ] Payment Management
- [ ] Reporting

---

## License

FAOSBall merupakan perangkat lunak yang dikembangkan khusus untuk kebutuhan Football Academy Operating System. Seluruh hak cipta dan kepemilikan sistem dilindungi sesuai ketentuan yang berlaku.