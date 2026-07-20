# Brief: Perbaikan UX Mobile/Tablet — Halaman Detail Academy (`academies/show.blade.php`)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Baca `CLAUDE.md` dan `docs/frontend-standard.md` dulu — terutama section "Tabel Responsif: Table Desktop + Card List Mobile/Tablet", supaya paham **kenapa** mobile/tablet diprioritaskan di FAOSBall (alat kerja utama coach/staff/admin di lapangan, bukan desktop).
> **Cara pakai brief ini**: Kerjakan **Tahap 1 → 2** berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Sumber temuan**: hasil review manual (analisis CSS/markup, bukan screenshot browser) terhadap `resources/views/academies/show.blade.php` — halaman detail satu academy yang dibuka Super Admin dari menu Academy Management.

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Pindahkan blok HTML "Informasi Ringkas"/"Informasi Langganan" secara fisik ke atas dalam DOM | Merusak urutan baca/tab-order yang konsisten dengan tampilan desktop, dan bikin diff jadi besar & rawan salah potong-tempel. Cukup pakai utility `order-*` Tailwind — DOM tetap sama, cuma urutan **visual** yang berubah | [3.2](#32-kenapa-order--bukan-pindah-posisi-div-di-html) |
| Terapkan fix `flex-wrap` di `card-actions` **hanya** langsung di `academies/show.blade.php` (misal nulis ulang `class="flex items-center gap-3 flex-wrap"` di file itu saja) | `card-actions` adalah `@utility` di `components.css`, dipakai di **28 file** lain (lihat `docs/frontend-standard.md` → *Kapan Membuat @utility Baru*). Kalau ditambal cuma di satu file, halaman lain (`players/show`, `roles/show`, `permissions/show`, dst) tetap punya risiko overflow yang sama | [3.1](#31-kenapa-fix-card-actions-di-componentscss-bukan-cuma-di-academiesshowbladephp) |
| Terapkan `order-*` yang sama ke `players/show.blade.php`, `roles/show.blade.php`, atau `permissions/show.blade.php` | Brief ini **hanya** scope untuk `academies/show.blade.php` sesuai temuan review. Konten "apa yang paling prioritas dilihat duluan" beda-beda tiap module — jangan disamakan tanpa didiskusikan dulu (lihat Aturan Utama `CLAUDE.md`) | [3.3](#33-kenapa-scope-order--cuma-academiesshowbladephp) |
| Tambah `flex-wrap` lalu ikut mengubah `gap-3` jadi nilai lain, atau menambah `justify-*` baru | Di luar scope — potensi mengubah tampilan desktop yang sudah benar. Cukup tambah **satu** utility (`flex-wrap`), jangan sentuh yang lain | — |
| Uji cuma di lebar desktop lalu anggap selesai | Inti brief ini justru perbaikan mobile/tablet — **wajib** dicek di DevTools lebar ≤375px (phone) dan 768–1023px (tablet portrait, masih di bawah breakpoint `lg`=1024px) | Tahap 1 & 2 |

---

## 1. Konteks & Masalah

FAOSBall dipakai lewat HP/tablet sebagai alat kerja utama (lihat `docs/frontend-standard.md` → *Tabel Responsif*, alasan yang sama juga berlaku di sini). Review terhadap `academies/show.blade.php` menemukan 2 masalah konkret yang bertentangan dengan prioritas itu:

### Masalah 1 — Info penting terdorong ke bawah di layar sempit

Halaman ini pakai layout:

```blade
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">   {{-- Deskripsi Academy, Alamat Lengkap --}}
    <div class="space-y-6">                  {{-- Informasi Ringkas, Informasi Langganan, Informasi Sistem --}}
```

`grid-cols-1` di bawah breakpoint `lg` (1024px) cuma men-stack 2 blok itu **sesuai urutan DOM** — kolom kiri (teks panjang: Deskripsi & Alamat) duluan, kolom kanan (Status Keaktifan, Kontak, **Status Langganan**) belakangan. Akibatnya di HP/tablet, user harus scroll lewat 2 blok teks panjang dulu sebelum sampai ke info yang justru paling dibutuhkan cepat: status aktif/nonaktif academy dan status langganan (Aktif/Akan Berakhir/Kadaluarsa).

### Masalah 2 — Baris tombol aksi berisiko overflow di HP sempit

```blade
<div class="card-actions">   {{-- "Kembali" + "Ubah Profile" (icon+teks) + dropdown akun --}}
```

`card-actions` (didefinisikan di `resources/css/theme/components.css`) saat ini:

```css
@utility card-actions {
    @apply flex items-center gap-3;
}
```

Tidak ada `flex-wrap`. Tiga elemen (`Kembali`, `Ubah Profile` dengan ikon+teks, dropdown akun) berpotensi lebih lebar dari viewport yang tersedia di HP kecil (~360–375px) setelah dikurangi padding card+layout — karena `btn` tidak menyusut di bawah lebar konten teksnya, hasilnya tombol bisa kepotong/overflow horizontal alih-alih rapi wrap ke baris kedua.

**Scope brief ini**: 2 perbaikan di atas saja. **Bukan** scope: redesign card, mengubah copy/teks, menambah section baru, atau menyentuh halaman detail module lain (players/roles/permissions) meski polanya mirip.

---

## 2. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `resources/css/theme/components.css` | ✏️ Tambah `flex-wrap` ke `@utility card-actions` | 1 |
| `resources/views/academies/show.blade.php` | ✏️ Tambah `order-*` di 2 div kolom (main content & sidebar) | 2 |
| **`resources/views/players/show.blade.php`, `roles/show.blade.php`, `permissions/show.blade.php`** | 🚫 **Jangan sentuh** — otomatis ikut lebih aman dari fix Tahap 1 (karena pakai utility yang sama), tapi **jangan** tambahkan `order-*` ke file-file ini di brief ini | — |
| **Field/data academy (`AcademyController`, `AcademyManagementService`, dst)** | 🚫 **Jangan sentuh** — brief ini murni CSS/markup, tidak ada perubahan data/logic | — |

---

## Tahap 1 — `card-actions` boleh wrap ke baris kedua di layar sempit

**Tujuan**: baris tombol aksi (di halaman manapun yang pakai `card-actions`) tidak overflow/kepotong di HP sempit — kalau tidak muat satu baris, wrap ke baris berikutnya secara rapi.

Di `resources/css/theme/components.css`, baris 27–29, ubah:

```css
@utility card-actions {
    @apply flex items-center gap-3;
}
```

Menjadi:

```css
@utility card-actions {
    @apply flex flex-wrap items-center gap-3;
}
```

> Cuma menambah `flex-wrap`. `gap-3` yang sudah ada otomatis dipakai juga sebagai jarak antar-baris saat wrap terjadi — tidak perlu tambahan apa pun. Di layar yang cukup lebar (desktop, atau HP dengan sedikit tombol), tidak ada perubahan visual sama sekali karena wrap cuma aktif kalau kontennya memang tidak muat.

**✅ Cek dulu**

1. Jalankan `npm run dev` (atau pastikan asset ter-build), buka `/academies/{id}` di browser.
2. Buka DevTools → Toggle device toolbar → set lebar **375px** (atau device preset "iPhone SE"). Baris tombol "Kembali" / "Ubah Profile" / dropdown akun harus **wrap ke baris kedua tanpa terpotong dan tanpa scroll horizontal** — bukan menyempit atau tenggelam di luar card.
3. Kembalikan lebar ke ≥1024px (desktop) — pastikan tampilan baris tombol **sama seperti sebelum perubahan** (satu baris, tidak ada regresi).
4. Spot-check 2 halaman lain yang juga pakai `card-actions` dengan beberapa tombol, misal `/players/{id}` (halaman detail Player) — pastikan tidak ada regresi visual di lebar desktop maupun mobile.

---

## Tahap 2 — Prioritaskan info penting di mobile (reorder visual, bukan DOM)

**Tujuan**: di layar di bawah `lg` (1024px), card "Informasi Ringkas" dan "Informasi Langganan" tampil **duluan**, sebelum "Deskripsi Academy" dan "Alamat Lengkap". Di layar `lg` ke atas, tampilan tetap seperti sekarang (2 kolom kiri-kanan, tidak berubah).

Di `resources/views/academies/show.blade.php`, cari baris berikut (kolom kiri/main content):

```blade
            <div class="space-y-6 lg:col-span-2">
```

Ubah menjadi:

```blade
            <div class="order-2 space-y-6 lg:order-0 lg:col-span-2">
```

Lalu cari baris berikut (kolom kanan/sidebar, tepat di bawah `</div>` penutup kolom kiri):

```blade
            <div class="space-y-6">
```

Ubah menjadi:

```blade
            <div class="order-1 space-y-6 lg:order-0">
```

> **Kenapa `order-*`, bukan pindah blok HTML**: lihat [3.2](#32-kenapa-order--bukan-pindah-posisi-div-di-html). Isi kedua div — semua card di dalamnya (Deskripsi Academy, Alamat Lengkap, Informasi Ringkas, Informasi Langganan, Informasi Sistem) — **tidak ada satu pun yang perlu diubah/dipindah**. Cukup 2 baris `class` di atas.

**✅ Cek dulu**

1. Di DevTools, set lebar **375px** (mobile) — urutan card dari atas ke bawah harus: **Informasi Ringkas → Informasi Langganan → Informasi Sistem → Deskripsi Academy → Alamat Lengkap**.
2. Set lebar **768px** (tablet portrait, masih di bawah `lg`=1024px) — urutan harus **sama** seperti mobile (satu kolom, sidebar duluan).
3. Set lebar **≥1024px** (desktop/tablet landscape besar) — layout harus **kembali seperti semula**: 2 kolom berdampingan, kolom kiri (Deskripsi & Alamat, lebih lebar `lg:col-span-2`) di kiri, kolom kanan (Informasi Ringkas/Langganan/Sistem) di kanan — **posisi kiri-kanan tidak boleh ikut kebalik**, cuma urutan vertikal di mobile yang berubah.
4. Tab lewat keyboard (`Tab` key) dari tombol "Ubah Profile" — pastikan fokus berikutnya jatuh ke link email (`mailto:...` di "Informasi Ringkas") dengan wajar, tidak error/hilang fokus. (Ini cuma sanity check ringan — `order-*` di kasus ini aman karena blok "Deskripsi Academy"/"Alamat Lengkap" tidak berisi elemen interaktif apa pun, jadi urutan tab praktis tidak berubah signifikan.)

---

## 3. Alasan Teknis (boleh dibaca belakangan, tapi keputusan di sini tidak boleh dilanggar)

### 3.1 Kenapa fix `card-actions` di `components.css`, bukan cuma di `academies/show.blade.php`

`card-actions` dipakai di 28 file Blade lain di seluruh module (players, roles, permissions, player-types, dst — cek sendiri: `grep -rl "card-actions" resources/views`). Risiko overflow yang ditemukan di `academies/show.blade.php` (3 tombol dalam satu baris tanpa wrap) bukan masalah spesifik file itu — itu sifat dari utility `card-actions` itu sendiri. Menambal cuma satu file berarti halaman lain dengan jumlah tombol serupa (`players/show.blade.php` juga punya 2 tombol + 1 dropdown) tetap punya bug yang sama, cuma belum ditemukan. Fix di satu tempat (`components.css`) otomatis merapikan semua halaman sekaligus — konsisten dengan prinsip *Kapan Membuat @utility Baru* di `docs/frontend-standard.md`.

### 3.2 Kenapa `order-*`, bukan pindah posisi div di HTML

Dua alternatif yang sama-sama bisa membuat sidebar tampil duluan di mobile:

1. **Pindahkan blok HTML** kolom kanan (sidebar) supaya secara fisik ditulis sebelum kolom kiri di file Blade, lalu balik lagi urutannya pakai CSS untuk layar besar.
2. **Tetap DOM order asli**, cuma tambah class utility `order-1`/`order-2` (mobile) dan `lg:order-0` (reset ke urutan alami di layar besar).

Opsi 2 yang dipakai brief ini, karena:
- **Diff minimal & aman** — cuma 2 baris `class` yang berubah, bukan memotong-tempel ratusan baris markup (5 card, termasuk `@php` block perhitungan badge subscription) yang rawan salah taruh kurung/`@endphp`.
- **Urutan baca/tab-order tetap mengikuti struktur logis aslinya** (info utama dulu secara konseptual: identitas → detail → data sekunder), CSS `order` cuma mengubah urutan **visual**, bukan urutan DOM — jadi screen reader/tab masih membaca dengan urutan yang predictable, bukan ikut lompat-lompat visual.

### 3.3 Kenapa scope `order-*` cuma `academies/show.blade.php`

`roles/show.blade.php` dan `permissions/show.blade.php` pakai grid 2 kolom yang sama, dan `players/show.blade.php` juga punya pola sidebar serupa. Tapi brief ini murni berangkat dari review khusus `academies/show.blade.php` — konten apa yang "paling prioritas dilihat duluan di mobile" itu keputusan per-halaman (untuk Player mungkin fisik/posisi yang lebih prioritas daripada catatan, misalnya), bukan sesuatu yang boleh digeneralisasi otomatis ke semua halaman detail tanpa direview satu-satu. Kalau pola ini mau diterapkan ke halaman lain, itu keputusan terpisah — diskusikan dulu dengan user (lihat Aturan Utama `CLAUDE.md`), jangan diperluas sepihak di brief ini.
