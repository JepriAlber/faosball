# Setup Guide

Dokumen ini menjelaskan cara melakukan instalasi dan menjalankan project **FAOSBall** pada lingkungan development.

---

# System Requirements

Pastikan perangkat telah memenuhi kebutuhan berikut:

| Software | Version             |
| -------- | ------------------- |
| PHP      | 8.4 atau lebih baru |
| Composer | Latest Stable       |
| Node.js  | Latest LTS          |
| NPM      | Latest              |
| MySQL    | 8.x                 |
| Git      | Latest              |

---

# Clone Repository

Clone repository FAOSBall.

```bash
git clone <repository-url>
```

Masuk ke folder project.

```bash
cd faosball
```

---

# Install Dependencies

Install seluruh dependency PHP.

```bash
composer install
```

Install dependency frontend.

```bash
npm install
```

---

# Environment Configuration

Salin file environment.

```bash
cp .env.example .env
```

Sesuaikan konfigurasi database pada file `.env`.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=faosball
DB_USERNAME=root
DB_PASSWORD=
```

---

# Generate Application Key

Generate application key.

```bash
php artisan key:generate
```

---

# Database Migration

Jalankan migration.

```bash
php artisan migrate
```

Apabila ingin mengisi data awal sistem.

```bash
php artisan db:seed
```

Atau langsung menggunakan:

```bash
php artisan migrate:fresh --seed
```

Seeder akan membuat data awal yang diperlukan oleh sistem, seperti:

* Role
* Permission
* Academy Default
* Super Admin
* Academy Owner
* Academy Admin

---

# Storage Link

Buat symbolic link untuk Laravel Storage.

```bash
php artisan storage:link
```

---

# Build Frontend Assets

Mode Development.

```bash
npm run dev
```

---

# Menjalankan Project

Jalankan Laravel Development Server.

```bash
php artisan serve
```

Secara default aplikasi dapat diakses melalui:

```
http://127.0.0.1:8000
```

---

# Authentication

FAOSBall menggunakan **Laravel Breeze** sebagai sistem authentication.

Data awal sistem, termasuk Role, Permission, Academy, dan akun administrator, akan dibuat melalui proses seeding menggunakan `RolePermissionSeeder`.

Konfigurasi akun awal dikelola melalui file seeder dan tidak didokumentasikan pada repository ini.


---

# Project Structure

Folder utama yang sering digunakan selama pengembangan.

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

database/
├── migrations/
├── seeders/

resources/
├── views/

routes/
├── web.php

docs/
```

---

# Next Step

Setelah instalasi selesai, lanjutkan dengan membaca dokumentasi berikut:

1. `docs/architecture.md`
2. `docs/multi-tenancy.md`
3. `docs/authorization.md`
4. `docs/coding-standard.md`
5. `docs/development-guide.md`
6. `docs/module-standard.md`
7. `docs/frontend-standard.md`
8. `docs/query-performance.md`
9. `docs/permission-reference.md`

Dokumen tersebut menjelaskan standar arsitektur dan aturan pengembangan yang wajib diikuti pada seluruh module FAOSBall.
