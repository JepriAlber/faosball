# Brief: Refactor Role & Permission menjadi Academy Based

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 12 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> Kalau cuma mau eksekusi, cukup baca Bagian 0–3 lalu langsung ke Tahap 1. Bagian 4 (alasan teknis) boleh dibaca belakangan, **tapi aturannya tetap tidak boleh dilanggar**.

---

## 0. Aturan Emas

Sembilan larangan ini bukan preferensi gaya. Masing-masing sudah pernah bikin bug nyata atau sudah diverifikasi akan gagal. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Menambah `AcademyScope` / global scope ke model `Role` | Meracuni cache Spatie lintas tenant, menu hilang acak | [4.3](#43-jangan-pasang-global-scope-pada-model-role) |
| Memakai trait `BelongsToAcademy` di model `Role` | Super Admin jadi gagal membuat role | [4.4](#44-jangan-pakai-trait-belongstoacademy-pada-model-role) |
| `$user->assignRole('NamaRole')` bentuk **string** di kode aplikasi | Bisa memberi role milik academy lain | [4.2](#42-accountserviceassignrole-bisa-memberi-role-milik-academy-lain) |
| `Role::firstOrCreate()` / `findByName()` **tanpa** `id_academy` | Menemukan role academy lain | [4.2](#42-accountserviceassignrole-bisa-memberi-role-milik-academy-lain) |
| Menulis `where('id_academy', null)` di query mentah | `= NULL` selalu false di SQL, pakai `whereNull()` | [4.5](#45-gotcha-sql-whereid_academy-null-tidak-pernah-cocok) |
| Menambah `id_academy` ke tabel `permissions` | Permission tetap **global** | [1](#1-tujuan) |
| Mengaktifkan fitur `teams` Spatie | Bukan pendekatan yang dipilih, akan bentrok | [5](#5-keputusan-arsitektur) |
| Membuat folder `lang/` | Pesan Indonesia di-hardcode | `docs/coding-standard.md` |
| Mengubah `2026_06_25_143759_create_permission_tables.php` | Pakai migration baru | [Tahap 1](#tahap-1--migration) |

---

## 1. Tujuan

Sekarang Role bersifat **global**: satu baris `Owner` dipakai bersama seluruh academy. Padahal tiap academy butuh hak akses berbeda meski nama role-nya sama.

Yang kita tuju:

```text
Academy A                      Academy B
Owner                          Owner
├── dashboard.view             ├── dashboard.view
├── player.view                ├── player.view
└── attendance.view            ├── staff.view
                               ├── payment.report
                               └── report.view
```

**Aturan inti**: Permission tetap **global** (fitur sistem). Role menjadi **milik Academy**.

---

## 2. Cara Kerja Solusi

Baca sampai paham. Kalau bagian ini tidak nyantol, sisa brief akan terasa acak.

Kuncinya cuma satu kalimat: **dua academy = dua baris role yang berbeda.**

Tabel `roles` setelah refactor:

| id | id_academy | name | guard_name |
|----|-----------|------|------------|
| 1 | `NULL` | Super Admin | web |
| 3 | `uuid-academy-A` | Owner | web |
| 7 | `uuid-academy-B` | Owner | web |

Tabel `role_has_permissions`:

| role_id | permission |
|---------|-----------|
| 3 | dashboard.view, player.view, attendance.view |
| 7 | dashboard.view, player.view, staff.view, payment.report, report.view |

Owner Academy A memegang baris **3**. Owner Academy B memegang baris **7**.

**Kenapa ini otomatis benar?** Karena Spatie mencocokkan permission lewat **primary key role**, bukan lewat nama:

```text
@can('staff.view')
  └─> HasPermissions::hasPermissionViaRole()          vendor/.../Traits/HasPermissions.php:300
        └─> $this->hasRole($permission->roles)
              └─> $this->roles->contains($keyName, $role->getKey())    Traits/HasRoles.php:352
```

- **Owner A** → permission `staff.view` punya roles `[7]`, tapi `$user->roles` = `[3]` → `contains(id, 7)` = **false** → menu Staff tidak muncul.
- **Owner B** → `$user->roles` = `[7]` → `contains(id, 7)` = **true** → menu Staff muncul.

Jadi pembedaan per-academy datang dari **baris role + tabel pivot**. Bukan dari scope, bukan dari filter, bukan dari tipe primary key.

Lalu `id_academy` gunanya apa? Hanya untuk menentukan **siapa yang boleh melihat & mengelola role itu di UI**. Itu saja.

**Arti nilai `id_academy`:**

| Nilai | Arti | Siapa yang mengelola |
|-------|------|---------------------|
| `NULL` | Role System | Hanya Super Admin |
| UUID | Role milik Academy tersebut | Owner academy itu + Super Admin |

**Siapa mengisi `id_academy` saat create role:**

| Pelaku | Sumber `id_academy` |
|--------|---------------------|
| Owner / user academy | Otomatis dari academy miliknya. Field academy **tidak dirender** di form. |
| Super Admin | Dari dropdown academy di form. Pilih "— Role System —" → `NULL`. |

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/…_add_id_academy_to_roles_table.php` | 🆕 Baru | 1 |
| `app/Models/Role.php` | 🆕 Baru | 2 |
| `config/permission.php` | ✏️ Ubah 1 baris | 2 |
| `config/faos.php` | ✏️ Tambah `role_templates` | 3 |
| `app/Services/AccountService.php` | ✏️ Ubah `assignRole()` | 4 |
| `app/Services/RoleService.php` | ✏️ Ubah | 5 |
| `app/Services/AcademyManagementService.php` | ✏️ Ubah `create()` | 6 |
| `app/Http/Requests/Role/RoleFormRequest.php` | ✏️ Ubah | 7 |
| `app/Http/Controllers/Controller.php` | ✏️ Tambah trait | 8 |
| `app/Policies/RolePolicy.php` | 🆕 Baru | 8 |
| `app/Http/Controllers/RoleController.php` | ✏️ Ubah | 8 |
| `resources/views/roles/{index,create,edit}.blade.php` | ✏️ Ubah | 9 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Ubah | 10 |
| `database/factories/AcademyFactory.php` | 🆕 Baru | 11 |
| `tests/Feature/RoleAcademyTest.php` | 🆕 Baru | 11 |
| `docs/multi-tenancy.md`, `docs/authorization.md` | ✏️ Ubah | 12 |
| **`app/Services/PermissionService.php`** | 🚫 **Jangan sentuh** | — |
| **`app/Http/Controllers/PermissionController.php`** | 🚫 **Jangan sentuh** | — |
| **`resources/views/permissions/*`** | 🚫 **Jangan sentuh** | — |
| **`routes/web.php`** | 🚫 **Jangan sentuh** | — |
| **`app/Services/PlayerService.php`** | 🚫 **Jangan sentuh** | — |
| **`app/Http/Controllers/PlayerAccountController.php`** | 🚫 **Jangan sentuh** | — |

> `PlayerService` & `PlayerAccountController` sengaja tidak berubah. Keduanya memanggil `accountService->create([...], 'Player')`, dan resolver baru di Tahap 4 otomatis mengambil role `Player` milik academy yang benar. Kalau kamu merasa perlu mengubahnya, berarti ada yang salah di Tahap 4.

---

## Tahap 1 — Migration

**Tujuan**: `roles` punya kolom `id_academy`, dan dua academy boleh punya role bernama sama.

```bash
php artisan make:migration add_id_academy_to_roles_table
```

Isi file barunya:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('id_academy')->nullable()->after('id');

            $table->foreign('id_academy')
                ->references('id_academy')
                ->on('academies')
                ->cascadeOnDelete();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_name_unique');
            $table->unique(['id_academy', 'name', 'guard_name'], 'roles_academy_name_guard_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_academy_name_guard_unique');
            $table->dropForeign(['id_academy']);
            $table->dropColumn('id_academy');
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });
    }
};
```

**Perubahan unique index inilah yang membuat dua `Owner` bisa hidup berdampingan.** Tanpa ini, MySQL menolak insert `Owner` kedua dan seluruh refactor mentok di baris pertama.

Catatan:
- Nama index `roles_name_guard_name_unique` sudah diverifikasi konsisten di MySQL maupun SQLite (test), karena `config('permission.testing')` tidak diset di project ini.
- `cascadeOnDelete` → academy dihapus, role-nya ikut terhapus, dan assignment di `model_has_roles` ikut bersih (FK-nya sudah `cascadeOnDelete` dari migration bawaan).
- Dua `Schema::table()` terpisah itu disengaja, bukan typo — kolom harus ada dulu sebelum dipakai di index.

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table roles
```

Harus terlihat kolom `id_academy` dan index `roles_academy_name_guard_unique`.

---

## Tahap 2 — Model `App\Models\Role`

**Tujuan**: punya model Role sendiri yang paham academy.

**File baru** `app/Models/Role.php` — salin utuh:

```php
<?php

namespace App\Models;

use App\Services\AcademyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Role sengaja TIDAK memakai trait BelongsToAcademy.
     * Trait itu selalu mengisi id_academy dari user yang login dan melempar
     * exception saat currentId() null, sehingga Super Admin tidak dapat
     * membuat Role System maupun role untuk academy lain.
     * id_academy diisi eksplisit oleh Service.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy');
    }

    /**
     * Batasi role sesuai academy aktif. Super Admin melihat seluruh role.
     *
     * Ini scope LOKAL, dipanggil eksplisit oleh RoleService — bukan global
     * scope. Global scope pada Role akan ikut memfilter query
     * Permission::with('roles') milik PermissionRegistrar, yang hasilnya
     * di-cache dan dipakai bersama seluruh tenant. Lihat Bagian 4.3.
     */
    public function scopeForCurrentAcademy(Builder $query): Builder
    {
        $academyService = app(AcademyService::class);

        if ($academyService->isSuperAdmin()) {
            return $query;
        }

        return $query->where('roles.id_academy', $academyService->currentId());
    }

    /**
     * Override create() bawaan Spatie.
     *
     * Versi bawaan menolak role dengan nama sama TANPA memeriksa id_academy,
     * sehingga Academy B tidak dapat membuat role "Owner" ketika Academy A
     * sudah memilikinya. Lihat Bagian 4.1.
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= config('faos.guard');

        $academyId = $attributes['id_academy'] ?? null;

        $duplicate = static::query()
            ->where('name', $attributes['name'])
            ->where('guard_name', $attributes['guard_name'])
            ->when(
                $academyId,
                fn (Builder $query) => $query->where('id_academy', $academyId),
                fn (Builder $query) => $query->whereNull('id_academy'),
            )
            ->exists();

        if ($duplicate) {
            throw new \Exception('Nama role sudah digunakan pada academy ini.');
        }

        return static::query()->create($attributes);
    }
}
```

**Ubah `config/permission.php`** — cari `'role' => Role::class` di dalam array `models`, ganti jadi:

```php
'role' => App\Models\Role::class,
```

> Biarkan `'permission' => Permission::class` bawaan Spatie **apa adanya**. Permission tetap global.

```bash
php artisan config:clear
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
get_class(app(\Spatie\Permission\PermissionRegistrar::class)->getRoleClass());
// harus: "App\Models\Role"
```

---

## Tahap 3 — Template role di `config/faos.php`

**Tujuan**: academy baru otomatis dapat role default.

Tambahkan blok ini ke `config/faos.php` (sebelum `'upload'`):

```php
/*
|--------------------------------------------------------------------------
| Role Template
|--------------------------------------------------------------------------
| Role default yang otomatis dibuat untuk setiap academy baru.
| Seluruh permission di sini WAJIB sudah ada di RolePermissionSeeder.
*/

'role_templates' => [

    'Owner' => [
        'player.view', 'player.create', 'player.update', 'player.delete',
        'coach.view', 'coach.create', 'coach.update', 'coach.delete',
        'team.view', 'team.create', 'team.update', 'team.delete',
        'training.view', 'training.create', 'training.update', 'training.delete',
        'attendance.view', 'attendance.create', 'attendance.update',
        'evaluation.view', 'evaluation.create', 'evaluation.update',
        'payment.view', 'payment.create', 'payment.update', 'payment.report',
        'report.view', 'report.export',
        'user.view', 'user.create', 'user.update', 'user.delete',
        'role.view', 'role.create', 'role.update', 'role.delete',
    ],

    'Coach' => [
        'player.view',
        'team.view',
        'training.view', 'training.create', 'training.update',
        'attendance.view', 'attendance.create', 'attendance.update',
        'evaluation.view', 'evaluation.create', 'evaluation.update',
    ],

    'Staff' => [
        'player.view', 'player.create', 'player.update',
        'coach.view',
        'team.view',
        'training.view',
        'attendance.view', 'attendance.create', 'attendance.update',
    ],

    'Finance' => [
        'payment.view', 'payment.create', 'payment.update', 'payment.report',
        'report.view', 'report.export',
    ],

    'Player' => [
        'training.view',
        'attendance.view',
        'evaluation.view',
    ],

    'Parent' => [
        'child.profile.view',
        'child.training.view',
        'child.payment.view',
    ],

],
```

> ⚠️ **`Player` dan `Parent` wajib ada.** `PlayerService.php:90` dan `PlayerAccountController.php:55` memanggil `accountService->create([...], 'Player')`. Kalau academy tidak punya role `Player`, pembuatan akun player gagal.

> ⚠️ Isi permission tiap template di atas adalah **usulan awal yang masuk akal, belum dikonfirmasi ke pemilik produk**. Tanyakan dulu kalau ragu. `Owner` sengaja diberi `role.*` supaya bisa mengelola role academy-nya sendiri.

**✅ Cek dulu**

```bash
php artisan config:clear
php artisan tinker
```

```php
array_keys(config('faos.role_templates'));
// harus: ["Owner","Coach","Staff","Finance","Player","Parent"]
```

---

## Tahap 4 — `AccountService`

**Tujuan**: user tidak pernah dapat role milik academy lain.

Di `app/Services/AccountService.php`, tambahkan import:

```php
use App\Models\Role;
```

**Ganti** method `assignRole()` yang lama (baris ~75) dengan dua method ini:

```php
/**
 * Assign role ke user.
 *
 * Role wajib berasal dari academy yang sama dengan user
 * (atau Role System untuk user tanpa academy).
 */
public function assignRole(User $user, Role|string $role): User
{
    $user->syncRoles([
        $this->resolveRole($user, $role),
    ]);

    return $user;
}

/**
 * Terjemahkan nama role menjadi baris Role milik academy user.
 *
 * JANGAN diganti dengan Role::findByName(). Method itu mengambil baris
 * pertama yang cocok tanpa peduli academy. Lihat Bagian 4.2.
 */
protected function resolveRole(User $user, Role|string $role): Role
{
    if ($role instanceof Role) {

        if ($role->id_academy !== $user->id_academy) {
            throw new \Exception('Role tidak berasal dari academy yang sama dengan user.');
        }

        return $role;
    }

    $query = Role::query()
        ->where('name', $role)
        ->where('guard_name', config('faos.guard'));

    // Perhatikan: where('id_academy', null) tidak pernah cocok di SQL.
    $user->id_academy === null
        ? $query->whereNull('id_academy')
        : $query->where('id_academy', $user->id_academy);

    $resolved = $query->first();

    if (! $resolved) {
        throw new \Exception('Role "' . $role . '" tidak ditemukan pada academy user.');
    }

    return $resolved;
}
```

`AccountService::create()` **tidak berubah** — ia sudah membuat user (lengkap dengan `id_academy`) sebelum memanggil `assignRole()`, jadi resolver di atas otomatis dapat konteks yang benar.

**✅ Cek dulu**: belum bisa diuji penuh sampai Tahap 10 (seeder). Cukup pastikan `php artisan tinker` tidak error saat `app(\App\Services\AccountService::class)`.

---

## Tahap 5 — `RoleService`

**Tujuan**: daftar role terisolasi per academy + bisa bikin role default.

`app/Services/RoleService.php`. Ini **file utuh** — salin semua, jangan tambal sebagian:

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Role;
use App\Support\PermissionPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RoleService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    public function paginate(?int $perPage = null)
    {
        return Role::forCurrentAcademy()
            ->with('academy')
            ->withCount(['permissions', 'users'])
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    public function permissionGroups(): Collection
    {
        return Permission::orderBy('name')
            ->get()
            ->groupBy(fn ($permission) => explode('.', $permission->name)[0]);
    }

    public function detail(Role $role): array
    {
        $role->load(['permissions', 'users']);

        $permissionGroups = $role->permissions
            ->sortBy('name')
            ->groupBy(fn ($permission) => PermissionPresenter::module($permission->name))
            ->map(
                fn ($permissions) => $permissions->map(
                    fn ($permission) => PermissionPresenter::present($permission)
                )
            );

        return [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
        ];
    }

    /**
     * Tentukan id_academy untuk role baru.
     *
     * User academy : otomatis dari academy miliknya, input form DIABAIKAN.
     * Super Admin  : dari pilihan academy di form (null = Role System).
     */
    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {

            $role = Role::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'guard_name' => config('faos.guard'),
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {

            if ($role->name === config('faos.super_admin_role')) {
                throw new \Exception('Role Super Admin tidak dapat diubah.');
            }

            // id_academy sengaja TIDAK ikut diubah.
            // Role tidak dapat berpindah academy.
            $role->update([
                'name' => $data['name'],
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });
    }

    public function delete(Role $role): bool
    {
        return DB::transaction(function () use ($role) {

            if ($role->name === config('faos.super_admin_role')) {
                throw new \Exception('Role Super Admin tidak dapat dihapus.');
            }

            if ($role->users()->exists()) {
                throw new \Exception('Role masih digunakan oleh user, tidak dapat dihapus.');
            }

            return $role->delete();
        });
    }

    /**
     * Buat role default untuk academy baru dari config('faos.role_templates').
     *
     * Academy di-pass eksplisit (bukan dari academy aktif) karena yang membuat
     * academy adalah Super Admin, yang id_academy-nya null.
     */
    public function createDefaultRoles(Academy $academy): void
    {
        foreach (config('faos.role_templates') as $name => $permissions) {

            $role = Role::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'guard_name' => config('faos.guard'),
            ]);

            $role->syncPermissions(
                Permission::whereIn('name', $permissions)->get()
            );
        }
    }
}
```

> `Permission::whereIn(...)->get()` dipakai supaya permission yang belum ada di database dilewati, bukan melempar exception. Konsekuensinya: **salah ketik di `role_templates` hilang diam-diam**. Test nomor 7 di Tahap 11 mengunci risiko ini.

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Services\RoleService::class)` tidak error (membuktikan constructor injection-nya benar).

---

## Tahap 6 — `AcademyManagementService`

**Tujuan**: bikin academy → role default langsung jadi.

Di `app/Services/AcademyManagementService.php`:

**1.** Tambah constructor di atas method `uploadLogo()`:

```php
protected RoleService $roleService;

public function __construct(RoleService $roleService)
{
    $this->roleService = $roleService;
}
```

**2.** Di method `create()`, ganti baris `return Academy::create($data);` menjadi:

```php
$academy = Academy::create($data);

$this->roleService->createDefaultRoles($academy);

return $academy;
```

Method `create()` sudah dibungkus `DB::transaction()`, jadi kalau pembuatan role gagal, academy-nya ikut batal. Itu memang yang kita mau.

**✅ Cek dulu**: `php artisan tinker` → `app(\App\Services\AcademyManagementService::class)` tidak error.

---

## Tahap 7 — `RoleFormRequest`

**Tujuan**: nama role unik **per academy**, dan hanya Super Admin boleh mengirim `id_academy`.

`app/Http/Requests/Role/RoleFormRequest.php`. Tambah import:

```php
use App\Services\AcademyService;
```

Ganti `rules()` dan `messages()`:

```php
public function rules(): array
{
    $academyService = app(AcademyService::class);

    $academyId = $academyService->isSuperAdmin()
        ? $this->input('id_academy')
        : $academyService->currentId();

    return [
        // Hanya Super Admin yang boleh mengirim id_academy.
        // User academy: field ini tidak dirender & ditolak kalau tetap dikirim.
        'id_academy' => [
            $academyService->isSuperAdmin() ? 'nullable' : 'prohibited',
            'uuid',
            'exists:academies,id_academy',
        ],

        'name' => [
            'required',
            'string',
            'max:100',
            Rule::unique('roles', 'name')
                ->where(fn ($query) => $query
                    ->where('guard_name', config('faos.guard'))
                    ->when(
                        $academyId,
                        fn ($q) => $q->where('id_academy', $academyId),
                        fn ($q) => $q->whereNull('id_academy'),
                    )
                )
                ->ignore($this->role?->id),
        ],

        'permissions' => ['nullable', 'array'],
        'permissions.*' => ['exists:permissions,name'],
    ];
}

public function messages(): array
{
    return [
        'name.required' => 'Nama role wajib diisi.',
        'name.string' => 'Nama role harus berupa teks.',
        'name.max' => 'Nama role maksimal 100 karakter.',
        'name.unique' => 'Nama role sudah digunakan pada academy ini.',

        'id_academy.prohibited' => 'Academy tidak dapat dipilih.',
        'id_academy.uuid' => 'Academy tidak valid.',
        'id_academy.exists' => 'Academy tidak ditemukan.',

        'permissions.array' => 'Format permission tidak valid.',
        'permissions.*.exists' => 'Permission yang dipilih tidak ditemukan.',
    ];
}
```

Catatan:
- `->ignore($this->role?->id)` tetap memakai `id` — primary key `roles` tidak berubah.
- Pesan Indonesia di-hardcode. Jangan bikin folder `lang/` (`docs/coding-standard.md`).

---

## Tahap 8 — Base Controller, Policy, dan `RoleController`

**Tujuan**: Owner A tidak bisa membuka `/roles/7` milik Academy B lewat URL.

Middleware `permission:role.view` di route hanya menjawab *"boleh mengelola role atau tidak"*. Ia **tidak** memeriksa role itu milik siapa. Itu tugas Policy.

### 8a. Base Controller — WAJIB, jangan dilewat

`app/Http/Controllers/Controller.php` saat ini adalah `abstract class` polos **tanpa trait `AuthorizesRequests`**. Tanpa langkah ini, `$this->authorize()` akan error `Call to undefined method`.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;   // ← tambah
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    use AuthorizesRequests;                                  // ← tambah

    // handleException() yang sudah ada BIARKAN apa adanya.
}
```

### 8b. `app/Policies/RolePolicy.php` — file baru

```php
<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Super Admin tidak pernah sampai ke sini — Gate::before() di
     * AppServiceProvider:25 sudah mengembalikan true lebih dulu.
     */
    public function view(User $user, Role $role): bool
    {
        return $this->sameAcademy($user, $role);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->sameAcademy($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->sameAcademy($user, $role);
    }

    /**
     * Role System (id_academy null) hanya boleh disentuh Super Admin,
     * yang sudah lolos lebih dulu lewat Gate::before().
     */
    protected function sameAcademy(User $user, Role $role): bool
    {
        return $role->id_academy !== null
            && $role->id_academy === $user->id_academy;
    }
}
```

Policy ini **auto-discovered** Laravel (`App\Models\Role` → `App\Policies\RolePolicy`). Tidak perlu didaftarkan manual.

### 8c. `RoleController`

1. Ganti import `use Spatie\Permission\Models\Role;` → `use App\Models\Role;`
2. Tambah import `use App\Models\Academy;` dan `use App\Services\AcademyService;`
3. Ganti constructor:

```php
protected RoleService $roleService;
protected AcademyService $academyService;

public function __construct(RoleService $roleService, AcademyService $academyService)
{
    $this->roleService = $roleService;
    $this->academyService = $academyService;
}
```

4. Tambah `$this->authorize(...)` sebagai **baris pertama** di 4 method:

```php
public function show(Role $role)     { $this->authorize('view', $role);   /* sisanya tetap */ }
public function edit(Role $role)     { $this->authorize('update', $role); /* sisanya tetap */ }
public function update(RoleFormRequest $request, Role $role) { $this->authorize('update', $role); /* sisanya tetap */ }
public function destroy(Role $role)  { $this->authorize('delete', $role); /* sisanya tetap */ }
```

> Di `update()`, taruh `authorize()` **sebelum** `try/catch`. `AuthorizationException` bukan kegagalan Service — ia harus jadi 403, bukan flash message error.

5. Kirim data academy ke view. Di `index()` tambahkan ke array view:

```php
'isSuperAdmin' => $this->academyService->isSuperAdmin(),
```

Di `create()` dan `edit()` tambahkan:

```php
'isSuperAdmin' => $this->academyService->isSuperAdmin(),
'academies' => $this->academyService->isSuperAdmin()
    ? Academy::orderBy('name')->get()
    : collect(),
```

`routes/web.php` **tidak berubah**.

**✅ Cek dulu**: buka `/roles` sebagai Super Admin (`superadmin@faosball.com` / `password`). Halaman harus tampil normal.

---

## Tahap 9 — View

`resources/views/roles/`. Ingat `docs/development-guide.md`: **tidak ada business logic di Blade**, dan wajib dicek di mobile/tablet.

Ikuti konvensi form yang sudah dipakai module ini: `form-group`, `form-label`, `form-select`, plus `@error` + `<span class="form-error">`. **Jangan** pakai `<x-input-error>` — komponen itu ada, tapi bukan yang dipakai di view roles/players.

### 9a. `create.blade.php`

Sisipkan blok ini **sebelum** `<div class="form-group">` milik "Nama Role" (baris ~30):

```blade
@if ($isSuperAdmin)
    <div class="form-group">

        <label class="form-label">Academy</label>

        <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror">
            <option value="">— Role System —</option>
            @foreach ($academies as $academy)
                <option value="{{ $academy->id_academy }}" @selected(old('id_academy') === $academy->id_academy)>
                    {{ $academy->name }}
                </option>
            @endforeach
        </select>

        @error('id_academy')
            <span class="form-error">{{ $message }}</span>
        @enderror

    </div>
@endif
```

Untuk user academy, field ini **tidak dirender sama sekali** — bukan sekadar `disabled`. Tiga lapis pertahanan: tidak dirender → `RoleFormRequest` menolaknya (`prohibited`) → `RoleService::resolveAcademyId()` mengabaikannya.

### 9b. `edit.blade.php`

Tampilkan academy sebagai **teks read-only**, bukan select — role tidak bisa pindah academy:

```blade
@if ($isSuperAdmin)
    <div class="form-group">
        <label class="form-label">Academy</label>
        <p class="form-input bg-gray-50 dark:bg-gray-800">
            {{ $role->id_academy ? $role->academy->name : 'Role System' }}
        </p>
    </div>
@endif
```

### 9c. `index.blade.php`

Ganti kolom **Guard** menjadi **Academy**. Nilai guard selalu `web` dan tidak informatif; academy jauh lebih berguna di tempatnya.

Di `<thead>`:

```blade
@if ($isSuperAdmin)
    <th class="table-header-cell">Academy</th>
@endif
```

Di `<tbody>` (ganti `<td>` yang berisi badge `$role->guard_name`):

```blade
@if ($isSuperAdmin)
    <td class="table-cell">
        @if ($role->id_academy)
            <span class="badge badge-secondary">{{ $role->academy->name }}</span>
        @else
            <span class="badge badge-primary">Role System</span>
        @endif
    </td>
@endif
```

> Jumlah `<th>` dan `<td>` harus tetap sama. Kalau `@if` dipasang di salah satunya saja, tabel akan bergeser.

**✅ Cek dulu**: login sebagai Super Admin → `/roles/create` menampilkan dropdown Academy. Cek juga di lebar layar HP (DevTools → 375px), tabel tidak boleh memaksa halaman melebar.

---

## Tahap 10 — Seeder

**Tujuan**: `migrate:fresh --seed` menghasilkan data yang sesuai arsitektur baru.

`database/seeders/RolePermissionSeeder.php`.

- Blok **Permissions** (baris 29–116): **tidak berubah**. Permission tetap global.
- Import: ganti `use Spatie\Permission\Models\Role;` → `use App\Models\Role;`, tambah `use App\Services\RoleService;` dan `use App\Services\AccountService;`.

**Hapus** pembuatan role global `Academy Owner`, `Academy Admin`, `Coach`, `Player`, `Parent` (baris ~132–156). Role-role itu sekarang lahir per academy dari template.

**Ganti** blok Roles menjadi:

```php
$superAdmin = Role::firstOrCreate([
    'name' => 'Super Admin',
    'guard_name' => 'web',
    'id_academy' => null,
]);
```

Setelah `$academy = Academy::firstOrCreate(...)` untuk FAOS Academy, tambahkan:

```php
app(RoleService::class)->createDefaultRoles($academy);

$ownerRole = Role::where('id_academy', $academy->id_academy)
    ->where('name', 'Owner')
    ->firstOrFail();

$staffRole = Role::where('id_academy', $academy->id_academy)
    ->where('name', 'Staff')
    ->firstOrFail();
```

Lalu ganti assignment user:

```php
$superAdminUser->assignRole($superAdmin);          // Role instance, aman
$superAdmin->syncPermissions(Permission::all());   // tetap

app(AccountService::class)->assignRole($academyOwnerUser, $ownerRole);
app(AccountService::class)->assignRole($academyAdminUser, $staffRole);
```

> ⚠️ `Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web'])` **tanpa `id_academy`** akan menemukan `Owner` milik academy lain lalu diam-diam tidak membuat role baru. Kalau memakai `firstOrCreate` untuk role academy, `id_academy` **wajib** ikut di array pencarian.

> Seeder berjalan tanpa auth. Sadari: `AcademyService::isSuperAdmin()` mengembalikan `Auth::user()?->id_academy === null` → **`true` saat tidak ada yang login**. Jadi `forCurrentAcademy()` tidak memfilter apa pun di seeder. Itu memang yang kita mau di sini.

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
php artisan tinker
```

```php
\App\Models\Role::pluck('name', 'id_academy');
// Harus ada: 1 "Super Admin" dengan id_academy null,
// + 6 role (Owner, Coach, Staff, Finance, Player, Parent) dengan id_academy FAOS Academy.

\App\Models\User::where('email','owner@faosacademy.com')->first()->can('player.view');
// harus: true
```

---

## Tahap 11 — Test

**Tujuan**: mengunci tujuan refactor supaya tidak rusak diam-diam nanti.

Test memakai SQLite in-memory (lihat `phpunit.xml`).

### 11a. `database/factories/AcademyFactory.php` — file baru

Belum ada factory untuk Academy, jadi buat dulu:

```php
<?php

namespace Database\Factories;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Academy>
 */
class AcademyFactory extends Factory
{
    protected $model = Academy::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'code' => strtoupper(Str::random(4)),
            'slug' => Str::slug($name),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '081234567890',
            'status' => true,
        ];
    }
}
```

Tambahkan `use HasFactory;` ke `app/Models/Academy.php` (import `Illuminate\Database\Eloquent\Factories\HasFactory`).

### 11b. `tests/Feature/RoleAcademyTest.php` — file baru

Ini template **yang sudah jalan** untuk skenario 1 & 2. Lanjutkan sisanya dengan pola yang sama:

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleAcademyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang
        // dibuat di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeRole(Academy $academy, string $name, array $permissions): Role
    {
        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => $name,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        return $role;
    }

    protected function makeUser(Academy $academy, Role $role): User
    {
        $user = User::factory()->create([
            'id_academy' => $academy->id_academy,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * INI ALASAN SELURUH REFACTOR INI ADA.
     */
    public function test_role_nama_sama_dapat_memiliki_permission_berbeda(): void
    {
        Permission::create(['name' => 'player.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'staff.view', 'guard_name' => 'web']);

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $ownerA = $this->makeUser($academyA, $this->makeRole($academyA, 'Owner', ['player.view']));
        $ownerB = $this->makeUser($academyB, $this->makeRole($academyB, 'Owner', ['player.view', 'staff.view']));

        $this->assertTrue($ownerA->can('player.view'));
        $this->assertFalse($ownerA->can('staff.view'));   // ← inti refactor

        $this->assertTrue($ownerB->can('player.view'));
        $this->assertTrue($ownerB->can('staff.view'));
    }

    /**
     * Mengunci override Role::create() di Tahap 2.
     */
    public function test_dua_academy_boleh_punya_role_dengan_nama_sama(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $this->makeRole($academyA, 'Owner', []);
        $this->makeRole($academyB, 'Owner', []);

        $this->assertSame(2, Role::where('name', 'Owner')->count());
    }
}
```

### 11c. Skenario sisanya (wajib ditulis, pola sama)

| # | Skenario | Assert kunci |
|---|----------|--------------|
| 3 | Isolasi daftar | `actingAs($ownerA)` → `RoleService::paginate()` tidak memuat role Academy B |
| 4 | Isolasi URL | `actingAs($ownerA)->get(route('roles.show', $roleB))` → `assertForbidden()`. Role A harus punya `role.view` supaya lolos middleware dan benar-benar diuji Policy-nya, bukan middleware. |
| 5 | Assign lintas academy ditolak | `AccountService::assignRole($userB, $roleA)` → `expectException(\Exception::class)` |
| 6 | Resolve by name benar | `AccountService::create([...id_academy B...], 'Player')` → user dapat role `Player` **milik Academy B** (bandingkan `id`-nya, bukan namanya) |
| 7 | Academy baru dapat role default | `AcademyManagementService::create()` → role sesuai `config('faos.role_templates')` **lengkap dengan permission-nya** (mengunci risiko salah ketik di config) |
| 8 | Super Admin melihat semua | `actingAs($superAdmin)` → `paginate()` memuat role seluruh academy |

**✅ Cek dulu**

```bash
php artisan test --filter=RoleAcademyTest
```

Semua hijau.

---

## Tahap 12 — Update `docs/`

Wajib, sesuai aturan di `CLAUDE.md`. Dokumen berikut sekarang **bertentangan** dengan hasil refactor:

| File | Baris | Yang salah sekarang | Perbaiki jadi |
|------|-------|---------------------|---------------|
| `docs/multi-tenancy.md` | 80–90 | `roles` didaftar sebagai tabel global tanpa `id_academy` | Pindahkan `roles` ke tabel tenant. Jelaskan kenapa Role **tidak** pakai `BelongsToAcademy`/global scope (Bagian 4.3 & 4.4). |
| `docs/authorization.md` | 105–121 | Daftar "System Roles" berisi Academy Owner, Coach, Player, dst | Hanya `Super Admin` yang system. Tambah bagian **Role Academy Based**: aturan `id_academy`, template role, scope lokal + Policy, peringatan cache Spatie. |
| `docs/authorization.md` | 461–472 | "Role Management akan menggunakan model bawaan Spatie" | Perbarui ke `App\Models\Role`. |

---

## 4. Kenapa Begini? (alasan teknis)

Bagian ini menjelaskan Aturan Emas di Bagian 0. Semuanya sudah diverifikasi langsung di kode vendor — bukan dugaan.

### 4.1 `Role::create()` bawaan Spatie akan MENOLAK role kedua

`vendor/spatie/laravel-permission/src/Models/Role.php:75-77`

```php
if (static::findByParam($params)) {          // $params = ['name', 'guard_name'] SAJA
    throw RoleAlreadyExists::create(...);
}
```

`findByParam()` hanya mengecek `name` + `guard_name`, **tanpa `id_academy`**, karena fitur `teams` di `config/permission.php:151` bernilai `false`.

**Akibat**: `Role::create(['id_academy' => B, 'name' => 'Owner'])` melempar exception hanya karena Academy A sudah punya `Owner`. Ini blocker langsung untuk tujuan refactor.

→ Diatasi dengan override `create()` di **Tahap 2**.

### 4.2 `AccountService::assignRole()` bisa memberi role milik academy lain

`app/Services/AccountService.php:75` menerima **string** lalu meneruskannya ke `syncRoles(['Owner'])` → `getStoredRole()` → `Role::findByName('Owner')` → mengambil **baris pertama yang cocok**, tanpa peduli academy.

**Akibat**: begitu ada dua `Owner`, user Academy B bisa mendapat baris **3** — yaitu seluruh permission milik Academy A. Kebocoran hak akses lintas tenant.

→ Diatasi dengan resolver eksplisit di **Tahap 4**.

### 4.3 JANGAN pasang Global Scope pada model Role

`PermissionRegistrar::getPermissionsWithRoles()` (`vendor/.../PermissionRegistrar.php:293`) menjalankan:

```php
Permission::select()->with('roles')->get();
```

lalu menyimpan hasilnya ke **satu cache key bersama** (`spatie.permission.cache`) untuk semua tenant.

**Akibat kalau `Role` dipasangi global scope**: eager-load `with('roles')` ikut terfilter oleh academy siapa pun yang kebetulan memicu rebuild cache. Kalau Owner A yang memicu, peta `staff.view` tersimpan **tanpa baris 7**. Owner B lalu buka menu Staff → `contains(id, 7)` → false, padahal datanya benar di database. Menu hilang sendiri sampai cache di-flush — acak, dan nyaris mustahil dilacak.

→ Karena itu dipakai scope **lokal** `forCurrentAcademy()` + `RolePolicy` (**Tahap 2 & 8**).

### 4.4 JANGAN pakai trait `BelongsToAcademy` pada model Role

`app/Traits/BelongsToAcademy.php:27-33` melempar exception saat `currentId()` bernilai `null`, dan selalu mengisi `id_academy` dari user yang login.

**Akibat**: Super Admin (`id_academy = null`) gagal membuat Role System, dan gagal membuat role default untuk Academy B yang bukan miliknya.

→ `id_academy` diisi eksplisit oleh Service (**Tahap 2 & 5**).

### 4.5 Gotcha SQL: `where('id_academy', null)` tidak pernah cocok

Di SQL, `id_academy = NULL` selalu `false`. Untuk mencari Role System **wajib** `whereNull('id_academy')`.

> Pengecualian: Eloquent `where(['id_academy' => null])` (bentuk array, mis. di `firstOrCreate`) **otomatis** diterjemahkan jadi `whereNull` oleh Query Builder. Yang berbahaya adalah bentuk `->where('id_academy', $variabelYangKebetulanNull)`.

---

## 5. Keputusan Arsitektur

Draft awal issue ini meminta tiga hal yang **sengaja tidak diikuti**. Kalau mau mengubahnya, **diskusikan dulu** — jangan diam-diam dikembalikan.

| Draft awal | Keputusan | Alasan |
|-----------|-----------|--------|
| Role pakai Global Scope | **Scope lokal + Policy** | Global scope meracuni cache Spatie lintas tenant (4.3). Justru mengancam tujuan refactor ini. |
| Role pakai UUID primary key | **PK tetap bigint**, hanya tambah kolom `id_academy` UUID | `roles` tabel milik package, bukan tabel bisnis FAOSBall. Mengubah ke UUID memaksa rewrite FK di `model_has_roles` + `role_has_permissions`, wajib `migrate:fresh` (semua assignment hilang), dan **nol manfaat fungsional** — `contains($key, 3)` dan `contains($key, 'uuid-…')` sama-sama jalan (lihat Bagian 2). |
| Template role sebagai record DB | **Template dari `config/faos.php`** | Deterministik, ter-version di git, tidak ada record role `NULL` ambigu yang harus diatur siapa boleh melihatnya. |

---

## 6. Definition of Done

- [ ] `php artisan migrate:fresh --seed` bersih tanpa error.
- [ ] Tabel `roles` punya `id_academy` UUID nullable + unique `(id_academy, name, guard_name)`.
- [ ] `config/permission.php` → `models.role` = `App\Models\Role::class`.
- [ ] Dua academy bisa punya role `Owner` dengan permission berbeda — terbukti lewat test, bukan lewat asumsi.
- [ ] Owner hanya melihat & mengelola role academy sendiri; akses lintas academy → 403.
- [ ] Super Admin melihat seluruh role dan bisa memilih academy saat create.
- [ ] Academy baru otomatis dapat role default dari `config('faos.role_templates')`.
- [ ] Pembuatan akun Player masih jalan (`PlayerService`, `PlayerAccountController` tidak berubah).
- [ ] `php artisan test` hijau.
- [ ] Controller tetap tipis, business logic di Service, validasi di Form Request.
- [ ] Pesan user-facing Bahasa Indonesia, hardcoded, tanpa folder `lang/`.
- [ ] View sudah dicek di mobile / tablet / desktop.
- [ ] `docs/multi-tenancy.md` dan `docs/authorization.md` sudah diperbarui.

---

## 7. Urutan Commit

Kerjakan berurutan, jangan digabung — tiap commit harus bisa di-review sendiri.

| # | Isi | Tahap |
|---|-----|-------|
| 1 | Migration `id_academy` + unique index | 1 |
| 2 | Model `App\Models\Role` + `config/permission.php` + `role_templates` | 2, 3 |
| 3 | `AccountService`, `RoleService`, `AcademyManagementService` | 4, 5, 6 |
| 4 | `RoleFormRequest`, base Controller, `RolePolicy`, `RoleController`, View | 7, 8, 9 |
| 5 | Seeder + Factory + Test | 10, 11 |
| 6 | Update `docs/` | 12 |

Kalau perilaku permission terasa aneh saat manual testing:

```bash
php artisan permission:cache-reset
php artisan config:clear
```

---

## 8. Hasil Akhir

Setelah refactor ini, Permission tetap menjadi fitur global sistem, sedangkan Role menjadi identitas dan struktur organisasi masing-masing academy. Setiap academy menjadi organisasi independen: bebas membuat role sendiri, nama role boleh sama antar academy, dan kombinasi permission-nya berbeda tanpa saling memengaruhi.

Ini fondasi untuk module berikutnya — Coach, Team, Training, Attendance, Evaluation, Payment — yang seluruhnya akan menggantungkan hak aksesnya pada model authorization ini.
