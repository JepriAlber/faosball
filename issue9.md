# Brief: Modul Employment Type (bagian 1/3 dari "Office")

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `docs/module-standard.md`, `docs/development-guide.md`, `docs/coding-standard.md` (*Bahasa Pesan & Multi-Language*), `docs/multi-tenancy.md`, `docs/authorization.md`, `docs/frontend-standard.md` (*Urutan & Pengelompokan Field Form*, *Tabel Responsif*). Modul referensi paling mirip yang **wajib** dibuka dulu sebagai template: `app/Models/PlayerType.php`, `app/Services/PlayerTypeService.php`, `app/Http/Controllers/PlayerTypeController.php`, `app/Http/Requests/PlayerType/PlayerTypeFormRequest.php`, `resources/views/player-types/*.blade.php`.
> **Bagian dari brief besar "Office"**: Office adalah modul payung untuk pengelolaan staff academy, terdiri dari **3 brief berurutan** yang saling bergantung lewat foreign key — **wajib dikerjakan urut**:
> 1. **`issue9.md` (brief ini) — Employment Type** — master data jenis pekerjaan staff (Permanent, Contract, Intern, dst). Tidak bergantung ke brief lain.
> 2. **`issue10.md` — Staff Position** — master data jabatan staff (Head Coach, Finance Manager, dst) + field `role_id` (Default Role). Tidak bergantung ke brief ini, tapi **dikerjakan setelah** brief ini (urutan penempatan menu sidebar).
> 3. **`issue11.md` — Staff** — entitas utama (data staff + akun login opsional). **Bergantung** ke `employment_types` (brief ini) dan `staff_positions` (`issue10.md`) sebagai foreign key wajib, jadi harus dikerjakan **terakhir**.
>
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 10** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: CRUD master data Employment Type (tenant-scoped, per academy) — persis pola Player Type/Player Category, **tanpa** fitur account/foto (itu khusus modul Staff di `issue11.md`). Sekalian brief ini yang membuat **dropdown menu "Office"** di sidebar (kosong isinya sampai `issue10.md`/`issue11.md` menambah item mereka sendiri). **Bukan** scope: guard delete berbasis relasi ke `staff` (tabel `staff` belum ada — guard itu ditambahkan belakangan di `issue11.md` Tahap 9, lihat [Aturan Emas](#0-aturan-emas)).

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Tambah guard delete "masih dipakai staff, tidak dapat dihapus" di brief ini | Tabel `staff` **belum ada** sampai `issue11.md` selesai — memanggil relasi ke model yang belum ada akan error. Guard delete ditambahkan belakangan di `issue11.md` Tahap 9 (edit `EmploymentTypeService::delete()` yang dibuat brief ini) | [Tahap 4](#tahap-4--employmenttypeservice), `issue11.md` |
| Taruh business logic (generate default, filter, guard) di Model/Controller | Model `EmploymentType` cuma Fillable/Cast/Relasi (`docs/module-standard.md`), Controller cuma terima request → panggil Service → return response. Logic wajib di `EmploymentTypeService` | [Tahap 4](#tahap-4--employmenttypeservice) |
| Extend `Model` langsung untuk `EmploymentType` | Wajib `extends FaosModel` (bukan `extends Model`) — otomatis dapat UUID PK auto-generate + trait `BelongsToAcademy` (global scope tenant + auto-isi `id_academy`). Beda dengan `PlayerPosition` yang sengaja `extends Model` biasa karena tabelnya **global** (tanpa `id_academy`) — Employment Type BUKAN kasus itu, ini master data **per-academy** | [Tahap 2](#tahap-2--model-employmenttype) |
| Bikin migration `add_id_employment_type_to_staff_table` di brief ini | Tabel `staff` belum ada. Kolom FK `staff.id_employment_type` dibuat langsung di migration `create_staff_table` milik `issue11.md`, bukan migration tambahan di sini | — |
| Tambahkan item "Employment Type" ke dropdown "Office" dengan asumsi "Staff"/"Staff Position" juga sudah ada linknya | Brief ini **satu-satunya** yang membuat struktur dropdown "Office" dari nol — isinya cuma 1 item (Employment Type) dulu. `issue10.md` & `issue11.md` menyisipkan `<li>` mereka sendiri ke dropdown yang **sudah ada** ini, di posisi yang sudah ditentukan (lihat Tahap 9) | [Tahap 9](#tahap-9--menu-sidebar-dropdown-office) |
| Bikin permission baru selain `employment_type.view/create/update/delete` | 4 permission CRUD standar sudah cukup, ikuti pola persis `player_type.*` — jangan tambah permission granular yang tidak diminta | [Tahap 6](#tahap-6--permission) |

---

## 1. Konteks & Tujuan

Modul "Office" mengelola staff academy (coach, admin, finance, dst) — analog dengan bagaimana modul Player mengelola data pemain. Sebelum entitas utama `Staff` bisa dibangun, dia butuh 2 master data sebagai foreign key wajib: **jenis pekerjaan** (Employment Type: Permanent/Contract/Intern/dst) dan **jabatan** (Staff Position: Head Coach/Finance Manager/dst — brief terpisah, `issue10.md`).

Brief ini membangun **Employment Type** — pola CRUD-nya **identik 1:1** dengan `PlayerType`/`PlayerCategory` yang sudah ada: master data sederhana, tenant-scoped per academy, isolasi lewat `AcademyScope` (akses lintas academy = **404**, bukan 403), tanpa field aneh-aneh.

```text
Office (dropdown baru di sidebar)
│
├── Staff              -- issue11.md (belum ada sampai brief itu selesai)
├── Staff Position      -- issue10.md (belum ada sampai brief itu selesai)
└── Employment Type     -- BRIEF INI
```

## 2. Cara Kerja Solusi

### 2a. Tiru PlayerType 1:1, ganti nama saja

Tidak ada mekanisme baru yang perlu ditemukan — migration, model, service, controller, form request, routes, views SEMUA meniru struktur `PlayerType` persis, cuma nama entitas diganti (`PlayerType` → `EmploymentType`, `player_type` → `employment_type`, dst). Ini bukan sekadar preferensi kode-mirip — `docs/module-standard.md` eksplisit meminta reuse pola existing untuk module master data sederhana.

### 2b. Field lebih sedikit dari PlayerType

Employment Type **tidak** butuh `is_billable` (itu spesifik konteks pemain/SPP) — field-nya cukup `name`, `description`, `status`. Struktur query/service/view yang butuh field itu (toggle `is_billable` di form, kolom "Tagihan" di tabel) dihilangkan, sisanya identik.

### 2c. Menu "Office" dibangun bertahap lintas 3 brief

Brief ini membuat **struktur dropdown "Office"** di sidebar (heading group + 1 item dropdown + submenu) berisi **cuma 1 link** (Employment Type) — karena route `staff.*`/`staff-positions.*` belum ada, menautkannya sekarang akan menghasilkan link mati (`RouteNotFoundException` kalau nekat dipanggil, atau tombol yang 404 kalau di-hardcode URL). `issue10.md` dan `issue11.md` masing-masing menyisipkan `<li>` baru ke dropdown yang sudah ada ini di posisi yang sudah ditentukan, sampai akhirnya urutannya jadi **Staff → Staff Position → Employment Type** (sesuai urutan diagram) setelah ketiga brief selesai.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/..._create_employment_types_table.php` | 🆕 Baru | 1 |
| `app/Models/EmploymentType.php` | 🆕 Baru | 2 |
| `database/factories/EmploymentTypeFactory.php` | 🆕 Baru | 2 |
| `app/Services/EmploymentTypeService.php` | 🆕 Baru | 4 |
| `app/Http/Requests/EmploymentType/EmploymentTypeFormRequest.php` | 🆕 Baru | 5 |
| `app/Http/Controllers/EmploymentTypeController.php` | 🆕 Baru | 3 |
| `routes/web.php` | ✏️ Tambah resource route `employment-types` | 6 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah 4 permission + panggilan `createDefaultEmploymentTypes()` | 6 |
| `config/faos.php` | ✏️ Tambah `role_templates.Owner` + `employment_type_templates` | 6 |
| `app/Services/AcademyManagementService.php` | ✏️ Inject `EmploymentTypeService`, panggil `createDefaultEmploymentTypes()` di `create()` | 7 |
| `resources/views/employment-types/index.blade.php` | 🆕 Baru | 8 |
| `resources/views/employment-types/create.blade.php` | 🆕 Baru | 8 |
| `resources/views/employment-types/edit.blade.php` | 🆕 Baru | 8 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Tambah dropdown "Office" (1 item) | 9 |
| `lang/en.json` | ✏️ Entry baru | 9 |
| `tests/Feature/EmploymentTypeTest.php` | 🆕 Baru | 10 |
| `docs/permission-reference.md` | ✏️ Tambah section Employment Type | 10 |
| `README.md` | ✏️ Tidak dicentang dulu (Staff Management dicentang di `issue11.md` setelah ketiganya selesai) | — |

---

## Tahap 1 — Migration

```bash
php artisan make:migration create_employment_types_table
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
        Schema::create('employment_types', function (Blueprint $table) {

            $table->uuid('id_employment_type')->primary();

            $table->uuid('id_academy');

            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index('id_academy');
            $table->unique(['id_academy', 'name'], 'employment_types_academy_name_unique');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_types');
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table employment_types
```

Harus ada kolom `id_employment_type` (uuid, PK), `id_academy` (uuid), `name`, `description` (nullable), `status` (boolean, default 1), index `id_academy`, unique `(id_academy, name)`.

---

## Tahap 2 — Model `EmploymentType`

`app/Models/EmploymentType.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentType extends FaosModel
{
    use HasFactory;

    protected $table = 'employment_types';
    protected $primaryKey = 'id_employment_type';

    protected $fillable = ['id_academy', 'name', 'description', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    // relasi staff() BELUM ada di sini -- ditambahkan issue11.md Tahap 9
    // setelah tabel `staff` dibuat (lihat Aturan Emas brief ini).
}
```

> `extends FaosModel` (bukan `Model`) — otomatis dapat UUID PK auto-generate + trait `BelongsToAcademy` (global scope tenant, auto-isi `id_academy` saat `creating`). Lihat `app/Models/FaosModel.php` & `app/Traits/BelongsToAcademy.php` kalau perlu verifikasi.

`database/factories/EmploymentTypeFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\EmploymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentType>
 */
class EmploymentTypeFactory extends Factory
{
    protected $model = EmploymentType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'status' => true,
        ];
    }
}
```

**✅ Cek dulu**: `php artisan tinker` → `(new \App\Models\EmploymentType)->getFillable()` harus memuat 4 field di atas.

---

## Tahap 3 — Controller

`app/Http/Controllers/EmploymentTypeController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentType\EmploymentTypeFormRequest;
use App\Models\Academy;
use App\Models\EmploymentType;
use App\Services\AcademyService;
use App\Services\EmploymentTypeService;

class EmploymentTypeController extends Controller
{
    protected EmploymentTypeService $employmentTypeService;
    protected AcademyService $academyService;

    public function __construct(EmploymentTypeService $employmentTypeService, AcademyService $academyService)
    {
        $this->employmentTypeService = $employmentTypeService;
        $this->academyService = $academyService;
    }

    public function index()
    {
        return view('employment-types.index', [
            'title' => __('Employment Type'),
            'breadcrumb' => [
                ['label' => __('Office')],
                ['label' => __('Employment Type')],
            ],
            'employmentTypes' => $this->employmentTypeService->paginate(),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        return view('employment-types.create', [
            'title' => __('Tambah Employment Type'),
            'breadcrumb' => [
                ['label' => __('Employment Type'), 'url' => route('employment-types.index')],
                ['label' => __('Tambah Employment Type')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(EmploymentTypeFormRequest $request)
    {
        try {

            $this->employmentTypeService->create($request->validated());

            return redirect()
                ->route('employment-types.index')
                ->with('success', __('Employment type berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan employment type'));
        }
    }

    public function edit(EmploymentType $employmentType)
    {
        return view('employment-types.edit', [
            'title' => __('Edit Employment Type'),
            'breadcrumb' => [
                ['label' => __('Employment Type'), 'url' => route('employment-types.index')],
                ['label' => __('Edit Employment Type')],
            ],
            'employmentType' => $employmentType,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(EmploymentTypeFormRequest $request, EmploymentType $employmentType)
    {
        try {

            $this->employmentTypeService->update($employmentType, $request->validated());

            return redirect()
                ->route('employment-types.index')
                ->with('success', __('Employment type berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui employment type'));
        }
    }

    public function destroy(EmploymentType $employmentType)
    {
        try {

            $this->employmentTypeService->delete($employmentType);

            return redirect()
                ->route('employment-types.index')
                ->with('success', __('Employment type berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus employment type'), 'employment-types.index');
        }
    }
}
```

**✅ Cek dulu**: `php -l app/Http/Controllers/EmploymentTypeController.php` tidak ada syntax error.

---

## Tahap 4 — `EmploymentTypeService`

`app/Services/EmploymentTypeService.php`:

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\EmploymentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EmploymentTypeService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Daftar employment type untuk halaman index. Tidak perlu filter
     * id_academy manual -- AcademyScope sudah menangani.
     */
    public function paginate(?int $perPage = null)
    {
        return EmploymentType::with('academy')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar employment type untuk dropdown di form Staff (issue11.md).
     *
     * $academyId null -> seluruh academy (Super Admin di form create Staff).
     * $includeId      -> type yang sedang dipakai staff tetap ikut walau
     *                    sudah dinonaktifkan, supaya nilainya tidak hilang
     *                    saat form edit disimpan ulang.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return EmploymentType::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_employment_type', $includeId);
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

    public function create(array $data): EmploymentType
    {
        return DB::transaction(function () use ($data) {

            return EmploymentType::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(EmploymentType $employmentType, array $data): EmploymentType
    {
        return DB::transaction(function () use ($employmentType, $data) {

            // id_academy sengaja TIDAK ikut diubah -- sama alasan PlayerType.
            $employmentType->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $employmentType;
        });
    }

    /**
     * Guard delete DITAMBAHKAN di issue11.md Tahap 9 (cek relasi ke staff)
     * setelah tabel `staff` ada. Untuk sekarang, delete polos.
     */
    public function delete(EmploymentType $employmentType): bool
    {
        return DB::transaction(fn () => $employmentType->delete());
    }

    /**
     * Buat employment type default untuk academy baru dari
     * config('faos.employment_type_templates').
     */
    public function createDefaultEmploymentTypes(Academy $academy): void
    {
        foreach (config('faos.employment_type_templates') as $name => $attributes) {

            EmploymentType::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'status' => true,
            ]);
        }
    }
}
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create();
app(\App\Services\EmploymentTypeService::class)->createDefaultEmploymentTypes($academy);
\App\Models\EmploymentType::where('id_academy', $academy->id_academy)->pluck('name');
// harus kosong dulu -- config belum diisi (Tahap 6). Jalankan ulang setelah Tahap 6.
```

---

## Tahap 5 — Form Request

`app/Http/Requests/EmploymentType/EmploymentTypeFormRequest.php`:

```php
<?php

namespace App\Http\Requests\EmploymentType;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmploymentTypeFormRequest extends FormRequest
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

            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employment_types', 'name')
                    ->where(fn ($query) => $query->where('id_academy', $academyId))
                    ->ignore($this->route('employment_type')?->id_employment_type, 'id_employment_type'),
            ],

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

            'name.required' => __('Nama employment type wajib diisi.'),
            'name.string' => __('Nama employment type harus berupa teks.'),
            'name.max' => __('Nama employment type maksimal :max karakter.'),
            'name.unique' => __('Nama employment type sudah digunakan pada academy ini.'),

            'description.string' => __('Deskripsi harus berupa teks.'),

            'status.required' => __('Status wajib ditentukan.'),
            'status.boolean' => __('Status tidak valid.'),
        ];
    }
}
```

**✅ Cek dulu**: submit form create dengan `name` yang sudah ada di academy yang sama → harus muncul pesan "Nama employment type sudah digunakan pada academy ini." (lakukan setelah Tahap 8 view siap).

---

## Tahap 6 — Routes, Permission, Config

### 6a. Routes

`routes/web.php` — tambah import di atas (urutan alfabetis mengikuti use statement yang sudah ada):

```php
use App\Http\Controllers\EmploymentTypeController;
```

Tambahkan block route baru, setelah block `player-categories` (baris ~175) dan sebelum `Profile`:

```php
    /*
    |--------------------------------------------------------------------------
    | Employment Type Management
    |--------------------------------------------------------------------------
    */
    Route::resource('employment-types', EmploymentTypeController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:employment_type.view')
        ->middlewareFor(['create', 'store'], 'permission:employment_type.create')
        ->middlewareFor(['edit', 'update'], 'permission:employment_type.update')
        ->middlewareFor('destroy', 'permission:employment_type.delete');
```

### 6b. Permission — `database/seeders/RolePermissionSeeder.php`

Tambahkan ke array `$permissions` (di dalam method `run()`), setelah blok `// Player Category`:

```php
            // Employment Type
            'employment_type.view',
            'employment_type.create',
            'employment_type.update',
            'employment_type.delete',
```

### 6c. `config/faos.php`

Tambahkan ke `role_templates.Owner` (array permission Owner), setelah baris `'player_category.view', 'player_category.create', 'player_category.update', 'player_category.delete',`:

```php
            'employment_type.view', 'employment_type.create', 'employment_type.update', 'employment_type.delete',
```

> Hanya Owner (default) -- sama pola dengan `player_type.*`/`player_category.*`. Kalau nanti ada role lain butuh akses, delegasikan lewat halaman Role Management, jangan tambah manual di sini.

Tambahkan section config baru, setelah block `player_category_templates` (sebelum `upload`):

```php
    /*
    |--------------------------------------------------------------------------
    | Employment Type Template
    |--------------------------------------------------------------------------
    | Employment Type default yang otomatis dibuat untuk setiap academy baru.
    | Academy bebas menambah/mengubah lewat menu Employment Type.
    */

    'employment_type_templates' => [

        'Permanent' => ['description' => 'Staff tetap dengan kontrak jangka panjang.'],
        'Contract' => ['description' => 'Staff kontrak dengan jangka waktu tertentu.'],
        'Intern' => ['description' => 'Staff magang/on-the-job training.'],
        'Volunteer' => ['description' => 'Staff sukarelawan, tanpa gaji tetap.'],
        'Part Time' => ['description' => 'Staff paruh waktu.'],
        'Freelance' => ['description' => 'Staff lepas, dibayar per proyek/sesi.'],

    ],
```

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
```

Harus sukses tanpa error. `php artisan tinker` → `\Spatie\Permission\Models\Permission::where('name','like','employment_type.%')->count()` → harus `4`.

---

## Tahap 7 — Wiring `AcademyManagementService`

**Tujuan**: academy baru otomatis dapat 6 employment type default (persis seperti dapat 3 player type / 3 player category default).

`app/Services/AcademyManagementService.php`:

Tambah import:

```php
use App\Services\EmploymentTypeService;
```

Tambah property + constructor param:

```php
    protected EmploymentTypeService $employmentTypeService;

    public function __construct(
        RoleService $roleService,
        PlayerTypeService $playerTypeService,
        PlayerCategoryService $playerCategoryService,
        EmploymentTypeService $employmentTypeService,
        AccountService $accountService
    ) {
        $this->roleService = $roleService;
        $this->playerTypeService = $playerTypeService;
        $this->playerCategoryService = $playerCategoryService;
        $this->employmentTypeService = $employmentTypeService;
        $this->accountService = $accountService;
    }
```

Di method `create()`, tambahkan baris setelah `$this->playerCategoryService->createDefaultPlayerCategories($academy);`:

```php
            $this->employmentTypeService->createDefaultEmploymentTypes($academy);
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$svc = app(\App\Services\AcademyManagementService::class);
$academy = $svc->create([
    'name' => 'Test Office FC', 'code' => 'TOFC', 'phone' => '08123456', 'email' => 't@t.com',
    'address' => 'Jl. Test', 'tagline' => 'Test', 'subscription_type' => 'monthly',
    'subscription_fee' => 100000, 'subscription_started_at' => now(), 'subscription_ends_at' => now()->addMonth(),
    'primary_color' => '#465fff',
]);
\App\Models\EmploymentType::where('id_academy', $academy->id_academy)->pluck('name');
// harus: Permanent, Contract, Intern, Volunteer, Part Time, Freelance
```

---

## Tahap 8 — Views

**Tujuan**: 3 view (`index`, `create`, `edit`) mengikuti pola `player-types/*.blade.php` PERSIS, cuma tanpa toggle `is_billable` dan tanpa kolom "Tagihan"/"Player" (ganti jumlah relasi jadi placeholder kosong dulu — akan diisi jumlah staff di `issue11.md`).

`resources/views/employment-types/index.blade.php`:

```blade
@extends('layouts.app', ['page' => 'employment-types'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Employment Type List') }}</h3>
                <p class="card-description">{{ __('Manajemen jenis pekerjaan staff (Permanent, Contract, Intern, dsb) per academy.') }}</p>
            </div>

            @can('employment_type.create')
                <div class="card-actions">
                    <a href="{{ route('employment-types.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Employment Type') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Employment Type') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($employmentTypes as $employmentType)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $employmentType->name }}</span>
                                    <span class="table-subtitle">{{ $employmentType->description ?? '-' }}</span>
                                </div>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $employmentType->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                @if ($employmentType->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('employment_type.update')
                                        <a href="{{ route('employment-types.edit', $employmentType) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('employment_type.delete')
                                        <x-button.delete :action="route('employment-types.destroy', $employmentType)"
                                            :name="$employmentType->name" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 4 : 3 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Employment Type') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan employment type pertama.') }}</p>

                                    @can('employment_type.create')
                                        <a href="{{ route('employment-types.create') }}" class="empty-link">{{ __('Tambah Employment Type') }}</a>
                                    @endcan

                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <!-- Card List (mobile & tablet) -->
        <div class="table-card-list">
            @forelse ($employmentTypes as $employmentType)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $employmentType->name }}</span>
                            <span class="table-subtitle">{{ $employmentType->description ?? '-' }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <span class="badge badge-secondary shrink-0">{{ $employmentType->academy->name }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Status') }}</span>
                            @if ($employmentType->status)
                                <span class="badge badge-success w-fit">{{ __('Aktif') }}</span>
                            @else
                                <span class="badge badge-danger w-fit">{{ __('Nonaktif') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('employment_type.update')
                            <a href="{{ route('employment-types.edit', $employmentType) }}" class="btn-icon btn-icon-warning"
                                title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('employment_type.delete')
                            <x-button.delete :action="route('employment-types.destroy', $employmentType)"
                                :name="$employmentType->name" />
                        @endcan
                    </div>
                </div>
            @empty
                <div class="table-card">
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                            class="text-gray-300 dark:text-gray-700 mb-3">
                            <path
                                d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                stroke="currentColor" stroke-width="2.5" />
                        </svg>
                        <h4 class="empty-title">{{ __('Belum ada Employment Type') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan employment type pertama.') }}</p>

                        @can('employment_type.create')
                            <a href="{{ route('employment-types.create') }}" class="empty-link">{{ __('Tambah Employment Type') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($employmentTypes->hasPages())
            <div class="table-footer">
                {{ $employmentTypes->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
```

`resources/views/employment-types/create.blade.php`:

```blade
@extends('layouts.app', ['page' => 'employment-types'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Employment Type') }}</h3>
                <p class="card-description">{{ __('Tambahkan jenis pekerjaan staff baru untuk academy.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('employment-types.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('employment-types.store') }}" method="POST">
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
                            {{ __('Nama Employment Type') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="{{ __('Contoh: Permanent, Contract, Intern') }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Deskripsi') }}</label>

                        <textarea name="description" rows="3" placeholder="{{ __('Keterangan singkat tentang jenis pekerjaan ini') }}"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description') }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>

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
                    {{ __('Simpan Employment Type') }}
                </button>

            </div>

        </form>

    </div>

@endsection
```

`resources/views/employment-types/edit.blade.php` — sama seperti `create.blade.php` tapi:
- Judul card: `__('Perbarui detail jenis pekerjaan.')`.
- Field Academy (kalau Super Admin) jadi teks read-only: `<p class="form-input bg-gray-50 dark:bg-gray-800">{{ $employmentType->academy->name }}</p>`.
- `value`/`old()` semua field pakai default `$employmentType->xxx`.
- Form action `route('employment-types.update', $employmentType)`, `@method('PUT')`.
- Tombol footer: `Batal` (link ke index, bukan `type="reset"`) + `Update Employment Type`.

(Pola persis `player-types/edit.blade.php` — copy strukturnya, ganti nama variabel/field seperti di atas.)

**✅ Cek dulu**: `php artisan route:list --name=employment-types` menampilkan 5 route (index/create/store/edit/update — `destroy` juga karena `except(['show'])` cuma exclude show). Login sebagai Owner academy manapun → buka `/employment-types` → 6 employment type default sudah tampil. Tambah/edit/hapus berfungsi normal, termasuk validasi duplikat nama.

---

## Tahap 9 — Menu Sidebar (Dropdown "Office")

**Tujuan**: dropdown baru "Office" di sidebar, isi 1 item (Employment Type) untuk sekarang.

`resources/views/partials/sidebar.blade.php` — sisipkan **setelah** `<!-- ===== END: Football Academy ===== -->` (baris ~210) dan **sebelum** `<!-- ===== Menu Item: Profile (tanpa dropdown) ===== -->`:

```blade
                    <h3 class="menu-group-heading">
                        <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                            {{ __('office') }}
                        </span>
                        {{-- Dots icon saat sidebar collapsed di desktop --}}
                        <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'" class="menu-group-icon"
                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                                fill="" />
                        </svg>
                    </h3>

                    <!-- ===== Menu Item: Office (dengan dropdown) ===== -->

                    @php
                        $officeRoutes = ['employment-types.*'];

                        $isOfficeActive = false;

                        foreach ($officeRoutes as $route) {
                            if (Route::is($route)) {
                                $isOfficeActive = true;
                                break;
                            }
                        }
                    @endphp

                    <li x-data="{ open: {{ $isOfficeActive ? 'true' : 'false' }} }">

                        <a href="#" @click.prevent="open = !open" class="menu-item group"
                            :class="open ? 'menu-item-active' : 'menu-item-inactive'">

                            <svg :class="open ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 8.5C4 7.39543 4.89543 6.5 6 6.5H18C19.1046 6.5 20 7.39543 20 8.5V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V8.5Z"
                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8.5 6.5V5.5C8.5 4.67157 9.17157 4 10 4H14C14.8284 4 15.5 4.67157 15.5 5.5V6.5"
                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M4 12H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{ __('Office') }}
                            </span>

                            <svg class="menu-item-arrow transition-transform duration-200"
                                :class="[
                                    open ? 'menu-item-arrow-active rotate-180' : 'menu-item-arrow-inactive',
                                    sidebarToggle ? 'lg:hidden' : ''
                                ]"
                                width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                        </a>

                        {{-- Dropdown submenu --}}

                        <div x-show="open" x-collapse class="overflow-hidden">

                            <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown">
                                {{--
                                    Staff & Staff Position disisipkan di sini oleh issue11.md &
                                    issue10.md -- SEBELUM item Employment Type di bawah ini, supaya
                                    urutan akhir jadi: Staff, Staff Position, Employment Type.
                                --}}

                                {{-- Employment Type --}}
                                @can('employment_type.view')
                                    <li>
                                        <a href="{{ route('employment-types.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('employment-types.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Employment Type') }}
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>

                    <!-- ===== END: Office ===== -->

```

> Icon briefcase di atas dipilih supaya beda visual dari icon Academy/Profil Academy yang sudah pakai bentuk "gedung" (`M4 21V8L12 3L20 8V21H14V14H10V21H4Z`) — hindari 2 menu beda konteks kelihatan sama.
>
> `$officeRoutes` cuma berisi `'employment-types.*'` untuk sekarang — `issue10.md` akan mengubahnya jadi `['staff-positions.*', 'employment-types.*']` dan `issue11.md` jadi `['staff.*', 'staff-positions.*', 'employment-types.*']`.

`lang/en.json` — tambahkan entry baru (cek dulu key mana yang mungkin sudah ada dari module lain sebelum menambah):

```json
"office": "office",
"Office": "Office",
"Employment Type": "Employment Type",
"Employment Type List": "Employment Type List",
"Manajemen jenis pekerjaan staff (Permanent, Contract, Intern, dsb) per academy.": "Manage staff employment types (Permanent, Contract, Intern, etc.) per academy.",
"Tambah Employment Type": "Add Employment Type",
"Belum ada Employment Type": "No Employment Types Yet",
"Tambahkan employment type pertama.": "Add the first employment type.",
"Informasi Employment Type": "Employment Type Information",
"Tambahkan jenis pekerjaan staff baru untuk academy.": "Add a new staff employment type for the academy.",
"Perbarui detail jenis pekerjaan.": "Update the employment type details.",
"Nama Employment Type": "Employment Type Name",
"Contoh: Permanent, Contract, Intern": "Example: Permanent, Contract, Intern",
"Keterangan singkat tentang jenis pekerjaan ini": "Brief description of this employment type",
"Simpan Employment Type": "Save Employment Type",
"Update Employment Type": "Update Employment Type",
"Employment type berhasil ditambahkan.": "Employment type added successfully.",
"Employment type berhasil diperbarui.": "Employment type updated successfully.",
"Employment type berhasil dihapus.": "Employment type deleted successfully.",
"Academy wajib dipilih.": "Academy must be selected.",
"Academy tidak dapat dipilih.": "Academy cannot be selected.",
"Academy tidak valid.": "Invalid academy.",
"Academy tidak ditemukan.": "Academy not found.",
"Nama employment type wajib diisi.": "Employment type name is required.",
"Nama employment type harus berupa teks.": "Employment type name must be text.",
"Nama employment type maksimal :max karakter.": "Employment type name may not exceed :max characters.",
"Nama employment type sudah digunakan pada academy ini.": "This employment type name is already used in this academy.",
"Deskripsi harus berupa teks.": "Description must be text.",
"Status wajib ditentukan.": "Status is required.",
"Status tidak valid.": "Invalid status."
```

> Beberapa key di atas (`"Academy wajib dipilih."`, `"Deskripsi harus berupa teks."`, dst) kemungkinan **sudah ada** dari module Player Type/Category — cek dulu, jangan duplikat.

**✅ Cek dulu**: buka sidebar → group baru "Office" muncul (posisi setelah "Football Academy", sebelum "Profile"), berisi 1 item "Employment Type". Klik → masuk ke halaman index. Ganti locale ke Inggris → seluruh teks di halaman Employment Type (list/create/edit) + menu sidebar tampil Bahasa Inggris.

---

## Tahap 10 — Test & Dokumentasi

### 10a. `tests/Feature/EmploymentTypeTest.php` — file baru

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\User;
use App\Services\EmploymentTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsOwner(Academy $academy): User
    {
        $role = Role::factory()->create(['id_academy' => $academy->id_academy, 'name' => 'Owner']);
        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner);

        return $owner;
    }

    public function test_create_default_employment_types_membuat_6_type_dari_config(): void
    {
        $academy = Academy::factory()->create();

        app(EmploymentTypeService::class)->createDefaultEmploymentTypes($academy);

        $this->assertSame(
            array_keys(config('faos.employment_type_templates')),
            EmploymentType::where('id_academy', $academy->id_academy)->pluck('name')->all()
        );
    }

    public function test_nama_duplikat_di_academy_yang_sama_ditolak(): void
    {
        $academy = Academy::factory()->create();
        $this->actingAsOwner($academy);

        EmploymentType::factory()->create(['id_academy' => $academy->id_academy, 'name' => 'Permanent']);

        $svc = app(EmploymentTypeService::class);

        // FormRequest yang menegakkan unique -- di sini langsung cek DB
        // constraint-nya (unique index) sebagai jaring pengaman terakhir.
        $this->expectException(\Illuminate\Database\QueryException::class);

        EmploymentType::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Permanent',
            'status' => true,
        ]);
    }

    public function test_academy_lain_tidak_bisa_lihat_employment_type_academy_lain(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        EmploymentType::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Permanent']);
        EmploymentType::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'Contract']);

        $this->actingAsOwner($academyB);

        $response = $this->get(route('employment-types.index'));

        $response->assertOk();
        $response->assertDontSee('Permanent');
        $response->assertSee('Contract');
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=EmploymentTypeTest
php artisan test
```

Seluruh test harus **pass**, termasuk full suite (baseline sebelumnya + test baru, tidak boleh ada regresi).

### 10b. `docs/permission-reference.md`

Tambahkan section baru (format sama seperti section "Module: Player Type"), diletakkan setelah section Player Category dan sebelum section Academy Management (atau di posisi yang paling masuk akal sesuai Table of Contents dokumen saat ini — cek urutan TOC dulu):

```markdown
## Module: Employment Type

Status: **✅ Implemented**

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `employment_type.view` | Lihat daftar employment type | `employment-types.index` (route middleware) |
| `employment_type.create` | Tambah employment type baru | `employment-types.create`, `employment-types.store` (route middleware) + `@can()` tombol "Tambah" |
| `employment_type.update` | Ubah employment type | `employment-types.edit`, `employment-types.update` (route middleware) + `@can()` tombol Edit |
| `employment_type.delete` | Hapus employment type | `employment-types.destroy` (route middleware) + `@can()` tombol Hapus |

Catatan:
- Isolasi antar academy memakai `AcademyScope` (global scope), **bukan** Policy — akses employment type academy lain menghasilkan **404**, bukan 403. Pola sama dengan Player Type/Player Category.
- Default: 4 permission ini cuma di-assign ke role **Owner** lewat `config('faos.role_templates')`. Delegasi ke role lain lewat halaman Role Management.
- Guard delete ("masih dipakai staff") **belum aktif** di brief ini (tabel `staff` belum ada) — ditambahkan saat `issue11.md` (modul Staff) selesai.
```

Tambahkan juga link section baru ini ke Table of Contents dokumen (baris paling atas, format sama seperti entry lain).

**✅ Cek dulu**: buka `docs/permission-reference.md`, pastikan section baru muncul + TOC ter-update.

---

## 4. Alasan Teknis

### 4.1 Kenapa dropdown "Office" dibangun bertahap, bukan sekaligus 3 item

Kalau ketiga `<li>` (Staff, Staff Position, Employment Type) ditulis sekaligus di brief ini dengan `@can('staff.view')`/`@can('staff_position.view')` padahal permission itu belum ada di database sama sekali, ada risiko nyata untuk **Super Admin**: `Gate::before()` (`AppServiceProvider`) memberi akses penuh ke Super Admin untuk ability APA PUN, termasuk permission yang namanya belum terdaftar di tabel `permissions` — jadi `@can('staff.view')` akan tetap `true` untuk Super Admin walau route `staff.index` belum pernah didaftarkan, menghasilkan `RouteNotFoundException` saat link itu di-render (`route('staff.index')` dipanggil sebelum route-nya ada). Solusinya: setiap brief cuma menyisipkan `<li>` untuk modul yang **benar-benar sudah** dia bangun sendiri, urut sesuai urutan pengerjaan.

### 4.2 Kenapa `EmploymentType` beda field dengan `PlayerType` padahal "template"-nya sama

`is_billable` di `PlayerType` itu konsep spesifik SPP/iuran pemain — tidak ada padanannya di konteks jenis pekerjaan staff. Brief ini sengaja tidak memaksakan field itu ada "supaya mirip" — pola yang ditiru adalah **struktur kode** (migration/model/service/controller/request/view), bukan daftar field literalnya.

