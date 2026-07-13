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

| Component | Technology |
|-----------|------------|
| Framework | Laravel 13 |
| Frontend | Tailwind CSS |
| Admin Template | TailAdmin |
| JavaScript | Alpine.js |
| Authentication | Laravel Breeze |
| Authorization | Spatie Laravel Permission |
| Database | MySQL |
| Storage | Laravel Storage |
| Version Control | Git |

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
└── Traits/

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
- [ ] Staff Management
- [ ] Team Management
- [ ] Training Management
- [ ] Attendance Management
- [ ] Evaluation Management
- [ ] Payment Management
- [ ] Reporting

---

## License

FAOSBall merupakan perangkat lunak yang dikembangkan khusus untuk kebutuhan Football Academy Operating System. Seluruh hak cipta dan kepemilikan sistem dilindungi sesuai ketentuan yang berlaku.