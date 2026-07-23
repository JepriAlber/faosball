# Brief: Fitur Upload Dokumen Privat — Reusable Lintas Module (Document)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: `issue9.md`–`issue12.md` (modul Office: Staff, Employment Contract) dan module Player (`app/Models/Player.php`, `app/Services/PlayerService.php`, `resources/views/players/show.blade.php`) **wajib sudah ada**. Baca juga `docs/architecture.md` (section *File Storage* & *Service Layer Architecture*), `docs/multi-tenancy.md` (`AcademyScope`/`BelongsToAcademy`), `docs/authorization.md` (`Gate::before()`, Policy). Modul referensi paling mirip untuk pola upload+delete file: `app/Services/StaffService.php` (method `uploadPhoto()`/`deletePhoto()`), tapi **BUKAN** dicontoh apa adanya — fitur ini beda fundamental (disk privat, bukan publik; lihat Bagian 2).
> **Bukan sekadar 1 module baru** — ini **infrastruktur reusable**: 1 tabel/model/service/policy/component yang dipakai oleh **Staff** dan **Player** sekaligus di brief ini, dan disiapkan supaya module lain (mis. Payment nanti) tinggal pakai ulang tanpa bikin sistem upload baru dari nol.
> **Cara pakai brief ini**: Kerjakan Tahap 1 → 12 berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus. Tahap 1–7 (migration → route) adalah fondasi yang dipakai bersama; Tahap 9 mengintegrasikan ke **2 module** (Staff **dan** Player) — jangan berhenti setelah salah satu saja.
> **Scope**: Upload/lihat/hapus dokumen (PDF/gambar) yang tertaut ke 1 entitas (Staff atau Player), disimpan di disk **privat** (`storage/app/private`, **bukan** `storage/app/public` yang dipakai foto/logo), struktur folder `{academy_code}/{modul}/{kode_entitas}/`, dan **tidak bisa diakses lewat URL langsung** — cuma lewat route yang mengecek permission dulu. Diterapkan konkret ke halaman Staff Detail (`staff.show`, tab/section baru "Dokumen") dan Player Detail (`players.show`, mengisi tab "Dokumen / File" yang **sudah ada placeholder-nya** tapi masih kosong). **Bukan scope**: modul Payment (belum ada sama sekali — fitur ini cuma disiapkan supaya *reusable* untuk itu nanti, tidak ada baris kode Payment di brief ini), preview thumbnail/OCR/scan virus, versioning dokumen (upload baru = row baru, tidak menimpa yang lama), approval/verifikasi dokumen oleh admin.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Simpan file dokumen ke disk `public` (pola `uploadPhoto()` yang sudah ada) | Dokumen ini **sensitif** (ijazah/akte berisi data pribadi) — disk `public` di-symlink dan bisa diakses siapapun yang tahu/menebak URL-nya, tanpa auth sama sekali. WAJIB pakai disk `local` (root `storage/app/private`, **tidak** di-symlink, sudah terkonfirmasi ada di `config/filesystems.php`) | [Tahap 4](#tahap-4--documentservice) |
| Bikin route publik/tanpa auth untuk lihat dokumen (mis. `<img src="{{ Storage::url($doc->path) }}">` atau `asset('storage/...')`) | Sama seperti poin di atas — SATU-SATUNYA cara lihat dokumen wajib lewat `DocumentController::show()` yang mengecek `DocumentPolicy` dulu, distream via `Storage::disk('local')->response(...)`, bukan URL statis | [Tahap 6](#tahap-6--storedocumentrequest--documentcontroller), [Tahap 7](#tahap-7--routes) |
| Terima `documentable_type`/`documentable_id` dari input form/body yang dikirim client untuk route upload | Kalau client bebas menentukan "upload ini untuk Staff mana", user bisa menempelkan dokumen ke Staff/Player siapapun (termasuk academy lain kalau tidak hati-hati). Route upload WAJIB nested per module (`staff/{staff}/documents`, `players/{player}/documents`) supaya `$staff`/`$player` datang dari **route model binding** (otomatis ke-scope `AcademyScope`), bukan dari input yang dikontrol client | [Tahap 7](#tahap-7--routes) |
| Pakai nama file asli (`$file->getClientOriginalName()`) apa adanya sebagai nama file fisik di disk | Nama file dari client tidak terpercaya — bisa mengandung karakter `/`/`\\` yang berisiko path traversal, atau nama yang bentrok antar upload. WAJIB pakai `Str::uuid()` sebagai prefix nama file fisik; nama asli cuma disimpan di kolom `original_name` buat ditampilkan balik, bukan dipakai sebagai path | [Tahap 4](#tahap-4--documentservice) |
| Taruh logic penentuan folder/permission per module (`staff` vs `player` vs nanti `payment`) di Controller | Business logic wajib di Service Layer (`docs/architecture.md`) — `DocumentService::resolveContext()` yang menentukan nama folder & tipe entitas berdasarkan class model, Controller cuma manggil Service | [Tahap 4](#tahap-4--documentservice) |
| Lupa `$this->authorize()` di `DocumentController::show()`/`destroy()` | Beda dari route upload yang nested (sudah digerbang middleware `permission:staff.update`/`permission:player.update` otomatis lewat parameter route), route `documents.show`/`documents.destroy` itu **flat** (`/documents/{document}`) — middleware permission biasa tidak bisa tahu ini dokumen milik Staff atau Player. Otorisasi WAJIB dinamis lewat `DocumentPolicy` di dalam Controller | [Tahap 5](#tahap-5--documentpolicy), [Tahap 6](#tahap-6--storedocumentrequest--documentcontroller) |
| Bikin permission baru `document.*` | **Reuse** permission module yang sudah ada (`staff.view`/`staff.update` untuk dokumen Staff, `player.view`/`player.update` untuk dokumen Player) — dokumen adalah bagian dari data entitas itu, bukan module berdiri sendiri secara otorisasi. Pola sama Employment Contract (`issue12.md`/`issue14.md`) | [Tahap 5](#tahap-5--documentpolicy), [Tahap 12](#tahap-12--dokumentasi) |

---

## 1. Konteks & Tujuan

Module Office (Staff) dan Player sudah hampir lengkap, tapi keduanya butuh kemampuan yang sama: upload dokumen pendukung (ijazah/akte/KTP untuk Staff; akte kelahiran/KK/ijazah untuk Player). Kalau dibangun terpisah-pisah per module, hasilnya duplikasi kode upload/delete/tampil yang identik di 2+ tempat — dan module Payment yang direncanakan nanti (bukti transaksi) akan butuh hal yang sama lagi.

Brief ini membangun **1 fondasi reusable**: tabel `documents` polymorphic (1 dokumen bisa menempel ke entitas manapun — Staff, Player, atau model lain di masa depan), `DocumentService` untuk upload/hapus, `DocumentPolicy` untuk otorisasi dinamis, dan 1 Blade Component `<x-document-manager>` yang menampilkan form upload + daftar dokumen + tombol lihat/hapus — dipasang identik di halaman Staff Detail dan Player Detail. Module baru yang butuh dokumen di masa depan (Payment, dst) tinggal: (1) tambah relasi `documents()` ke model-nya, (2) tambah 2 baris route nested `{module}/{id}/documents`, (3) tambah `<x-document-manager>` di view-nya — tanpa menyentuh tabel/service/policy/component yang sudah ada.

## 2. Cara Kerja Solusi

### 2a. Kenapa disk `local`, bukan `public`

Semua upload yang ada sekarang (foto Staff/Player, logo Academy) sengaja publik — avatar/logo memang harus bisa ditampilkan ke siapapun tanpa login. Dokumen di brief ini kebalikannya: ijazah/akte berisi data pribadi yang cuma boleh dilihat orang berwenang. `config/filesystems.php` sudah punya disk `local` (root `storage_path('app/private')`, **tidak** masuk daftar `links` yang di-symlink `php artisan storage:link`) — disk ini persis yang dibutuhkan, tidak perlu config baru.

### 2b. Kenapa polymorphic (1 tabel `documents`), bukan kolom per module

Table `documents` pakai `documentable_type` + `documentable_id` (Laravel morphTo) supaya 1 struktur data yang sama dipakai Staff, Player, dan module manapun di masa depan — tanpa migration baru tiap kali ada module baru yang butuh upload dokumen. Karena 1 entitas bisa punya **banyak** dokumen dengan jenis berbeda (ijazah + akte + KTP sekaligus), ini WAJIB tabel relasi 1-ke-banyak, bukan 1 kolom `document_path` di tabel Staff/Player.

### 2c. Kenapa dua lapis route (nested untuk upload, flat untuk lihat/hapus)

- **Upload** (`POST staff/{staff}/documents`, `POST players/{player}/documents`) — nested di bawah parent-nya, persis pola Employment Contract (`staff/{staff}/contracts`). `$staff`/`$player` datang dari route model binding yang otomatis ke-scope `AcademyScope`, dan middleware `permission:staff.update`/`permission:player.update` sudah cukup menggerbang siapa boleh upload — konsisten dengan pola nested resource yang sudah ada di project ini.
- **Lihat & Hapus** (`GET documents/{document}`, `DELETE documents/{document}`) — flat, karena setelah dokumen ada, cara paling natural mengaksesnya adalah lewat ID dokumennya sendiri (dari daftar yang ditampilkan `<x-document-manager>`), bukan lewat parent lagi. Karena flat, middleware permission biasa tidak tahu ini dokumen Staff atau Player — makanya butuh `DocumentPolicy` yang membaca `documentable_type` lalu cek permission yang sesuai secara dinamis.

### 2d. Kenapa `AcademyScope` sudah cukup jadi lapis pertama pertahanan

`Document` (lewat `FaosModel`/`BelongsToAcademy`) otomatis kena `AcademyScope` — user academy biasa yang mencoba akses `documents.show`/`documents.destroy` milik academy lain akan dapat **404** (route model binding gagal resolve, bukan cuma ditolak permission), karena query-nya sendiri sudah tersaring duluan sebelum Policy sempat jalan. `DocumentPolicy` adalah lapis KEDUA (permission per-module), bukan pengganti `AcademyScope`.

### 2e. Struktur folder & nama file

```text
storage/app/private/{academy_code}/{modul}/{kode_entitas}/{uuid}-{nama_file_asli}
```

Contoh: `storage/app/private/FCY26/staff/FCY2600001/a1b2c3d4-...-ijazah.pdf`. `{modul}` dan `{kode_entitas}` (mis. `staff_code`/`player_code`) ditentukan `DocumentService::resolveContext()` berdasarkan class model — lihat Tahap 4.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `database/migrations/..._create_documents_table.php` | 🆕 Baru | 1 |
| `app/Models/Document.php` | 🆕 Baru | 2 |
| `database/factories/DocumentFactory.php` | 🆕 Baru | 2 |
| `app/Models/Staff.php` | ✏️ Tambah relasi `documents()` | 2 |
| `app/Models/Player.php` | ✏️ Tambah relasi `documents()` | 2 |
| `config/faos.php` | ✏️ Tambah `document_types`, hapus key `upload.documents` yang tidak dipakai | 3 |
| `app/Services/DocumentService.php` | 🆕 Baru | 4 |
| `app/Policies/DocumentPolicy.php` | 🆕 Baru | 5 |
| `app/Http/Requests/Document/StoreDocumentRequest.php` | 🆕 Baru | 6 |
| `app/Http/Controllers/DocumentController.php` | 🆕 Baru | 6 |
| `routes/web.php` | ✏️ Tambah route nested (staff/player) + flat (show/destroy) | 7 |
| `app/View/Components/DocumentManager.php` | 🆕 Baru | 8 |
| `resources/views/components/document-manager.blade.php` | 🆕 Baru | 8 |
| `resources/views/staff/show.blade.php` | ✏️ Tambah section "Dokumen" | 9 |
| `resources/views/players/show.blade.php` | ✏️ Isi tab "Dokumen / File" yang masih placeholder | 9 |
| `lang/en.json` | ✏️ Entry baru | 10 |
| `tests/Feature/DocumentTest.php` | 🆕 Baru | 11 |
| `docs/architecture.md` | ✏️ Tambah subsection "File Storage Privat (Document)" | 12 |
| `docs/module-standard.md` | ✏️ Catat: module baru yang butuh upload dokumen wajib reuse `Document`/`DocumentService` | 12 |
| `docs/permission-reference.md` | ✏️ Tambah section baru "Module: Document" | 12 |

---

## Tahap 1 — Migration: `documents`

```bash
php artisan make:migration create_documents_table
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
        Schema::create('documents', function (Blueprint $table) {

            $table->uuid('id_document')->primary();

            $table->uuid('id_academy');

            // Polymorphic -- 1 dokumen menempel ke 1 entitas (Staff, Player,
            // dst di masa depan). uuidMorphs() bikin documentable_id (uuid)
            // + documentable_type (string) + index gabungan otomatis.
            $table->uuidMorphs('documentable');

            $table->string('type', 50); // ijazah/akte/ktp/dst -- lihat config('faos.document_types')
            $table->string('original_name');
            $table->string('path'); // path relatif di disk 'local', BUKAN disk 'public'
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size'); // bytes

            $table->uuid('uploaded_by')->nullable();

            $table->timestamps();
            // TIDAK ada softDeletes() -- hapus dokumen = hapus permanen
            // (file fisik ikut dihapus, lihat Tahap 4), tidak perlu recycle bin.

            $table->index('id_academy');

            $table->foreign('id_academy')->references('id_academy')->on('academies')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
```

**✅ Cek dulu**

```bash
php artisan migrate
php artisan db:table documents
```

Harus ada kolom `documentable_type`, `documentable_id`, `type`, `original_name`, `path`, `mime_type`, `size`, `uploaded_by`. `documentable_type`/`documentable_id` NOT NULL.

---

## Tahap 2 — Model `Document` + Factory + Relasi di Staff & Player

`app/Models/Document.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends FaosModel
{
    use HasFactory;

    protected $table = 'documents';
    protected $primaryKey = 'id_document';

    protected $fillable = [
        'id_academy', 'documentable_type', 'documentable_id',
        'type', 'original_name', 'path', 'mime_type', 'size', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_user');
    }
}
```

`database/factories/DocumentFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            // documentable_type/documentable_id SENGAJA tidak diisi di sini
            // -- wajib dipassing eksplisit tiap kali dipakai di test, pola
            // sama EmploymentContractFactory yang tidak isi id_staff sendiri.
            'type' => 'ijazah',
            'original_name' => 'dokumen.pdf',
            'path' => 'dummy/dummy.pdf',
            'mime_type' => 'application/pdf',
            'size' => 102400,
            'uploaded_by' => null,
        ];
    }
}
```

`app/Models/Staff.php` — tambah import `MorphMany` dan method baru (taruh di bawah `draftContract()`):

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;

// ...

public function documents(): MorphMany
{
    return $this->morphMany(Document::class, 'documentable')->latest();
}
```

`app/Models/Player.php` — tambah method baru (taruh di bawah `secondaryPosition()`, sebelum `}` penutup class), **tidak perlu** import baru karena tidak ada type-hint return eksplisit di file ini (ikuti gaya method lain di file yang sama, tanpa return type):

```php
    /*
    |--------------------------------------------------------------------------
    | Relationship Documents
    |--------------------------------------------------------------------------
    */
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$staff = \App\Models\Staff::first(); // atau factory()->create()
$staff->documents; // Collection kosong, tidak error
$player = \App\Models\Player::first();
$player->documents; // Collection kosong, tidak error
```

---

## Tahap 3 — Config: `document_types` + Bersihkan Key Tidak Terpakai

`config/faos.php` — di dalam array `'upload' => [...]`, **hapus** baris `'documents' => 'documents',` (key ini sudah ada sebelumnya tapi tidak pernah dipakai di manapun — brief ini menggantinya dengan mekanisme folder dinamis di `DocumentService::resolveContext()`, bukan path statis).

Tambah array baru **setelah** blok `'upload' => [...]`:

```php
/*
|--------------------------------------------------------------------------
| Document Types (per module)
|--------------------------------------------------------------------------
| Dipakai dropdown "Jenis Dokumen" di <x-document-manager>. Module baru
| yang mengintegrasikan Document (issue15.md) tinggal tambah key baru
| di sini, mis. 'payment' => ['bukti_transfer' => 'Bukti Transfer'].
*/
'document_types' => [
    'staff' => [
        'ktp' => 'KTP',
        'ijazah' => 'Ijazah',
        'akte' => 'Akte Kelahiran',
        'lainnya' => 'Lainnya',
    ],
    'player' => [
        'akte' => 'Akte Kelahiran',
        'kk' => 'Kartu Keluarga',
        'ijazah' => 'Ijazah',
        'lainnya' => 'Lainnya',
    ],
],
```

**✅ Cek dulu**: `php artisan tinker` → `config('faos.document_types.staff')` mengembalikan array 4 item. `config('faos.upload.documents')` harus `null` (sudah dihapus).

---

## Tahap 4 — `DocumentService`

`app/Services/DocumentService.php`:

```php
<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Player;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Tentukan nama folder module & kode entitas dari class model-nya.
     * Module baru yang butuh Document (mis. Payment) tinggal tambah 1
     * arm match baru di sini -- TIDAK perlu ubah bagian lain Service ini
     * (issue15.md Bagian 2b).
     */
    protected function resolveContext(Model $documentable): array
    {
        return match (get_class($documentable)) {
            Staff::class => ['module' => 'staff', 'code' => $documentable->staff_code],
            Player::class => ['module' => 'players', 'code' => $documentable->player_code],
            default => throw new \Exception(__('Module dokumen untuk entitas ini belum didukung.')),
        };
    }

    /**
     * Upload dokumen baru untuk 1 entitas (Staff/Player/dst). Disimpan di
     * disk 'local' (PRIVAT, bukan 'public') -- lihat issue15.md Bagian 2a
     * & Aturan Emas.
     */
    public function upload(Model $documentable, UploadedFile $file, string $type): Document
    {
        $context = $this->resolveContext($documentable);

        // Nama file fisik WAJIB pakai UUID, bukan nama asli client apa
        // adanya -- cegah path traversal & tabrakan nama (Aturan Emas).
        $safeOriginalName = str_replace(['/', '\\'], '-', $file->getClientOriginalName());
        $filename = Str::uuid() . '-' . $safeOriginalName;

        $directory = $documentable->academy->code . '/' . $context['module'] . '/' . $context['code'];

        $path = $file->storeAs($directory, $filename, 'local');

        return Document::create([
            'id_academy' => $documentable->id_academy,
            'documentable_type' => get_class($documentable),
            'documentable_id' => $documentable->getKey(),
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    public function delete(Document $document): bool
    {
        if (Storage::disk('local')->exists($document->path)) {
            Storage::disk('local')->delete($document->path);
        }

        return $document->delete();
    }
}
```

**✅ Cek dulu**

```bash
php -l app/Services/DocumentService.php
php artisan tinker
```

```php
use Illuminate\Http\UploadedFile;

$staff = \App\Models\Staff::first();
$file = UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf');
$doc = app(\App\Services\DocumentService::class)->upload($staff, $file, 'ijazah');
$doc->path; // mis. "FCY26/staff/FCY2600001/xxxxx-ijazah.pdf"
\Illuminate\Support\Facades\Storage::disk('local')->exists($doc->path); // true
\Illuminate\Support\Facades\Storage::disk('public')->exists($doc->path); // false -- WAJIB false, buktikan tidak nyasar ke disk publik
app(\App\Services\DocumentService::class)->delete($doc);
\Illuminate\Support\Facades\Storage::disk('local')->exists($doc->path); // false
```

---

## Tahap 5 — `DocumentPolicy`

`app/Policies/DocumentPolicy.php` — **baru**, otomatis ke-*discover* Laravel tanpa perlu didaftarkan manual (konvensi `Document` model → `DocumentPolicy`, tidak ada `AuthServiceProvider` custom di project ini, cek `app/Policies/StaffPolicy.php` yang juga tidak didaftarkan manual di manapun):

```php
<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Player;
use App\Models\Staff;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Reuse permission module pemilik dokumen -- BUKAN permission baru
     * document.* (Aturan Emas). Module baru yang butuh Document tinggal
     * tambah 1 arm match baru di kedua method ini.
     */
    public function view(User $user, Document $document): bool
    {
        return match ($document->documentable_type) {
            Staff::class => $user->can('staff.view'),
            Player::class => $user->can('player.view'),
            default => false,
        };
    }

    public function delete(User $user, Document $document): bool
    {
        return match ($document->documentable_type) {
            Staff::class => $user->can('staff.update'),
            Player::class => $user->can('player.update'),
            default => false,
        };
    }
}
```

**✅ Cek dulu**: `php -l app/Policies/DocumentPolicy.php` tidak ada error. Verifikasi penuh menyusul Tahap 11 (butuh Controller + route).

---

## Tahap 6 — `StoreDocumentRequest` + `DocumentController`

`app/Http/Requests/Document/StoreDocumentRequest.php`:

```php
<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi sesungguhnya sudah di middleware route (nested,
        // permission:staff.update/player.update) -- lihat Tahap 7.
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('Jenis dokumen wajib dipilih.'),
            'file.required' => __('Berkas wajib diunggah.'),
            'file.file' => __('Berkas tidak valid.'),
            'file.mimes' => __('Berkas harus berformat PDF, JPG, JPEG, atau PNG.'),
            'file.max' => __('Ukuran berkas maksimal 5MB.'),
        ];
    }
}
```

`app/Http/Controllers/DocumentController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Player;
use App\Models\Staff;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function storeForStaff(StoreDocumentRequest $request, Staff $staff)
    {
        try {

            $this->documentService->upload($staff, $request->file('file'), $request->validated('type'));

            return back()->with('success', __('Dokumen berhasil diunggah.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengunggah dokumen'));
        }
    }

    public function storeForPlayer(StoreDocumentRequest $request, Player $player)
    {
        try {

            $this->documentService->upload($player, $request->file('file'), $request->validated('type'));

            return back()->with('success', __('Dokumen berhasil diunggah.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal mengunggah dokumen'));
        }
    }

    /**
     * Flat route -- tidak digerbang middleware permission per-module
     * (beda dari storeForStaff/storeForPlayer di atas), WAJIB
     * authorize() manual lewat DocumentPolicy (Aturan Emas).
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);

        return Storage::disk('local')->response($document->path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        try {

            $this->documentService->delete($document);

            return back()->with('success', __('Dokumen berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus dokumen'));
        }
    }
}
```

**✅ Cek dulu**: `php -l` kedua file tidak ada error.

---

## Tahap 7 — Routes

`routes/web.php` — tambah **2 blok**. Blok pertama, nested di bawah Staff (taruh persis sebelum blok "Profile" yang sudah ada, atau setelah blok Employment Contract):

```php
/*
|--------------------------------------------------------------------------
| Document (Staff) -- reusable Document, lihat issue15.md
|--------------------------------------------------------------------------
*/
Route::post('staff/{staff}/documents', [DocumentController::class, 'storeForStaff'])
    ->name('staff.documents.store')
    ->middleware('permission:staff.update');

/*
|--------------------------------------------------------------------------
| Document (Player) -- reusable Document, lihat issue15.md
|--------------------------------------------------------------------------
*/
Route::post('players/{player}/documents', [DocumentController::class, 'storeForPlayer'])
    ->name('players.documents.store')
    ->middleware('permission:player.update');

/*
|--------------------------------------------------------------------------
| Document -- flat routes, otorisasi dinamis lewat DocumentPolicy
|--------------------------------------------------------------------------
| TIDAK ada middleware permission di sini SENGAJA -- lihat
| DocumentController::show()/destroy() yang authorize() manual
| berdasarkan documentable_type (issue15.md Aturan Emas).
*/
Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
```

**✅ Cek dulu**

```bash
php artisan route:list --name=documents
```

Harus muncul 4 baris: `staff.documents.store` (middleware `permission:staff.update`), `players.documents.store` (middleware `permission:player.update`), `documents.show`, `documents.destroy` (2 terakhir **tanpa** middleware permission).

---

## Tahap 8 — Blade Component `<x-document-manager>`

`app/View/Components/DocumentManager.php` — class-based (lihat `docs/frontend-standard.md` bagian *Reusable View dengan Data Dinamis*, konsisten pola `LogoUploadField`):

```php
<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class DocumentManager extends Component
{
    public Model $documentable;
    public string $uploadRoute;
    public array $types;
    public bool $canManage;

    public function __construct(Model $documentable, string $uploadRoute, array $types, bool $canManage = false)
    {
        $this->documentable = $documentable;
        $this->uploadRoute = $uploadRoute;
        $this->types = $types;
        $this->canManage = $canManage;
    }

    public function render(): View
    {
        return view('components.document-manager', [
            'documents' => $this->documentable->documents,
        ]);
    }
}
```

`resources/views/components/document-manager.blade.php`:

```blade
{{--
    Reusable document upload + list, dipakai lintas module (Staff, Player,
    nanti Payment). Props:
    - documentable : model target (Staff/Player/dst), harus punya
                      relasi documents() (MorphMany).
    - upload-route  : URL tujuan POST form upload (sudah termasuk parameter
                       parent-nya, mis. route('staff.documents.store', $staff)).
    - types         : array [key => label] dari config('faos.document_types.<module>').
    - can-manage    : boolean, kontrol tampil/sembunyi form upload & tombol hapus
                       (otorisasi SESUNGGUHNYA tetap di route/Policy, ini cuma UX).
--}}
<div class="space-y-4">

    @if ($canManage)
        <form action="{{ $uploadRoute }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800 sm:flex-row sm:items-end">
            @csrf

            <div class="form-group mb-0 flex-1">
                <label class="form-label">{{ __('Jenis Dokumen') }}</label>
                <select name="type" class="form-select @error('type') form-danger @enderror" required>
                    <option value="">{{ __('Pilih Jenis Dokumen') }}</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group mb-0 flex-1">
                <label class="form-label">{{ __('Berkas') }}</label>
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="form-input @error('file') form-danger @enderror" required>
                @error('file')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary shrink-0">{{ __('Unggah') }}</button>
        </form>
    @endif

    <div class="space-y-2">
        @forelse ($documents as $document)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                <div class="min-w-0">
                    <span class="table-title truncate">{{ $document->original_name }}</span>
                    <span class="table-subtitle">
                        {{ $types[$document->type] ?? $document->type }}
                        &middot; {{ round($document->size / 1024, 1) }} KB
                        &middot; {{ $document->created_at->format('d M Y') }}
                    </span>
                </div>

                <div class="flex shrink-0 gap-2">
                    <a href="{{ route('documents.show', $document) }}" target="_blank" class="btn-icon" title="{{ __('Lihat') }}">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                            <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </a>

                    @if ($canManage)
                        <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('{{ __('Hapus dokumen ini?') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Hapus') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M2.5 5H17.5M6.66667 5V3.33333C6.66667 2.8731 7.03976 2.5 7.5 2.5H12.5C12.9602 2.5 13.3333 2.8731 13.3333 3.33333V5M15.8333 5V15.8333C15.8333 16.2936 15.4602 16.6667 15 16.6667H5C4.53976 16.6667 4.16667 16.2936 4.16667 15.8333V5H15.8333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada dokumen yang diunggah.') }}</p>
        @endforelse
    </div>

</div>
```

**✅ Cek dulu**: `php -l app/View/Components/DocumentManager.php` tidak ada error. Verifikasi tampilan menyusul Tahap 9.

---

## Tahap 9 — Integrasi ke Staff Detail & Player Detail

**`resources/views/staff/show.blade.php`** — tambah section baru **setelah** blok "Riwayat Kontrak" (`</div>` penutup di baris ~332) dan **sebelum** blok "Catatan" (`{{ __('Catatan') }}`, baris ~334):

```blade
<div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

    <h4 class="section-title">
        {{ __('Dokumen') }}
    </h4>

    <div class="mt-3">
        <x-document-manager :documentable="$staff" :upload-route="route('staff.documents.store', $staff)"
            :types="config('faos.document_types.staff')" :can-manage="auth()->user()->can('staff.update')" />
    </div>

</div>
```

**`resources/views/players/show.blade.php`** — **ganti isi** placeholder tab "Dokumen / File" (baris ~254-264, div `x-show="tab==='dokumen'"`) dari:

```blade
<div x-show="tab==='dokumen'" x-cloak class="tab-panel">

    <div class="rounded-lg border border-dashed border-gray-200 p-5 dark:border-gray-800">

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Dokumen pemain belum tersedia.') }}
        </p>

    </div>

</div>
```

menjadi:

```blade
<div x-show="tab==='dokumen'" x-cloak class="tab-panel">

    <x-document-manager :documentable="$player" :upload-route="route('players.documents.store', $player)"
        :types="config('faos.document_types.player')" :can-manage="auth()->user()->can('player.update')" />

</div>
```

**✅ Cek dulu**: buka halaman Staff Detail (`staff.show`) — section "Dokumen" muncul dengan form upload (kalau punya `staff.update`) + daftar kosong. Upload 1 file PDF, refresh, file muncul di daftar, klik "Lihat" membuka file di tab baru, klik "Hapus" (dengan konfirmasi) menghilangkan dari daftar. Ulangi persis yang sama di halaman Player Detail tab "Dokumen / File".

---

## Tahap 10 — Multi-Language

Tambah ke `lang/en.json` (jalankan `php -r "json_decode(file_get_contents('lang/en.json'), true) or die('invalid json');"` setelah edit):

```json
"Dokumen": "Documents",
"Jenis Dokumen": "Document Type",
"Pilih Jenis Dokumen": "Select Document Type",
"Berkas": "File",
"Unggah": "Upload",
"Lihat": "View",
"Hapus": "Delete",
"Hapus dokumen ini?": "Delete this document?",
"Belum ada dokumen yang diunggah.": "No documents uploaded yet.",
"Dokumen berhasil diunggah.": "Document uploaded successfully.",
"Dokumen berhasil dihapus.": "Document deleted successfully.",
"Gagal mengunggah dokumen": "Failed to upload document",
"Gagal menghapus dokumen": "Failed to delete document",
"Jenis dokumen wajib dipilih.": "Document type is required.",
"Berkas wajib diunggah.": "File is required.",
"Berkas tidak valid.": "Invalid file.",
"Berkas harus berformat PDF, JPG, JPEG, atau PNG.": "File must be PDF, JPG, JPEG, or PNG format.",
"Ukuran berkas maksimal 5MB.": "File size must not exceed 5MB.",
"Module dokumen untuk entitas ini belum didukung.": "Document module for this entity is not supported yet.",
"KTP": "ID Card",
"Ijazah": "Diploma",
"Akte Kelahiran": "Birth Certificate",
"Kartu Keluarga": "Family Card",
"Lainnya": "Other"
```

(`Dokumen / File` kemungkinan **sudah ada** di `lang/en.json` dari placeholder Player yang lama — cek dulu sebelum menambah duplikat key.)

**✅ Cek dulu**: buka `staff.show`/`players.show` dengan `?locale=en`, pastikan tidak ada teks Bahasa Indonesia yang bocor di section/tab Dokumen.

---

## Tahap 11 — Tests

`tests/Feature/DocumentTest.php` — **baru**:

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Document;
use App\Models\EmploymentType;
use App\Models\Player;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsOwner(Academy $academy): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['staff.view', 'staff.update', 'player.view', 'player.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['staff.view', 'staff.update', 'player.view', 'player.update'])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner);

        return $owner;
    }

    public function test_upload_dokumen_staff_tersimpan_di_disk_privat(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $this->actingAsOwner($academy);

        $file = UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf');

        $this->post(route('staff.documents.store', $staff), [
            'type' => 'ijazah',
            'file' => $file,
        ])->assertRedirect();

        $document = Document::first();

        $this->assertNotNull($document);
        $this->assertSame(Staff::class, $document->documentable_type);
        $this->assertSame($staff->id_staff, $document->documentable_id);

        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
    }

    /**
     * Player TIDAK punya factory di codebase ini -- dibuat langsung lewat
     * Player::create() dengan field minimal yang NOT NULL, pola sama
     * yang sudah dipakai PlayerTypeTest/PlayerCategoryTest.
     */
    protected function makePlayer(Academy $academy): Player
    {
        return Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST' . random_int(10000, 99999),
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);
    }

    public function test_player_bisa_upload_dokumen_akte(): void
    {
        Storage::fake('local');

        $academy = Academy::factory()->create();
        $player = $this->makePlayer($academy);

        $this->actingAsOwner($academy);

        $file = UploadedFile::fake()->create('akte.pdf', 100, 'application/pdf');

        $this->post(route('players.documents.store', $player), [
            'type' => 'akte',
            'file' => $file,
        ])->assertRedirect();

        $document = Document::first();

        $this->assertNotNull($document);
        $this->assertSame(Player::class, $document->documentable_type);
    }

    public function test_lihat_dokumen_ditolak_untuk_user_tanpa_permission_view(): void
    {
        Storage::fake('local');

        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $document = Document::factory()->create([
            'id_academy' => $academy->id_academy,
            'documentable_type' => Staff::class,
            'documentable_id' => $staff->id_staff,
        ]);

        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $this->actingAs($user)->get(route('documents.show', $document))->assertForbidden();
    }

    public function test_dokumen_academy_lain_tidak_ditemukan_404(): void
    {
        Storage::fake('local');

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $staffB = Staff::factory()->create(['id_academy' => $academyB->id_academy]);

        $documentB = Document::factory()->create([
            'id_academy' => $academyB->id_academy,
            'documentable_type' => Staff::class,
            'documentable_id' => $staffB->id_staff,
        ]);

        $this->actingAsOwner($academyA);

        $this->get(route('documents.show', $documentB))->assertNotFound();
    }

    public function test_hapus_dokumen_menghapus_file_fisik_dan_row_database(): void
    {
        Storage::fake('local');

        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $this->actingAsOwner($academy);

        $file = UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf');

        $this->post(route('staff.documents.store', $staff), ['type' => 'ijazah', 'file' => $file]);

        $document = Document::first();
        $path = $document->path;

        $this->delete(route('documents.destroy', $document))->assertRedirect();

        $this->assertNull(Document::find($document->id_document));
        Storage::disk('local')->assertMissing($path);
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=DocumentTest
php artisan test
```

Semua test baru lulus. Jumlah pass/fail total tidak berkurang dibanding sebelum brief ini dikerjakan (baseline: 5 failure + 2 error bawaan Breeze scaffolding, lihat `issue14.md`/`issue13.md`).

---

## Tahap 12 — Dokumentasi

**`docs/architecture.md`** — tambah subsection baru **setelah** section "File Storage" yang sudah ada:

```markdown
### File Storage Privat (Document)

Untuk file yang TIDAK boleh diakses publik (dokumen pribadi: ijazah, akte, KTP, dst -- beda dari foto profil/logo yang memang publik), pakai `App\Models\Document` (polymorphic) + `App\Services\DocumentService::upload()`/`delete()`, disk `local` (root `storage/app/private`, TIDAK di-symlink), bukan disk `public`. Akses file WAJIB lewat route `documents.show` yang mengecek `App\Policies\DocumentPolicy` dulu (`Storage::disk('local')->response(...)`), tidak pernah lewat URL statis. Lihat `issue15.md` untuk detail lengkap & `<x-document-manager>` sebagai UI reusable-nya.
```

**`docs/module-standard.md`** — tambah catatan singkat di bagian yang relevan (dekat pembahasan upload file module baru): module baru yang butuh upload dokumen sensitif (bukan foto profil/logo publik) **wajib** reuse `Document`/`DocumentService`/`<x-document-manager>` yang sudah ada (`issue15.md`) — tambah 1 arm `match` baru di `DocumentService::resolveContext()` & `DocumentPolicy`, plus relasi `documents()` di model-nya — **jangan** bikin sistem upload/tabel/service baru dari nol.

**`docs/permission-reference.md`** — tambah section baru **sebelum** heading `## Development Rules`:

```markdown
## Module: Document (dokumen privat lintas-module)

| Permission | Untuk apa | Digerbang di |
|---|---|---|
| `staff.update` | Upload dokumen untuk Staff | `staff.documents.store` (route middleware) |
| `staff.view` | Lihat/unduh dokumen milik Staff | `DocumentPolicy@view` (dipanggil `DocumentController::show()`) |
| `staff.update` | Hapus dokumen milik Staff | `DocumentPolicy@delete` (dipanggil `DocumentController::destroy()`) |
| `player.update` | Upload dokumen untuk Player | `players.documents.store` (route middleware) |
| `player.view` | Lihat/unduh dokumen milik Player | `DocumentPolicy@view` |
| `player.update` | Hapus dokumen milik Player | `DocumentPolicy@delete` |

Catatan:
- **Reuse** permission module pemilik dokumen (`staff.*`/`player.*`) — bukan permission baru `document.*` (pola sama Employment Contract, `issue12.md`/`issue14.md`).
- Route `documents.show`/`documents.destroy` **flat** (tidak tahu dari URL ini dokumen siapa), jadi otorisasi dinamis di `DocumentPolicy` berdasarkan `documentable_type`, BUKAN middleware `permission:...` biasa.
- Lapis pertama proteksi lintas-academy adalah `AcademyScope` (bawaan `FaosModel`) — akses dokumen academy lain otomatis 404 sebelum sempat sampai ke Policy.
- Module baru (Payment, dst) yang mengintegrasikan Document tinggal tambah baris permission yang sesuai di tabel ini, ikuti pola yang sama.
```

**✅ Cek dulu**: baca ulang ketiga dokumen yang diedit, pastikan konsisten dengan kode yang sebenarnya (`php artisan route:list --name=documents` untuk cross-check tabel permission-reference.md).

---

## Ringkasan Alur Akhir

```text
Staff Detail / Player Detail
│
<x-document-manager :documentable :upload-route :types :can-manage>
│
├── Upload -> POST staff/{staff}/documents ATAU players/{player}/documents
│   (middleware permission:staff.update / player.update)
│   └── DocumentService::upload()
│       ├── resolveContext() -> tentukan folder module + kode entitas
│       ├── simpan file ke disk 'local' (PRIVAT)
│       └── Document::create() -- polymorphic ke Staff/Player
│
├── Lihat -> GET documents/{document}
│   └── DocumentPolicy::view() -> Storage::disk('local')->response(...)
│
└── Hapus -> DELETE documents/{document}
    └── DocumentPolicy::delete() -> DocumentService::delete()
        (hapus row DB + file fisik)

Module baru (mis. Payment) yang butuh dokumen nanti:
1. Tambah relasi documents() di model-nya
2. Tambah 1 arm match di DocumentService::resolveContext() & DocumentPolicy
3. Tambah 2 route nested (store) + pasang <x-document-manager> di view-nya
-- TIDAK perlu tabel/service/policy/component baru.
```
