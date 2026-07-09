Epic
Administration Module - Role Management
Tujuan

Membangun modul Role Management menggunakan Spatie Permission yang mengikuti standar arsitektur FAOSBall.

Role digunakan untuk mengelola seluruh Role pada sistem. Permission Assignment belum termasuk dalam modul ini dan akan dikerjakan pada modul berikutnya.

Issue 1 — Tambahkan Permission Seeder untuk Role Management
Deskripsi

Tambahkan permission dasar pada RolePermissionSeeder.

Permission baru:

role.view
role.create
role.update
role.delete

permission.view
permission.create
permission.update
permission.delete

Belum ada perubahan pada UI.

Acceptance Criteria
Permission baru berhasil dibuat melalui seeder.
Seeder tetap idempotent (firstOrCreate).
Role Super Admin memiliki seluruh permission tersebut.
Tidak Termasuk
CRUD Role.
Permission Management.
Issue 2 — Tambahkan Menu Administration
Deskripsi

Tambahkan menu baru pada Sidebar.

Struktur menu:

Administration
    ├── Academy Management
    ├── Roles
    └── Permissions

Menu hanya tampil untuk Super Admin.

Acceptance Criteria
Dropdown Administration menggunakan style yang sama seperti Football Academy.
Menu aktif mengikuti Route.
Hanya user Super Admin yang dapat melihat menu.
Tidak Termasuk
CRUD Role.
Permission.
Issue 3 — Routing Role Management
Deskripsi

Tambahkan Resource Route Role.

Gunakan:

Route::resource('roles', RoleController::class)
    ->except('show');
Acceptance Criteria

Route tersedia:

index
create
store
edit
update
destroy

Tidak membuat show.

Issue 4 — Buat RoleFormRequest
Deskripsi

Buat Form Request untuk validasi Role.

Field:

name

Rule:

required
string
max:100
unique

Gunakan pesan validasi Bahasa Indonesia.

Acceptance Criteria

Seluruh validasi berada di Form Request.

Controller tidak memiliki validasi.

Issue 5 — Buat RoleService
Deskripsi

Buat RoleService.

Method:

create()

update()

delete()

Gunakan DB::transaction() pada seluruh proses yang mengubah data.

Acceptance Criteria
Tidak ada logika bisnis di Controller.
Semua proses CRUD dilakukan melalui Service.
Menggunakan model bawaan Spatie (Spatie\Permission\Models\Role).
Tidak Termasuk
Assign Permission.
Assign User.
Issue 6 — Buat RoleController
Deskripsi

Controller hanya bertugas:

memanggil Service
menangani redirect
menangani flash message
menangani exception

Gunakan pola yang sama dengan AcademyController.

Acceptance Criteria

Method:

index
create
store
edit
update
destroy

Controller tidak boleh berisi business logic.

Issue 7 — View Role Management
Deskripsi

Buat View:

roles/

index.blade.php
create.blade.php
edit.blade.php
_form.blade.php

Menggunakan komponen yang sudah ada:

Breadcrumb
x-alert
x-button
x-input

Ikuti layout Academy Management.

Acceptance Criteria

Index menampilkan:

| No | Role | Total User | Action |

Form Create dan Edit menggunakan partial _form.blade.php.

Issue 8 — Proteksi Delete
Deskripsi

Tambahkan validasi pada RoleService.

Role tidak boleh dihapus jika:

masih memiliki user
termasuk System Role

System Role:

Super Admin
Academy Owner
Academy Admin
Coach
Player
Parent
Acceptance Criteria

Jika Role masih digunakan:

Role tidak dapat dihapus karena masih digunakan oleh user.

Jika System Role:

System Role tidak dapat dihapus.
Issue 9 — Proteksi Rename System Role
Deskripsi

System Role tidak boleh diubah namanya.

Role lain tetap dapat diubah.

Acceptance Criteria

Jika mencoba mengubah nama System Role:

System Role tidak dapat diubah.
Issue 10 — Final Testing dan Refactoring
Checklist
Semua proses menggunakan Service Layer.
Semua validasi menggunakan Form Request.
Tidak ada Business Logic di Controller.
Seluruh proses CRUD menggunakan DB Transaction.
Menggunakan Spatie Permission bawaan.
Konsisten dengan struktur Academy Management.
Mengikuti coding style FAOSBall (termasuk penggunaan storeAs() untuk upload pada modul yang memerlukan upload).
Tidak ada whitespace berlebihan pada Blade.
Seluruh route dan menu aktif berfungsi dengan benar.
Definition of Done (DoD)

Modul Role Management dinyatakan selesai apabila:

CRUD Role berfungsi penuh.
Mengikuti pola Controller → FormRequest → Service.
Menggunakan DB::transaction() pada seluruh proses perubahan data.
View konsisten dengan modul Academy Management.
Hanya Super Admin yang dapat mengakses menu dan route.
Role bawaan sistem tidak dapat diubah maupun dihapus.
Role yang masih digunakan oleh user tidak dapat dihapus.
Belum mencakup pengelolaan permission (akan dikerjakan pada modul berikutnya).