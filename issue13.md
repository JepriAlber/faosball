# Brief: Akun Owner Otomatis Jadi Staff (Academy Director)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Modul Academy Account (`AcademyAccountController`, `AcademyManagementService::create()`) dan modul Office (`issue9.md`–`issue12.md`: Employment Type, Staff Position, Staff, Employment Contract) **wajib sudah selesai & merged**. Baca juga `docs/module-standard.md`, `docs/architecture.md`, `docs/multi-tenancy.md`. Modul referensi paling mirip: `app/Http/Controllers/AcademyAccountController.php`, `app/Services/AcademyManagementService.php`, `app/Services/StaffService.php`.
> **Bukan module baru** — brief ini **menghubungkan** dua module yang sudah ada (Academy Account ↔ Staff), tidak menambah tabel/permission/route baru sama sekali.
> **Cara pakai brief ini**: Kerjakan Tahap 1 → 10 berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: Setiap kali akun Owner dibuat (baik lewat toggle "Buat Akun Owner" saat create Academy, maupun lewat halaman standalone `academies/{academy}/account/create`), sistem **otomatis** juga membuat 1 baris `staff` + 1 `employment_contract` (status Active) untuk orang itu — karena Owner secara nyata adalah bagian dari staff academy-nya sendiri. Posisi & jenis kepegawaian di-default otomatis (**Staff Position "Academy Director"**, **Employment Type "Permanent"** — keduanya sudah otomatis dibuat tiap Academy baru), TIDAK ditanyakan lewat form. **Bukan scope**: fitur pindah/transfer kepemilikan Academy ke Staff lain, sinkronisasi `Staff.email` saat Owner ganti email login (dianggap best-effort saat pembuatan saja), upload foto/isi biodata lengkap (nickname, agama, dll — itu semua tetap bisa diisi belakangan lewat halaman Edit Staff biasa), audit log siapa yang menghapus/mengubah data ini.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Isi `Staff.full_name`/`gender`/`birth_place`/`birth_date`/`phone` dengan data palsu/placeholder (mis. `'-'`, nama academy) supaya form Owner tetap cuma 2 field | Kolom-kolom itu **NOT NULL** di tabel `staff` untuk SEMUA staff, tanpa pengecualian — melonggarkan constraint atau mengisi data sampah merusak integritas Staff module secara umum. Form pembuatan akun Owner WAJIB menambah field biodata minimal ini, bukan mengakalinya | [Tahap 3](#tahap-3--acadamyformrequest--tambah-field-biodata-owner), [Tahap 4](#tahap-4--storeacademyaccountrequest--tambah-field-biodata) |
| Menampilkan dropdown Employment Type/Staff Position di form Owner | Posisi Owner **selalu** "Academy Director" + "Permanent" — dua-duanya sudah otomatis ada tiap Academy baru (`StaffPositionService::createDefaultStaffPositions()`, `EmploymentTypeService::createDefaultEmploymentTypes()`). Menanyakan ini di form Owner cuma menambah friksi tanpa gestur, dan berisiko admin salah pilih | [Tahap 1](#tahap-1--resolver-default-employment-type--staff-position-untuk-owner), [Tahap 2](#tahap-2--staffservice--createforowner-fix-createid_user-guard-delete-syncfullname) |
| Set `User.name` Owner = nama Academy seperti kode lama | Pola SEMUA account module lain (Player/Coach/Parent — lihat `PlayerAccountController`) memakai nama **orangnya**, bukan nama entitas induk. Sekarang Owner punya `full_name` asli (dari field biodata baru), `User.name` WAJIB ikut memakai itu, bukan `$academy->name` lagi | [Tahap 5](#tahap-5--academymanagementservicecreate--pakai-staffservicecreateforowner), [Tahap 6](#tahap-6--academyaccountcontroller--createforowner--syncfullname) |
| Biarkan `StaffService::delete()` bisa menghapus baris Staff milik Owner tanpa guard | `delete()` yang sudah ada otomatis ikut menghapus `User` terkait (`if ($staff->id_user) { User::where(...)->delete(); }`) — kalau Staff itu adalah Owner, ini diam-diam menghapus akun Owner DAN mengosongkan `academies.id_owner_user` (FK `nullOnDelete()`), tanpa peringatan jelas ke admin. WAJIB ditolak dengan pesan error yang jelas | [Tahap 2](#tahap-2--staffservice--createforowner-fix-createid_user-guard-delete-syncfullname) |
| Taruh logic pembuatan Staff untuk Owner langsung di Controller (`AcademyAccountController`/`AcademyController`) | Business logic wajib di Service Layer (lihat `docs/architecture.md`) — Controller cuma memanggil `StaffService::createForOwner()`, bukan menyusun payload `Staff::create()` sendiri | [Tahap 2](#tahap-2--staffservice--createforowner-fix-createid_user-guard-delete-syncfullname) |
| Lupa set `id_user` di payload `Staff::create()` pada `StaffService::create()` | `id_user` sudah ada di `$fillable` Staff, TAPI `StaffService::create()` saat ini tidak menaruhnya di array payload sama sekali — kalau tidak ditambahkan, `createForOwner()` akan diam-diam membuat Staff **tanpa** link ke User Owner-nya | [Tahap 2](#tahap-2--staffservice--createforowner-fix-createid_user-guard-delete-syncfullname) |

---

## 1. Konteks & Tujuan

Modul Academy Account (`AcademyAccountController`, toggle "Buat Akun Owner" di `AcademyManagementService::create()`) selama ini hanya membuat baris `users` + assign role "Owner" — **tidak pernah** membuat baris `staff`. Akibatnya Owner "menghilang" dari modul Staff: tidak muncul di halaman index Staff, tidak punya riwayat kontrak/posisi, dan data biodatanya (kalau suatu saat dibutuhkan utk keperluan administratif academy) tidak tersimpan di mana pun.

Secara bisnis, Owner **adalah** staff academy-nya sendiri (pemilik/pengelola, biasanya menjabat sebagai direktur/penanggung jawab) — bukan entitas terpisah. Brief ini menyambungkan pembuatan akun Owner dengan pembuatan Staff, supaya begitu akun Owner dibuat, orang itu otomatis:

1. Punya baris `staff` (identitas + biodata).
2. Punya 1 `employment_contract` berstatus **Active**, posisi **Academy Director**, jenis **Permanent** (default otomatis, tidak ditanyakan lewat form).
3. Muncul normal di halaman Staff (index/show/edit) seperti staff lain — bedanya cuma dia juga kebetulan pemilik academy.

## 2. Cara Kerja Solusi

### 2a. Kenapa field employment tidak ditanyakan di form Owner

Employment Type ("Permanent") dan Staff Position ("Academy Director") untuk Owner **selalu sama** — dua-duanya sudah menjadi bagian dari default template yang otomatis dibuat tiap Academy baru (lihat `config('faos.staff_position_templates')`, entry `'Academy Director' => ['code' => 'AD', 'default_role' => 'Owner', ...]`, dan `config('faos.employment_type_templates')`, entry `'Permanent'`). Karena nilainya deterministik, `StaffService::createForOwner()` (Tahap 2) me-resolve dua ID ini sendiri lewat kode ("AD") dan nama ("Permanent") — form pembuatan akun Owner cukup menanyakan biodata staff (nama lengkap, jenis kelamin, tempat/tanggal lahir, telepon), persis field minimal yang **NOT NULL** di tabel `staff`.

### 2b. Dua entry point, satu Service method

Akun Owner bisa dibuat dari 2 tempat:

1. `AcademyManagementService::create()` — toggle "Buat Akun Owner" saat Academy baru dibuat (field `owner_email`/`owner_password`, prefix `owner_` supaya tidak bentrok dengan field akademi sendiri).
2. `AcademyAccountController::store()` — halaman standalone, untuk Academy yang sudah ada tapi belum punya Owner (field `email`/`password`, tanpa prefix, karena form ini tidak punya field akademi lain yang bisa bentrok).

Dua tempat itu mengumpulkan field mentah dengan nama berbeda (`owner_full_name` vs `full_name`, dst). Supaya `StaffService` tidak perlu tahu asal-usul prefix itu, masing-masing pemanggil **menormalisasi** payload-nya sendiri (map ke key polos: `full_name`, `gender`, `birth_place`, `birth_date`, `phone`) sebelum memanggil `StaffService::createForOwner(Academy $academy, User $owner, array $data)` yang sama persis di kedua tempat.

### 2c. `User.name` sekarang nama orang, bukan nama Academy

Kode lama menyetel `'name' => $academy->name` saat membuat User Owner — itu tambal sulam karena dulu tidak ada data nama personal sama sekali. Sekarang form Owner mengumpulkan `full_name` asli, jadi `AccountService::create()` dipanggil dengan `'name' => $data['full_name']` (bukan nama academy lagi), konsisten dengan pola `PlayerAccountController` yang memakai `$player->name`.

### 2d. Sinkronisasi nama saat akun Owner diedit

Halaman Edit Akun Owner (`academies/account/edit.blade.php`) sudah punya field "Nama Account" yang mengubah `User.name`. Supaya data di halaman Staff tidak basi dibanding nama akun login-nya, `AcademyAccountController::update()` — setelah `AccountService::update()` — juga memanggil `StaffService::syncFullName($academy->owner, $request->name)` yang meng-update `Staff.full_name` milik User itu (kalau ada). Field biodata Staff LAIN (gender, tempat/tanggal lahir, telepon) **tidak** ikut disinkronkan di sini — itu tetap diedit manual lewat halaman Edit Staff biasa kalau perlu berubah, karena form Edit Akun Owner memang tidak pernah menanyakan field-field itu.

### 2e. Guard: Staff milik Owner tidak boleh dihapus dari halaman Staff

`StaffService::delete()` yang sudah ada otomatis ikut menghapus `User` terkait kalau `staff.id_user` terisi. Kalau staff itu kebetulan Owner academy-nya, ini akan diam-diam menghapus akun login Owner DAN mengosongkan `academies.id_owner_user` (FK `nullOnDelete()`) tanpa admin sadar dampaknya. `StaffService::delete()` ditambah guard di awal: kalau `$staff->id_user` sama dengan `$staff->academy->id_owner_user`, lempar exception yang jelas — admin harus lewat alur Academy Account kalau memang ingin mengganti/menghapus Owner (transfer kepemilikan sendiri **bukan scope brief ini**, lihat catatan di header).

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `app/Services/EmploymentTypeService.php` | ✏️ Tambah `findDefaultForOwner()` | 1 |
| `app/Services/StaffPositionService.php` | ✏️ Tambah `findDefaultForOwner()` | 1 |
| `app/Services/StaffService.php` | ✏️ Inject 2 Service baru, fix `create()` set `id_user`, tambah `createForOwner()`/`syncFullName()`, guard di `delete()` | 2 |
| `app/Http/Requests/Academy/AcademyFormRequest.php` | ✏️ Tambah rule `owner_full_name`/`owner_gender`/`owner_birth_place`/`owner_birth_date`/`owner_phone` | 3 |
| `app/Http/Requests/Academy/StoreAcademyAccountRequest.php` | ✏️ Tambah rule `full_name`/`gender`/`birth_place`/`birth_date`/`phone` | 4 |
| `app/Services/AcademyManagementService.php` | ✏️ Inject `StaffService`, panggil `createForOwner()` | 5 |
| `app/Http/Controllers/AcademyAccountController.php` | ✏️ Inject `StaffService`, `store()` panggil `createForOwner()`, `update()` panggil `syncFullName()` | 6 |
| `resources/views/academies/create.blade.php` | ✏️ Tambah field biodata di box toggle "Buat Akun Owner" | 7 |
| `resources/views/academies/account/create.blade.php` | ✏️ Tambah field biodata | 7 |
| `lang/en.json` | ✏️ Entry baru | 8 |
| `tests/Feature/AcademyAccountTest.php` | ✏️ Tambah biodata ke payload existing test + assertion Staff/Contract baru + test guard delete | 9 |
| `docs/architecture.md` | ✏️ Catat keputusan `User.name` Owner = nama personal, bukan nama academy | 10 |

---

## Tahap 1 — Resolver Default Employment Type & Staff Position untuk Owner

**`app/Services/EmploymentTypeService.php`** — tambah method baru (di bawah `createDefaultEmploymentTypes()`):

```php
/**
 * Employment Type default untuk Staff yang mewakili Owner Academy --
 * "Permanent" sudah otomatis dibuat tiap Academy baru lewat
 * createDefaultEmploymentTypes(). WAJIB ada; kalau somehow terhapus,
 * lempar error jelas alih-alih diam-diam membuat Contract tanpa
 * id_employment_type (issue13.md).
 */
public function findDefaultForOwner(Academy $academy): EmploymentType
{
    $employmentType = EmploymentType::where('id_academy', $academy->id_academy)
        ->where('name', 'Permanent')
        ->first();

    if (! $employmentType) {
        throw new \Exception(__('Employment Type default "Permanent" untuk academy ini tidak ditemukan.'));
    }

    return $employmentType;
}
```

**`app/Services/StaffPositionService.php`** — tambah method baru (di bawah `createDefaultStaffPositions()`):

```php
/**
 * Staff Position default untuk Owner Academy -- "Academy Director"
 * (code "AD") sudah otomatis dibuat tiap Academy baru lewat
 * createDefaultStaffPositions(). Cari pakai `code`, bukan `name` --
 * code lebih stabil kalau label pernah diterjemahkan/diubah nanti
 * (issue13.md).
 */
public function findDefaultForOwner(Academy $academy): StaffPosition
{
    $staffPosition = StaffPosition::where('id_academy', $academy->id_academy)
        ->where('code', 'AD')
        ->first();

    if (! $staffPosition) {
        throw new \Exception(__('Staff Position default "Academy Director" untuk academy ini tidak ditemukan.'));
    }

    return $staffPosition;
}
```

Tambahkan `use App\Models\Academy;` di kedua file kalau belum ada.

**✅ Cek dulu**

```bash
php artisan tinker
>>> $a = \App\Models\Academy::factory()->create();
>>> (new \App\Services\EmploymentTypeService())->findDefaultForOwner($a)->name; // "Permanent"
>>> (new \App\Services\StaffPositionService(...))->findDefaultForOwner($a)->code; // "AD"
```

(Kalau constructor `StaffPositionService` butuh dependency lain, resolve lewat `app(StaffPositionService::class)` saja.)

---

## Tahap 2 — StaffService: `createForOwner()`, fix `create()`, guard `delete()`, `syncFullName()`

**`app/Services/StaffService.php`** — inject 2 service baru:

```php
protected AcademyService $academyService;
protected EmploymentContractService $employmentContractService;
protected EmploymentTypeService $employmentTypeService;
protected StaffPositionService $staffPositionService;

public function __construct(
    AcademyService $academyService,
    EmploymentContractService $employmentContractService,
    EmploymentTypeService $employmentTypeService,
    StaffPositionService $staffPositionService
) {
    $this->academyService = $academyService;
    $this->employmentContractService = $employmentContractService;
    $this->employmentTypeService = $employmentTypeService;
    $this->staffPositionService = $staffPositionService;
}
```

Di `create()`, tambahkan `id_user` ke payload `Staff::create()` (baris ini sebelumnya tidak ada sama sekali — tanpa ini, `createForOwner()` di bawah tidak akan pernah benar-benar menautkan Staff ke User Owner-nya):

```php
$staff = Staff::create([
    'id_academy' => $academy->id_academy,
    'id_user' => $data['id_user'] ?? null,
    'staff_code' => $staffCode,
    // ... field lain tetap sama persis
]);
```

Tambah 2 method baru (di bawah `create()`):

```php
/**
 * Dipanggil dari alur pembuatan akun Owner
 * (AcademyManagementService::create() & AcademyAccountController::store())
 * -- Owner SELALU jadi bagian dari Staff academy-nya sendiri. Employment
 * Type & Staff Position di-default otomatis ("Permanent" + "Academy
 * Director") -- form pembuatan akun Owner tidak menanyakan dropdown ini
 * sama sekali (issue13.md Bagian 2a).
 */
public function createForOwner(Academy $academy, User $owner, array $data): Staff
{
    $employmentType = $this->employmentTypeService->findDefaultForOwner($academy);
    $staffPosition = $this->staffPositionService->findDefaultForOwner($academy);

    return $this->create(array_merge($data, [
        'id_academy' => $academy->id_academy,
        'id_user' => $owner->id_user,
        'id_employment_type' => $employmentType->id_employment_type,
        'id_staff_position' => $staffPosition->id_staff_position,
        'email' => $data['email'] ?? $owner->email,
    ]));
}

/**
 * Selaraskan Staff.full_name saat nama akun Owner diubah lewat
 * AcademyAccountController::update() -- supaya data di halaman Staff
 * tidak basi dibanding nama akun login-nya (issue13.md Bagian 2d).
 * No-op kalau User ini tidak (lagi) tertaut ke Staff manapun.
 */
public function syncFullName(User $user, string $fullName): void
{
    Staff::where('id_user', $user->id_user)->update(['full_name' => $fullName]);
}
```

Tambah guard di **awal** `delete()` (sebelum `return DB::transaction(...)`):

```php
public function delete(Staff $staff): bool
{
    // Staff yang jadi Owner academy-nya sendiri TIDAK BOLEH dihapus lewat
    // sini -- menghapusnya ikut menghapus User (di bawah) dan diam-diam
    // mengosongkan academies.id_owner_user (FK nullOnDelete). Ganti/pindahkan
    // kepemilikan academy dulu lewat Academy Account management
    // (issue13.md Bagian 2e).
    if ($staff->id_user && $staff->academy->id_owner_user === $staff->id_user) {
        throw new \Exception(__('Staff ini adalah pemilik (Owner) academy dan tidak dapat dihapus. Ganti kepemilikan academy terlebih dahulu lewat menu Academy.'));
    }

    return DB::transaction(function () use ($staff) {
        // ... isi method tetap sama persis
    });
}
```

**✅ Cek dulu**

```bash
php artisan tinker
>>> $academy = \App\Models\Academy::factory()->create();
>>> $owner = \App\Models\User::factory()->create(['id_academy' => $academy->id_academy]);
>>> $staff = app(\App\Services\StaffService::class)->createForOwner($academy, $owner, [
...     'full_name' => 'Budi Owner', 'gender' => 'male',
...     'birth_place' => 'Jakarta', 'birth_date' => '1985-01-01', 'phone' => '0812',
... ]);
>>> $staff->id_user === $owner->id_user; // true
>>> $staff->activeContract->position->code; // "AD"
>>> $staff->activeContract->employmentType->name; // "Permanent"
>>> $academy->update(['id_owner_user' => $owner->id_user]);
>>> app(\App\Services\StaffService::class)->delete($staff->fresh()); // harus throw Exception
```

---

## Tahap 3 — `AcademyFormRequest`: Tambah Field Biodata Owner

**`app/Http/Requests/Academy/AcademyFormRequest.php`** — tambah rule setelah `owner_password` (pola `required_if:create_account,1` identik dengan `owner_email`/`owner_password`):

```php
'owner_full_name' => [
    'required_if:create_account,1',
    'nullable',
    'string',
    'max:255',
],

'owner_gender' => [
    'required_if:create_account,1',
    'nullable',
    'in:male,female',
],

'owner_birth_place' => [
    'required_if:create_account,1',
    'nullable',
    'string',
    'max:100',
],

'owner_birth_date' => [
    'required_if:create_account,1',
    'nullable',
    'date',
],

'owner_phone' => [
    'required_if:create_account,1',
    'nullable',
    'string',
    'max:50',
],
```

Tambah messages setelah `owner_password.confirmed`:

```php
'owner_full_name.required_if' => __('Nama lengkap Owner wajib diisi.'),
'owner_full_name.max' => __('Nama lengkap Owner tidak boleh lebih dari 255 karakter.'),

'owner_gender.required_if' => __('Jenis kelamin Owner wajib dipilih.'),
'owner_gender.in' => __('Jenis kelamin Owner tidak valid.'),

'owner_birth_place.required_if' => __('Tempat lahir Owner wajib diisi.'),

'owner_birth_date.required_if' => __('Tanggal lahir Owner wajib diisi.'),
'owner_birth_date.date' => __('Tanggal lahir Owner tidak valid.'),

'owner_phone.required_if' => __('Nomor telepon Owner wajib diisi.'),
'owner_phone.max' => __('Nomor telepon Owner tidak boleh lebih dari 50 karakter.'),
```

**✅ Cek dulu** — POST ke `academies.store` dengan `create_account=1` tanpa `owner_full_name`/dst harus `assertSessionHasErrors(['owner_full_name', 'owner_gender', 'owner_birth_place', 'owner_birth_date', 'owner_phone'])`.

---

## Tahap 4 — `StoreAcademyAccountRequest`: Tambah Field Biodata

**`app/Http/Requests/Academy/StoreAcademyAccountRequest.php`** — beda dari Tahap 3, field ini **selalu required** (bukan `required_if`) karena seluruh tujuan endpoint ini memang membuat akun Owner:

```php
'full_name' => ['required', 'string', 'max:255'],
'gender' => ['required', 'in:male,female'],
'birth_place' => ['required', 'string', 'max:100'],
'birth_date' => ['required', 'date'],
'phone' => ['required', 'string', 'max:50'],
```

+ messages senada `StoreStaffRequest` (`full_name.required`, `gender.required`, `gender.in`, `birth_place.required`, `birth_date.required`, `birth_date.date`, `phone.required`, `phone.max`).

**✅ Cek dulu** — POST ke `academies.account.store` tanpa field-field ini harus `assertSessionHasErrors`.

---

## Tahap 5 — `AcademyManagementService::create()`: Pakai `StaffService::createForOwner()`

**`app/Services/AcademyManagementService.php`** — inject `StaffService`:

```php
protected StaffService $staffService;

public function __construct(
    RoleService $roleService,
    PlayerTypeService $playerTypeService,
    PlayerCategoryService $playerCategoryService,
    EmploymentTypeService $employmentTypeService,
    StaffPositionService $staffPositionService,
    AccountService $accountService,
    StaffService $staffService
) {
    // ... assignment lain tetap sama
    $this->staffService = $staffService;
}
```

Ubah blok `if (!empty($data['create_account']))` di `create()`:

```php
if (!empty($data['create_account'])) {

    $owner = $this->accountService->create([
        'id_academy' => $academy->id_academy,
        'name' => $data['owner_full_name'],
        'email' => $data['owner_email'],
        'password' => $data['owner_password'],
    ], 'Owner');

    $this->staffService->createForOwner($academy, $owner, [
        'full_name' => $data['owner_full_name'],
        'gender' => $data['owner_gender'],
        'birth_place' => $data['owner_birth_place'],
        'birth_date' => $data['owner_birth_date'],
        'phone' => $data['owner_phone'],
    ]);

    $academy->update([
        'id_owner_user' => $owner->id_user,
    ]);
}
```

(`'name' => $academy->name` yang lama diganti `$data['owner_full_name']` — lihat Aturan Emas & Bagian 2c.)

**✅ Cek dulu** — buat Academy baru lewat form dengan toggle Owner aktif; setelah submit, `Staff::where('id_user', $academy->owner->id_user)->first()` harus ada, `full_name` = yang diinput, `activeContract->position->code === 'AD'`.

---

## Tahap 6 — `AcademyAccountController`: `createForOwner()` + `syncFullName()`

**`app/Http/Controllers/AcademyAccountController.php`** — inject `StaffService`:

```php
use App\Services\StaffService;

protected AccountService $accountService;
protected StaffService $staffService;

public function __construct(AccountService $accountService, StaffService $staffService)
{
    $this->accountService = $accountService;
    $this->staffService = $staffService;
}
```

Ubah `store()`:

```php
DB::transaction(function () use ($request, $academy) {

    $user = $this->accountService->create([
        'id_academy' => $academy->id_academy,
        'name' => $request->full_name,
        'email' => $request->email,
        'password' => $request->password,
    ], 'Owner');

    $this->staffService->createForOwner($academy, $user, [
        'full_name' => $request->full_name,
        'gender' => $request->gender,
        'birth_place' => $request->birth_place,
        'birth_date' => $request->birth_date,
        'phone' => $request->phone,
    ]);

    $academy->update([
        'id_owner_user' => $user->id_user,
    ]);
});
```

Ubah `update()` — tambah 1 baris setelah `$this->accountService->update(...)`:

```php
$this->accountService->update(
    $academy->owner,
    $request->validated()
);

$this->staffService->syncFullName($academy->owner, $request->name);
```

**✅ Cek dulu** — POST ke `academies.account.store` (academy belum punya Owner) harus membuat Staff baru bertaut; PUT ke `academies.account.update` mengubah `name` harus ikut mengubah `Staff::full_name` terkait.

---

## Tahap 7 — Views: Tambah Field Biodata

**`resources/views/academies/create.blade.php`** — di dalam `<div x-show="createAccount" ...>` (setelah field `owner_password_confirmation`, sebelum `</div>` penutup), tambah field biodata bergaya sama (lihat pola `resources/views/staff/create.blade.php` untuk markup gender/tanggal lahir):

```blade
<div>
    <label class="form-label">
        {{ __('Nama Lengkap Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
    </label>

    <input type="text" name="owner_full_name" value="{{ old('owner_full_name') }}"
        class="form-input @error('owner_full_name') form-danger @enderror">

    @error('owner_full_name')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div>
    <label class="form-label">
        {{ __('Jenis Kelamin Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
    </label>

    <select name="owner_gender" class="form-select @error('owner_gender') form-danger @enderror">
        <option value="">{{ __('Pilih Jenis Kelamin') }}</option>
        <option value="male" @selected(old('owner_gender') === 'male')>{{ __('Laki-laki') }}</option>
        <option value="female" @selected(old('owner_gender') === 'female')>{{ __('Perempuan') }}</option>
    </select>

    @error('owner_gender')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div>
    <label class="form-label">
        {{ __('Tempat Lahir Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
    </label>

    <input type="text" name="owner_birth_place" value="{{ old('owner_birth_place') }}"
        class="form-input @error('owner_birth_place') form-danger @enderror">

    @error('owner_birth_place')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div>
    <label class="form-label">
        {{ __('Tanggal Lahir Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
    </label>

    <input type="date" name="owner_birth_date" value="{{ old('owner_birth_date') }}"
        class="form-input @error('owner_birth_date') form-danger @enderror">

    @error('owner_birth_date')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div>
    <label class="form-label">
        {{ __('Nomor Telepon Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
    </label>

    <input type="text" name="owner_phone" value="{{ old('owner_phone') }}"
        class="form-input @error('owner_phone') form-danger @enderror">

    @error('owner_phone')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

**`resources/views/academies/account/create.blade.php`** — tambah field yang sama (tanpa prefix `owner_`, tanpa `x-show`) setelah field `email`, sebelum `password`:

```blade
<div class="form-group">
    <label class="form-label">
        {{ __('Nama Lengkap') }} <span class="text-error-500">*</span>
    </label>

    <input type="text" name="full_name" value="{{ old('full_name') }}"
        class="form-input @error('full_name') form-danger @enderror">

    @error('full_name')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">
        {{ __('Jenis Kelamin') }} <span class="text-error-500">*</span>
    </label>

    <select name="gender" class="form-select @error('gender') form-danger @enderror">
        <option value="">{{ __('Pilih Jenis Kelamin') }}</option>
        <option value="male" @selected(old('gender') === 'male')>{{ __('Laki-laki') }}</option>
        <option value="female" @selected(old('gender') === 'female')>{{ __('Perempuan') }}</option>
    </select>

    @error('gender')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">
        {{ __('Tempat Lahir') }} <span class="text-error-500">*</span>
    </label>

    <input type="text" name="birth_place" value="{{ old('birth_place') }}"
        class="form-input @error('birth_place') form-danger @enderror">

    @error('birth_place')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">
        {{ __('Tanggal Lahir') }} <span class="text-error-500">*</span>
    </label>

    <input type="date" name="birth_date" value="{{ old('birth_date') }}"
        class="form-input @error('birth_date') form-danger @enderror">

    @error('birth_date')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">
        {{ __('Nomor Telepon') }} <span class="text-error-500">*</span>
    </label>

    <input type="text" name="phone" value="{{ old('phone') }}"
        class="form-input @error('phone') form-danger @enderror">

    @error('phone')
        <span class="form-error">{{ $message }}</span>
    @enderror
</div>
```

**✅ Cek dulu** — buka kedua halaman di browser (atau HTTP GET test), pastikan field baru muncul & toggle `x-show` di form Academy create tetap bekerja (field biodata Owner ikut sembul-muncul bersama field email/password Owner yang sudah ada).

---

## Tahap 8 — Multi-Language

Tambah ke `lang/en.json` (jalankan `php -r "json_decode(file_get_contents('lang/en.json'), true) or die('invalid json');"` setelah edit):

```json
"Nama Lengkap Owner": "Owner Full Name",
"Jenis Kelamin Owner": "Owner Gender",
"Tempat Lahir Owner": "Owner Birth Place",
"Tanggal Lahir Owner": "Owner Birth Date",
"Nomor Telepon Owner": "Owner Phone Number",
"Nama lengkap Owner wajib diisi.": "Owner full name is required.",
"Nama lengkap Owner tidak boleh lebih dari 255 karakter.": "Owner full name may not be greater than 255 characters.",
"Jenis kelamin Owner wajib dipilih.": "Owner gender is required.",
"Jenis kelamin Owner tidak valid.": "Owner gender is invalid.",
"Tempat lahir Owner wajib diisi.": "Owner birth place is required.",
"Tanggal lahir Owner wajib diisi.": "Owner birth date is required.",
"Tanggal lahir Owner tidak valid.": "Owner birth date is invalid.",
"Nomor telepon Owner wajib diisi.": "Owner phone number is required.",
"Nomor telepon Owner tidak boleh lebih dari 50 karakter.": "Owner phone number may not be greater than 50 characters.",
"Staff ini adalah pemilik (Owner) academy dan tidak dapat dihapus. Ganti kepemilikan academy terlebih dahulu lewat menu Academy.": "This staff is the academy's Owner and cannot be deleted. Transfer academy ownership first via the Academy menu.",
"Employment Type default \"Permanent\" untuk academy ini tidak ditemukan.": "Default Employment Type \"Permanent\" for this academy was not found.",
"Staff Position default \"Academy Director\" untuk academy ini tidak ditemukan.": "Default Staff Position \"Academy Director\" for this academy was not found."
```

(Field `full_name`/`gender`/`birth_place`/`birth_date`/`phone`/`Nama Lengkap`/`Jenis Kelamin`/`Tempat Lahir`/`Tanggal Lahir`/`Nomor Telepon` di `StoreAcademyAccountRequest` & view standalone kemungkinan **sudah ada** di `lang/en.json` — dipakai bersama `StoreStaffRequest`/`staff/create.blade.php`. Cek dulu sebelum menambah duplikat key.)

**✅ Cek dulu** — `php artisan route:list` tidak error, buka halaman dengan `?locale=en` dan pastikan tidak ada teks Bahasa Indonesia yang bocor di field baru.

---

## Tahap 9 — Tests

**`tests/Feature/AcademyAccountTest.php`** — method `baseAcademyPayload()` **tidak** perlu diubah (biodata Owner cuma relevan kalau `create_account=1`). Tapi **setiap** test yang mengirim `'create_account' => 1` (7 method: lihat daftar di bawah) WAJIB ditambah 5 field baru di payload-nya, kalau tidak akan gagal validasi:

```php
'owner_full_name' => 'Budi Owner',
'owner_gender' => 'male',
'owner_birth_place' => 'Jakarta',
'owner_birth_date' => '1985-01-01',
'owner_phone' => '081234567890',
```

Method yang perlu diupdate: `test_super_admin_bisa_buat_academy_sekaligus_akun_owner`, `test_owner_email_tidak_bentrok_dengan_email_kontak_academy`, `test_super_admin_bisa_reset_password_owner`, `test_super_admin_bisa_nonaktifkan_akun_owner`, `test_halaman_account_create_dan_edit_tampil_dengan_benar`, `test_halaman_index_tampilkan_tombol_buat_akun_hanya_untuk_academy_tanpa_owner`. (`test_toggle_aktif_tapi_owner_email_kosong_ditolak` sengaja TIDAK mengirim `owner_email` — biarkan tetap begitu, cukup pastikan assert-nya tidak berubah.)

Tambah assertion baru di `test_super_admin_bisa_buat_academy_sekaligus_akun_owner` (setelah assertion yang sudah ada):

```php
$staff = \App\Models\Staff::where('id_user', $academy->owner->id_user)->first();

$this->assertNotNull($staff);
$this->assertSame('Budi Owner', $staff->full_name);
$this->assertSame('active', $staff->activeContract->status);
$this->assertSame('AD', $staff->activeContract->position->code);
$this->assertSame('Permanent', $staff->activeContract->employmentType->name);
```

Tambah 2 test baru:

```php
public function test_akun_owner_standalone_juga_membuat_staff(): void
{
    $superAdmin = $this->makeSuperAdmin();
    $academy = Academy::factory()->create();

    $this->actingAs($superAdmin)->post(route('academies.account.store', $academy), [
        'email' => 'owner@tes.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'full_name' => 'Citra Owner',
        'gender' => 'female',
        'birth_place' => 'Bandung',
        'birth_date' => '1990-05-05',
        'phone' => '089876543210',
    ]);

    $academy->refresh();
    $staff = \App\Models\Staff::where('id_user', $academy->id_owner_user)->first();

    $this->assertNotNull($staff);
    $this->assertSame('Citra Owner', $staff->full_name);
}

public function test_hapus_staff_pemilik_academy_ditolak(): void
{
    $superAdmin = $this->makeSuperAdmin();

    $payload = $this->baseAcademyPayload([
        'create_account' => 1,
        'owner_email' => 'owner@tes.com',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
        'owner_full_name' => 'Budi Owner',
        'owner_gender' => 'male',
        'owner_birth_place' => 'Jakarta',
        'owner_birth_date' => '1985-01-01',
        'owner_phone' => '081234567890',
    ]);

    $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

    $academy = Academy::where('code', $payload['code'])->first();
    $staff = \App\Models\Staff::where('id_user', $academy->id_owner_user)->first();

    $this->expectException(\Exception::class);

    app(\App\Services\StaffService::class)->delete($staff);
}
```

**✅ Cek dulu** — `php artisan test --filter=AcademyAccountTest` semua lulus, lalu jalankan full suite (`php artisan test`) pastikan jumlah pass/fail sama seperti baseline (5 failure + 2 error yang sudah ada sebelumnya, tidak nambah).

---

## Tahap 10 — Dokumentasi

Tambah catatan singkat di `docs/architecture.md` pada bagian yang menjelaskan `AccountService` (reusable service untuk Player/Coach/Parent/Academy Account) — sebutkan bahwa khusus Academy Owner, pembuatan akun sejak `issue13.md` **selalu diikuti** pembuatan baris `Staff` (lewat `StaffService::createForOwner()`), dengan Employment Type/Staff Position ter-default otomatis ke "Permanent"/"Academy Director". Ini supaya pembaca berikutnya tidak bingung kenapa Owner muncul di halaman index Staff padahal tidak pernah dibuat lewat form Staff biasa.

**✅ Cek dulu** — baca ulang catatan yang ditambahkan, pastikan menjelaskan KENAPA (bukan cuma APA), konsisten gaya penulisan dokumen lain di `docs/`.

---

## Ringkasan Alur Akhir

```text
Buat akun Owner (toggle saat create Academy, ATAU standalone)
│
├── AccountService::create()       -- User + assign role "Owner"
│                                       'name' = full_name asli Owner
│
├── StaffService::createForOwner() -- dalam transaction yang sama
│   ├── resolve Employment Type "Permanent" (otomatis)
│   ├── resolve Staff Position "Academy Director" (otomatis)
│   └── StaffService::create()     -- Staff + EmploymentContract (Active)
│
└── $academy->update(['id_owner_user' => $owner->id_user])

Edit akun Owner (ubah nama)
└── AccountService::update() + StaffService::syncFullName()

Hapus Staff yang kebetulan Owner
└── StaffService::delete() -- DITOLAK, guard di Bagian 2e
```
