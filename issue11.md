# Brief: Modul Staff (bagian 3/3 dari "Office")

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: `issue9.md` (Employment Type) **dan** `issue10.md` (Staff Position) **wajib sudah selesai** — brief ini bergantung ke `employment_types`/`staff_positions` sebagai foreign key wajib. Baca juga `docs/module-standard.md`, `docs/development-guide.md`, `docs/multi-tenancy.md`, `docs/authorization.md`, `docs/frontend-standard.md`. Modul referensi paling mirip yang **wajib** dibuka dulu: `app/Models/Player.php`, `app/Services/PlayerService.php`, `app/Http/Controllers/PlayerController.php`, `app/Http/Controllers/PlayerAccountController.php`, `app/Services/AccountService.php`, `resources/views/players/*.blade.php` (termasuk `players/account/*.blade.php`, `players/show.blade.php`).
> **Bagian dari brief besar "Office"**: 3 dari 3 brief. Lihat `issue9.md` bagian header untuk peta lengkap.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 12** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: (1) Entitas `Staff` — data staff academy (biodata, kontak, kepegawaian) dengan **akun login opsional**, pola akun 1:1 meniru Player Account (controller terpisah: create/edit/reset-password/enable-disable). (2) Field "Default Role" (`issue10.md`) dipakai sebagai **pilihan awal** (bukan paksaan) di form buat akun. (3) **Melengkapi** `issue9.md`/`issue10.md`: tambah guard delete berbasis relasi ke `staff`, dan menyelesaikan menu sidebar "Office" (item "Staff" jadi yang PALING ATAS). **Bukan** scope: modul Coach/Team/Training terpisah, upload dokumen staff, riwayat gaji/payroll.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Assign role staff secara hardcode (`'Staff'` seperti pola `AccountService::create($data, 'Player')`) | Role staff **dinamis** — dipilih dari dropdown saat buat akun (pre-selected dari `StaffPosition::role_id` kalau ada, tapi tetap bisa diganti). Butuh `AccountService::create()` menerima objek `Role`, bukan cuma nama string | [Tahap 1](#tahap-1--accountservice-terima-role-object), [4.1](#41-kenapa-accountservicecreate-diperluas-terima-roleobject-bukan-bikin-method-baru) |
| Taruh fitur "Create Account"/"Reset Password"/dst di dalam `StaffController`/form create Staff | Ikuti pola Player: **controller terpisah** `StaffAccountController` + route prefix `staff/{staff}/account`, bukan toggle di form create seperti Academy Owner. Staff adalah entitas yang bisa dikelola role lain yang belum tentu boleh kelola akun login-nya (delegasi parsial) — beda dengan Academy Management yang tidak pernah didelegasikan | [Tahap 6](#tahap-6--controllers) |
| Bikin permission baru `staff_account.*` | **Reuse** `user.create`/`user.update` yang sudah dipakai Player Account — permission generik ini memang didesain dipakai lintas module yang py sub-fitur akun serupa | [Tahap 8](#tahap-8--routes-permission-config) |
| Lupa tambah `getNameAttribute()` accessor di Model `Staff` | `<x-account.dropdown>` (komponen shared, dipakai Player DAN brief ini) mengakses `$model->name` secara generik — Staff py `full_name`, bukan `name`. Tanpa accessor, dropdown akan menampilkan nama kosong/error di modal konfirmasi. Solusinya accessor Eloquent, BUKAN mengubah nama kolom `full_name` jadi `name` (field itu memang lebih deskriptif untuk entitas Staff) | [Tahap 3](#tahap-3--model-staff), [4.2](#42-kenapa-getnameattribute-bukan-ubah-nama-kolom) |
| Skip Tahap 9 (guard delete balik ke `issue9.md`/`issue10.md`) | `EmploymentTypeService::delete()`/`StaffPositionService::delete()` saat ini **belum** punya guard (`issue9.md`/`issue10.md` sengaja menunda ini karena tabel `staff` belum ada). Kalau brief ini selesai tanpa Tahap 9, Employment Type/Staff Position yang masih dipakai staff bisa terhapus diam-diam (FK `restrictOnDelete()` di migration jadi satu-satunya jaring pengaman — pesan error-nya jelek/generic, bukan pesan ramah seperti module lain) | [Tahap 9](#tahap-9--guard-delete-balik-ke-issue9mdissue10md) |
| Bikin `role_id`/`id_staff_position`/`id_employment_type` nullable di migration `staff` | Beda dengan `players.id_player_type` yang nullable karena **retrofit** ke tabel lama yang sudah py data — tabel `staff` di brief ini BARU, tidak ada data lama untuk dikhawatirkan. Employment Type & Staff Position wajib diisi sejak awal | [Tahap 2](#tahap-2--migration) |

---

## 1. Konteks & Tujuan

Ini brief terakhir dari 3 modul "Office". Setelah `issue9.md` (Employment Type) dan `issue10.md` (Staff Position) selesai, brief ini membangun entitas utamanya: **Staff** — data staff academy (coach, admin, finance, dst) dengan akun login opsional.

```text
Staff
├── id_employment_type  -> FK WAJIB ke employment_types (issue9.md)
├── id_staff_position   -> FK WAJIB ke staff_positions (issue10.md)
│                            └── role_id (Default Role) -> dipakai sebagai
│                                pilihan AWAL saat staff ini dibuatkan akun
└── id_user (NULLABLE)  -> akun login opsional, dikelola StaffAccountController
                            terpisah (create/edit/reset-password/enable-disable)
                            -- pola 1:1 meniru Player Account
```

## 2. Cara Kerja Solusi

### 2a. Struktur & field ikuti taksonomi form 8 kategori (`docs/frontend-standard.md`)

```text
KOLOM KIRI (kategori 1-4)                    KOLOM KANAN (kategori 5-8)
├── Academy (Super Admin saja)      Scope     ├── Gender, Tempat/Tgl Lahir,
├── Nama Lengkap, Nickname          Identitas │   Kewarganegaraan, Agama,
├── Employment Type, Staff Position Klasifikasi│   Gol. Darah, St. Nikah   Deskriptif
└── Telepon, Email, Alamat,         Kontak    ├── Tgl Bergabung, Tgl Keluar,
    Kota, Provinsi, Kode Pos                  │   Gaji, Catatan
                                               ├── Foto Staff              Media
                                               └── Status Kepegawaian      Status
```

### 2b. Akun staff: pola Player Account 1:1, plus dropdown Role

Beda dengan `AccountService::create($data, 'Player')` yang hardcode nama role, form "Buat Akun Staff" py dropdown Role (isi: seluruh Role academy staff itu, lihat `Role::forCurrentAcademy()`/filter `id_academy`), **pre-selected** ke `staff->position->role_id` kalau ada (`old('role_id', $staff->position->role_id)`), tapi admin bebas ganti. `AccountService::create()` diperluas terima `Role` object (Tahap 1) supaya bisa langsung dioper Role yang sudah dipilih dari dropdown, tanpa lookup nama string.

### 2c. Guard delete balik ke `issue9.md`/`issue10.md` — brief ini yang menutup celah itu

`EmploymentTypeService`/`StaffPositionService` di 2 brief sebelumnya sengaja `delete()` polos (tabel `staff` belum ada saat itu). Tahap 9 brief ini menambah `staff()` relasi ke `EmploymentType`/`StaffPosition`, lalu meng-upgrade `delete()` masing-masing supaya menolak hapus kalau masih dipakai staff — pola identik `PlayerTypeService::delete()`.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `app/Services/AccountService.php` | ✏️ Widen `create()` terima `Role\|string` | 1 |
| `database/migrations/..._create_staff_table.php` | 🆕 Baru | 2 |
| `app/Models/Staff.php` | 🆕 Baru | 3 |
| `database/factories/StaffFactory.php` | 🆕 Baru | 3 |
| `app/View/Components/StaffPhotoField.php` | 🆕 Baru | 4 |
| `resources/views/components/staff-photo-field.blade.php` | 🆕 Baru | 4 |
| `app/Services/StaffService.php` | 🆕 Baru | 5 |
| `app/Http/Controllers/StaffController.php` | 🆕 Baru | 6 |
| `app/Http/Controllers/StaffAccountController.php` | 🆕 Baru | 6 |
| `app/Http/Requests/Staff/StoreStaffRequest.php` | 🆕 Baru | 7 |
| `app/Http/Requests/Staff/UpdateStaffRequest.php` | 🆕 Baru | 7 |
| `app/Http/Requests/Staff/StoreStaffAccountRequest.php` | 🆕 Baru | 7 |
| `app/Http/Requests/Staff/UpdateStaffAccountRequest.php` | 🆕 Baru | 7 |
| `routes/web.php` | ✏️ Tambah resource route `staff` + prefix `staff/{staff}/account` | 8 |
| `database/seeders/RolePermissionSeeder.php` | ✏️ Tambah 4 permission `staff.*` | 8 |
| `config/faos.php` | ✏️ Tambah `role_templates.Owner` (`staff.*`) | 8 |
| `app/Models/EmploymentType.php` | ✏️ Tambah relasi `staff()` | 9 |
| `app/Models/StaffPosition.php` | ✏️ Tambah relasi `staff()` | 9 |
| `app/Services/EmploymentTypeService.php` | ✏️ `delete()` tambah guard | 9 |
| `app/Services/StaffPositionService.php` | ✏️ `delete()` tambah guard | 9 |
| `resources/views/staff/index.blade.php` | 🆕 Baru | 10 |
| `resources/views/staff/create.blade.php` | 🆕 Baru | 10 |
| `resources/views/staff/edit.blade.php` | 🆕 Baru | 10 |
| `resources/views/staff/show.blade.php` | 🆕 Baru | 10 |
| `resources/views/staff/account/create.blade.php` | 🆕 Baru | 10 |
| `resources/views/staff/account/edit.blade.php` | 🆕 Baru | 10 |
| `resources/views/components/account/dropdown.blade.php` | ✏️ Bungkus `__()` (luput dari sweep i18n sebelumnya) | 10 |
| `resources/views/partials/sidebar.blade.php` | ✏️ Sisip item "Staff" ke dropdown Office (PALING ATAS) | 11 |
| `lang/en.json` | ✏️ Entry baru | 11 |
| `tests/Feature/StaffTest.php` | 🆕 Baru | 12 |
| `tests/Feature/StaffAccountTest.php` | 🆕 Baru | 12 |
| `docs/permission-reference.md` | ✏️ Tambah section Staff | 12 |
| `README.md` | ✏️ Centang `[x] Staff Management` di Roadmap | 12 |

---

## Tahap 1 — `AccountService` Terima `Role` Object

**Tujuan**: `AccountService::create()` bisa dipanggil dengan `Role` object hasil pilihan dropdown, bukan cuma nama string. Perubahan **backward-compatible** — pemanggil lama (`'Player'`, `'Owner'`) tetap jalan tanpa perlu diubah.

`app/Services/AccountService.php` — ganti signature method `create()`:

```php
    public function create(array $data, Role|string $role): User
```

(baris ini sebelumnya `public function create(array $data,string $role): User`). Tidak ada perubahan lain di method itu — `assignRole()`/`resolveRole()` yang dipanggil di dalamnya **sudah** menerima `Role|string` (cek isinya, tidak perlu diubah).

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
// Pastikan pemanggilan lama (string) masih jalan -- regresi check.
$academy = \App\Models\Academy::factory()->create();
app(\App\Services\RoleService::class)->createDefaultRoles($academy);
$u = app(\App\Services\AccountService::class)->create([
    'id_academy' => $academy->id_academy, 'name' => 'Test', 'email' => 'test@test.com', 'password' => 'password',
], 'Owner');
$u->roles->pluck('name'); // ['Owner']
```

```bash
php artisan test --filter=AcademyAccountTest
php artisan test --filter=Player
```

Tidak boleh ada regresi di test Academy/Player Account yang sudah ada.

---

## Tahap 2 — Migration

```bash
php artisan make:migration create_staff_table
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
        Schema::create('staff', function (Blueprint $table) {

            $table->uuid('id_staff')->primary();

            /*
            |--------------------------------------------------------------------------
            | Tenant & Account
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_academy');
            $table->uuid('id_user')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Klasifikasi (WAJIB -- tabel baru, tidak ada data lama untuk
            | dikhawatirkan seperti players.id_player_type yang nullable).
            |--------------------------------------------------------------------------
            */
            $table->uuid('id_employment_type');
            $table->uuid('id_staff_position');

            /*
            |--------------------------------------------------------------------------
            | Staff Identity
            |--------------------------------------------------------------------------
            */
            $table->string('staff_code', 30)->unique();
            $table->string('photo')->nullable();
            $table->string('full_name');
            $table->string('nickname', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Biodata
            |--------------------------------------------------------------------------
            */
            $table->enum('gender', ['male', 'female']);
            $table->string('birth_place', 100);
            $table->date('birth_date');
            $table->string('nationality', 50)->default('Indonesia');
            $table->enum('religion', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya'])->nullable();
            $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Info
            |--------------------------------------------------------------------------
            | `email` di sini KONTAK/informasi, TERPISAH dari users.email (akun
            | login) -- staff boleh punya email kontak tanpa punya akun sama
            | sekali, dan akun login (kalau ada) boleh pakai email berbeda.
            */
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Employment Info
            |--------------------------------------------------------------------------
            */
            $table->date('join_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'resigned'])->default('active');

            /*
            |--------------------------------------------------------------------------
            | Additional
            |--------------------------------------------------------------------------
            */
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('id_academy');
            $table->index('id_user');
            $table->index('id_employment_type');
            $table->index('id_staff_position');
            $table->index(['id_academy', 'status'], 'staff_academy_status_index');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();

            // restrictOnDelete() (bukan nullOnDelete()) -- id_employment_type &
            // id_staff_position WAJIB terisi (NOT NULL), jadi FK tidak mungkin
            // di-set NULL saat baris induknya dihapus. Ini jaring pengaman
            // DATABASE, guard "ramah" (pesan __()) ada di Tahap 9.
            $table->foreign('id_employment_type')->references('id_employment_type')->on('employment_types')->restrictOnDelete();
            $table->foreign('id_staff_position')->references('id_staff_position')->on('staff_positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table staff
```

`id_employment_type`/`id_staff_position` harus **NOT NULL**. `id_user` **nullable**. `deleted_at` ada (soft delete).

---

## Tahap 3 — Model `Staff`

`app/Models/Staff.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends FaosModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';
    protected $primaryKey = 'id_staff';

    protected $fillable = [
        'id_academy', 'id_user', 'id_employment_type', 'id_staff_position',
        'staff_code', 'photo', 'full_name', 'nickname',
        'gender', 'birth_place', 'birth_date', 'nationality', 'religion', 'blood_type', 'marital_status',
        'phone', 'email', 'address', 'city', 'province', 'postal_code',
        'join_date', 'end_date', 'salary', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
            'end_date' => 'date',
            'salary' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'id_employment_type', 'id_employment_type');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(StaffPosition::class, 'id_staff_position', 'id_staff_position');
    }

    /**
     * Accessor supaya komponen shared yang generik mengasumsikan atribut
     * `name` (mis. <x-account.dropdown>, dipakai bersama Player) tetap
     * berfungsi tanpa modifikasi -- TIDAK mengganti nama kolom `full_name`
     * jadi `name` (lihat issue11.md Bagian 4.2).
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }
}
```

`database/factories/StaffFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'staff_code' => strtoupper(fake()->unique()->bothify('STF-####')),
            'full_name' => fake()->name(),
            'nickname' => fake()->firstName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->date(),
            'nationality' => 'Indonesia',
            'phone' => fake()->phoneNumber(),
            'join_date' => now(),
            'status' => 'active',
        ];
    }
}
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$staff = new \App\Models\Staff(['full_name' => 'Budi Santoso']);
$staff->name; // harus "Budi Santoso" -- accessor jalan.
```

---

## Tahap 4 — `<x-staff-photo-field>` Component

**Tujuan**: upload foto staff, pola sederhana meniru `<x-player-photo-field>` (preview base64 via `FileReader`, TANPA crop modal — beda dengan `<x-logo-upload-field>` milik Academy yang py crop). Ditulis baru (bukan reuse `PlayerPhotoField`) supaya scope brief ini tidak menyentuh file module Player — lihat catatan di bawah kalau nanti mau digeneralisasi.

`app/View/Components/StaffPhotoField.php`:

```php
<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StaffPhotoField extends Component
{
    public ?string $currentPhotoUrl;

    public function __construct(?string $currentPhotoUrl = null)
    {
        $this->currentPhotoUrl = $currentPhotoUrl;
    }

    public function render(): View
    {
        return view('components.staff-photo-field');
    }
}
```

`resources/views/components/staff-photo-field.blade.php`:

```blade
<div class="form-group" x-data="{ imagePreview: @js($currentPhotoUrl) }">

    <label class="form-label">
        {{ __('Foto Staff') }}
    </label>

    <div class="form-file-upload">

        <input type="file" name="photo" accept="image/*"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
            @change="
            const file=$event.target.files[0];
            if(file){
                const reader=new FileReader();
                reader.onload=(e)=>imagePreview=e.target.result;
                reader.readAsDataURL(file);
            }
        ">

        <div x-show="!imagePreview" class="empty-state">

            <span class="avatar avatar-lg mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12 16V8M8 12L12 8L16 12M3 15V18C3 18.5 3.2 19 3.6 19.4C4 19.8 4.5 20 5 20H19C19.5 20 20 19.8 20.4 19.4C20.8 19 21 18.5 21 18V15"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>

            <p class="empty-title">
                {{ $currentPhotoUrl ? __('Klik untuk mengganti foto staff') : __('Klik untuk upload foto staff') }}
            </p>

            <p class="empty-description">
                {{ __('JPG, PNG, WEBP maksimal 2MB') }}
            </p>

        </div>

        <div x-show="imagePreview" x-cloak class="flex flex-col items-center">
            <div class="avatar avatar-xl avatar-square mb-4">
                <img :src="imagePreview" class="h-full w-full object-cover">
            </div>

            <span class="link-primary text-xs font-semibold">
                {{ __('Ganti Foto') }}
            </span>
        </div>

    </div>

    @error('photo')
        <span class="form-error">{{ $message }}</span>
    @enderror

</div>
```

> `<x-player-photo-field>` (aslinya) py string mentah tanpa `__()` sama sekali (luput dari sweep i18n) — versi Staff di sini ditulis benar dari awal, tidak mewariskan gap itu. Kalau nanti mau digeneralisasi jadi `<x-photo-field>` reusable dipakai Player+Staff, itu kerja terpisah di luar scope brief ini (lihat `docs/frontend-standard.md` → *Kapan Membuat @utility Baru*, prinsip yang sama berlaku untuk keputusan generalisasi component).

**✅ Cek dulu**: `php -l app/View/Components/StaffPhotoField.php` tidak ada error.

---

## Tahap 5 — `StaffService`

`app/Services/StaffService.php`:

```php
<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StaffService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    protected function uploadPhoto($file, string $staffCode): string
    {
        $filename = strtoupper($staffCode) . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs(config('faos.upload.staff'), $filename, 'public');
    }

    protected function deletePhoto(?string $photo): void
    {
        if ($photo && Storage::disk('public')->exists($photo)) {
            Storage::disk('public')->delete($photo);
        }
    }

    protected function resolveAcademy(array $data): Academy
    {
        if ($this->academyService->isSuperAdmin()) {

            $academy = Academy::find($data['id_academy'] ?? null);

            if (! $academy) {
                throw new \Exception(__('Academy tidak ditemukan.'));
            }

            return $academy;
        }

        $academy = $this->academyService->current();

        if (! $academy) {
            throw new \Exception(__('Academy tidak ditemukan.'));
        }

        return $academy;
    }

    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('staff_code', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['id_employment_type'])) {
            $query->where('id_employment_type', $filters['id_employment_type']);
        }

        if (! empty($filters['id_staff_position'])) {
            $query->where('id_staff_position', $filters['id_staff_position']);
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Staff::with(['employmentType', 'position', 'user']);

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('full_name'),
            'name_desc' => $query->orderByDesc('full_name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    public function statusCounts(array $filters = []): array
    {
        $countFor = function (string $status) use ($filters) {

            $query = Staff::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return [
            'active' => $countFor('active'),
            'inactive' => $countFor('inactive'),
            'resigned' => $countFor('resigned'),
        ];
    }

    /**
     * Pola generate kode identik PlayerService::generatePlayerCode() --
     * prefix {ACADEMY_CODE}{YY}, 5 digit berurutan, row-lock supaya aman
     * dari race condition saat 2 staff dibuat bersamaan.
     */
    protected function generateStaffCode(Academy $academy): string
    {
        $prefix = strtoupper($academy->code) . now()->format('y');

        return DB::transaction(function () use ($prefix) {

            $last = Staff::withoutGlobalScopes()
                ->where('staff_code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('staff_code')
                ->first();

            $next = $last ? ((int) substr($last->staff_code, strlen($prefix)) + 1) : 1;

            do {
                $code = $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
                $exists = Staff::withoutGlobalScopes()->where('staff_code', $code)->exists();
                $next++;
            } while ($exists);

            return $code;
        });
    }

    public function create(array $data): Staff
    {
        return DB::transaction(function () use ($data) {

            $academy = $this->resolveAcademy($data);
            $staffCode = $this->generateStaffCode($academy);

            $photo = isset($data['photo']) ? $this->uploadPhoto($data['photo'], $staffCode) : null;

            return Staff::create([
                'id_academy' => $academy->id_academy,
                'id_employment_type' => $data['id_employment_type'],
                'id_staff_position' => $data['id_staff_position'],
                'staff_code' => $staffCode,
                'photo' => $photo,
                'full_name' => $data['full_name'],
                'nickname' => $data['nickname'] ?? null,
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'religion' => $data['religion'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'join_date' => $data['join_date'] ?? now(),
                'end_date' => $data['end_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function update(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data) {

            $oldPhoto = $staff->photo;
            $photo = $oldPhoto;

            if (isset($data['photo'])) {
                $photo = $this->uploadPhoto($data['photo'], $staff->staff_code);
            }

            $staff->update([
                // id_academy & staff_code sengaja TIDAK ikut diubah.
                'id_employment_type' => $data['id_employment_type'],
                'id_staff_position' => $data['id_staff_position'],
                'photo' => $photo,
                'full_name' => $data['full_name'],
                'nickname' => $data['nickname'] ?? null,
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'religion' => $data['religion'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'join_date' => $data['join_date'] ?? $staff->join_date,
                'end_date' => $data['end_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'status' => $data['status'] ?? $staff->status,
                'notes' => $data['notes'] ?? null,
            ]);

            // Hapus foto lama SETELAH update sukses -- pola sama PlayerService.
            if (isset($data['photo']) && $oldPhoto) {
                $this->deletePhoto($oldPhoto);
            }

            return $staff;
        });
    }

    public function delete(Staff $staff): bool
    {
        return DB::transaction(function () use ($staff) {

            $this->deletePhoto($staff->photo);

            // Ikut hapus akun login terkait, kalau ada -- pola sama PlayerService.
            if ($staff->id_user) {
                User::where('id_user', $staff->id_user)->delete();
            }

            return $staff->delete();
        });
    }
}
```

**✅ Cek dulu**: `php -l app/Services/StaffService.php` tidak ada error. Verifikasi penuh menyusul Tahap 10 (butuh view/form).

---

## Tahap 6 — Controllers

`app/Http/Controllers/StaffController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Academy;
use App\Models\Staff;
use App\Services\AcademyService;
use App\Services\EmploymentTypeService;
use App\Services\StaffPositionService;
use App\Services\StaffService;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    protected StaffService $staffService;
    protected AcademyService $academyService;
    protected EmploymentTypeService $employmentTypeService;
    protected StaffPositionService $staffPositionService;

    public function __construct(
        StaffService $staffService,
        AcademyService $academyService,
        EmploymentTypeService $employmentTypeService,
        StaffPositionService $staffPositionService
    ) {
        $this->staffService = $staffService;
        $this->academyService = $academyService;
        $this->employmentTypeService = $employmentTypeService;
        $this->staffPositionService = $staffPositionService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only([
            'search', 'status', 'id_employment_type', 'id_staff_position', 'gender', 'sort',
        ]));

        $isSuperAdmin = $this->academyService->isSuperAdmin();
        $academyId = $isSuperAdmin ? null : $this->academyService->currentId();

        return view('staff.index', [
            'title' => __('Staff'),
            'breadcrumb' => [
                ['label' => __('Office')],
                ['label' => __('Staff')],
            ],
            'staff' => $this->staffService->paginate($filters),
            'statusCounts' => $this->staffService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            'employmentTypeOptions' => $this->employmentTypeService->selectable($academyId),
            'staffPositionOptions' => $this->staffPositionService->selectable($academyId),
        ]);
    }

    public function create()
    {
        $isSuperAdmin = $this->academyService->isSuperAdmin();
        $academyId = $isSuperAdmin ? null : $this->academyService->currentId();

        return view('staff.create', [
            'title' => __('Tambah Staff'),
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => __('Tambah Staff')],
            ],
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
            'employmentTypes' => $this->employmentTypeService->selectable($academyId),
            'staffPositions' => $this->staffPositionService->selectable($academyId),
        ]);
    }

    public function store(StoreStaffRequest $request)
    {
        try {

            $this->staffService->create($request->validated());

            return redirect()
                ->route('staff.index')
                ->with('success', __('Staff berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan staff'));
        }
    }

    public function show(Staff $staff)
    {
        $staff->load(['academy', 'employmentType', 'position', 'user.roles']);

        return view('staff.show', [
            'title' => $staff->full_name,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name],
            ],
            'staff' => $staff,
        ]);
    }

    public function edit(Staff $staff)
    {
        return view('staff.edit', [
            'title' => __('Edit Staff'),
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => __('Edit Staff')],
            ],
            'staff' => $staff,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'employmentTypes' => $this->employmentTypeService->selectable($staff->id_academy, $staff->id_employment_type),
            'staffPositions' => $this->staffPositionService->selectable($staff->id_academy, $staff->id_staff_position),
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        try {

            $this->staffService->update($staff, $request->validated());

            return redirect()
                ->route('staff.index')
                ->with('success', __('Staff berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui staff'));
        }
    }

    public function destroy(Staff $staff)
    {
        try {

            $this->staffService->delete($staff);

            return redirect()
                ->route('staff.index')
                ->with('success', __('Staff berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus staff'), 'staff.index');
        }
    }
}
```

`app/Http/Controllers/StaffAccountController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffAccountRequest;
use App\Http\Requests\Staff\UpdateStaffAccountRequest;
use App\Models\Role;
use App\Models\Staff;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;

class StaffAccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function create(Staff $staff)
    {
        if ($staff->id_user) {
            return redirect()->route('staff.index')->with('error', __('Staff sudah memiliki akun.'));
        }

        return view('staff.account.create', [
            'title' => __('Buat Akun Staff'),
            'staff' => $staff,
            // Default Role staff position (kalau ada) jadi pilihan AWAL di
            // view lewat old('role_id', $staff->position->role_id) -- bukan
            // dipaksa di sini, admin tetap bisa pilih role lain.
            'roles' => Role::where('id_academy', $staff->id_academy)->orderBy('name')->get(),
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => __('Buat Akun')],
            ],
        ]);
    }

    public function store(StoreStaffAccountRequest $request, Staff $staff)
    {
        try {

            if ($staff->id_user) {
                return redirect()->route('staff.index')->with('error', __('Staff sudah memiliki akun.'));
            }

            DB::transaction(function () use ($request, $staff) {

                $role = Role::findOrFail($request->role_id);

                $user = $this->accountService->create([
                    'id_academy' => $staff->id_academy,
                    'name' => $staff->full_name,
                    'email' => $request->email,
                    'password' => $request->password,
                ], $role);

                $staff->update(['id_user' => $user->id_user]);
            });

            return redirect()->route('staff.index')->with('success', __('Akun staff berhasil dibuat.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membuat akun staff'));
        }
    }

    public function edit(Staff $staff)
    {
        if (! $staff->user) {
            return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
        }

        return view('staff.account.edit', [
            'title' => __('Edit Akun Staff'),
            'staff' => $staff,
            'user' => $staff->user,
            'breadcrumb' => [
                ['label' => __('Staff'), 'url' => route('staff.index')],
                ['label' => $staff->full_name, 'url' => route('staff.show', $staff)],
                ['label' => __('Edit Account')],
            ],
        ]);
    }

    public function update(UpdateStaffAccountRequest $request, Staff $staff)
    {
        try {

            if (! $staff->user) {
                return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
            }

            $this->accountService->update($staff->user, $request->validated());

            return redirect()->route('staff.show', $staff)->with('success', __('Account staff berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal update account'));
        }
    }

    public function password(Staff $staff)
    {
        try {

            if (! $staff->user) {
                return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
            }

            $newPassword = $this->accountService->generatePassword();

            $this->accountService->resetPassword($staff->user, $newPassword);

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', __('Password berhasil direset. Password baru: ') . $newPassword);

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal reset password'), 'staff.show', [$staff]);
        }
    }

    public function status(Staff $staff)
    {
        try {

            if (! $staff->user) {
                return redirect()->route('staff.show', $staff)->with('error', __('Staff belum memiliki akun.'));
            }

            $status = ! $staff->user->status;

            $this->accountService->changeStatus($staff->user, $status);

            return redirect()
                ->route('staff.show', $staff)
                ->with('success', $status
                    ? __('Account staff berhasil diaktifkan.')
                    : __('Account staff berhasil dinonaktifkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengubah status account'), 'staff.show', [$staff]);
        }
    }
}
```

**✅ Cek dulu**: `php -l` kedua file di atas tidak ada error.

---

## Tahap 7 — Form Requests

`app/Http/Requests/Staff/StoreStaffRequest.php`:

```php
<?php

namespace App\Http\Requests\Staff;

use App\Services\AcademyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
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

            'id_employment_type' => [
                'required',
                'uuid',
                Rule::exists('employment_types', 'id_employment_type')
                    ->where(fn ($query) => $query->where('id_academy', $academyId)->where('status', true)),
            ],

            'id_staff_position' => [
                'required',
                'uuid',
                Rule::exists('staff_positions', 'id_staff_position')
                    ->where(fn ($query) => $query->where('id_academy', $academyId)->where('status', true)),
            ],

            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],

            'gender' => ['required', 'in:male,female'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'in:islam,kristen,katolik,hindu,buddha,konghucu,lainnya'],
            'blood_type' => ['nullable', 'in:A,B,AB,O'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],

            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'join_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive,resigned'],

            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_academy.required' => __('Academy wajib dipilih.'),
            'id_academy.prohibited' => __('Academy tidak dapat dipilih.'),
            'id_academy.uuid' => __('Academy tidak valid.'),
            'id_academy.exists' => __('Academy tidak ditemukan.'),

            'id_employment_type.required' => __('Employment type wajib dipilih.'),
            'id_employment_type.exists' => __('Employment type tidak valid.'),

            'id_staff_position.required' => __('Staff position wajib dipilih.'),
            'id_staff_position.exists' => __('Staff position tidak valid.'),

            'full_name.required' => __('Nama lengkap wajib diisi.'),
            'full_name.max' => __('Nama lengkap maksimal :max karakter.'),
            'nickname.max' => __('Nickname maksimal :max karakter.'),

            'gender.required' => __('Jenis kelamin wajib dipilih.'),
            'gender.in' => __('Jenis kelamin tidak valid.'),

            'birth_place.required' => __('Tempat lahir wajib diisi.'),
            'birth_date.required' => __('Tanggal lahir wajib diisi.'),
            'birth_date.date' => __('Tanggal lahir tidak valid.'),

            'nationality.max' => __('Kewarganegaraan maksimal :max karakter.'),
            'religion.in' => __('Agama tidak valid.'),
            'blood_type.in' => __('Golongan darah tidak valid.'),
            'marital_status.in' => __('Status pernikahan tidak valid.'),

            'phone.required' => __('Nomor telepon wajib diisi.'),
            'phone.max' => __('Nomor telepon maksimal :max karakter.'),
            'email.email' => __('Format email tidak valid.'),

            'end_date.after_or_equal' => __('Tanggal berhenti tidak boleh sebelum tanggal bergabung.'),
            'salary.numeric' => __('Gaji harus berupa angka.'),
            'salary.min' => __('Gaji tidak boleh negatif.'),
            'status.in' => __('Status tidak valid.'),

            'photo.image' => __('Foto harus berupa gambar.'),
            'photo.max' => __('Ukuran foto tidak boleh lebih dari 2MB.'),
        ];
    }
}
```

`app/Http/Requests/Staff/UpdateStaffRequest.php` — sama seperti `StoreStaffRequest`, dengan 2 perbedaan (pola identik `UpdatePlayerRequest` vs `StorePlayerRequest`):
1. **Tidak ada** rule `id_academy` (academy tidak berubah saat edit).
2. `id_employment_type`/`id_staff_position` exists-check **TANPA** filter `->where('status', true)` — staff boleh tetap pakai type/position yang sudah dinonaktifkan (sudah terlanjur dipakai, jangan dipaksa ganti saat sekadar update data lain).

```php
<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_employment_type' => ['required', 'uuid', 'exists:employment_types,id_employment_type'],
            'id_staff_position' => ['required', 'uuid', 'exists:staff_positions,id_staff_position'],

            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],

            'gender' => ['required', 'in:male,female'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'in:islam,kristen,katolik,hindu,buddha,konghucu,lainnya'],
            'blood_type' => ['nullable', 'in:A,B,AB,O'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],

            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'join_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive,resigned'],

            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_employment_type.required' => __('Employment type wajib dipilih.'),
            'id_employment_type.exists' => __('Employment type tidak valid.'),

            'id_staff_position.required' => __('Staff position wajib dipilih.'),
            'id_staff_position.exists' => __('Staff position tidak valid.'),

            'full_name.required' => __('Nama lengkap wajib diisi.'),
            'full_name.max' => __('Nama lengkap maksimal :max karakter.'),
            'nickname.max' => __('Nickname maksimal :max karakter.'),

            'gender.required' => __('Jenis kelamin wajib dipilih.'),
            'gender.in' => __('Jenis kelamin tidak valid.'),

            'birth_place.required' => __('Tempat lahir wajib diisi.'),
            'birth_date.required' => __('Tanggal lahir wajib diisi.'),
            'birth_date.date' => __('Tanggal lahir tidak valid.'),

            'nationality.max' => __('Kewarganegaraan maksimal :max karakter.'),
            'religion.in' => __('Agama tidak valid.'),
            'blood_type.in' => __('Golongan darah tidak valid.'),
            'marital_status.in' => __('Status pernikahan tidak valid.'),

            'phone.required' => __('Nomor telepon wajib diisi.'),
            'phone.max' => __('Nomor telepon maksimal :max karakter.'),
            'email.email' => __('Format email tidak valid.'),

            'end_date.after_or_equal' => __('Tanggal berhenti tidak boleh sebelum tanggal bergabung.'),
            'salary.numeric' => __('Gaji harus berupa angka.'),
            'salary.min' => __('Gaji tidak boleh negatif.'),
            'status.in' => __('Status tidak valid.'),

            'photo.image' => __('Foto harus berupa gambar.'),
            'photo.max' => __('Ukuran foto tidak boleh lebih dari 2MB.'),
        ];
    }
}
```

`app/Http/Requests/Staff/StoreStaffAccountRequest.php`:

```php
<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // $this->staff -- route-model-binding magic property (nama
            // parameter route {staff}), pola sama UpdatePlayerAccountRequest.
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('id_academy', $this->staff->id_academy)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('Email akun staff wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.unique' => __('Email sudah digunakan oleh akun lain.'),

            'password.required' => __('Password akun staff wajib diisi.'),
            'password.min' => __('Password minimal :min karakter.'),
            'password.confirmed' => __('Konfirmasi password tidak sesuai.'),

            'role_id.required' => __('Role wajib dipilih.'),
            'role_id.exists' => __('Role tidak ditemukan pada academy ini.'),
        ];
    }
}
```

`app/Http/Requests/Staff/UpdateStaffAccountRequest.php` (pola identik `UpdatePlayerAccountRequest`):

```php
<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->staff->id_user, 'id_user'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Nama akun wajib diisi.'),
            'name.max' => __('Nama maksimal :max karakter.'),

            'email.required' => __('Email akun wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'email.unique' => __('Email sudah digunakan akun lain.'),
        ];
    }
}
```

**✅ Cek dulu**: `php -l` keempat file di atas tidak ada error.

---

## Tahap 8 — Routes, Permission, Config

### 8a. Routes

`routes/web.php` — tambah import:

```php
use App\Http\Controllers\StaffAccountController;
use App\Http\Controllers\StaffController;
```

Tambahkan block route Staff Account **sebelum** block Staff Management (pola sama Player — account routes ditulis sebelum resource route entitasnya), setelah block `staff-positions` (dari `issue10.md`):

```php
    /*
    |--------------------------------------------------------------------------
    | Staff Account Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff/{staff}/account')
    ->name('staff.account.')
    ->group(function () {

        Route::middleware('permission:user.create')->group(function () {

            Route::get('/create', [StaffAccountController::class, 'create'])->name('create');
            Route::post('/', [StaffAccountController::class, 'store'])->name('store');

        });

        Route::middleware('permission:user.update')->group(function () {

            Route::get('/edit', [StaffAccountController::class, 'edit'])->name('edit');
            Route::put('/', [StaffAccountController::class, 'update'])->name('update');
            Route::patch('/status', [StaffAccountController::class, 'status'])->name('status');
            Route::patch('/password', [StaffAccountController::class, 'password'])->name('password');

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Staff Management
    |--------------------------------------------------------------------------
    */
    Route::resource('staff', StaffController::class)
        ->middlewareFor(['index', 'show'], 'permission:staff.view')
        ->middlewareFor(['create', 'store'], 'permission:staff.create')
        ->middlewareFor(['edit', 'update'], 'permission:staff.update')
        ->middlewareFor('destroy', 'permission:staff.delete');
```

### 8b. Permission — `database/seeders/RolePermissionSeeder.php`

Tambahkan ke array `$permissions`, setelah blok `// Staff Position` (dari `issue10.md`):

```php
            // Staff
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',
```

> `user.create`/`user.update` **tidak perlu ditambah** — sudah ada di array permission (dipakai Player Account).

### 8c. `config/faos.php`

Tambahkan ke `role_templates.Owner`, setelah baris `staff_position.*` (dari `issue10.md`):

```php
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
```

**✅ Cek dulu**

```bash
php artisan migrate:fresh --seed
php artisan route:list --name=staff
```

Harus tampil route `staff.index/create/store/show/edit/update/destroy` + `staff.account.create/store/edit/update/status/password`.

---

## Tahap 9 — Guard Delete Balik ke `issue9.md`/`issue10.md`

**Tujuan**: menutup celah yang sengaja ditunda `issue9.md`/`issue10.md` — sekarang tabel `staff` sudah ada, tambahkan relasi + guard delete di `EmploymentType`/`StaffPosition`.

`app/Models/EmploymentType.php` — tambahkan relasi, setelah `academy()`:

```php
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'id_employment_type', 'id_employment_type');
    }
```

(tambahkan import `use Illuminate\Database\Eloquent\Relations\HasMany;` dan `use App\Models\Staff;` — atau cukup `Staff::class` kalau sudah 1 namespace `App\Models`, tidak perlu import tambahan).

`app/Models/StaffPosition.php` — sama, tambahkan setelah `role()`:

```php
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'id_staff_position', 'id_staff_position');
    }
```

(tambahkan import `use Illuminate\Database\Eloquent\Relations\HasMany;`).

`app/Services/EmploymentTypeService.php` — ganti method `delete()`:

```php
    public function delete(EmploymentType $employmentType): bool
    {
        return DB::transaction(function () use ($employmentType) {

            if ($employmentType->staff()->exists()) {
                throw new \Exception(__('Employment type masih digunakan oleh staff, tidak dapat dihapus. Nonaktifkan employment type ini kalau sudah tidak dipakai.'));
            }

            return $employmentType->delete();
        });
    }
```

`app/Services/StaffPositionService.php` — ganti method `delete()`:

```php
    public function delete(StaffPosition $staffPosition): bool
    {
        return DB::transaction(function () use ($staffPosition) {

            if ($staffPosition->staff()->exists()) {
                throw new \Exception(__('Staff position masih digunakan oleh staff, tidak dapat dihapus. Nonaktifkan staff position ini kalau sudah tidak dipakai.'));
            }

            return $staffPosition->delete();
        });
    }
```

`lang/en.json` — tambahkan:

```json
"Employment type masih digunakan oleh staff, tidak dapat dihapus. Nonaktifkan employment type ini kalau sudah tidak dipakai.": "This employment type is still used by staff and cannot be deleted. Deactivate it instead if it's no longer needed.",
"Staff position masih digunakan oleh staff, tidak dapat dihapus. Nonaktifkan staff position ini kalau sudah tidak dipakai.": "This staff position is still used by staff and cannot be deleted. Deactivate it instead if it's no longer needed."
```

Sekalian update `resources/views/employment-types/index.blade.php` & `resources/views/staff-positions/index.blade.php` (dari `issue9.md`/`issue10.md`) — tambahkan `:disabled` di `<x-button.delete>` (di KEDUA tempat, table & card list), sama pola `player-types/index.blade.php`:

```blade
<x-button.delete :action="route('employment-types.destroy', $employmentType)" :name="$employmentType->name"
    :disabled="$employmentType->staff_count > 0"
    reason="{{ __('Employment type masih digunakan oleh staff, tidak dapat dihapus.') }}" />
```

```blade
<x-button.delete :action="route('staff-positions.destroy', $staffPosition)" :name="$staffPosition->name"
    :disabled="$staffPosition->staff_count > 0"
    reason="{{ __('Staff position masih digunakan oleh staff, tidak dapat dihapus.') }}" />
```

Supaya `$employmentType->staff_count`/`$staffPosition->staff_count` tersedia, tambahkan `withCount('staff')` di `EmploymentTypeService::paginate()` dan `StaffPositionService::paginate()` (edit method yang sudah ada di kedua file, dari `issue9.md`/`issue10.md`):

```php
    public function paginate(?int $perPage = null)
    {
        return EmploymentType::with('academy')
            ->withCount('staff')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }
```

```php
    public function paginate(?int $perPage = null)
    {
        return StaffPosition::with(['academy', 'role'])
            ->withCount('staff')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create();
$et = \App\Models\EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
$sp = \App\Models\StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
$staff = \App\Models\Staff::factory()->create([
    'id_academy' => $academy->id_academy, 'id_employment_type' => $et->id_employment_type, 'id_staff_position' => $sp->id_staff_position,
]);

app(\App\Services\EmploymentTypeService::class)->delete($et);
// harus throw Exception "Employment type masih digunakan oleh staff..."
```

Buka `/employment-types` dan `/staff-positions` di browser → tombol hapus untuk baris yang masih dipakai staff harus disabled dengan alasan.

---

## Tahap 10 — Views

**Tujuan**: 6 view baru (index/create/edit/show + account/create + account/edit), plus perbaikan i18n `<x-account.dropdown>`.

### 10a. `resources/views/components/account/dropdown.blade.php`

Bungkus seluruh string dengan `__()` (luput dari sweep i18n sebelumnya, sekarang disentuh ulang jadi sekalian dibenahi — pola sama `<x-logo-upload-field>`/`<x-table.toolbar>` di brief-brief sebelumnya):

```blade
 <div x-data="{ open: false }" class="relative">

     <button type="button" @click="open=!open" class="btn btn-secondary">
         <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
             <path d="M10 4.5V4.51M10 10V10.01M10 15.5V15.51" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" />
         </svg>

     </button>

     <div x-show="open" x-cloak @click.away="open=false" class="dropdown-menu right-0">

         @if ($user)

             {{-- Edit Account --}}
             <a href="{{ route($routeEdit, $model) }}" class="dropdown-item">
                 {{ __('Edit Account') }}
             </a>

             {{-- Reset Password --}}
             <button type="button" class="dropdown-item-danger w-full text-left"
                 @click="$dispatch('reset-password-confirm',{
                    action:'{{ route($routePassword, $model) }}',
                    name:'{{ $model->name }}'
                })">
                 {{ __('Reset Password') }}
             </button>

             <div class="dropdown-divider"></div>

             {{-- Status Toggle --}}
             @if ($user->status)
                 <button type="button" class="dropdown-item-danger w-full text-left"
                     @click="$dispatch('status-confirm',{
                        action:'{{ route($routeStatus, $model) }}',
                        name:'{{ $model->name }}',
                        status:true
                    })">
                     {{ __('Disable Account') }}
                 </button>
             @else
                 <button type="button" class="dropdown-item-success w-full text-left"
                     @click="$dispatch('status-confirm',{
                        action:'{{ route($routeStatus, $model) }}',
                        name:'{{ $model->name }}',
                        status:false
                    })">
                     {{ __('Enable Account') }}
                 </button>
             @endif
         @else
             <a href="{{ route($routeCreate, $model) }}" class="dropdown-item">
                 {{ __('Buat Account') }}
             </a>

         @endif

     </div>

 </div>
```

### 10b. `resources/views/staff/index.blade.php`

Struktur mengikuti `player-types/index.blade.php` (tabel + card list responsif) TAPI dengan toolbar filter (`<x-table.toolbar>`) & tabs status (`<x-table.tabs>`) — karena data staff berpotensi banyak baris, sama seperti `players/index.blade.php`. Kolom tabel: **Staff** (foto+nama+kode), **Employment Type**, **Staff Position**, **Kontak** (telepon), **Status**, **Akun** (badge ada/tidak), **Aksi**.

```blade
@extends('layouts.app', ['page' => 'staff'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Staff List') }}</h3>
                <p class="card-description">{{ __('Manajemen data staff academy (coach, admin, finance, dsb).') }}</p>
            </div>

            @can('staff.create')
                <div class="card-actions">
                    <a href="{{ route('staff.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Staff') }}
                    </a>
                </div>
            @endcan
        </div>

        <x-table.tabs :tabs="[
            '' => __('Semua'),
            'active' => __('Aktif'),
            'inactive' => __('Nonaktif'),
            'resigned' => __('Resign'),
        ]" :counts="$statusCounts" :active="$filters['status'] ?? ''" param="status" />

        <x-table.toolbar :filters="$filters" search-placeholder="{{ __('Cari nama, nickname, atau kode staff...') }}">
            <select name="id_employment_type" class="form-select">
                <option value="">{{ __('Semua Employment Type') }}</option>
                @foreach ($employmentTypeOptions as $type)
                    <option value="{{ $type->id_employment_type }}" @selected(($filters['id_employment_type'] ?? null) === $type->id_employment_type)>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>

            <select name="id_staff_position" class="form-select">
                <option value="">{{ __('Semua Staff Position') }}</option>
                @foreach ($staffPositionOptions as $position)
                    <option value="{{ $position->id_staff_position }}" @selected(($filters['id_staff_position'] ?? null) === $position->id_staff_position)>
                        {{ $position->name }}
                    </option>
                @endforeach
            </select>
        </x-table.toolbar>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Staff') }}</th>
                        <th class="table-header-cell">{{ __('Employment Type') }}</th>
                        <th class="table-header-cell">{{ __('Staff Position') }}</th>
                        <th class="table-header-cell">{{ __('Telepon') }}</th>
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell">{{ __('Akun') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($staff as $item)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <div class="avatar avatar-square shrink-0">
                                        @if ($item->photo)
                                            <img src="{{ asset('storage/' . $item->photo) }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="avatar-placeholder">{{ strtoupper(substr($item->full_name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="table-title">{{ $item->full_name }}</span>
                                        <span class="table-subtitle">{{ $item->staff_code }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $item->employmentType->name ?? '-' }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $item->position->name ?? '-' }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $item->phone }}</span>
                            </td>

                            <td class="table-cell">
                                @php
                                    $statusBadge = match ($item->status) {
                                        'active' => ['label' => __('Aktif'), 'class' => 'badge-success'],
                                        'inactive' => ['label' => __('Nonaktif'), 'class' => 'badge-danger'],
                                        'resigned' => ['label' => __('Resign'), 'class' => 'badge-secondary'],
                                        default => ['label' => '-', 'class' => 'badge-secondary'],
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                            </td>

                            <td class="table-cell">
                                @if ($item->id_user)
                                    <span class="badge badge-success">{{ __('Ada') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('Belum Ada') }}</span>
                                @endif
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('staff.view')
                                        <a href="{{ route('staff.show', $item) }}" class="btn-icon" title="{{ __('Detail') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M2 10C2 10 5 4 10 4C15 4 18 10 18 10C18 10 15 16 10 16C5 16 2 10 2 10Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                                <circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('staff.update')
                                        <a href="{{ route('staff.edit', $item) }}" class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('staff.delete')
                                        <x-button.delete :action="route('staff.destroy', $item)" :name="$item->full_name" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Staff') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan staff pertama.') }}</p>

                                    @can('staff.create')
                                        <a href="{{ route('staff.create') }}" class="empty-link">{{ __('Tambah Staff') }}</a>
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
            @forelse ($staff as $item)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="avatar avatar-square shrink-0">
                                @if ($item->photo)
                                    <img src="{{ asset('storage/' . $item->photo) }}" class="h-full w-full object-cover">
                                @else
                                    <span class="avatar-placeholder">{{ strtoupper(substr($item->full_name, 0, 2)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <span class="table-title truncate">{{ $item->full_name }}</span>
                                <span class="table-subtitle">{{ $item->staff_code }}</span>
                            </div>
                        </div>

                        @php
                            $statusBadgeCard = match ($item->status) {
                                'active' => ['label' => __('Aktif'), 'class' => 'badge-success'],
                                'inactive' => ['label' => __('Nonaktif'), 'class' => 'badge-danger'],
                                'resigned' => ['label' => __('Resign'), 'class' => 'badge-secondary'],
                                default => ['label' => '-', 'class' => 'badge-secondary'],
                            };
                        @endphp
                        <span class="badge {{ $statusBadgeCard['class'] }} shrink-0">{{ $statusBadgeCard['label'] }}</span>
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Employment Type') }}</span>
                            <span class="table-text">{{ $item->employmentType->name ?? '-' }}</span>
                        </div>
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Staff Position') }}</span>
                            <span class="table-text">{{ $item->position->name ?? '-' }}</span>
                        </div>
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Telepon') }}</span>
                            <span class="table-text">{{ $item->phone }}</span>
                        </div>
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Akun') }}</span>
                            @if ($item->id_user)
                                <span class="badge badge-success w-fit">{{ __('Ada') }}</span>
                            @else
                                <span class="badge badge-secondary w-fit">{{ __('Belum Ada') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('staff.view')
                            <a href="{{ route('staff.show', $item) }}" class="btn-icon" title="{{ __('Detail') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M2 10C2 10 5 4 10 4C15 4 18 10 18 10C18 10 15 16 10 16C5 16 2 10 2 10Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('staff.update')
                            <a href="{{ route('staff.edit', $item) }}" class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('staff.delete')
                            <x-button.delete :action="route('staff.destroy', $item)" :name="$item->full_name" />
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
                        <h4 class="empty-title">{{ __('Belum ada Staff') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan staff pertama.') }}</p>

                        @can('staff.create')
                            <a href="{{ route('staff.create') }}" class="empty-link">{{ __('Tambah Staff') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($staff->hasPages())
            <div class="table-footer">
                {{ $staff->withQueryString()->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
```

> `<x-table.tabs>`/`<x-table.toolbar>` dipakai di sini (BEDA dengan `issue9.md`/`issue10.md` yang cuma master data kecil) — data Staff berpotensi banyak baris, sama alasannya dengan `players/index.blade.php`. Cek dulu prop persis komponen itu (`resources/views/components/table/tabs.blade.php` & `.../toolbar.blade.php`) sebelum pakai — sesuaikan nama prop kalau beda dari yang ditulis di atas (brief ini menulis berdasarkan pola yang dijelaskan `docs/frontend-standard.md`, verifikasi ke source component sebelum implementasi final).

### 10c. `resources/views/staff/create.blade.php`

Ikuti taksonomi [2a](#2a-struktur--field-ikuti-taksonomi-form-8-kategori-docsfrontend-standardmd) — kolom kiri: Academy(SA)/Nama Lengkap/Nickname/Employment Type/Staff Position/Telepon/Email/Alamat/Kota/Provinsi/Kode Pos. Kolom kanan: Gender/Tempat Lahir/Tanggal Lahir/Kewarganegaraan/Agama/Golongan Darah/Status Pernikahan/Tanggal Bergabung/Tanggal Keluar/Gaji/Catatan/Foto (`<x-staff-photo-field />`)/Status Kepegawaian (toggle select, bukan boolean — 3 nilai).

Pola field individual (select/input/textarea/toggle) **ikuti persis** contoh yang sudah ditulis lengkap di `issue9.md`/`issue10.md` Tahap 8 (`form-group`, `form-input`, `form-select`, `@error`, dst) — brief ini tidak mengulang seluruh markup HTML supaya tidak berlebihan panjang, tapi strukturnya WAJIB sama. Poin-poin spesifik Staff:

- Dropdown `id_employment_type`/`id_staff_position`: `@foreach ($employmentTypes as $type) <option value="{{ $type->id_employment_type }}">{{ $type->name }}</option> @endforeach` (sama pola `players/create.blade.php` untuk `playerTypes`/`playerCategories`).
- Dropdown `gender`: `<option value="male">{{ __('Laki-laki') }}</option><option value="female">{{ __('Perempuan') }}</option>`.
- Dropdown `religion`: opsi `islam/kristen/katolik/hindu/buddha/konghucu/lainnya`, label kapital sesuai (`{{ __('Islam') }}`, dst), **tidak required** (ada opsi kosong "Pilih Agama").
- Dropdown `blood_type`: opsi `A/B/AB/O`, tidak required.
- Dropdown `marital_status`: opsi `single/married/divorced/widowed` → label `{{ __('Belum Menikah') }}`/`{{ __('Menikah') }}`/`{{ __('Cerai') }}`/`{{ __('Janda/Duda') }}`, tidak required.
- Input `salary`: `type="number" step="1000" min="0"`.
- Dropdown `status`: opsi `active/inactive/resigned` → `{{ __('Aktif') }}`/`{{ __('Nonaktif') }}`/`{{ __('Resign') }}`, default `active`. **BUKAN toggle boolean** (beda dari `status` di `issue9.md`/`issue10.md`) — 3 kemungkinan nilai, wajib pakai `<select>`.
- Foto: `<x-staff-photo-field :current-photo-url="null" />` (Tahap 4).
- Footer: `Reset` + `Simpan Staff` (form create, ada index — ikuti aturan tombol submit `docs/frontend-standard.md`).

### 10d. `resources/views/staff/edit.blade.php`

Sama seperti create, dengan penyesuaian standar form edit (`value`/`old()` pakai default `$staff->xxx`, field Academy jadi teks read-only kalau Super Admin, `<x-staff-photo-field :current-photo-url="$staff->photo ? asset('storage/' . $staff->photo) : null" />`, tambahkan **Staff Code** readonly di kolom kiri paling atas (`<p class="form-input bg-gray-50 dark:bg-gray-800">{{ $staff->staff_code }}</p>`), method PUT, tombol footer `Batal` + `Update Staff`). Tambahkan juga `<x-account.dropdown>` di `card-actions` header (pola persis `players/edit.blade.php`):

```blade
                @if ($staff->id_user)
                    @can('user.update')
                        <x-account.dropdown :model="$staff" :user="$staff->user" route-create="staff.account.create"
                            route-edit="staff.account.edit" route-password="staff.account.password"
                            route-status="staff.account.status" />
                    @endcan
                @else
                    @can('user.create')
                        <x-account.dropdown :model="$staff" :user="$staff->user" route-create="staff.account.create"
                            route-edit="staff.account.edit" route-password="staff.account.password"
                            route-status="staff.account.status" />
                    @endcan
                @endif
```

Tambahkan `<x-modal.reset-password />` dan `<x-modal.status />` sebelum `@endsection`.

### 10e. `resources/views/staff/show.blade.php`

Struktur ikuti `players/show.blade.php` (Tahap referensi awal brief ini) PERSIS — header avatar+nama+aksi (Kembali/Edit/dropdown akun), tab kiri (`Profil Staff`/`Kontak`/`Kepegawaian`), card kanan (`Informasi Employment` [Employment Type + Staff Position + Status], `Informasi Account`, `Informasi Sistem`). Ganti seluruh `$player->name` → `$staff->full_name`, `$player->player_code` → `$staff->staff_code`, field lain menyesuaikan (birth_place/birth_date/nationality/religion/blood_type/marital_status di tab Profil; phone/email/address/city/province/postal_code di tab Kontak; employmentType/position/join_date/end_date/salary/status di tab Kepegawaian). `<x-modal.reset-password />`/`<x-modal.status />` di akhir, sama seperti `players/show.blade.php`.

### 10f. `resources/views/staff/account/create.blade.php`

Sama persis `players/account/create.blade.php` (Tahap referensi awal brief ini) dengan **1 field tambahan**: dropdown Role (setelah Email, sebelum Password):

```blade
                <div class="form-group">
                    <label class="form-label">
                        {{ __('Role') }} <span class="text-error-500">*</span>
                    </label>

                    <select name="role_id" class="form-select @error('role_id') form-danger @enderror">
                        <option value="">{{ __('Pilih Role') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}"
                                @selected((string) old('role_id', $staff->position->role_id) === (string) $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('role_id')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
```

> `old('role_id', $staff->position->role_id)` — Default Role dari Staff Position (`issue10.md`) jadi pilihan AWAL, TIDAK dipaksa (admin bebas ganti sebelum submit). Ganti `route('players.account.store', $player)` → `route('staff.account.store', $staff)`, `$player->name` → `$staff->full_name`, teks "Buat Akun Player" → "Buat Akun Staff", dst.

### 10g. `resources/views/staff/account/edit.blade.php`

Sama persis `players/account/edit.blade.php`, ganti `$player`/`route('players.account.*')` → `$staff`/`route('staff.account.*')`, `$player->name` → `$staff->full_name`.

**✅ Cek dulu**: buka `/staff` → tabs + toolbar filter berfungsi. `/staff/create` → seluruh field taksonomi tampil sesuai urutan kolom. Submit → staff baru muncul di index. Buka `/staff/{id}` → detail tab berfungsi. Klik dropdown akun → "Buat Account" → form create account → dropdown Role ter-pre-select ke Default Role staff position → submit → akun terbuat, `$staff->id_user` terisi. Reset password/enable-disable via dropdown+modal berfungsi.

---

## Tahap 11 — Menu Sidebar (Final) & `lang/en.json`

`resources/views/partials/sidebar.blade.php` — ubah `$officeRoutes` jadi (final, ketiga modul):

```php
                        $officeRoutes = ['staff.*', 'staff-positions.*', 'employment-types.*'];
```

Sisipkan `<li>` "Staff" **SEBELUM** `<li>` "Staff Position" (paling atas di dropdown, sesuai urutan diagram awal):

```blade
                                {{-- Staff --}}
                                @can('staff.view')
                                    <li>
                                        <a href="{{ route('staff.index') }}" class="menu-dropdown-item group"
                                            :class="{{ Route::is('staff.*') ? 'true' : 'false' }}
                                                ?
                                                'menu-dropdown-item-active' :
                                                'menu-dropdown-item-inactive'">
                                            {{ __('Staff') }}
                                        </a>
                                    </li>
                                @endcan
```

Boleh hapus komentar placeholder `{{-- Staff & Staff Position disisipkan di sini... --}}` dari `issue9.md` sekarang (semua 3 item sudah terpasang).

`lang/en.json` — tambahkan entry (daftar cukup panjang, cek dulu duplikat sebelum menambah — banyak istilah seperti "Aktif"/"Status"/"Deskripsi" kemungkinan sudah ada):

```json
"Staff": "Staff",
"Staff List": "Staff List",
"Manajemen data staff academy (coach, admin, finance, dsb).": "Manage academy staff data (coaches, admins, finance, etc.).",
"Tambah Staff": "Add Staff",
"Belum ada Staff": "No Staff Yet",
"Tambahkan staff pertama.": "Add the first staff member.",
"Cari nama, nickname, atau kode staff...": "Search name, nickname, or staff code...",
"Semua Employment Type": "All Employment Types",
"Semua Staff Position": "All Staff Positions",
"Telepon": "Phone",
"Akun": "Account",
"Ada": "Yes",
"Belum Ada": "Not Yet",
"Detail": "Details",
"Resign": "Resigned",
"Nama Lengkap": "Full Name",
"Nickname": "Nickname",
"Jenis Kelamin": "Gender",
"Laki-laki": "Male",
"Perempuan": "Female",
"Tempat Lahir": "Birth Place",
"Tanggal Lahir": "Birth Date",
"Kewarganegaraan": "Nationality",
"Agama": "Religion",
"Pilih Agama": "Select Religion",
"Islam": "Islam",
"Kristen": "Christian",
"Katolik": "Catholic",
"Hindu": "Hindu",
"Buddha": "Buddhist",
"Konghucu": "Confucian",
"Lainnya": "Other",
"Golongan Darah": "Blood Type",
"Pilih Golongan Darah": "Select Blood Type",
"Status Pernikahan": "Marital Status",
"Pilih Status Pernikahan": "Select Marital Status",
"Belum Menikah": "Single",
"Menikah": "Married",
"Cerai": "Divorced",
"Janda/Duda": "Widowed",
"Alamat": "Address",
"Kota": "City",
"Provinsi": "Province",
"Kode Pos": "Postal Code",
"Tanggal Bergabung": "Join Date",
"Tanggal Keluar": "End Date",
"Gaji": "Salary",
"Catatan": "Notes",
"Status Kepegawaian": "Employment Status",
"Employment Type": "Employment Type",
"Staff Position": "Staff Position",
"Staff Code": "Staff Code",
"Foto Staff": "Staff Photo",
"Klik untuk mengganti foto staff": "Click to change staff photo",
"Klik untuk upload foto staff": "Click to upload staff photo",
"JPG, PNG, WEBP maksimal 2MB": "JPG, PNG, WEBP up to 2MB",
"Ganti Foto": "Change Photo",
"Simpan Staff": "Save Staff",
"Update Staff": "Update Staff",
"Staff berhasil ditambahkan.": "Staff added successfully.",
"Staff berhasil diperbarui.": "Staff updated successfully.",
"Staff berhasil dihapus.": "Staff deleted successfully.",
"Buat Akun Staff": "Create Staff Account",
"Edit Akun Staff": "Edit Staff Account",
"Membuat akun login untuk": "Creating a login account for",
"Mengubah informasi akun login untuk": "Updating login account information for",
"Role": "Role",
"Pilih Role": "Select Role",
"Buat Akun": "Create Account",
"Akun staff berhasil dibuat.": "Staff account created successfully.",
"Account staff berhasil diperbarui.": "Staff account updated successfully.",
"Account staff berhasil diaktifkan.": "Staff account activated successfully.",
"Account staff berhasil dinonaktifkan.": "Staff account deactivated successfully.",
"Staff sudah memiliki akun.": "Staff already has an account.",
"Staff belum memiliki akun.": "Staff does not have an account yet.",
"Employment type wajib dipilih.": "Employment type is required.",
"Employment type tidak valid.": "Invalid employment type.",
"Staff position wajib dipilih.": "Staff position is required.",
"Staff position tidak valid.": "Invalid staff position.",
"Nama lengkap wajib diisi.": "Full name is required.",
"Nama lengkap maksimal :max karakter.": "Full name may not exceed :max characters.",
"Nickname maksimal :max karakter.": "Nickname may not exceed :max characters.",
"Jenis kelamin wajib dipilih.": "Gender is required.",
"Jenis kelamin tidak valid.": "Invalid gender.",
"Tempat lahir wajib diisi.": "Birth place is required.",
"Tanggal lahir wajib diisi.": "Birth date is required.",
"Tanggal lahir tidak valid.": "Invalid birth date.",
"Kewarganegaraan maksimal :max karakter.": "Nationality may not exceed :max characters.",
"Agama tidak valid.": "Invalid religion.",
"Golongan darah tidak valid.": "Invalid blood type.",
"Status pernikahan tidak valid.": "Invalid marital status.",
"Nomor telepon wajib diisi.": "Phone number is required.",
"Nomor telepon maksimal :max karakter.": "Phone number may not exceed :max characters.",
"Tanggal berhenti tidak boleh sebelum tanggal bergabung.": "End date cannot be before the join date.",
"Gaji harus berupa angka.": "Salary must be a number.",
"Gaji tidak boleh negatif.": "Salary cannot be negative.",
"Foto harus berupa gambar.": "Photo must be an image.",
"Ukuran foto tidak boleh lebih dari 2MB.": "Photo size must not exceed 2MB.",
"Email akun staff wajib diisi.": "Staff account email is required.",
"Password akun staff wajib diisi.": "Staff account password is required.",
"Role tidak ditemukan pada academy ini.": "Role not found in this academy.",
"Edit Account": "Edit Account",
"Reset Password": "Reset Password",
"Disable Account": "Disable Account",
"Enable Account": "Enable Account",
"Buat Account": "Create Account"
```

**✅ Cek dulu**: dropdown "Office" final urutannya **Staff → Staff Position → Employment Type**. Ganti locale Inggris → seluruh halaman Staff (index/create/edit/show/account) + dropdown akun (`<x-account.dropdown>`) tampil Bahasa Inggris.

---

## Tahap 12 — Test & Dokumentasi

### 12a. `tests/Feature/StaffTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStaffPrereqs(Academy $academy): array
    {
        return [
            'employmentType' => EmploymentType::factory()->create(['id_academy' => $academy->id_academy]),
            'staffPosition' => StaffPosition::factory()->create(['id_academy' => $academy->id_academy]),
        ];
    }

    public function test_create_staff_generate_staff_code_otomatis(): void
    {
        $academy = Academy::factory()->create(['code' => 'FCX']);
        $prereqs = $this->makeStaffPrereqs($academy);

        $staff = app(StaffService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'full_name' => 'Budi Santoso',
            'gender' => 'male',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'phone' => '081234567890',
        ]);

        $this->assertStringStartsWith('FCX' . now()->format('y'), $staff->staff_code);
        $this->assertSame('Indonesia', $staff->nationality);
        $this->assertSame('active', $staff->status);
    }

    public function test_hapus_staff_ikut_menghapus_akun_terkait(): void
    {
        $academy = Academy::factory()->create();
        $prereqs = $this->makeStaffPrereqs($academy);
        $user = User::factory()->create(['id_academy' => $academy->id_academy]);

        $staff = Staff::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_user' => $user->id_user,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
        ]);

        app(StaffService::class)->delete($staff);

        $this->assertDatabaseMissing('users', ['id_user' => $user->id_user]);
    }

    public function test_getname_attribute_mengembalikan_full_name(): void
    {
        $staff = new Staff(['full_name' => 'Citra Dewi']);

        $this->assertSame('Citra Dewi', $staff->name);
    }
}
```

### 12b. `tests/Feature/StaffAccountTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_buat_akun_staff_dengan_role_dari_dropdown(): void
    {
        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $coachRole = Role::factory()->create(['id_academy' => $academy->id_academy, 'name' => 'Coach']);
        $staffPosition = StaffPosition::factory()->create([
            'id_academy' => $academy->id_academy, 'role_id' => $coachRole->id,
        ]);

        $staff = Staff::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_employment_type' => $employmentType->id_employment_type,
            'id_staff_position' => $staffPosition->id_staff_position,
        ]);

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $ownerRole = Role::factory()->create(['id_academy' => $academy->id_academy, 'name' => 'Owner']);
        $owner->assignRole($ownerRole);
        $this->actingAs($owner);

        $response = $this->post(route('staff.account.store', $staff), [
            'email' => 'coach@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $coachRole->id,
        ]);

        $response->assertRedirect();
        $staff->refresh();
        $this->assertNotNull($staff->id_user);
        $this->assertTrue($staff->user->hasRole('Coach'));
    }

    public function test_role_dari_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $employmentType = EmploymentType::factory()->create(['id_academy' => $academyA->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academyA->id_academy]);
        $roleAcademyB = Role::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'RoleB']);

        $staff = Staff::factory()->create([
            'id_academy' => $academyA->id_academy,
            'id_employment_type' => $employmentType->id_employment_type,
            'id_staff_position' => $staffPosition->id_staff_position,
        ]);

        $owner = User::factory()->create(['id_academy' => $academyA->id_academy, 'status' => true]);
        $ownerRole = Role::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Owner']);
        $owner->assignRole($ownerRole);
        $this->actingAs($owner);

        $response = $this->post(route('staff.account.store', $staff), [
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $roleAcademyB->id,
        ]);

        $response->assertSessionHasErrors('role_id');
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=StaffTest
php artisan test --filter=StaffAccountTest
php artisan test
npm run build
```

Seluruh test pass, termasuk full suite (baseline + test 3 brief Office, tanpa regresi). `npm run build` sukses (ada perubahan Blade baru).

### 12c. `docs/permission-reference.md`

Tambahkan section Staff (format sama seperti section Player, termasuk sub-module Account) setelah section Staff Position:

```markdown
## Module: Staff

Status: **✅ Implemented**

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `staff.view` | Lihat daftar & detail staff | `staff.index`, `staff.show` (route middleware) |
| `staff.create` | Tambah staff baru | `staff.create`, `staff.store` (route middleware) + `@can()` tombol "Tambah" |
| `staff.update` | Ubah data staff | `staff.edit`, `staff.update` (route middleware) + `@can()` tombol Edit |
| `staff.delete` | Hapus staff | `staff.destroy` (route middleware) + `@can()` tombol Hapus |

### Sub-module: Staff Account (login staff, opsional)

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `user.create` | Buat akun login untuk staff yang belum punya akun | `staff.account.create`, `staff.account.store` |
| `user.update` | Edit akun, reset password, DAN aktif/nonaktifkan akun staff | `staff.account.edit`, `staff.account.update`, `staff.account.status`, `staff.account.password` + `<x-account.dropdown>` |

Catatan:
- Sub-module Account **reuse** permission generik `user.create`/`user.update` yang sama dipakai Player Account — bukan permission baru `staff_account.*` (pola sama Player, beda dengan Academy Account yang sengaja pakai `academy.update`).
- Field "Default Role" (`staff_positions.role_id`, `issue10.md`) cuma jadi pilihan AWAL di dropdown Role saat buat akun — admin tetap bebas pilih role lain. Role yang dipilih divalidasi harus milik academy yang sama dengan staff (`StoreStaffAccountRequest`).
- Isolasi antar academy memakai `AcademyScope` — akses lintas academy = **404**. Default: 4 permission CRUD Owner-only lewat `config('faos.role_templates')`.
```

Perbarui juga section **Employment Type** (`issue9.md`) dan **Staff Position** (`issue10.md`) — hapus baris catatan "Guard delete belum aktif...", ganti jadi:

```markdown
- Guard delete ("masih dipakai staff") **aktif** — `EmploymentTypeService::delete()`/`StaffPositionService::delete()` menolak hapus kalau masih ada baris `staff` yang mereferensikannya.
```

### 12d. `README.md`

Centang roadmap:

```markdown
- [x] Staff Management
```

**✅ Cek dulu**: `docs/permission-reference.md` section Staff + update section Employment Type/Staff Position sudah benar. `README.md` roadmap "Staff Management" tercentang.

---

## 4. Alasan Teknis

### 4.1 Kenapa `AccountService::create()` diperluas terima `Role` object, bukan bikin method baru

`resolveRole()` (dipanggil `assignRole()`, dipanggil dari dalam `create()`) **sudah** menerima `Role|string $role` — infrastruktur untuk menerima objek Role langsung sudah ada, cuma `create()` sendiri yang membatasi parameternya ke `string`. Melebarkan tipe parameter itu (bukan bikin `createWithRole()` terpisah) adalah perubahan paling minimal: 1 baris, backward-compatible penuh (pemanggilan lama dengan string tetap valid), tidak menduplikasi logic `DB::transaction`/`Hash::make`/`assignRole` yang sudah ada.

### 4.2 Kenapa `getNameAttribute()`, bukan ubah nama kolom

Kolom `full_name` (bukan `name`) dipilih sengaja karena lebih deskriptif untuk entitas Staff (ada juga `nickname` terpisah, konsisten pola Player `name`+`nick_name` — cuma nama kolom utamanya beda). Tapi `<x-account.dropdown>` (shared component, dipakai Player DAN Staff) generik mengakses `$model->name` tanpa peduli entitas apa yang dioper. Daripada mengubah `full_name` jadi `name` (kurang deskriptif, beda dari field lain macam `join_date`/`birth_date` yang sudah dipilih dengan penamaan jelas), Eloquent accessor `getNameAttribute()` membuat `$staff->name` otomatis tersedia sebagai alias baca-saja ke `full_name` — solusi 1 method, tidak menyentuh skema database, tidak mengubah component shared, dan mengikuti konvensi Eloquent standar (accessor untuk atribut turunan, `docs/coding-standard.md` tidak melarang pola ini).
