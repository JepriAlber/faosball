# Brief: Modul Staff Position (bagian 2/3 dari "Office")

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: `issue9.md` **wajib sudah selesai** (dropdown "Office" di sidebar, `config('faos.role_templates')`/`RolePermissionSeeder` sudah punya pola yang diikuti brief ini). Baca juga `docs/module-standard.md`, `docs/multi-tenancy.md`, `docs/authorization.md` (*Role Academy Based*), `docs/frontend-standard.md`. Modul referensi: sama seperti `issue9.md` (`PlayerType`), **plus** `app/Models/Role.php` (wajib dibaca — PK-nya beda dari model lain di app ini, lihat [Aturan Emas](#0-aturan-emas)).
> **Bagian dari brief besar "Office"**: 2 dari 3 brief berurutan (`issue9.md` → **brief ini** → `issue11.md`). Lihat `issue9.md` bagian header untuk peta lengkap.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 10** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: CRUD master data Staff Position (tenant-scoped) — pola dasar sama seperti `issue9.md`, **plus** 3 field tambahan: `code` (kode jabatan), `is_coach` (flag filter pelatih, dipakai fitur masa depan), dan `role_id` (nullable FK ke `roles.id` — "Default Role", dipakai `issue11.md` saat staff dengan posisi ini dibuatkan akun login). **Bukan** scope: guard delete berbasis relasi ke `staff` (sama seperti `issue9.md`, ditambahkan belakangan di `issue11.md` Tahap 9), dan **bukan** scope mengubah `app/Models/Role.php`/`RoleService` sama sekali — brief ini cuma MEMBACA data Role yang sudah ada, tidak pernah menulis/mengubahnya.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Bikin kolom `role_id` bertipe `uuid` | `roles.id` adalah **`bigint` auto-increment bawaan Spatie** (`spatie/laravel-permission`), BUKAN uuid seperti PK lain di seluruh app ini. `staff_positions.role_id` **wajib** `unsignedBigInteger`, foreign key ke `roles.id` (bukan ke kolom uuid apa pun) | [Tahap 1](#tahap-1--migration), [4.1](#41-kenapa-role_id-bertipe-bigint-bukan-uuid-seperti-fk-lain) |
| Pakai `Role::findByName()` atau query `Role` tanpa filter `id_academy` | Bisa ambil role dari academy lain (nama role boleh sama di academy berbeda, lihat `docs/authorization.md` → *Role Academy Based*). Selalu query lewat scope `Role::forCurrentAcademy()` atau filter eksplisit `where('id_academy', ...)` | [Tahap 3](#tahap-3--controller), [Tahap 4](#tahap-4--staffpositionservice) |
| Bikin `StaffPosition extends Model` biasa atau taruh `role_id` di `BelongsToAcademy` | `StaffPosition` tetap tenant biasa (`extends FaosModel`, sama seperti `EmploymentType`) — BEDA dengan `Role` yang sengaja TIDAK pakai `BelongsToAcademy` karena isu cache Spatie (`docs/multi-tenancy.md` → *Role: Tenant Tanpa BelongsToAcademy*). Pengecualian itu cuma berlaku untuk model `Role` itu sendiri, bukan model lain yang punya FK ke Role | [Tahap 2](#tahap-2--model-staffposition) |
| Petakan default role "Admin" ke role baru bernama "Admin" | Role default yang otomatis dibuat tiap academy cuma: **Owner, Coach, Staff, Finance, Player, Parent** (`config('faos.role_templates')`) — TIDAK ADA role "Admin". Seed default Staff Position "Admin" dipetakan ke role **"Staff"** yang sudah ada (paling dekat cakupan izinnya), bukan bikin role baru | [Tahap 6](#tahap-6c-configfaosphp) |
| Filter dropdown Role di form create tanpa peduli academy mana yang dipilih (khusus Super Admin) | Role tenant-scoped per academy — dropdown Super Admin wajib dikelompokkan per academy (`<optgroup>`), bukan daftar flat semua role tercampur | [Tahap 8](#tahap-8--views) |

---

## 1. Konteks & Tujuan

Staff Position adalah master data jabatan staff academy (Head Coach, Finance Manager, Admin, dst) — pola CRUD dasarnya identik `issue9.md` (Employment Type), tapi py 3 field tambahan yang jadi alasan brief ini terpisah:

```text
staff_positions
├── code          -- kode jabatan pendek (mis. "HC", "FM"), unik per academy
├── name          -- nama jabatan
├── is_coach      -- flag boolean, dipakai fitur Training/Team di masa depan
│                    untuk filter "siapa saja yang berperan sebagai pelatih"
└── role_id       -- "Default Role" (NULLABLE, FK ke roles.id) -- saat staff
                     dengan posisi ini dibuatkan akun login (issue11.md),
                     role ini otomatis jadi PILIHAN AWAL (bukan otomatis
                     ter-assign begitu saja -- admin tetap bisa ganti)
```

## 2. Cara Kerja Solusi

### 2a. `role_id` cuma DEFAULT/SARAN, bukan paksaan

Field ini nullable dan cuma dipakai sebagai **nilai awal pre-selected** di dropdown role saat membuat akun staff (`issue11.md`) — persis semangat `min_age`/`max_age` di Player Category yang cuma "saran", bukan aturan mengikat. Admin yang buat akun staff tetap bisa pilih role lain lewat dropdown yang sama.

### 2b. Dropdown Role di form Staff Position sendiri (bukan buat akun) — kelompok per academy untuk Super Admin

`Role` bertenant per academy (`docs/authorization.md` → *Role Academy Based*), jadi kalau Super Admin sedang membuat Staff Position dan belum pasti akan pilih academy mana, daftar Role yang muncul wajib dikelompokkan biar tidak salah pilih role dari academy yang salah. Owner biasa (bukan Super Admin) otomatis cuma lihat role academy-nya sendiri lewat `Role::forCurrentAcademy()`.

### 2c. Seed default: 6 dari 7 posisi terpetakan ke role yang sudah ada, 1 sengaja kosong

Tabel referensi awal (Head Coach/Assistant Coach/Goalkeeper Coach/Finance Manager/Finance Staff/Academy Director/Admin) dipetakan ke role default yang **benar-benar ada**: Coach, Finance, Owner, dan Staff (untuk Admin — lihat [Aturan Emas](#0-aturan-emas)). Tidak ada posisi yang di-skip atau dikosongkan paksa — semua 7 posisi seed dapat `role_id` terisi karena semuanya berhasil dipetakan ke salah satu dari 6 role default yang ada.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/..._create_staff_positions_table.php` | 🆕 Baru | 1 |
| `app/Models/StaffPosition.php` | 🆕 Baru | 2 |
| `database/factories/StaffPositionFactory.php` | 🆕 Baru | 2 |
| `app/Services/StaffPositionService.php` | 🆕 Baru | 4 |
| `app/Http/Requests/StaffPosition/StaffPositionFormRequest.php` | 🆕 Baru | 5 |
| `app/Http/Controllers/StaffPositionController.php` | 🆕 Baru | 3 |
| `routes/web.php` | ✏️ Tambah resource route `staff-positions` | 6 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah 4 permission + panggilan `createDefaultStaffPositions()` | 6 |
| `config/faos.php` | ✏️ Tambah `role_templates.Owner` + `staff_position_templates` | 6 |
| `app/Services/AcademyManagementService.php` | ✏️ Inject `StaffPositionService`, panggil `createDefaultStaffPositions()` di `create()` (SETELAH `createDefaultRoles()`) | 7 |
| `resources/views/staff-positions/index.blade.php` | 🆕 Baru | 8 |
| `resources/views/staff-positions/create.blade.php` | 🆕 Baru | 8 |
| `resources/views/staff-positions/edit.blade.php` | 🆕 Baru | 8 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Sisip item "Staff Position" ke dropdown Office (SEBELUM Employment Type) | 9 |
| `lang/en.json` | ✏️ Entry baru | 9 |
| `tests/Feature/StaffPositionTest.php` | 🆕 Baru | 10 |
| `docs/permission-reference.md` | ✏️ Tambah section Staff Position | 10 |

---

## Tahap 1 — Migration

```bash
php artisan make:migration create_staff_positions_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_positions', function (Blueprint $table) {

            $table->uuid('id_staff_position')->primary();

            $table->uuid('id_academy');

            /*
            |--------------------------------------------------------------------------
            | Default Role
            |--------------------------------------------------------------------------
            | unsignedBigInteger, BUKAN uuid -- roles.id adalah PK bawaan
            | spatie/laravel-permission (bigint auto-increment), beda dari seluruh
            | FK lain di app ini yang uuid. Nullable: posisi boleh belum punya
            | default role, admin isi manual belakangan lewat form edit.
            */
            $table->unsignedBigInteger('role_id')->nullable();

            $table->string('code', 20);
            $table->string('name', 100);
            $table->boolean('is_coach')->default(false);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');
            $table->index('role_id');
            $table->unique(['id_academy', 'name'], 'staff_positions_academy_name_unique');
            $table->unique(['id_academy', 'code'], 'staff_positions_academy_code_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_positions');
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table staff_positions
```

Kolom `role_id` harus tipe `bigint unsigned`, **nullable**. Kalau `php artisan migrate` gagal dengan error FK type mismatch, cek lagi tipe `roles.id` (`php artisan db:table roles`) — harus `bigint`.

---

## Tahap 2 — Model `StaffPosition`

`app/Models/StaffPosition.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPosition extends FaosModel
{
    use HasFactory;

    protected $table = 'staff_positions';
    protected $primaryKey = 'id_staff_position';

    protected $fillable = ['id_academy', 'role_id', 'code', 'name', 'is_coach', 'description', 'status'];

    protected function casts(): array
    {
        return [
            'is_coach' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    /**
     * "Default Role" -- role_id merujuk ke roles.id (bigint), BUKAN kolom
     * uuid manapun. belongsTo() default owner key 'id' sudah otomatis
     * benar untuk kasus ini (tidak perlu parameter ketiga).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // relasi staff() BELUM ada di sini -- ditambahkan issue11.md Tahap 9
    // setelah tabel `staff` dibuat (lihat Aturan Emas issue9.md).
}
```

`database/factories/StaffPositionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\StaffPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffPosition>
 */
class StaffPositionFactory extends Factory
{
    protected $model = StaffPosition::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->unique()->jobTitle(),
            'is_coach' => false,
            'description' => fake()->sentence(),
            'status' => true,
        ];
    }
}
```

**✅ Cek dulu**: `php artisan tinker` → `(new \App\Models\StaffPosition)->getFillable()` harus memuat 7 field di atas.

---

## Tahap 3 — Controller

`app/Http/Controllers/StaffPositionController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffPosition\StaffPositionFormRequest;
use App\Models\Academy;
use App\Models\Role;
use App\Models\StaffPosition;
use App\Services\AcademyService;
use App\Services\StaffPositionService;

class StaffPositionController extends Controller
{
    protected StaffPositionService $staffPositionService;
    protected AcademyService $academyService;

    public function __construct(StaffPositionService $staffPositionService, AcademyService $academyService)
    {
        $this->staffPositionService = $staffPositionService;
        $this->academyService = $academyService;
    }

    public function index()
    {
        return view('staff-positions.index', [
            'title' => __('Staff Position'),
            'breadcrumb' => [
                ['label' => __('Office')],
                ['label' => __('Staff Position')],
            ],
            'staffPositions' => $this->staffPositionService->paginate(),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('staff-positions.create', [
            'title' => __('Tambah Staff Position'),
            'breadcrumb' => [
                ['label' => __('Staff Position'), 'url' => route('staff-positions.index')],
                ['label' => __('Tambah Staff Position')],
            ],
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
            // Super Admin: dikelompokkan per academy (Role tenant-scoped per
            // academy) supaya tidak salah pilih role dari academy lain.
            // Owner biasa: cukup role academy-nya sendiri.
            'roles' => $isSuperAdmin
                ? Role::whereNotNull('id_academy')->with('academy')->orderBy('name')->get()->groupBy(fn ($role) => $role->academy->name)
                : Role::forCurrentAcademy()->orderBy('name')->get(),
        ]);
    }

    public function store(StaffPositionFormRequest $request)
    {
        try {

            $this->staffPositionService->create($request->validated());

            return redirect()
                ->route('staff-positions.index')
                ->with('success', __('Staff position berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan staff position'));
        }
    }

    public function edit(StaffPosition $staffPosition)
    {
        return view('staff-positions.edit', [
            'title' => __('Edit Staff Position'),
            'breadcrumb' => [
                ['label' => __('Staff Position'), 'url' => route('staff-positions.index')],
                ['label' => __('Edit Staff Position')],
            ],
            'staffPosition' => $staffPosition,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            // Academy record sudah pasti (edit, bukan create) -- cukup role
            // milik academy itu, tidak perlu grouping optgroup lagi.
            'roles' => Role::where('id_academy', $staffPosition->id_academy)->orderBy('name')->get(),
        ]);
    }

    public function update(StaffPositionFormRequest $request, StaffPosition $staffPosition)
    {
        try {

            $this->staffPositionService->update($staffPosition, $request->validated());

            return redirect()
                ->route('staff-positions.index')
                ->with('success', __('Staff position berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui staff position'));
        }
    }

    public function destroy(StaffPosition $staffPosition)
    {
        try {

            $this->staffPositionService->delete($staffPosition);

            return redirect()
                ->route('staff-positions.index')
                ->with('success', __('Staff position berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus staff position'), 'staff-positions.index');
        }
    }
}
```

**✅ Cek dulu**: `php -l app/Http/Controllers/StaffPositionController.php` tidak ada syntax error.

---

## Tahap 4 — `StaffPositionService`

`app/Services/StaffPositionService.php`:

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Role;
use App\Models\StaffPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StaffPositionService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    public function paginate(?int $perPage = null)
    {
        return StaffPosition::with(['academy', 'role'])
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar staff position untuk dropdown di form Staff (issue11.md).
     * Pola sama persis PlayerTypeService::selectable().
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return StaffPosition::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_staff_position', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): StaffPosition
    {
        return DB::transaction(function () use ($data) {

            return StaffPosition::create([
                'id_academy' => $this->resolveAcademyId($data),
                'role_id' => $data['role_id'] ?? null,
                'code' => $data['code'],
                'name' => $data['name'],
                'is_coach' => $data['is_coach'] ?? false,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(StaffPosition $staffPosition, array $data): StaffPosition
    {
        return DB::transaction(function () use ($staffPosition, $data) {

            // id_academy sengaja TIDAK ikut diubah -- sama alasan PlayerType.
            $staffPosition->update([
                'role_id' => $data['role_id'] ?? null,
                'code' => $data['code'],
                'name' => $data['name'],
                'is_coach' => $data['is_coach'] ?? false,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $staffPosition;
        });
    }

    /**
     * Guard delete DITAMBAHKAN di issue11.md Tahap 9 (cek relasi ke staff)
     * setelah tabel `staff` ada. Untuk sekarang, delete polos.
     */
    public function delete(StaffPosition $staffPosition): bool
    {
        return DB::transaction(fn () => $staffPosition->delete());
    }

    /**
     * Buat staff position default untuk academy baru dari
     * config('faos.staff_position_templates'). WAJIB dipanggil SETELAH
     * RoleService::createDefaultRoles() -- role_id di-resolve dengan
     * mencari role yang namanya cocok ('default_role') pada academy yang
     * SAMA. Kalau role belum ada (urutan pemanggilan salah), role_id
     * jatuh ke null -- tidak error, tapi kehilangan nilai default-nya.
     */
    public function createDefaultStaffPositions(Academy $academy): void
    {
        foreach (config('faos.staff_position_templates') as $name => $attributes) {

            $roleId = null;

            if (! empty($attributes['default_role'])) {
                $roleId = Role::where('id_academy', $academy->id_academy)
                    ->where('name', $attributes['default_role'])
                    ->value('id');
            }

            StaffPosition::create([
                'id_academy' => $academy->id_academy,
                'role_id' => $roleId,
                'code' => $attributes['code'],
                'name' => $name,
                'is_coach' => $attributes['is_coach'] ?? false,
                'description' => $attributes['description'] ?? null,
                'status' => true,
            ]);
        }
    }
}
```

**✅ Cek dulu**: `php -l app/Services/StaffPositionService.php` tidak ada syntax error. Verifikasi penuh menyusul Tahap 7.

---

## Tahap 5 — Form Request

`app/Http/Requests/StaffPosition/StaffPositionFormRequest.php`:

```php
<?php

namespace App\Http\Requests\StaffPosition;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffPositionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academyService = app(AcademyService::class);

        $academyId = $academyService->isSuperAdmin()
            ? $this->input('id_academy')
            : $academyService->currentId();

        return [
            'id_academy' => [
                $academyService->isSuperAdmin() ? 'required' : 'prohibited',
                'uuid',
                'exists:academies,id_academy',
            ],

            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('staff_positions', 'code')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('staff_position')?->id_staff_position, 'id_staff_position'),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('staff_positions', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('staff_position')?->id_staff_position, 'id_staff_position'),
            ],

            // Nullable -- "Default Role" boleh kosong, admin isi belakangan.
            // exists() difilter id_academy supaya tidak bisa pilih role dari
            // academy lain (roles.id itu bigint, BUKAN uuid seperti FK lain).
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('id_academy', $academyId)),
            ],

            'is_coach' => ['required', 'boolean'],

            'description' => ['nullable', 'string'],

            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => __('Academy wajib dipilih.'),
            'id_academy.prohibited' => __('Academy tidak dapat dipilih.'),
            'id_academy.uuid' => __('Academy tidak valid.'),
            'id_academy.exists' => __('Academy tidak ditemukan.'),

            'code.required' => __('Kode jabatan wajib diisi.'),
            'code.string' => __('Kode jabatan harus berupa teks.'),
            'code.max' => __('Kode jabatan maksimal :max karakter.'),
            'code.unique' => __('Kode jabatan sudah digunakan pada academy ini.'),

            'name.required' => __('Nama jabatan wajib diisi.'),
            'name.string' => __('Nama jabatan harus berupa teks.'),
            'name.max' => __('Nama jabatan maksimal :max karakter.'),
            'name.unique' => __('Nama jabatan sudah digunakan pada academy ini.'),

            'role_id.integer' => __('Default role tidak valid.'),
            'role_id.exists' => __('Default role tidak ditemukan pada academy ini.'),

            'is_coach.required' => __('Status pelatih wajib ditentukan.'),
            'is_coach.boolean' => __('Status pelatih tidak valid.'),

            'description.string' => __('Deskripsi harus berupa teks.'),

            'status.required' => __('Status wajib ditentukan.'),
            'status.boolean' => __('Status tidak valid.'),
        ];
    }
}
```

**✅ Cek dulu**: submit form create dengan `code` duplikat di academy yang sama → pesan "Kode jabatan sudah digunakan pada academy ini." (verifikasi penuh setelah Tahap 8).

---

## Tahap 6 — Routes, Permission, Config

### 6a. Routes

`routes/web.php` — tambah import:

```php
use App\Http\Controllers\StaffPositionController;
```

Tambahkan block route, setelah block `employment-types` (dari `issue9.md`):

```php
    /*
    |--------------------------------------------------------------------------
    | Staff Position Management
    |--------------------------------------------------------------------------
    */
    Route::resource('staff-positions', StaffPositionController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:staff_position.view')
        ->middlewareFor(['create', 'store'], 'permission:staff_position.create')
        ->middlewareFor(['edit', 'update'], 'permission:staff_position.update')
        ->middlewareFor('destroy', 'permission:staff_position.delete');
```

### 6b. Permission — `database/seeders/RolePermissionSeeder.php`

Tambahkan ke array `$permissions`, setelah blok `// Employment Type` (dari `issue9.md`):

```php
            // Staff Position
            'staff_position.view',
            'staff_position.create',
            'staff_position.update',
            'staff_position.delete',
```

### 6c. `config/faos.php`

Tambahkan ke `role_templates.Owner`, setelah baris `employment_type.*` (dari `issue9.md`):

```php
            'staff_position.view', 'staff_position.create', 'staff_position.update', 'staff_position.delete',
```

Tambahkan section config baru, setelah block `employment_type_templates`:

```php
    /*
    |--------------------------------------------------------------------------
    | Staff Position Template
    |--------------------------------------------------------------------------
    | Staff Position default yang otomatis dibuat untuk setiap academy baru.
    |
    | 'default_role' merujuk ke NAMA role di config('role_templates') di atas
    | (Owner/Coach/Staff/Finance/Player/Parent) -- di-resolve jadi role_id
    | (bigint) saat academy baru dibuat, lihat
    | StaffPositionService::createDefaultStaffPositions().
    |
    | Tidak ada role default bernama "Admin" -- posisi "Admin" sengaja
    | dipetakan ke role "Staff" (cakupan izin paling dekat), BUKAN dibiarkan
    | null dan BUKAN bikin role baru.
    */

    'staff_position_templates' => [

        'Head Coach' => [
            'code' => 'HC', 'is_coach' => true, 'default_role' => 'Coach',
            'description' => 'Pelatih kepala, penanggung jawab utama program latihan.',
        ],

        'Assistant Coach' => [
            'code' => 'AC', 'is_coach' => true, 'default_role' => 'Coach',
            'description' => 'Pelatih asisten, membantu Head Coach.',
        ],

        'Goalkeeper Coach' => [
            'code' => 'GK', 'is_coach' => true, 'default_role' => 'Coach',
            'description' => 'Pelatih khusus penjaga gawang.',
        ],

        'Finance Manager' => [
            'code' => 'FM', 'is_coach' => false, 'default_role' => 'Finance',
            'description' => 'Penanggung jawab keuangan academy.',
        ],

        'Finance Staff' => [
            'code' => 'FS', 'is_coach' => false, 'default_role' => 'Finance',
            'description' => 'Staff administrasi keuangan.',
        ],

        'Academy Director' => [
            'code' => 'AD', 'is_coach' => false, 'default_role' => 'Owner',
            'description' => 'Direktur/penanggung jawab academy.',
        ],

        'Admin' => [
            'code' => 'ADM', 'is_coach' => false, 'default_role' => 'Staff',
            'description' => 'Staff administrasi umum academy.',
        ],

    ],
```

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
```

Harus sukses. `php artisan tinker` → `\Spatie\Permission\Models\Permission::where('name','like','staff_position.%')->count()` → `4`.

---

## Tahap 7 — Wiring `AcademyManagementService`

`app/Services/AcademyManagementService.php`:

Tambah import:

```php
use App\Services\StaffPositionService;
```

Tambah property + constructor param (setelah `EmploymentTypeService` yang ditambahkan `issue9.md`):

```php
    protected StaffPositionService $staffPositionService;

    public function __construct(
        RoleService $roleService,
        PlayerTypeService $playerTypeService,
        PlayerCategoryService $playerCategoryService,
        EmploymentTypeService $employmentTypeService,
        StaffPositionService $staffPositionService,
        AccountService $accountService
    ) {
        $this->roleService = $roleService;
        $this->playerTypeService = $playerTypeService;
        $this->playerCategoryService = $playerCategoryService;
        $this->employmentTypeService = $employmentTypeService;
        $this->staffPositionService = $staffPositionService;
        $this->accountService = $accountService;
    }
```

Di method `create()`, tambahkan baris **setelah** `$this->employmentTypeService->createDefaultEmploymentTypes($academy);` (urutan ini WAJIB — `createDefaultStaffPositions()` butuh role yang sudah dibuat `$this->roleService->createDefaultRoles($academy);` di baris paling atas, yang sudah lebih dulu dipanggil):

```php
            $this->staffPositionService->createDefaultStaffPositions($academy);
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$svc = app(\App\Services\AcademyManagementService::class);
$academy = $svc->create([
    'name' => 'Test Office FC 2', 'code' => 'TOF2', 'phone' => '08123456', 'email' => 't2@t.com',
    'address' => 'Jl. Test', 'tagline' => 'Test', 'subscription_type' => 'monthly',
    'subscription_fee' => 100000, 'subscription_started_at' => now(), 'subscription_ends_at' => now()->addMonth(),
    'primary_color' => '#465fff',
]);

\App\Models\StaffPosition::where('id_academy', $academy->id_academy)->with('role')->get()
    ->each(fn ($p) => print($p->name . ' -> ' . ($p->role->name ?? 'NULL') . PHP_EOL));
// Head Coach -> Coach, Assistant Coach -> Coach, Goalkeeper Coach -> Coach,
// Finance Manager -> Finance, Finance Staff -> Finance,
// Academy Director -> Owner, Admin -> Staff
// SEMUA harus terisi (bukan NULL) -- kalau ada yang NULL, cek urutan
// pemanggilan createDefaultStaffPositions() vs createDefaultRoles().
```

---

## Tahap 8 — Views

**Tujuan**: 3 view mengikuti pola `player-types/*.blade.php`, tambah kolom/field `code`, toggle `is_coach`, dan dropdown `role_id`.

`resources/views/staff-positions/index.blade.php` — sama struktur `employment-types/index.blade.php` (`issue9.md` Tahap 8), dengan penyesuaian:
- Kolom tabel tambahan setelah "Staff Position": **Kode** (`{{ $staffPosition->code }}`), **Default Role** (`{{ $staffPosition->role->name ?? '-' }}`), **Pelatih** (badge `Ya`/`-` dari `is_coach`).
- `@can('staff_position.create')`/`update`/`delete` (ganti semua `employment_type.*` jadi `staff_position.*`, semua `employment-types.*` jadi `staff-positions.*`, `$employmentType` jadi `$staffPosition`).
- Card List mobile: tambahkan field yang sama (Kode, Default Role, Pelatih) di `table-card-body`.
- Judul: `__('Staff Position List')`, deskripsi `__('Manajemen jabatan staff (Head Coach, Finance Manager, dsb) per academy.')`.

`resources/views/staff-positions/create.blade.php`:

```blade
@extends('layouts.app', ['page' => 'staff-positions'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Staff Position') }}</h3>
                <p class="card-description">{{ __('Tambahkan jabatan staff baru untuk academy.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('staff-positions.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('staff-positions.store') }}" method="POST">
            @csrf

            <div class="form-row">

                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Academy') }} <span class="text-error-500">*</span>
                            </label>

                            <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror"
                                required>
                                <option value="">{{ __('Pilih Academy') }}</option>
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

                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Nama Jabatan') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="{{ __('Contoh: Head Coach, Finance Manager') }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Kode Jabatan') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="code" value="{{ old('code') }}"
                            placeholder="{{ __('Contoh: HC, FM') }}"
                            class="form-input @error('code') form-danger @enderror" required>

                        @error('code')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Deskripsi') }}</label>

                        <textarea name="description" rows="3" placeholder="{{ __('Keterangan singkat tentang jabatan ini') }}"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description') }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Default Role') }}</label>

                        @if ($isSuperAdmin)
                            <select name="role_id" class="form-select @error('role_id') form-danger @enderror">
                                <option value="">{{ __('Tidak ada / atur manual nanti') }}</option>
                                @foreach ($roles as $academyName => $academyRoles)
                                    <optgroup label="{{ $academyName }}">
                                        @foreach ($academyRoles as $role)
                                            <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        @else
                            <select name="role_id" class="form-select @error('role_id') form-danger @enderror">
                                <option value="">{{ __('Tidak ada / atur manual nanti') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <p class="mt-1 text-xs text-gray-400">
                            {{ __('Dipakai sebagai pilihan awal saat staff dengan jabatan ini dibuatkan akun login -- tetap bisa diganti saat itu.') }}
                        </p>

                        @error('role_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group" x-data="{ isCoach: {{ old('is_coach', 0) ? 'true' : 'false' }} }">

                        <label class="form-label">{{ __('Pelatih') }}</label>

                        <input type="hidden" name="is_coach" :value="isCoach ? 1 : 0">

                        <label class="flex cursor-pointer items-center">

                            <input type="checkbox" class="sr-only" :checked="isCoach" @change="isCoach = !isCoach">

                            <div class="form-toggle" :class="isCoach && 'form-toggle-active'">
                                <span class="form-toggle-dot" :class="isCoach && 'form-toggle-checked'"></span>
                            </div>

                            <span class="ml-3 text-sm text-gray-500"
                                x-text="isCoach ? '{{ __('Jabatan ini berperan sebagai pelatih') }}' : '{{ __('Bukan jabatan pelatih') }}'">
                            </span>

                        </label>

                        @error('is_coach')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    <div class="form-group" x-data="{ isActive: {{ old('status', 1) ? 'true' : 'false' }} }">

                        <label class="form-label">{{ __('Status') }}</label>

                        <input type="hidden" name="status" :value="isActive ? 1 : 0">

                        <label class="flex cursor-pointer items-center">

                            <input type="checkbox" class="sr-only" :checked="isActive" @change="isActive = !isActive">

                            <div class="form-toggle" :class="isActive && 'form-toggle-active'">
                                <span class="form-toggle-dot" :class="isActive && 'form-toggle-checked'"></span>
                            </div>

                            <span class="ml-3 text-sm text-gray-500" x-text="isActive ? '{{ __('Aktif') }}' : '{{ __('Nonaktif') }}'">
                            </span>

                        </label>

                        @error('status')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <button type="reset" class="btn btn-secondary">
                    {{ __('Reset') }}
                </button>

                <button type="submit" class="btn btn-primary">
                    {{ __('Simpan Staff Position') }}
                </button>

            </div>

        </form>

    </div>

@endsection
```

`resources/views/staff-positions/edit.blade.php` — sama seperti `create.blade.php` tapi:
- Field Academy (kalau Super Admin) jadi teks read-only.
- Dropdown `role_id` **selalu flat** (tidak perlu `@if ($isSuperAdmin)`/optgroup — lihat Tahap 3, `edit()` controller sudah kirim `$roles` terscope ke academy record yang sedang diedit).
- `value`/`old()` semua field pakai default `$staffPosition->xxx` (`$staffPosition->role_id` untuk `@selected` dropdown role).
- Form action `route('staff-positions.update', $staffPosition)`, `@method('PUT')`.
- Tombol footer: `Batal` (link) + `Update Staff Position`.

**✅ Cek dulu**: buka `/staff-positions` (Owner) → 7 posisi default tampil dengan Default Role masing-masing sesuai Tahap 7. Buka `/staff-positions/create` sebagai Super Admin → dropdown Default Role terkelompok per academy (`<optgroup>`). Submit dengan `code` duplikat → error tervalidasi.

---

## Tahap 9 — Menu Sidebar

`resources/views/partials/sidebar.blade.php` — ubah `$officeRoutes` (ditambah `issue9.md`):

```php
                        $officeRoutes = ['staff-positions.*', 'employment-types.*'];
```

Sisipkan `<li>` baru **SEBELUM** `<li>` "Employment Type" yang sudah ada (tepat setelah komentar `{{-- Staff & Staff Position disisipkan di sini... --}}`):

```blade
                                {{-- Staff Position --}}
                                @can('staff_position.view')
                                    <li>
                                        <a href="{{ route('staff-positions.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('staff-positions.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Staff Position') }}
                                        </a>
                                    </li>
                                @endcan
```

> Komentar `{{-- Staff & Staff Position disisipkan di sini... --}}` boleh dihapus sekarang (cuma "Staff" yang tersisa, akan disisipkan `issue11.md`) atau dibiarkan — tidak mempengaruhi rendering.

`lang/en.json` — tambahkan entry baru (cek dulu duplikat):

```json
"Staff Position": "Staff Position",
"Staff Position List": "Staff Position List",
"Manajemen jabatan staff (Head Coach, Finance Manager, dsb) per academy.": "Manage staff positions (Head Coach, Finance Manager, etc.) per academy.",
"Tambah Staff Position": "Add Staff Position",
"Belum ada Staff Position": "No Staff Positions Yet",
"Tambahkan staff position pertama.": "Add the first staff position.",
"Informasi Staff Position": "Staff Position Information",
"Tambahkan jabatan staff baru untuk academy.": "Add a new staff position for the academy.",
"Perbarui detail jabatan staff.": "Update the staff position details.",
"Nama Jabatan": "Position Name",
"Contoh: Head Coach, Finance Manager": "Example: Head Coach, Finance Manager",
"Kode Jabatan": "Position Code",
"Contoh: HC, FM": "Example: HC, FM",
"Keterangan singkat tentang jabatan ini": "Brief description of this position",
"Default Role": "Default Role",
"Dipakai sebagai pilihan awal saat staff dengan jabatan ini dibuatkan akun login -- tetap bisa diganti saat itu.": "Used as the initial selection when a staff member with this position gets a login account created -- still changeable at that time.",
"Tidak ada / atur manual nanti": "None / set manually later",
"Pelatih": "Coach",
"Jabatan ini berperan sebagai pelatih": "This position acts as a coach",
"Bukan jabatan pelatih": "Not a coaching position",
"Simpan Staff Position": "Save Staff Position",
"Update Staff Position": "Update Staff Position",
"Staff position berhasil ditambahkan.": "Staff position added successfully.",
"Staff position berhasil diperbarui.": "Staff position updated successfully.",
"Staff position berhasil dihapus.": "Staff position deleted successfully.",
"Kode jabatan wajib diisi.": "Position code is required.",
"Kode jabatan harus berupa teks.": "Position code must be text.",
"Kode jabatan maksimal :max karakter.": "Position code may not exceed :max characters.",
"Kode jabatan sudah digunakan pada academy ini.": "This position code is already used in this academy.",
"Nama jabatan wajib diisi.": "Position name is required.",
"Nama jabatan harus berupa teks.": "Position name must be text.",
"Nama jabatan maksimal :max karakter.": "Position name may not exceed :max characters.",
"Nama jabatan sudah digunakan pada academy ini.": "This position name is already used in this academy.",
"Default role tidak valid.": "Invalid default role.",
"Default role tidak ditemukan pada academy ini.": "Default role not found in this academy.",
"Status pelatih wajib ditentukan.": "Coach status is required.",
"Status pelatih tidak valid.": "Invalid coach status."
```

**✅ Cek dulu**: dropdown "Office" sekarang berisi 2 item, urutan **Staff Position** lalu **Employment Type**. Ganti locale Inggris → seluruh teks halaman Staff Position + menu tampil Bahasa Inggris.

---

## Tahap 10 — Test & Dokumentasi

### 10a. `tests/Feature/StaffPositionTest.php` — file baru

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\AcademyManagementService;
use App\Services\StaffPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_staff_position_terpetakan_ke_role_yang_benar(): void
    {
        $academy = app(AcademyManagementService::class)->create([
            'name' => 'FC Test', 'code' => 'FCTEST', 'phone' => '08123456', 'email' => 'fc@test.com',
            'address' => 'Jl. Test', 'tagline' => 'Test', 'subscription_type' => 'monthly',
            'subscription_fee' => 100000, 'subscription_started_at' => now(), 'subscription_ends_at' => now()->addMonth(),
            'primary_color' => '#465fff',
        ]);

        $positions = StaffPosition::where('id_academy', $academy->id_academy)
            ->with('role')
            ->get()
            ->pluck('role.name', 'name');

        $this->assertSame('Coach', $positions['Head Coach']);
        $this->assertSame('Finance', $positions['Finance Manager']);
        $this->assertSame('Owner', $positions['Academy Director']);
        $this->assertSame('Staff', $positions['Admin']);
    }

    public function test_role_id_boleh_null(): void
    {
        $academy = Academy::factory()->create();

        $position = app(StaffPositionService::class)->create([
            'code' => 'TST', 'name' => 'Test Position', 'is_coach' => false, 'status' => true,
        ]);

        $this->assertNull($position->role_id);
    }

    public function test_role_dari_academy_lain_ditolak_form_request(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $roleAcademyB = Role::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'RoleB']);

        $owner = User::factory()->create(['id_academy' => $academyA->id_academy, 'status' => true]);
        $ownerRole = Role::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Owner']);
        $owner->assignRole($ownerRole);

        $this->actingAs($owner);

        $response = $this->post(route('staff-positions.store'), [
            'code' => 'TST', 'name' => 'Test Position', 'is_coach' => 0, 'status' => 1,
            'role_id' => $roleAcademyB->id,
        ]);

        $response->assertSessionHasErrors('role_id');
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=StaffPositionTest
php artisan test
```

Seluruh test pass, termasuk full suite (baseline + test baru dari `issue9.md` + brief ini, tidak ada regresi).

### 10b. `docs/permission-reference.md`

Tambahkan section baru (format sama seperti `issue9.md` Tahap 10b), setelah section Employment Type:

```markdown
## Module: Staff Position

Status: **✅ Implemented**

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `staff_position.view` | Lihat daftar staff position | `staff-positions.index` (route middleware) |
| `staff_position.create` | Tambah staff position baru | `staff-positions.create`, `staff-positions.store` (route middleware) + `@can()` tombol "Tambah" |
| `staff_position.update` | Ubah staff position | `staff-positions.edit`, `staff-positions.update` (route middleware) + `@can()` tombol Edit |
| `staff_position.delete` | Hapus staff position | `staff-positions.destroy` (route middleware) + `@can()` tombol Hapus |

Catatan:
- Isolasi antar academy memakai `AcademyScope` — akses lintas academy = **404**. Default: Owner-only lewat `config('faos.role_templates')`.
- Field `role_id` (Default Role) merujuk ke `roles.id` (**bigint**, bukan uuid seperti FK lain) — divalidasi ulang di `StaffPositionFormRequest` supaya role yang dipilih benar-benar milik academy yang sama (role tenant-scoped per academy, lihat `docs/authorization.md` → *Role Academy Based*).
- Guard delete ("masih dipakai staff") **belum aktif** di brief ini — ditambahkan saat `issue11.md` selesai.
```

**✅ Cek dulu**: `docs/permission-reference.md` section baru muncul + TOC ter-update.

---

## 4. Alasan Teknis

### 4.1 Kenapa `role_id` bertipe bigint, bukan uuid seperti FK lain

Seluruh tabel di FAOSBall pakai UUID sebagai primary key (`docs/module-standard.md` → *Database Standard*) — **kecuali** `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` yang datang dari package `spatie/laravel-permission` (`database/migrations/2026_06_25_143759_create_permission_tables.php`), yang secara default memakai `$table->id()` (bigint auto-increment). FAOSBall memang menambahkan kolom `id_academy` (uuid) ke `roles` lewat migration tambahan, tapi **PK `roles` itu sendiri tidak diubah** — tetap bigint. Konsekuensinya, foreign key APA PUN yang menunjuk ke `roles.id` (seperti `staff_positions.role_id` di brief ini) **wajib** ikut tipe bigint, bukan uuid — kalau dipaksa uuid, migration akan gagal (`Illegal mix of collations`/tipe FK tidak cocok saat `Schema::create()` mencoba membuat foreign key constraint).

### 4.2 Kenapa dropdown Role di form Staff Position dikelompokkan per academy (Super Admin), bukan Alpine filter client-side seperti Player Type/Category di form Player

Form Player (`players/create.blade.php`) memfilter dropdown Type/Category berdasarkan academy yang dipilih pakai Alpine computed property (`availableTypes`), karena SEMUA type/category dikirim penuh ke client lalu difilter di JS. Untuk Role, pendekatan itu dihindari di brief ini supaya tidak perlu menduplikasi logic Alpine yang kompleks tanpa referensi kode yang terverifikasi persis — pendekatan `<optgroup>` per academy (di-render penuh di server, tanpa JS filtering) sama-sama mencegah salah pilih role dari academy lain, lebih sederhana untuk diimplementasikan dengan benar, dan tetap sesuai prinsip "server-render penuh, dropdown FK tidak pakai AJAX/select2" yang berlaku di seluruh app ini.
