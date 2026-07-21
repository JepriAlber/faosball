# Brief: Logo Sidebar (Wordmark) Terpisah dari Logo Persegi + Fallback Nama/Inisial Academy

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `docs/frontend-standard.md` (terutama *Urutan & Pengelompokan Field Form*, *Reusable View dengan Data Dinamis*, dan *Theming Per-Academy*), `docs/architecture.md` (Service Layer, Thin Controller), dan `docs/coding-standard.md` (*Bahasa Pesan & Multi-Language*). Referensi historis (file-nya sudah dihapus setelah merge, tapi konteksnya masih relevan lewat komentar kode): `issue3.md`/`issue4.md` (fitur crop logo pertama kali) dan `issue6.md` (`primary_color` + `ColorRamp` + `<x-academy-theme>`).
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 11** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: (1) Pisahkan logo academy jadi **2 upload independen** — logo persegi (existing, dipakai favicon + nanti kartu nama/kop surat) dan logo sidebar/wordmark (baru, upload+crop rasio lebar sendiri, bukan turunan otomatis dari logo persegi lagi). (2) Kalau academy (non-Super-Admin) belum upload logo sidebar/favicon, sidebar & header **tidak** lagi fallback ke logo generic sistem (`KantinITSvg.svg`) — tampilkan **nama academy** (slot lebar) atau **inisial 1-2 huruf** (slot persegi kecil), warnanya ikut `primary_color` academy lewat token brand yang sudah ada (`<x-academy-theme>`, `issue6.md`). **Bukan** scope: modul kartu nama/kop surat itu sendiri (baru rencana masa depan — brief ini cuma memastikan `logo` persegi tetap tersedia bersih untuk kebutuhan itu nanti), validasi kontras warna, atau redesign visual sidebar di luar slot logo.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Hapus `logo_sidebar` saat yang diganti user cuma `logo` (persegi), atau sebaliknya | Sejak brief ini, `logo` dan `logo_sidebar` adalah **2 upload independen** — mengganti salah satu tidak boleh menyentuh file yang lain. Pakai `deleteSquareLogoAssets()`/`deleteSidebarLogoAsset()` terpisah, jangan gabung lagi jadi satu `deleteLogoVariants()` seperti sebelumnya | [Tahap 4](#tahap-4--academymanagementservice) |
| Ubah `AcademyService::sidebarLogoUrl()`/`faviconUrl()` supaya return `null` saat academy belum upload logo | Dua method ini dipakai LANGSUNG oleh `<link rel="icon">` di `layouts/app.blade.php` — tag itu **wajib** selalu punya `href` gambar, tidak bisa fallback ke teks. Fallback teks/inisial cuma untuk slot yang dirender lewat `<x-academy-logo>` Component (sidebar, header user-avatar), bukan browser tab icon | [Tahap 5](#tahap-5--academyservice), [4.1](#41-kenapa-sidebarlogourlfaviconurl-tidak-diubah-jadi-nullable) |
| Taruh logic bikin inisial dari nama (`"FC Garuda"` → `"FG"`) di Blade/Component | Harus di class murni `App\Support\Initials` (dipanggil dari `AcademyService`, bukan langsung dari Component) — pola yang sama persis dengan `App\Support\ColorRamp` di `issue6.md`, supaya testable tanpa boot Laravel dan Component tetap "tidak menghitung, cuma menampilkan" | [Tahap 1](#tahap-1--appsupportinitials) |
| Bikin komponen crop/upload baru terpisah buat logo sidebar | `<x-logo-upload-field>` yang sudah ada **wajib** dijadikan reusable lewat `@props` (aspect ratio, ukuran output, label, teks bantuan dikonfigurasi) — dipakai 2x dengan parameter beda, bukan hand-roll markup baru. Ini persis alasan `docs/frontend-standard.md` sudah menandai `<x-logo-upload-field>` sebagai komponen wajib-reuse | [Tahap 2](#tahap-2--parameterisasi-logo-crop-fieldjs), [Tahap 3](#tahap-3--logo-upload-fieldbladephp-jadi-reusable) |
| Bikin `@utility` CSS baru untuk badge inisial/teks fallback | Markup fallback cuma dipakai di **1 file** (`academy-logo.blade.php`), tidak berulang di banyak tempat — cukup class Tailwind polos langsung di situ. `@utility` baru cuma untuk pola yang benar-benar berulang (lihat `docs/frontend-standard.md` → *Kapan Membuat @utility Baru*) | [Tahap 6](#tahap-6--academylogo-component--fallback-view) |
| Percaya `avatar` utility (`bg-gray-100 text-gray-600`) bisa ditimpa warna brand cuma dengan nambah class `bg-brand-50 text-brand-600` di elemen yang sama | `avatar` itu sendiri custom `@utility` — dia dan `bg-brand-50` sama-sama hidup di `@layer utilities`, urutan menang-kalahnya **tidak dijamin** ikut urutan penulisan class di HTML (beda dengan style unlayered di `issue6.md` 4.2). Supaya tidak ambigu, badge fallback ditulis dengan class Tailwind mandiri (tidak numpang di atas `avatar`), lihat [Tahap 6](#tahap-6--academylogo-component--fallback-view) | [4.2](#42-kenapa-badge-fallback-tidak-numpang-di-atas-utility-avatar) |
| Lewatkan `__()` di `logo-upload-field.blade.php` karena "sudah lama ada, harusnya sudah dicek" | File ini dibuat `f2dcf46` (2026-07-20), **sehari sebelum** fondasi multi-language (`98c7002`, 2026-07-21) — luput dari sweep i18n Academy yang katanya sudah "selesai". Semua string di dalamnya (label, placeholder, teks modal crop) masih Bahasa Indonesia mentah tanpa `__()`. Karena file ini disentuh ulang di brief ini, sekalian dibenahi — pola yang sama seperti pembenahan `<x-table.toolbar>` di kerjaan filter Permission sebelumnya | [Tahap 3](#tahap-3--logo-upload-fieldbladephp-jadi-reusable) |

---

## 1. Konteks & Tujuan

Academy saat ini cuma punya **1** upload logo (`logo`, di-crop **persegi** lewat modal cropper). Dari situ sistem otomatis menurunkan 2 varian: `logo_favicon` (crop persegi lagi, 64x64 — masuk akal, favicon memang harus persegi) dan `logo_sidebar` (`scaleDown` ke box 245x65 **tanpa** crop tambahan — masalahnya, karena sumbernya sudah persegi, hasil `scaleDown` ini cuma jadi logo kotak kecil nangkring di tengah slot lebar, bukan wordmark yang mengisi slot dengan baik).

```text
SEBELUM (1 upload, 2 turunan otomatis)              SESUDAH (2 upload independen)
┌─────────────┐                                      ┌─────────────┐   ┌──────────────────┐
│ logo (crop  │──generateLogoVariants()──┐            │ logo (crop  │   │ logo_sidebar (crop│
│  persegi)   │                          │            │  persegi)   │   │  rasio lebar,     │
└─────────────┘                          ▼            └──────┬──────┘   │  upload SENDIRI)  │
                              ┌────────────────────┐          │         └─────────┬─────────┘
                              │ logo_favicon (64x64)│          ▼                   │
                              │ logo_sidebar (scale-│   logo_favicon (64x64,       ▼
                              │  Down 245x65, TETAP  │    cover crop persegi   logo_sidebar
                              │  KELIHATAN PERSEGI)  │    dari logo persegi)   (rasio lebar
                              └────────────────────┘                          asli, bukan
                                                                               turunan lagi)
```

`logo` (persegi) **tetap dipertahankan** sebagai sumber untuk favicon — dan nanti jadi sumber untuk modul kartu nama/kop surat yang butuh logo proporsional, bukan wordmark. `logo_sidebar` sekarang py sumber sendiri: upload kedua dengan crop rasio lebar (≈245:65), independen dari logo persegi.

Konsekuensi lain: kalau academy **belum** upload salah satu (atau keduanya), sidebar sekarang **tidak** lagi diam-diam fallback ke logo generic sistem (`KantinITSvg.svg`) — itu cuma masuk akal untuk Super Admin (yang memang tidak py academy). Untuk academy yang login tapi belum sempat upload logo sidebar/favicon-nya sendiri, tampilkan **nama academy** (slot lebar) atau **inisial 1-2 huruf** (slot persegi kecil), diwarnai `primary_color` academy itu — supaya tetap terasa "identitas academy tersebut", bukan logo generic academy lain yang kebetulan belum sempat upload.

## 2. Cara Kerja Solusi

### 2a. Dua upload independen, bukan lagi 1 upload + 2 turunan

`logo_favicon` **tetap** diturunkan otomatis dari `logo` (persegi) — itu tetap masuk akal karena favicon memang harus persegi juga. Yang berubah cuma `logo_sidebar`: bukan lagi diturunkan dari `logo`, tapi py upload+crop sendiri. **Tidak perlu migration baru** — kolom `logo_sidebar` sudah ada (nullable, `2026_07_20_073807_add_logo_variants_to_academies_table.php`), cuma cara pengisiannya yang berubah.

### 2b. `<x-logo-upload-field>` jadi reusable lewat `@props`, dipakai 2x

Komponen upload+crop yang sudah ada (`resources/views/components/logo-upload-field.blade.php` + `resources/js/components/logo-crop-field.js`) saat ini **hardcode** untuk 1 kasus: field bernama `logo`, aspect ratio `1` (persegi), output `1024x1024`. Brief ini menjadikannya **reusable**: nama field, label, teks bantuan, aspect ratio, dan ukuran output semua jadi parameter (`@props`). Field kedua (`logo_sidebar`) dipasang di form yang sama dengan parameter aspect ratio ≈`3.77` (245:65) dan output `980x260`.

### 2c. Fallback teks/inisial cuma untuk slot yang dirender lewat Component, BUKAN untuk `<link rel="icon">`

```text
AcademyService::sidebarLogoUrl() / faviconUrl()   -> SELALU return URL gambar (tidak berubah!)
    dipakai LANGSUNG oleh:
    - <link rel="icon"> di layouts/app.blade.php  -> WAJIB selalu gambar

App\View\Components\AcademyLogo (variant sidebar/favicon)  -> BISA fallback teks/inisial
    dipakai oleh:
    - sidebar.blade.php (slot lebar + slot ikon collapsed)
    - header.blade.php (user-avatar trigger)
```

`AcademyLogo` Component memutuskan sendiri: kalau academy aktif (atau Super Admin) py logo gambar, render `<img>` seperti biasa (pakai `sidebarLogoUrl()`/`faviconUrl()` yang tidak berubah). Kalau tidak, render `<span>` teks nama academy atau inisial — **tidak lagi memanggil** `sidebarLogoUrl()`/`faviconUrl()` untuk kasus itu.

### 2d. Warna fallback ikut token brand yang sudah ada, tidak re-invent

Teks/inisial fallback dikasih warna lewat class Tailwind `text-brand-600 dark:text-brand-400` — token `brand` ini **sudah** di-override per-academy oleh `<x-academy-theme />` (`issue6.md`) kalau `primary_color` academy itu terisi. Tidak perlu baca `$academy->primary_color` lagi atau bikin mekanisme warna baru di brief ini — cukup numpang di token yang sudah jalan.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `app/Support/Initials.php` | 🆕 Baru | 1 |
| `resources/js/components/logo-crop-field.js` | ✏️ Parameterisasi aspect ratio & output size | 2 |
| `resources/views/components/logo-upload-field.blade.php` | ✏️ Jadi reusable (`@props`) + wrap `__()` | 3 |
| `app/Services/AcademyManagementService.php` | ✏️ Pisah proses logo persegi vs sidebar | 4 |
| `app/Services/AcademyService.php` | ✏️ Tambah method fallback (URL method lama TIDAK diubah) | 5 |
| `app/View/Components/AcademyLogo.php` | ✏️ Tambah logic fallback | 6 |
| `resources/views/components/academy-logo.blade.php` | ✏️ Render `<img>` atau fallback `<span>` | 6 |
| `app/Http/Requests/Academy/AcademyFormRequest.php` | ✏️ Tambah rule `logo_sidebar` | 7a |
| `app/Http/Requests/Academy/AcademyProfileFormRequest.php` | ✏️ Tambah rule `logo_sidebar` | 7b |
| `resources/views/academies/create.blade.php` | ✏️ Tambah `<x-logo-upload-field>` ke-2 | 8a |
| `resources/views/academies/edit.blade.php` | ✏️ Tambah `<x-logo-upload-field>` ke-2 | 8b |
| `resources/views/academy-profile/edit.blade.php` | ✏️ Tambah `<x-logo-upload-field>` ke-2 | 8c |
| `lang/en.json` | ✏️ Entry baru | 9 |
| `tests/Unit/InitialsTest.php` | 🆕 Baru | 10a |
| `tests/Feature/AcademyLogoFallbackTest.php` | 🆕 Baru | 10b |
| `docs/frontend-standard.md` | ✏️ Tambah catatan pola | 11 |
| **`database/migrations/`** | 🚫 **Jangan bikin migration baru** — kolom `logo_sidebar` sudah ada & nullable | — |
| **`app/Http/Controllers/AcademyController.php`, `AcademyProfileController.php`** | 🚫 **Jangan sentuh** — keduanya sudah mengoper `$request->validated()` mentah ke Service; field `logo_sidebar` otomatis ikut begitu masuk rules Form Request (Tahap 7) | — |
| **`AcademyService::sidebarLogoUrl()` / `faviconUrl()`** | 🚫 **Jangan diubah** jadi nullable — lihat [Aturan Emas](#0-aturan-emas) & [4.1](#41-kenapa-sidebarlogourlfaviconurl-tidak-diubah-jadi-nullable) | — |

---

## Tahap 1 — `App\Support\Initials`

**Tujuan**: 1 nama academy → 1-2 huruf inisial, class murni tanpa dependency Laravel (sama pola dengan `ColorRamp`, `issue6.md` Tahap 3).

Buat file baru `app/Support/Initials.php`:

```php
<?php

namespace App\Support;

class Initials
{
    /**
     * Ambil 1-2 huruf inisial dari nama academy, dipakai badge fallback
     * saat logo_favicon belum diupload:
     * - 2+ kata -> huruf pertama dari 2 kata PERTAMA ("FC Garuda Muda" -> "FG")
     * - 1 kata  -> 2 huruf pertama kata itu ("Garuda" -> "GA")
     * - kosong  -> "?" (secara praktik tidak pernah kejadian karena `name`
     *   wajib diisi saat Academy dibuat, tapi badge tidak boleh pernah
     *   menampilkan string kosong)
     */
    public static function from(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return '?';
        }

        if (count($words) === 1) {
            return strtoupper(mb_substr($words[0], 0, 2));
        }

        return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
}
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
\App\Support\Initials::from('FC Garuda Muda'); // "FG"
\App\Support\Initials::from('Garuda');         // "GA"
\App\Support\Initials::from('  fc   garuda '); // "FG" (trim + uppercase)
```

---

## Tahap 2 — Parameterisasi `logo-crop-field.js`

**Tujuan**: aspect ratio & ukuran output canvas jadi parameter, bukan hardcode `1`/`1024x1024`, supaya bisa dipakai ulang untuk crop rasio lebar.

`resources/js/components/logo-crop-field.js` — ganti seluruh isi:

```js
import Cropper from 'cropperjs';

export default (initialPreview = '', aspectRatio = 1, outputWidth = 1024, outputHeight = 1024) => ({
    imagePreview: initialPreview,
    showCropModal: false,
    cropper: null,
    pendingSourceUrl: null,

    onFileSelected(event) {
        const file = event.target.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();

        reader.onload = (e) => {
            this.pendingSourceUrl = e.target.result;
            this.showCropModal = true;

            this.$nextTick(() => this.initCropper());
        };

        reader.readAsDataURL(file);
    },

    initCropper() {
        this.cropper = new Cropper(this.$refs.cropperImage, {
            aspectRatio: aspectRatio,
            viewMode: 1,
            autoCropArea: 1,
            background: false,
        });
    },

    confirmCrop() {
        this.cropper.getCroppedCanvas({ width: outputWidth, height: outputHeight }).toBlob((blob) => {

            const croppedFile = new File([blob], 'logo.png', { type: 'image/png' });

            // WAJIB DataTransfer -- <input type="file"> tidak bisa diisi
            // langsung dengan Blob/File biasa, cuma lewat FileList asli.
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(croppedFile);
            this.$refs.fileInput.files = dataTransfer.files;

            this.imagePreview = URL.createObjectURL(blob);

            this.closeCropModal();

        }, 'image/png');
    },

    cancelCrop() {
        // Crop WAJIB, bukan opsional -- batal berarti tidak ada file yang
        // valid untuk di-submit, input harus dikosongkan lagi. Lihat
        // issue4.md Aturan Emas.
        this.$refs.fileInput.value = '';
        this.closeCropModal();
    },

    closeCropModal() {
        this.cropper?.destroy();
        this.cropper = null;
        this.pendingSourceUrl = null;
        this.showCropModal = false;
    },
});
```

> Registrasi `Alpine.data('logoCropField', logoCropField)` di `resources/js/app.js` **tidak perlu diubah** — Alpine memanggil factory function ini dengan argumen apa pun yang ditulis di `x-data="logoCropField(...)"`, jumlah parameter baru otomatis ke-pass.

**✅ Cek dulu**: `npm run build` sukses tanpa error.

---

## Tahap 3 — `logo-upload-field.blade.php` jadi Reusable

**Tujuan**: field nama, label, teks bantuan, aspect ratio, ukuran output, dan class preview semua jadi `@props` — dipakai 2x nanti (logo persegi & logo sidebar) tanpa duplikasi markup. Sekalian bungkus seluruh string dengan `__()` (lihat [Aturan Emas](#0-aturan-emas) — file ini luput dari sweep i18n sebelumnya).

`resources/views/components/logo-upload-field.blade.php` — ganti seluruh isi:

```blade
@props([
    'currentLogoUrl' => null,
    'name' => 'logo',
    'label' => __('Logo Academy'),
    'helpText' => __('SVG, PNG, JPG, WEBP maksimal 2MB -- akan diminta crop persegi setelah dipilih'),
    'cropTitle' => __('Sesuaikan Logo'),
    'cropDescription' => __('Geser & perbesar untuk memilih area logo (persegi).'),
    'aspectRatio' => 1,
    'outputWidth' => 1024,
    'outputHeight' => 1024,
    'previewClass' => 'avatar avatar-lg avatar-square',
])

<div class="form-group"
    x-data="logoCropField('{{ $currentLogoUrl }}', {{ $aspectRatio }}, {{ $outputWidth }}, {{ $outputHeight }})">

    <label class="form-label">
        {{ $label }}
    </label>

    <div class="form-file-upload">

        <input type="file" id="{{ $name }}" name="{{ $name }}" x-ref="fileInput"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" accept="image/*"
            @change="onFileSelected($event)">

        <div x-show="!imagePreview" class="empty-state">

            <span class="avatar avatar-lg mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12 16V8M8 12L12 8L16 12M3 15V18C3 18.5 3.2 19 3.6 19.4C4 19.8 4.5 20 5 20H19C19.5 20 20 19.8 20.4 19.4C20.8 19 21 18.5 21 18V15"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>

            <p class="empty-title">
                {{ __('Klik untuk unggah logo') }}
            </p>

            <p class="empty-description">
                {{ $helpText }}
            </p>

        </div>

        <div x-show="imagePreview" x-cloak class="flex flex-col items-center">

            <div class="{{ $previewClass }} mb-3">
                <img :src="imagePreview" class="h-full w-full object-cover">
            </div>

            <span class="link-primary text-xs font-semibold">
                {{ __('Ganti gambar') }}
            </span>

        </div>

    </div>

    @error($name)
        <span class="form-error">{{ $message }}</span>
    @enderror

    {{-- Modal Crop --}}
    <div x-show="showCropModal" x-cloak x-transition
        class="modal-overlay flex items-center justify-center p-4">

        <div class="modal-container modal-md">

            <div class="modal-header">
                <div>
                    <h3 class="modal-title">{{ $cropTitle }}</h3>
                    <p class="modal-description">{{ $cropDescription }}</p>
                </div>
            </div>

            <div class="modal-body">
                <div class="h-[400px] w-full overflow-hidden">
                    <template x-if="pendingSourceUrl">
                        <img :src="pendingSourceUrl" x-ref="cropperImage" class="block max-w-full">
                    </template>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="cancelCrop()">
                    {{ __('Batal') }}
                </button>

                <button type="button" class="btn btn-primary" @click="confirmCrop()">
                    {{ __('Pakai Crop Ini') }}
                </button>
            </div>

        </div>

    </div>

</div>
```

> `@error($name)` (bukan lagi `@error('logo')` hardcode) — supaya field kedua (`logo_sidebar`) menampilkan pesan error-nya sendiri, bukan ikut nampilin error field `logo`.

**✅ Cek dulu**: buka `/academies/create` — field logo yang sudah ada harus tampil & berfungsi PERSIS seperti sebelumnya (upload → crop modal muncul rasio persegi → preview lingkaran). Kalau ada regresi di sini, jangan lanjut ke tahap berikutnya.

---

## Tahap 4 — `AcademyManagementService`

**Tujuan**: `logo` (persegi) dan `logo_sidebar` diproses & dihapus **independen** — bukan lagi 1 fungsi yang menangani keduanya sekaligus.

Di `app/Services/AcademyManagementService.php`:

### 4a. Ganti `generateLogoVariants()` jadi `generateFaviconVariant()` (cuma favicon)

Cari method `generateLogoVariants()` (yang men-generate `logo_sidebar` DAN `logo_favicon` sekaligus dari 1 file), **hapus seluruhnya**, ganti dengan:

```php
    /**
     * Generate varian logo_favicon (cover 64x64, crop persegi) dari file
     * logo PERSEGI yang baru di-upload. Dulu method ini juga menurunkan
     * logo_sidebar dari sumber yang sama -- sejak issue8.md, logo_sidebar
     * punya upload+crop sendiri (lihat processSidebarLogoUpload() di bawah)
     * supaya logo persegi (dipakai kartu nama/kop surat nanti) tidak
     * dipaksa proporsi lebar yang jelek saat di-scaleDown ke slot sidebar.
     *
     * SVG di-skip (return null) -- driver GD tidak bisa membaca vektor.
     * Dalam praktiknya modal crop (logo-crop-field.js) selalu meng-flatten
     * hasil crop ke PNG sebelum submit, jadi cabang ini nyaris tidak pernah
     * kena lewat UI normal -- tetap dijaga untuk submit non-JS/manual.
     */
    protected function generateFaviconVariant($file, string $academyCode): ?string
    {
        if (strtolower($file->getClientOriginalExtension()) === 'svg') {
            return null;
        }

        try {

            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());

            $faviconPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-favicon.png';

            Storage::disk('public')->put(
                $faviconPath,
                (string) $image->cover(64, 64)->encode(new PngEncoder())
            );

            return $faviconPath;

        } catch (\Throwable $e) {

            Log::warning('Gagal generate varian favicon logo academy', [
                'academy_code' => $academyCode,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }


    /**
     * Upload logo_sidebar -- UPLOAD+CROP SENDIRI (rasio lebar, terpisah dari
     * logo persegi), bukan turunan otomatis lagi. scaleDown ke bounding box
     * 245x65 (jaga aspect ratio, TANPA crop tambahan -- user sudah crop
     * rasio yang benar di client lewat <x-logo-upload-field :aspect-ratio="...">).
     *
     * SVG di-skip, alasan sama seperti generateFaviconVariant().
     */
    protected function processSidebarLogoUpload($file, string $academyCode): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'svg') {
            return ['logo_sidebar' => null];
        }

        try {

            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());

            $sidebarPath = 'academies/logo/' . strtoupper($academyCode) . '-' . Str::uuid() . '-sidebar.png';

            Storage::disk('public')->put(
                $sidebarPath,
                (string) $image->scaleDown(
                    self::LOGO_SIDEBAR_MAX_WIDTH,
                    self::LOGO_SIDEBAR_MAX_HEIGHT
                )->encode(new PngEncoder())
            );

            return ['logo_sidebar' => $sidebarPath];

        } catch (\Throwable $e) {

            Log::warning('Gagal upload logo_sidebar academy', [
                'academy_code' => $academyCode,
                'exception' => $e->getMessage(),
            ]);

            return ['logo_sidebar' => null];
        }
    }
```

> Konstanta `LOGO_SIDEBAR_MAX_WIDTH`/`LOGO_SIDEBAR_MAX_HEIGHT` (245/65) **tetap dipakai apa adanya**, cuma sekarang jadi bounding box untuk hasil crop rasio lebar yang di-upload sendiri, bukan lagi target `scaleDown` dari logo persegi.

### 4b. Ganti `processLogoUpload()` (cuma untuk logo persegi + favicon)

```php
    /**
     * Upload logo PERSEGI asli + generate favicon-nya sekaligus, dipakai
     * bersama oleh create()/update()/updateProfile() untuk field `logo`.
     */
    protected function processLogoUpload($file, string $academyCode): array
    {
        return [
            'logo' => $this->uploadLogo($file, $academyCode),
            'logo_favicon' => $this->generateFaviconVariant($file, $academyCode),
        ];
    }
```

### 4c. Ganti `deleteLogoVariants()` jadi 2 method terpisah

Cari method `deleteLogoVariants(Academy $academy)`, **hapus**, ganti dengan:

```php
    /**
     * Hapus logo persegi + logo_favicon (dipakai saat field `logo` diganti
     * atau academy dihapus). TERPISAH dari deleteSidebarLogoAsset() --
     * mengganti logo persegi TIDAK BOLEH ikut menghapus logo_sidebar yang
     * mungkin tidak sedang diganti pada request yang sama.
     */
    protected function deleteSquareLogoAssets(Academy $academy): void
    {
        $this->deleteLogo($academy->logo);
        $this->deleteLogo($academy->logo_favicon);
    }


    /**
     * Hapus logo_sidebar saja (dipakai saat field itu diganti atau academy
     * dihapus). TERPISAH dari deleteSquareLogoAssets(), lihat di atas.
     */
    protected function deleteSidebarLogoAsset(Academy $academy): void
    {
        $this->deleteLogo($academy->logo_sidebar);
    }
```

### 4d. Wiring di `create()`, `update()`, `updateProfile()`, `delete()`

**`create()`** — tambahkan blok `logo_sidebar` setelah blok `logo` yang sudah ada:

```php
            if (isset($data['logo'])) {
                $data = array_merge($data, $this->processLogoUpload($data['logo'], $data['code']));
            }

            if (isset($data['logo_sidebar'])) {
                $data = array_merge($data, $this->processSidebarLogoUpload($data['logo_sidebar'], $data['code']));
            }
```

**`update()`** — ganti blok `logo` yang lama, tambahkan blok `logo_sidebar`:

```php
            if (isset($data['logo'])) {
                $this->deleteSquareLogoAssets($academy);
                $data = array_merge($data, $this->processLogoUpload($data['logo'], $data['code']));
            }

            if (isset($data['logo_sidebar'])) {
                $this->deleteSidebarLogoAsset($academy);
                $data = array_merge($data, $this->processSidebarLogoUpload($data['logo_sidebar'], $data['code']));
            }
```

**`updateProfile()`** — sama polanya, ganti blok `logo`, tambah blok `logo_sidebar` (perhatikan `$academy->code`, bukan `$data['code']` -- academy tidak boleh ganti code lewat self-service):

```php
            if (isset($data['logo'])) {
                $this->deleteSquareLogoAssets($academy);
                $payload = array_merge($payload, $this->processLogoUpload($data['logo'], $academy->code));
            }

            if (isset($data['logo_sidebar'])) {
                $this->deleteSidebarLogoAsset($academy);
                $payload = array_merge($payload, $this->processSidebarLogoUpload($data['logo_sidebar'], $academy->code));
            }
```

**`delete()`** — ganti pemanggilan `deleteLogoVariants($academy)` jadi 2 baris:

```php
    public function delete(Academy $academy): bool
    {
        return DB::transaction(function () use ($academy) {

            $this->deleteSquareLogoAssets($academy);
            $this->deleteSidebarLogoAsset($academy);

            return $academy->delete();
        });
    }
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create(['code' => 'TEST8']);
app(\App\Services\AcademyManagementService::class); // pastikan tidak ada error class-not-found dulu

// generateFaviconVariant / processSidebarLogoUpload dites tidak langsung
// di sini (butuh UploadedFile asli) -- cukup pastikan tidak ada syntax
// error dulu, verifikasi penuh lewat upload manual di Tahap 8.
```

`php artisan tinker` harus bisa `exit` tanpa error fatal (syntax/class-not-found). Verifikasi upload sungguhan menyusul di Tahap 8.

---

## Tahap 5 — `AcademyService`

**Tujuan**: tambah method untuk cek "apakah academy aktif punya logo sendiri" + teks/inisial fallback. `sidebarLogoUrl()`/`faviconUrl()` **tidak diubah sama sekali**.

Tambahkan import di atas `app/Services/AcademyService.php`:

```php
use App\Support\Initials;
```

Tambahkan method baru, setelah `faviconUrl()` dan sebelum `brandColorVariables()`:

```php
    /**
     * Apakah academy AKTIF sudah punya logo_sidebar sendiri (bukan berarti
     * Super Admin -- itu ranah isSuperAdmin()). Dipakai AcademyLogo Component
     * untuk memutuskan render <img> atau fallback teks nama academy.
     */
    public function hasOwnSidebarLogo(): bool
    {
        return (bool) $this->current()?->logo_sidebar;
    }


    /**
     * Sama seperti hasOwnSidebarLogo(), untuk slot favicon/ikon persegi.
     */
    public function hasOwnFaviconLogo(): bool
    {
        return (bool) $this->current()?->logo_favicon;
    }


    /**
     * Nama academy aktif, dipakai fallback slot "lebar" (sidebar/header)
     * saat belum ada logo_sidebar. Null kalau tidak ada academy aktif
     * (Super Admin -- tapi Super Admin tidak pernah masuk cabang ini,
     * lihat AcademyLogo Component).
     */
    public function sidebarFallbackName(): ?string
    {
        return $this->current()?->name;
    }


    /**
     * Inisial 1-2 huruf dari nama academy aktif, dipakai fallback slot
     * "ikon persegi" (favicon) saat belum ada logo_favicon.
     */
    public function faviconFallbackInitials(): ?string
    {
        $academy = $this->current();

        return $academy ? Initials::from($academy->name) : null;
    }
```

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
$academy = \App\Models\Academy::factory()->create(['name' => 'FC Garuda', 'logo_sidebar' => null, 'logo_favicon' => null]);
$user = \App\Models\User::factory()->create(['id_academy' => $academy->id_academy]);
\Illuminate\Support\Facades\Auth::login($user);

app(\App\Services\AcademyService::class)->hasOwnSidebarLogo();     // false
app(\App\Services\AcademyService::class)->sidebarFallbackName();   // "FC Garuda"
app(\App\Services\AcademyService::class)->faviconFallbackInitials(); // "FG"
```

---

## Tahap 6 — `AcademyLogo` Component + Fallback View

**Tujuan**: `<x-academy-logo>` render `<img>` kalau academy (atau Super Admin) punya logo, atau `<span>` teks/inisial kalau tidak.

`app/View/Components/AcademyLogo.php` — ganti seluruh isi:

```php
<?php

namespace App\View\Components;

use App\Services\AcademyService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AcademyLogo extends Component
{
    public ?string $url;
    public ?string $fallbackText;
    public bool $isFavicon;

    public function __construct(AcademyService $academyService, string $variant = 'sidebar')
    {
        $this->isFavicon = $variant === 'favicon';

        $hasOwnLogo = $academyService->isSuperAdmin()
            || ($this->isFavicon ? $academyService->hasOwnFaviconLogo() : $academyService->hasOwnSidebarLogo());

        $this->url = $hasOwnLogo
            ? ($this->isFavicon ? $academyService->faviconUrl() : $academyService->sidebarLogoUrl())
            : null;

        $this->fallbackText = $hasOwnLogo
            ? null
            : ($this->isFavicon ? $academyService->faviconFallbackInitials() : $academyService->sidebarFallbackName());
    }

    public function render(): View
    {
        return view('components.academy-logo');
    }
}
```

`resources/views/components/academy-logo.blade.php` — ganti seluruh isi:

```blade
@if ($url)
    <img {{ $attributes->merge(['src' => $url, 'alt' => 'Logo']) }} />
@elseif ($isFavicon)
    <span
        {{ $attributes->merge(['class' => 'flex size-8 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-xs font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400']) }}>{{ $fallbackText }}</span>
@else
    <span
        {{ $attributes->merge(['class' => 'block max-w-[160px] truncate text-lg font-bold text-brand-600 dark:text-brand-400']) }}>{{ $fallbackText }}</span>
@endif
```

> Kenapa TIDAK numpang di atas utility `avatar` yang sudah ada — lihat [Aturan Emas](#0-aturan-emas) & [4.2](#42-kenapa-badge-fallback-tidak-numpang-di-atas-utility-avatar). Class ditulis lengkap sendiri, bukan `avatar avatar-sm bg-brand-50 ...`.
>
> Header user-avatar (`header.blade.php`, wrapper `.header-avatar` cuma `h-11 w-11 overflow-hidden rounded-full` tanpa flex-center) mungkin terlihat sedikit tidak center untuk badge inisial `size-8` di dalamnya — itu quirk styling yang SUDAH ADA sebelum brief ini (wrapper-nya memang tidak center-kan konten apa pun, termasuk `<img>` biasa). Di luar scope brief ini untuk dibenahi; fokus visual brief ini adalah slot sidebar.

**✅ Cek dulu**: login sebagai user dari academy yang `name`-nya "FC Garuda" dan `logo_sidebar`/`logo_favicon` masih `null` (set manual lewat tinker kalau perlu) → buka dashboard → sidebar (expanded) harus menampilkan teks **"FC Garuda"**, bukan gambar; ikon collapsed/header harus menampilkan **"FG"**. Login sebagai Super Admin → sidebar & header harus tetap tampil logo `KantinITSvg.svg`/`kantinit-favicon.png` seperti sebelumnya (tidak ada regresi).

---

## Tahap 7 — Form Request

**Tujuan**: `logo_sidebar` tervalidasi sebagai file gambar opsional di kedua form, konsisten dengan rule `logo`.

Rule dan pesan sama di kedua file, tambahkan **tepat setelah** rule/pesan `logo`:

```php
'logo_sidebar' => [
    'nullable',
    'image',
    'mimes:jpeg,png,jpg,webp,svg',
    'max:2048',
],
```

```php
'logo_sidebar.image' => __('Logo sidebar harus berupa gambar.'),
'logo_sidebar.mimes' => __('Format gambar logo sidebar harus berupa: jpeg, png, jpg, webp, atau svg.'),
'logo_sidebar.max' => __('Ukuran logo sidebar tidak boleh lebih dari 2MB.'),
```

### 7a. `app/Http/Requests/Academy/AcademyFormRequest.php`

Tambahkan di `rules()` tepat setelah rule `logo`, dan di `messages()` tepat setelah pesan `logo.*`.

### 7b. `app/Http/Requests/Academy/AcademyProfileFormRequest.php`

Sama, tepat setelah rule/pesan `logo`.

**✅ Cek dulu**: submit form create academy dengan file non-gambar (mis. `.pdf`) di field `logo_sidebar` (kalau sudah dipasang di Tahap 8) → harus muncul pesan "Format gambar logo sidebar harus berupa: ...", bukan crash.

---

## Tahap 8 — Views

**Tujuan**: field upload logo sidebar muncul di 3 form, **tepat setelah** field Logo persegi yang sudah ada (satu kategori "Media/Upload" — lihat `docs/frontend-standard.md` → *Urutan & Pengelompokan Field Form*, field sekategori harus berdekatan), sebelum Warna Utama.

Markup yang disisipkan (sama di ketiga file, beda cuma `current-logo-url`):

```blade
                    {{-- Logo Sidebar (Wordmark) --}}
                    <x-logo-upload-field name="logo_sidebar" :current-logo-url="CURRENT_SIDEBAR_URL"
                        :label="__('Logo Sidebar (Wordmark)')"
                        :help-text="__('PNG, JPG, JPEG, WEBP maksimal 2MB -- akan diminta crop rasio lebar. Dipakai di sidebar & header saat sidebar diperluas; kalau belum diupload, sidebar menampilkan nama academy sebagai gantinya.')"
                        :crop-title="__('Sesuaikan Logo Sidebar')"
                        :crop-description="__('Geser & perbesar untuk memilih area logo (rasio lebar).')"
                        :aspect-ratio="3.77" :output-width="980" :output-height="260"
                        preview-class="flex h-16 w-40 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/5" />
```

> `aspect-ratio="3.77"` ≈ `245/65` (rasio bounding box `LOGO_SIDEBAR_MAX_WIDTH`/`LOGO_SIDEBAR_MAX_HEIGHT` di `AcademyManagementService`, Tahap 4). `output-width="980"`/`output-height="260"` = 4x bounding box itu, supaya hasil crop cukup tajam sebelum di-`scaleDown` server-side ke 245x65.

### 8a. `resources/views/academies/create.blade.php`

Sisipkan **tepat setelah** `<x-logo-upload-field />` (field `logo` yang sudah ada) dan **sebelum** blok `{{-- Warna Utama --}}`. `CURRENT_SIDEBAR_URL` = `null` (academy baru).

### 8b. `resources/views/academies/edit.blade.php`

Sisipkan di posisi yang sama. `CURRENT_SIDEBAR_URL` = `$academy->logo_sidebar ? asset('storage/' . $academy->logo_sidebar) : null`.

### 8c. `resources/views/academy-profile/edit.blade.php`

Sisipkan di posisi yang sama (setelah `<x-logo-upload-field :current-logo-url="..." />`, sebelum blok Warna Utama). `CURRENT_SIDEBAR_URL` sama seperti 8b.

**✅ Cek dulu**: buka `/academies/create`, `/academies/{id}/edit`, `/academy-profile` — field "Logo Sidebar (Wordmark)" muncul tepat di bawah field "Logo Academy", preview berbentuk kotak lebar (bukan lingkaran). Upload gambar apa saja → modal crop muncul dengan **guide rasio lebar** (bukan persegi) → submit → `php artisan tinker` → `Academy::find($id)->logo_sidebar` terisi path baru, dan `Academy::find($id)->logo` (persegi) **tidak ikut berubah** kalau cuma field sidebar yang diisi. Buka dashboard academy itu → sidebar menampilkan gambar wordmark yang baru diupload (bukan lagi teks nama academy).

---

## Tahap 9 — `lang/en.json`

**Tujuan**: seluruh string baru dari Tahap 3, 7, dan 8 punya terjemahan Inggris.

Cek dulu key mana yang **sudah ada** dari module lain (mis. "Batal" kemungkinan sudah ada) sebelum menambah — lihat catatan di `docs/coding-standard.md` soal duplikat key. Tambahkan entry yang belum ada, contoh (sesuaikan urutan sesuai isi file saat ini):

```json
"Klik untuk unggah logo": "Click to upload logo",
"SVG, PNG, JPG, WEBP maksimal 2MB -- akan diminta crop persegi setelah dipilih": "SVG, PNG, JPG, WEBP up to 2MB -- you'll be asked to crop it square after selecting",
"Sesuaikan Logo": "Adjust Logo",
"Geser & perbesar untuk memilih area logo (persegi).": "Drag & zoom to select the logo area (square).",
"Ganti gambar": "Change image",
"Pakai Crop Ini": "Use This Crop",
"Logo Sidebar (Wordmark)": "Sidebar Logo (Wordmark)",
"PNG, JPG, JPEG, WEBP maksimal 2MB -- akan diminta crop rasio lebar. Dipakai di sidebar & header saat sidebar diperluas; kalau belum diupload, sidebar menampilkan nama academy sebagai gantinya.": "PNG, JPG, JPEG, WEBP up to 2MB -- you'll be asked to crop it to a wide ratio. Used in the sidebar & header when expanded; if not uploaded, the sidebar shows the academy name instead.",
"Sesuaikan Logo Sidebar": "Adjust Sidebar Logo",
"Geser & perbesar untuk memilih area logo (rasio lebar).": "Drag & zoom to select the logo area (wide ratio).",
"Logo sidebar harus berupa gambar.": "Sidebar logo must be an image.",
"Format gambar logo sidebar harus berupa: jpeg, png, jpg, webp, atau svg.": "Sidebar logo image format must be: jpeg, png, jpg, webp, or svg.",
"Ukuran logo sidebar tidak boleh lebih dari 2MB.": "Sidebar logo size must not exceed 2MB."
```

> `"Logo Academy"` (label field pertama, tidak berubah) kemungkinan **belum** ada entry-nya juga (component ini luput dari sweep i18n) -- cek dulu, tambahkan `"Logo Academy": "Academy Logo"` kalau memang belum ada.

**✅ Cek dulu**: ganti locale ke Inggris (tombol switcher di header) → buka `/academies/create` → seluruh label/teks di kedua field logo (termasuk modal crop) tampil Bahasa Inggris, tidak ada yang tersisa Bahasa Indonesia atau tampil key mentah.

---

## Tahap 10 — Test

### 10a. `tests/Unit/InitialsTest.php` — file baru

```php
<?php

namespace Tests\Unit;

use App\Support\Initials;
use PHPUnit\Framework\TestCase;

class InitialsTest extends TestCase
{
    public function test_dua_kata_ambil_huruf_pertama_masing_masing(): void
    {
        $this->assertSame('FG', Initials::from('FC Garuda'));
    }

    public function test_tiga_kata_tetap_ambil_dari_dua_kata_pertama(): void
    {
        $this->assertSame('AF', Initials::from('Akademi Futsal Merdeka'));
    }

    public function test_satu_kata_ambil_dua_huruf_pertama(): void
    {
        $this->assertSame('GA', Initials::from('Garuda'));
    }

    public function test_spasi_berlebih_tidak_mempengaruhi_hasil(): void
    {
        $this->assertSame('FG', Initials::from('  FC   Garuda  '));
    }

    public function test_selalu_huruf_besar(): void
    {
        $this->assertSame('FG', Initials::from('fc garuda'));
    }
}
```

### 10b. `tests/Feature/AcademyLogoFallbackTest.php` — file baru

```php
<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyLogoFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_academy_tanpa_logo_sidebar_menampilkan_nama_academy(): void
    {
        $academy = Academy::factory()->create(['name' => 'FC Garuda', 'logo_sidebar' => null]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('FC Garuda');
    }

    public function test_academy_tanpa_logo_favicon_menampilkan_inisial(): void
    {
        $academy = Academy::factory()->create(['name' => 'FC Garuda', 'logo_favicon' => null]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('FG');
    }

    public function test_academy_dengan_logo_sidebar_menampilkan_gambar_bukan_teks(): void
    {
        $academy = Academy::factory()->create([
            'name' => 'FC Garuda',
            'logo_sidebar' => 'academies/logo/FAKE-sidebar.png',
        ]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('storage/academies/logo/FAKE-sidebar.png', false);
        $response->assertDontSee('FC Garuda');
    }

    public function test_super_admin_selalu_pakai_logo_sistem_default(): void
    {
        $superAdmin = User::factory()->create(['id_academy' => null, 'status' => true]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('KantinITSvg.svg', false);
    }
}
```

**✅ Cek dulu**

```bash
php artisan test --filter=InitialsTest
php artisan test --filter=AcademyLogoFallbackTest
php artisan test
```

Seluruh test harus **pass**, termasuk full suite (baseline sebelumnya, tidak boleh ada regresi baru).

---

## Tahap 11 — Dokumentasi

**Tujuan**: pola "2 upload logo independen lewat 1 komponen reusable + fallback teks/inisial ber-token brand" terdokumentasi, supaya kalau nanti modul kartu nama/kop surat dibangun (pakai `logo` persegi), atau field upload+crop lain butuh rasio berbeda, tidak perlu riset ulang.

Tambahkan section baru di `docs/frontend-standard.md`, setelah section *Theming Per-Academy (CSS Custom Property Override)*:

```markdown
## Upload Logo Multi-Slot (Persegi + Wordmark)

Academy punya 2 slot logo independen, masing-masing lewat `<x-logo-upload-field>` yang sama (reusable lewat `@props`: `name`, `aspect-ratio`, `output-width`/`output-height`, `preview-class`, dst):

1. **`logo`** (persegi, aspect ratio 1) -- sumber untuk `logo_favicon` (cover crop 64x64) dan calon kebutuhan lain yang butuh logo proporsional (kartu nama, kop surat).
2. **`logo_sidebar`** (wordmark, aspect ratio lebar ~3.77) -- dipakai apa adanya di slot sidebar/header (`scaleDown` ke bounding box 245x65, TANPA crop tambahan).

**Kapan pola ini dipakai lagi**: field upload+crop baru dengan rasio berbeda dari yang sudah ada -- reuse `<x-logo-upload-field>` dengan `:aspect-ratio`/`:output-width`/`:output-height` baru, JANGAN hand-roll komponen crop baru.

**Fallback saat academy belum upload**: `<x-academy-logo>` (`App\View\Components\AcademyLogo`) render teks nama academy (slot lebar) atau inisial 1-2 huruf lewat `App\Support\Initials` (slot persegi kecil) kalau academy aktif belum punya logo sendiri -- BUKAN fallback ke logo generic sistem (itu cuma untuk Super Admin). Warnanya numpang di token `brand` yang sudah di-override `<x-academy-theme>` per academy, tidak re-compute warna sendiri. Method `AcademyService::sidebarLogoUrl()`/`faviconUrl()` (selalu return URL gambar) TETAP dipakai apa adanya oleh `<link rel="icon">` -- fallback teks HANYA berlaku di jalur `<x-academy-logo>` Component.
```

**✅ Cek dulu**: buka `docs/frontend-standard.md`, pastikan section baru muncul di Table of Contents juga (tambahkan link-nya).

---

## 4. Alasan Teknis

### 4.1 Kenapa `sidebarLogoUrl()`/`faviconUrl()` tidak diubah jadi nullable

Kedua method ini dipakai di 2 konteks yang beda kebutuhannya:

```text
layouts/app.blade.php:20
  <link rel="icon" ... href="{{ app(AcademyService::class)->faviconUrl() }}">
  -> WAJIB selalu ada href gambar. href="" / href kosong akan membuat
     browser mencoba fetch halaman itu sendiri sebagai favicon (404/aneh).

sidebar.blade.php, header.blade.php
  <x-academy-logo variant="favicon" ...>
  -> BOLEH fallback ke elemen non-gambar (<span> teks/inisial).
```

Kalau `faviconUrl()` diubah return `?string` lalu `null`-nya dipakai langsung di `href`, tag `<link>` akan rusak. Makanya keputusannya: `sidebarLogoUrl()`/`faviconUrl()` TETAP seperti semula (selalu resolve ke URL gambar, fallback ke asset sistem untuk Super Admin ATAU academy yang benar-benar tidak punya baris academy sama sekali), dan logic "apakah academy PUNYA logo sendiri" jadi method terpisah (`hasOwnSidebarLogo()`/`hasOwnFaviconLogo()`) yang cuma dikonsumsi `AcademyLogo` Component, bukan dipakai di `<link>`.

### 4.2 Kenapa badge fallback tidak numpang di atas utility `avatar`

`resources/css/theme/utilities.css` mendefinisikan `avatar` sebagai `@utility` (`@apply flex size-10 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300`). Tailwind v4 CSS-first menaruh SEMUA `@utility` custom (termasuk `avatar`) dan utility bawaan (`bg-brand-50`, `text-brand-600`, dst) di layer yang sama, `@layer utilities` -- urutan menang-kalah antar dua class di layer yang sama ditentukan urutan generate CSS-nya oleh Tailwind, BUKAN urutan penulisan class di atribut HTML. Menulis `class="avatar bg-brand-50 text-brand-600"` TIDAK menjamin `bg-brand-50` menang lawan `bg-gray-100` bawaan `avatar` -- beda dengan kasus `<x-academy-theme>` (`issue6.md` 4.2) yang sengaja unlayered supaya kemenangannya pasti. Daripada bergantung pada urutan yang tidak terjamin, badge fallback ditulis dengan class Tailwind lengkap sendiri (tidak extend `avatar`), jadi tidak ada pertanyaan "siapa yang menang" sama sekali.
