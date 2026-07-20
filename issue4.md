# Brief: Crop Logo Sebelum Upload (Client-Side, Cropper.js)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md`, `README.md`, dan `docs/` dulu. Terutama `docs/frontend-standard.md` (Blade Component untuk view dengan data dinamis) dan `docs/coding-standard.md`. Baca juga `issue3.md` (Academy Logo — Varian Ukuran) karena brief ini menempel **di depan** alur upload yang dibangun di sana — logo yang sudah di-crop di browser tetap diproses `AcademyManagementService::generateLogoVariants()` seperti biasa, **tidak ada perubahan** di sisi server.
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 6 berurutan**. Jangan lompat. Setiap tahap punya blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.

---

## 0. Aturan Emas

Tujuh larangan ini bukan preferensi gaya. Masing-masing sudah dipikirkan konsekuensinya. Alasan lengkapnya di **Bagian 4**.

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Ubah `AcademyManagementService`, `AcademyFormRequest`, `AcademyProfileFormRequest`, atau route/controller manapun | Crop terjadi **sepenuhnya di browser**, sebelum form di-submit. Server menerima file yang **sudah** persegi/sudah di-crop, diproses persis seperti file upload biasa lewat `generateLogoVariants()` (`issue3.md`) — tidak ada field baru, tidak ada endpoint baru | [2b](#2b-kenapa-tidak-ada-perubahan-di-sisi-server) |
| Hapus/lewati pengecekan SVG di `generateLogoVariants()` (`issue3.md` Bagian 4.2) dengan asumsi "kan sudah di-crop jadi raster" | Crop cuma jalan kalau JavaScript aktif dan user memang melalui form ini. Request yang dikarang langsung ke endpoint (lewat Postman/curl, bukan lewat browser) bisa saja tetap mengirim SVG mentah — proteksi server **wajib** tetap ada sebagai lapisan pertahanan kedua, jangan percaya client sepenuhnya | [4.3](#43-kenapa-proteksi-svg-di-server-issue3md-tetap-wajib-ada) |
| Bikin 3 blok markup upload logo terpisah (copy-paste) di `academies/create.blade.php`, `academies/edit.blade.php`, `academy-profile/edit.blade.php` | Ketiga file itu **sudah** punya markup upload logo yang identik persis (cek sendiri — `x-data="{ imagePreview: ... }"`, `form-file-upload`, FileReader). Brief ini **wajib** mengekstraknya jadi satu Blade Component (`<x-logo-upload-field>`), bukan menambah crop UI 3× dengan copy-paste | [Tahap 3](#tahap-3--blade-component-logouploadfield-baru) |
| Biarkan `<input type="file">` tetap berisi file **asli** (belum di-crop) kalau user membatalkan crop | Modal crop ini **wajib**, bukan opsional (lihat [1](#1-tujuan)) — kalau user klik "Batal" di modal crop, file input harus di-reset (`value = ''`), supaya tidak ada jalan submit form dengan file yang belum melalui crop | [Tahap 2](#tahap-2--alpine-component-logo-crop-fieldjs) |
| Pasang `aspectRatio` bebas (`NaN`) di konfigurasi Cropper.js | Hasil crop jadi sumber untuk avatar Detail Academy (`avatar-square`, `issue.md`) **dan** varian sidebar/favicon (`issue3.md`). Rasio bebas bikin hasil tidak konsisten tiap academy. **Wajib** `aspectRatio: 1` (persegi) — konsisten dengan konteks avatar & sederhana untuk diturunkan lagi jadi varian lain di server | [2c](#2c-kenapa-crop-dipaksa-rasio-11-persegi) |
| Berharap ada test PHPUnit otomatis untuk interaksi crop (drag, zoom, klik tombol modal) | Itu murni interaksi JavaScript di browser — PHPUnit/Feature test **tidak bisa** menjalankan JS. Butuh Laravel Dusk (browser automation) untuk itu, yang **belum terpasang** di project ini dan di luar scope brief ini. Verifikasi fitur ini **manual** (lihat Tahap 5) | [4.4](#44-kenapa-tidak-ada-test-otomatis-untuk-interaksi-crop) |
| Import CSS Cropper.js lewat `<link>` CDN di Blade | Project ini sudah pakai Vite untuk seluruh asset (`docs/frontend-standard.md`) — CSS Cropper.js **wajib** di-import lewat `resources/js/app.js` (`import 'cropperjs/dist/cropper.css'`), diproses & di-bundle Vite seperti asset lain, bukan tag `<link>` terpisah ke CDN eksternal | [Tahap 1](#tahap-1--npm-install-cropperjs) |

---

## 1. Tujuan

Saat ini (setelah `issue3.md`), upload logo academy langsung dikirim apa adanya ke server — server yang secara otomatis meng-crop/resize jadi varian sidebar (`scaleDown` bounding box 245×65) dan favicon (`cover` 64×64, persegi paksa). Masalahnya: Super Admin/Owner tidak punya kendali atas **bagian mana** dari logo asli yang jadi fokus crop — kalau logo asli bukan persegi sempurna atau elemen pentingnya tidak di tengah, hasil auto-crop favicon (dan turunan lain) bisa memotong bagian yang salah.

**Scope brief ini**: sebelum file logo benar-benar terkirim ke server, user **wajib** melalui langkah crop interaktif di browser (pilih area, geser, zoom) lewat modal Cropper.js. Hasil crop (persegi, `aspectRatio: 1`) itulah yang jadi file `logo` yang dikirim ke server — server tetap memprosesnya lewat pipeline yang sama persis dengan `issue3.md` (upload asli → `generateLogoVariants()` → `logo_sidebar`/`logo_favicon`), **tanpa perubahan apapun** di sisi server.

**Bukan** scope brief ini: crop ulang logo yang sudah tersimpan (tanpa upload file baru), aspect ratio custom per academy, atau resize interaktif untuk `logo_sidebar`/`logo_favicon` secara terpisah — keduanya tetap diturunkan otomatis oleh server dari hasil crop persegi ini, persis seperti `issue3.md`.

---

## 2. Cara Kerja Solusi

### 2a. Alur end-to-end

```text
1. User klik area upload logo -> pilih file dari device
2. [BARU] File dibaca sebagai Data URL (FileReader), BUKAN langsung jadi imagePreview
3. [BARU] Modal crop terbuka, image ditampilkan dengan Cropper.js (aspectRatio: 1)
4. User geser/zoom, klik "Pakai Crop Ini"
5. [BARU] Cropper.js render hasil crop ke <canvas>, canvas.toBlob() -> Blob PNG 1024x1024
6. [BARU] Blob dibungkus jadi File baru, dipasang ke <input type="file"> via DataTransfer
   (menggantikan file asli yang dipilih di langkah 1)
7. [BARU] imagePreview diperbarui dari Blob itu, modal ditutup
8. User lanjut isi form lain, klik Submit -- TIDAK ADA BEDANYA dari alur sebelum brief ini
9. Server terima file (sudah persegi, PNG, ~1024x1024) di field `logo`, proses lewat
   AcademyManagementService::generateLogoVariants() -- SAMA PERSIS dengan issue3.md
```

Langkah 8-9 **tidak berubah sama sekali** dari `issue3.md` — makanya tidak ada file PHP (Controller/Service/FormRequest) yang perlu disentuh.

### 2b. Kenapa tidak ada perubahan di sisi server

`AcademyManagementService::generateLogoVariants()` (issue3.md) menerima **file apa saja** yang ada di `$data['logo']` — tidak peduli apakah itu foto asli langsung dari kamera user, atau hasil crop dari Cropper.js. Selama file yang sampai ke server valid (lolos `image`, `mimes:...`, `max:2048` di Form Request), pipeline resize-nya jalan sama persis. Cropper.js cuma mengganti **isi** `<input type="file">` sebelum form di-submit lewat JavaScript — dari sudut pandang server, ini tetap "user upload satu file gambar", titik.

### 2c. Kenapa crop dipaksa rasio 1:1 (persegi)

Hasil crop di sini jadi **sumber tunggal** untuk 3 tempat: avatar Detail Academy (`class="avatar-lg avatar-square"` — sudah persegi di CSS-nya), `logo_sidebar` (`scaleDown` bounding box 245×65 — akan otomatis mentok di sisi 65 kalau sumbernya persegi, hasil rapi), dan `logo_favicon` (`cover` 64×64 — kalau sumbernya sudah persegi, croppingnya jadi no-op, tidak ada bagian yang hilang lagi). Rasio bebas akan membuat hasilnya tidak konsisten tiap academy dan berpotensi membuat `logo_favicon` (yang **wajib** persegi) kembali ter-crop otomatis oleh server tanpa kendali user — balik ke masalah yang brief ini coba selesaikan.

### 2d. Kenapa Blade Component, bukan 3× copy-paste

`docs/frontend-standard.md` (section *Reusable View dengan Data Dinamis*) sudah menetapkan pola ini untuk kasus serupa (`AcademyLogo` dari `issue3.md`). Markup upload logo di `academies/create.blade.php`, `academies/edit.blade.php`, `academy-profile/edit.blade.php` **sudah identik** (nama field selalu `logo`, class `form-file-upload` yang sama, cuma beda nilai awal `imagePreview` untuk form edit). Brief ini mengekstraknya jadi `<x-logo-upload-field :current-logo-url="..." />` — satu tempat untuk nambah crop modal, bukan 3.

---

## 3. Peta Perubahan File

Kerangka lengkap. Kalau sebuah file tidak ada di tabel ini, **jangan disentuh**.

| File | Aksi | Tahap |
|------|------|-------|
| `package.json` (`cropperjs`) | ✏️ Tambah dependency | 1 |
| `resources/js/app.js` | ✏️ Import CSS Cropper.js + register Alpine component | 1, 2 |
| `resources/js/components/logo-crop-field.js` | 🆕 Baru | 2 |
| `app/View/Components/LogoUploadField.php` | 🆕 Baru | 3 |
| `resources/views/components/logo-upload-field.blade.php` | 🆕 Baru (termasuk markup modal crop) | 3 |
| `resources/views/academies/create.blade.php` | ✏️ Ganti blok upload logo jadi `<x-logo-upload-field>` | 4 |
| `resources/views/academies/edit.blade.php` | ✏️ Ganti blok upload logo jadi `<x-logo-upload-field>` | 4 |
| `resources/views/academy-profile/edit.blade.php` | ✏️ Ganti blok upload logo jadi `<x-logo-upload-field>` | 4 |
| `docs/frontend-standard.md` | ✏️ Tambah `LogoUploadField` ke daftar contoh Blade Component | 6 |
| **`app/Services/AcademyManagementService.php`, `AcademyFormRequest.php`, `AcademyProfileFormRequest.php`, routes/controller manapun** | 🚫 **Jangan sentuh** — lihat [2b](#2b-kenapa-tidak-ada-perubahan-di-sisi-server) | — |
| **`tests/Feature/AcademyLogoVariantTest.php` (issue3.md)** | 🚫 **Jangan sentuh** — server tidak berubah, test yang ada tetap valid apa adanya | — |

---

## Tahap 1 — `npm install cropperjs`

**Tujuan**: library crop terpasang & CSS-nya ikut ter-bundle Vite.

```bash
npm install cropperjs@^1.6
```

Tambahkan import CSS di baris paling atas `resources/js/app.js` (sebelum import lain):

```js
import 'cropperjs/dist/cropper.css'
import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
```

**✅ Cek dulu**

```bash
npm list cropperjs
npm run build
```

- `cropperjs` muncul di `package.json` → `dependencies`.
- `npm run build` sukses tanpa error (tanda CSS Cropper.js berhasil di-resolve Vite).

---

## Tahap 2 — Alpine Component `logo-crop-field.js`

**Tujuan**: logic crop (buka modal, render Cropper.js, konfirmasi/batal) di satu tempat, dipakai lewat `x-data="logoCropField()"`.

`resources/js/components/logo-crop-field.js` — file baru:

```js
import Cropper from 'cropperjs';

export default (initialPreview = '') => ({
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
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            background: false,
        });
    },

    confirmCrop() {
        this.cropper.getCroppedCanvas({ width: 1024, height: 1024 }).toBlob((blob) => {

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

Daftarkan di `resources/js/app.js`:

```js
import 'cropperjs/dist/cropper.css'
import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
import deleteModal from './components/delete-modal'
import resetAkunModal from './components/reset-akun-modal'
import statusModal from './components/status-modal'
import logoutModal from './components/logout-modal'
import rolePermissionForm from './components/role-permission-form';
import logoCropField from './components/logo-crop-field';

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.data('deleteModal', deleteModal)
Alpine.data('resetAkunModal', resetAkunModal)
Alpine.data('statusModal', statusModal)
Alpine.data('logoutModal', logoutModal)
Alpine.data('rolePermissionForm', rolePermissionForm);
Alpine.data('logoCropField', logoCropField);

window.Alpine = Alpine;

Alpine.start();
```

> `initialPreview` sebagai parameter factory (bukan properti statis) supaya tiap pemanggilan `<x-logo-upload-field>` bisa punya nilai awal beda (form edit sudah ada logo lama, form create kosong) — dipasok dari Blade Component di Tahap 3 lewat `x-data="logoCropField('{{ $currentLogoUrl }}')"`.

**✅ Cek dulu**

```bash
npm run build
```

Tidak ada error import. Cek juga manual di browser setelah Tahap 4 selesai (belum bisa dites sebelum komponennya dipasang ke view).

---

## Tahap 3 — Blade Component `LogoUploadField` (baru)

**Tujuan**: satu component menggantikan 3 blok markup upload logo yang identik, sekaligus membawa modal crop-nya.

`app/View/Components/LogoUploadField.php`:

```php
<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LogoUploadField extends Component
{
    public ?string $currentLogoUrl;

    public function __construct(?string $currentLogoUrl = null)
    {
        $this->currentLogoUrl = $currentLogoUrl;
    }

    public function render(): View
    {
        return view('components.logo-upload-field');
    }
}
```

`resources/views/components/logo-upload-field.blade.php` — struktur upload disalin dari markup yang sudah ada di `academies/edit.blade.php` (supaya class CSS-nya tetap konsisten), field crop modal ditambahkan di bawahnya:

```blade
<div class="form-group" x-data="logoCropField('{{ $currentLogoUrl }}')">

    <label class="form-label">
        Logo Academy
    </label>

    <div class="form-file-upload">

        <input type="file" id="logo" name="logo" x-ref="fileInput"
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
                Klik untuk unggah logo
            </p>

            <p class="empty-description">
                SVG, PNG, JPG, WEBP maksimal 2MB -- akan diminta crop persegi setelah dipilih
            </p>

        </div>

        <div x-show="imagePreview" x-cloak class="flex flex-col items-center">

            <div class="avatar avatar-lg avatar-square mb-3">
                <img :src="imagePreview" class="h-full w-full object-cover">
            </div>

            <span class="link-primary text-xs font-semibold">
                Ganti gambar
            </span>

        </div>

    </div>

    @error('logo')
        <span class="form-error">{{ $message }}</span>
    @enderror

    {{-- Modal Crop --}}
    <div x-show="showCropModal" x-cloak x-transition
        class="modal-overlay flex items-center justify-center p-4">

        <div class="modal-container modal-md">

            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Sesuaikan Logo</h3>
                    <p class="modal-description">Geser & perbesar untuk memilih area logo (persegi).</p>
                </div>
            </div>

            <div class="modal-body">
                <div class="max-h-[400px] overflow-hidden">
                    <template x-if="pendingSourceUrl">
                        <img :src="pendingSourceUrl" x-ref="cropperImage" class="block max-w-full">
                    </template>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="cancelCrop()">
                    Batal
                </button>

                <button type="button" class="btn btn-primary" @click="confirmCrop()">
                    Pakai Crop Ini
                </button>
            </div>

        </div>

    </div>

</div>
```

> Class `modal-overlay`/`modal-container`/`modal-header`/`modal-body`/`modal-footer` **sudah ada** (lihat `resources/views/components/modal/delete.blade.php`) — dipakai ulang apa adanya, bukan bikin style modal baru.

**✅ Cek dulu**

```bash
php artisan tinker
```

```php
(new \App\View\Components\LogoUploadField('https://example.com/logo.png'))->currentLogoUrl;
// harus: "https://example.com/logo.png", tidak error
```

---

## Tahap 4 — Terapkan ke 3 Form

**Tujuan**: `academies/create.blade.php`, `academies/edit.blade.php`, `academy-profile/edit.blade.php` semuanya pakai `<x-logo-upload-field>`, blok lama dihapus.

Di `academies/edit.blade.php` dan `academy-profile/edit.blade.php`, ganti seluruh blok (dari `<div class="form-group" x-data="{ imagePreview: ...` sampai `@enderror` penutup field logo) menjadi:

```blade
<x-logo-upload-field :current-logo-url="$academy->logo ? asset('storage/' . $academy->logo) : null" />
```

Di `academies/create.blade.php`, ganti blok yang sama (nilai awal kosong, belum ada academy) menjadi:

```blade
<x-logo-upload-field />
```

> Pastikan form pembungkusnya **tetap** punya `enctype="multipart/form-data"` (sudah ada di ketiganya sejak sebelum brief ini, tidak perlu ditambah) — tanpa itu file (asli maupun hasil crop) tidak akan ikut terkirim sama sekali.

**✅ Cek dulu** — jalankan `npm run dev` (atau `npm run build` lalu refresh), lalu:

- Buka `/academies/create` → klik area upload logo → pilih gambar apapun (persegi/lebar/tinggi) → modal crop **muncul otomatis**.
- Geser & zoom area crop → klik "Pakai Crop Ini" → modal tertutup, preview di halaman berubah jadi hasil crop (persegi).
- Klik "Batal" di modal (setelah pilih file baru) → preview **tidak** berubah, kalau field masih kosong sebelumnya tetap kosong.
- Submit form dengan logo yang sudah di-crop → academy tersimpan, cek `php artisan tinker` → `Academy::latest()->first()->logo_sidebar` dan `logo_favicon` tetap ter-generate normal (server tidak tahu bedanya).
- Ulangi di `/academies/{id}/edit` dan halaman Profil Academy (Owner) → perilaku sama.
- Test di mobile/tablet (resize browser atau device asli) → Cropper.js mendukung gesture sentuh secara native, pastikan drag/pinch-zoom berfungsi.

---

## Tahap 5 — Verifikasi Manual (pengganti automated test)

**Tujuan**: karena tidak ada perubahan server (lihat [2b](#2b-kenapa-tidak-ada-perubahan-di-sisi-server)) dan interaksi crop murni JavaScript (lihat [4.4](#44-kenapa-tidak-ada-test-otomatis-untuk-interaksi-crop)), verifikasi fitur ini **manual**, bukan lewat `php artisan test`.

Checklist manual (selain yang sudah dicek di Tahap 4):

- [ ] Upload logo **SVG** → tetap bisa dipilih (`accept="image/*"` browser biasanya masih menampilkannya), modal crop tetap muncul (Cropper.js bisa me-render SVG ke `<img>`/canvas untuk preview), hasil crop **selalu** PNG raster — artinya kasus "SVG di-skip dari resize" (`issue3.md` Bagian 4.2) jadi jarang terjadi lewat form ini (karena outputnya sudah otomatis PNG), tapi proteksi server tetap ada untuk request yang tidak lewat form (lihat Aturan Emas).
- [ ] Jalankan `php artisan test --filter=AcademyLogoVariantTest` (dari `issue3.md`) → **harus tetap pass** tanpa perubahan, membuktikan server benar-benar tidak tersentuh brief ini.
- [ ] Batalkan crop di form **create** (belum ada logo lama) → submit form tanpa pilih ulang logo → academy tersimpan **tanpa** logo (`logo` tetap null), tidak ada error.
- [ ] Batalkan crop di form **edit** (sudah ada logo lama) → preview kembali menampilkan logo lama (bukan kosong), submit form tanpa ganti logo → logo lama **tidak berubah**.

**✅ Cek dulu**: seluruh checklist di atas tercentang, dan `php artisan test` (full suite) tidak menghasilkan kegagalan baru dibanding sebelum brief ini dikerjakan.

---

## Tahap 6 — Dokumentasi

**Tujuan**: `docs/frontend-standard.md` mencatat `LogoUploadField` sebagai contoh tambahan Blade Component.

Di section *Reusable View dengan Data Dinamis*, ubah baris (yang sudah memuat `AcademyLogo` dari `issue3.md`):

```markdown
Contoh yang sudah ada: `App\View\Components\Alert`, `App\View\Components\Breadcrumb`, `App\View\Components\AuthSidebar`, `App\View\Components\AcademyLogo`. Pola bakunya:
```

menjadi:

```markdown
Contoh yang sudah ada: `App\View\Components\Alert`, `App\View\Components\Breadcrumb`, `App\View\Components\AuthSidebar`, `App\View\Components\AcademyLogo`, `App\View\Components\LogoUploadField`. Pola bakunya:
```

**✅ Cek dulu**: baca ulang, pastikan tidak menambah section baru yang duplikat — cukup satu baris di daftar contoh yang sudah ada.

---

## 4. Alasan Teknis

### 4.1. Kenapa Cropper.js (bukan library lain / bikin sendiri)

Cropper.js v1.x adalah library crop gambar client-side yang paling matang & framework-agnostic (tidak terikat React/Vue), cocok dengan stack Alpine.js + Vite yang sudah dipakai project ini — tinggal `new Cropper(imageElement, options)`, tidak perlu wrapper tambahan. Mendukung drag/zoom/gesture sentuh secara native, dan API `getCroppedCanvas()` langsung menghasilkan `<canvas>` yang bisa di-convert ke `Blob` lewat `toBlob()` — persis yang dibutuhkan untuk mengganti isi `<input type="file">`.

### 4.2. Kenapa `DataTransfer` untuk mengganti isi `<input type="file">`

Elemen `<input type="file">` bersifat **read-only** dari sisi JavaScript untuk alasan keamanan browser — tidak bisa di-assign langsung dengan `input.files = [someBlob]`. Satu-satunya cara resmi mengganti isinya secara terprogram adalah lewat `DataTransfer` API: buat instance baru, `.items.add(file)`, lalu assign `.files` dari `DataTransfer` itu ke input. Ini pola standar web, bukan workaround spesifik project ini.

### 4.3. Kenapa proteksi SVG di server (`issue3.md`) tetap wajib ada

Crop di browser cuma jalan kalau: (1) JavaScript aktif, (2) user benar-benar memakai form ini (bukan mengirim request langsung ke endpoint lewat tools seperti Postman/curl dengan kredensial yang valid), dan (3) tidak ada modifikasi/bypass di sisi client. Server **tidak pernah** boleh mengasumsikan "file yang masuk pasti sudah di-crop jadi raster PNG" — `generateLogoVariants()` di `issue3.md` tetap mengecek ekstensi SVG dan skip resize untuk kasus itu, sebagai lapisan pertahanan kedua yang independen dari apapun yang terjadi di browser.

### 4.4. Kenapa tidak ada test otomatis untuk interaksi crop

`php artisan test` (PHPUnit) menjalankan kode PHP di server — tidak ada browser sungguhan, tidak ada DOM, tidak ada JavaScript yang dieksekusi. Menguji "user drag area crop, klik tombol, canvas ter-render benar" butuh **browser automation** (Laravel Dusk, Playwright, dst), yang **belum terpasang** di project ini (`composer.json` tidak punya `laravel/dusk`) — menambahnya adalah keputusan terpisah (dependency besar, butuh ChromeDriver, dll), di luar scope brief ini. Yang **bisa** dan **wajib** tetap dites otomatis: `AcademyLogoVariantTest` dari `issue3.md`, karena itu murni server-side dan brief ini tidak mengubahnya sama sekali.

---

## 5. Development Checklist

Sebelum brief ini dinyatakan selesai, cocokkan dengan checklist `docs/module-standard.md`:

- [ ] `package.json`: `cropperjs` terpasang, `npm run build` sukses.
- [ ] `resources/js/app.js`: CSS Cropper.js di-import, `logoCropField` terdaftar sebagai `Alpine.data()`.
- [ ] `logo-crop-field.js`: `onFileSelected`/`initCropper`/`confirmCrop`/`cancelCrop` — batal mengosongkan `<input type="file">` (crop wajib, bukan opsional).
- [ ] Blade Component `LogoUploadField` dipakai di 3 form (create/edit Academy, edit Academy Profile), **tidak ada** lagi blok upload logo yang copy-paste.
- [ ] Modal crop pakai class CSS modal yang sudah ada (`modal-overlay`/`modal-container`/dst), bukan style baru.
- [ ] `AcademyManagementService.php`, `AcademyFormRequest.php`, `AcademyProfileFormRequest.php`, route/controller — **tidak ada** yang berubah sama sekali.
- [ ] `php artisan test --filter=AcademyLogoVariantTest` tetap pass tanpa modifikasi.
- [ ] Manual: upload logo apapun bentuknya (persegi/lebar/tinggi) → modal crop selalu muncul, hasil selalu persegi.
- [ ] Manual: batal crop di form create → submit tanpa logo, tidak error. Batal crop di form edit → logo lama tidak berubah.
- [ ] Manual: gesture sentuh (mobile/tablet) berfungsi di modal crop.
- [ ] `docs/frontend-standard.md`: `LogoUploadField` ditambahkan ke daftar contoh Blade Component.

## Summary

Brief ini menambahkan langkah crop interaktif (Cropper.js, rasio 1:1 dipaksa) sebelum file logo academy benar-benar dikirim ke server — user memilih file, modal crop otomatis terbuka, hasil crop (PNG persegi ~1024×1024) menggantikan isi `<input type="file">` lewat `DataTransfer` API sebelum form di-submit. Karena prosesnya sepenuhnya di browser, **tidak ada satupun file PHP (Controller/Service/FormRequest/route) yang berubah** — server tetap menerima "satu file gambar" seperti biasa dan memprosesnya lewat pipeline `generateLogoVariants()` yang sudah dibangun di `issue3.md` tanpa modifikasi. Tiga blok markup upload logo yang sebelumnya identik-copy-paste di `academies/create.blade.php`, `academies/edit.blade.php`, dan `academy-profile/edit.blade.php` diekstrak jadi satu Blade Component (`LogoUploadField`) yang membawa serta modal crop-nya, mengikuti pola `docs/frontend-standard.md`. Proteksi SVG di server (`issue3.md`) sengaja **tidak dihapus** meski crop browser menghasilkan raster — client tidak pernah dipercaya penuh, dan tidak ada test otomatis untuk interaksi crop itu sendiri karena butuh browser automation (Dusk) yang di luar scope, tapi test server-side yang sudah ada (`AcademyLogoVariantTest`) tetap wajib hijau sebagai bukti server benar-benar tidak tersentuh.
