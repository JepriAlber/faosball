# Frontend Standard

## Overview

Dokumen ini menjelaskan standar penulisan CSS/Tailwind dan Blade view pada FAOSBall. Berbeda dengan dokumen lain yang fokus ke backend, dokumen ini fokus ke konvensi frontend: struktur file CSS, kapan menulis Tailwind inline vs kapan membuat class reusable, dan jebakan teknis yang pernah terjadi.

---

## Table of Contents

- [Struktur File CSS](#struktur-file-css)
- [Tailwind CSS v4 (CSS-first)](#tailwind-css-v4-css-first)
- [Kapan Membuat @utility Baru](#kapan-membuat-utility-baru)
- [Gotcha: Varian Breakpoint vs Toggle Dinamis](#gotcha-varian-breakpoint-vs-toggle-dinamis)
- [Konsistensi Warna & Token](#konsistensi-warna--token)
- [Development Rules](#development-rules)
- [Summary](#summary)

---

## Struktur File CSS

```text
resources/css/
├── app.css              # entry point, import semuanya
└── theme/
    ├── variables.css     # @theme - design token (warna, breakpoint, shadow, z-index)
    ├── base.css          # reset elemen dasar (body, heading, input, dll)
    ├── utilities.css     # @utility - komponen sidebar/menu, scrollbar
    ├── components.css    # @utility - card, btn, form, badge, table, modal,
    │                     #            avatar, dropdown, header, layout
    └── vendor.css        # override style third-party (ApexCharts, Flatpickr, dll)
```

`app.css` meng-import kelimanya secara berurutan (`variables → base → utilities → components → vendor`). Urutan ini penting: `variables.css` mendefinisikan token yang dipakai semua file setelahnya.

---

## Tailwind CSS v4 (CSS-first)

FAOSBall pakai Tailwind v4 dengan config berbasis CSS (bukan `tailwind.config.js`).

- `@theme { ... }` di `variables.css` — definisi token (`--color-brand-500`, `--breakpoint-xsm`, `--z-index-9999`, dst). Tailwind otomatis membuat utility class dari token ini (`--color-brand-500` → class `bg-brand-500`/`text-brand-500`/dst).
- `@utility nama-class { @apply ...; }` — cara resmi Tailwind v4 untuk bikin custom utility yang tetap ikut sistem variant (`dark:`, `hover:`, `lg:`, dst), dipakai di `utilities.css` dan `components.css`.

Sebelum menambah `@theme` token baru, cek dulu `variables.css` — kemungkinan besar warna/shadow/spacing yang dibutuhkan sudah ada.

---

## Kapan Membuat @utility Baru

Gunakan aturan berikut saat menulis Blade view:

1. **String Tailwind yang berulang identik di 2+ tempat** (mis. class card-header yang sama persis di beberapa file) → wajib jadi `@utility` baru di `components.css`/`utilities.css`.
2. **String Tailwind yang panjang tapi cuma dipakai sekali** → tetap sebaiknya diberi nama (`@utility`) kalau lebih dari ~4-5 token, supaya blade tidak penuh soup class. Contoh: `.sidebar-header { @apply flex items-center gap-2 pt-8 pb-7; }`.
3. **Toggle dinamis Alpine yang sepele** (mis. `:class="open ? 'rotate-180' : ''"`, `:class="sidebarToggle ? 'lg:hidden' : ''"`) → **biarkan inline**, tidak perlu dibuatkan class baru. Ini bukan soup, ini state yang memang harus reaktif.
4. Class baru untuk pola sidebar/menu/dropdown-navigasi → `utilities.css`. Class baru untuk komponen UI umum (card, button, badge, avatar, header, layout) → `components.css`.

---

## Gotcha: Varian Breakpoint vs Toggle Dinamis

**Aturan wajib**: kalau sebuah elemen sudah di-toggle dinamis oleh Alpine memakai class Tailwind polos untuk properti tertentu (paling sering `display`, lewat `hidden`/`flex`/`block`), maka **varian breakpoint (`lg:`, `md:`, dst) atau `dark:` untuk properti yang sama tidak boleh dibungkus ke dalam custom `@utility`** — harus tetap ditulis sebagai class Tailwind langsung di Blade.

**Kenapa**: Tailwind menjamin urutan compile yang benar antar variant HANYA untuk utility yang dipakai langsung (ke-scan dari isi file lewat `@source`). Kalau sebuah variant (mis. `lg:flex`) di-`@apply` di dalam custom utility, Tailwind v4 menaruh rule tersebut pada posisi yang tidak dijamin lebih akhir dibanding rule polos (`hidden`/`flex`) yang dipakai terpisah di elemen yang sama. Kalau posisinya salah, class yang seharusnya menang (misalnya `lg:flex` di desktop) bisa kalah oleh `.hidden` yang muncul belakangan di file CSS hasil build — padahal secara logika harusnya menang.

**Contoh nyata (pernah kejadian)**: `app-header-actionbar` awalnya di-`@apply` termasuk `lg:flex lg:justify-end lg:px-0 lg:shadow-none`, padahal elemen yang sama di-toggle `:class="menuToggle ? 'flex' : 'hidden'"`. Akibatnya di desktop, action bar (dark mode/notifikasi/user) hilang total saat halaman pertama dimuat, karena `.hidden` menang cascade. Perbaikannya: keluarkan `lg:flex lg:justify-end lg:px-0 lg:shadow-none` dari `@utility`, taruh sebagai class Tailwind langsung berdampingan dengan `:class` binding-nya.

Contoh yang benar (lihat `resources/css/theme/components.css`, komentar di atas `.app-header-actionbar` dan `.sidebar-overlay`):

```php
<div :class="menuToggle ? 'flex' : 'hidden'"
    class="app-header-actionbar lg:flex lg:justify-end lg:px-0 lg:shadow-none">
```

Sebelum membuat `@utility` baru, selalu cek: apakah elemen ini juga punya `:class` dinamis dari Alpine yang menyentuh properti CSS yang sama (`display`, `justify-content`, dst)? Kalau ya, jangan bungkus varian tersebut ke `@utility`.

---

## Konsistensi Warna & Token

- Jangan hardcode nilai warna/shadow/z-index kalau token-nya sudah ada di `variables.css` (mis. pakai `z-9` bukan `z-[9]`, pakai `--color-gray-700` bukan `#344054`).
- Kalau butuh nilai arbitrary yang genuinely spesifik untuk satu komponen (mis. lebar sidebar `w-[290px]`, tinggi panel notifikasi `h-[480px]`), itu wajar dan tidak perlu dipaksakan jadi token baru — bukan semua angka harus jadi design token, hanya yang benar-benar dipakai berulang lintas komponen.

---

## Development Rules

Gunakan:

- `@utility` untuk pola yang berulang atau string Tailwind panjang.
- Token dari `variables.css` daripada hardcode warna/shadow/z-index.
- Cek dulu apakah class yang dibutuhkan sudah ada sebelum bikin baru (terutama di `components.css` - card/btn/badge/form/table/modal/avatar/dropdown sudah lengkap).

Hindari:

- Membungkus varian breakpoint/dark ke `@utility` untuk properti yang juga di-toggle dinamis lewat class Tailwind polos di elemen yang sama (lihat [Gotcha](#gotcha-varian-breakpoint-vs-toggle-dinamis)).
- Hardcode warna/shadow yang sebenarnya sudah ada tokennya.
- Membuat `@utility` baru untuk toggle dinamis yang sepele (single property, dipakai sekali).

---

## Summary

FAOSBall menggunakan Tailwind CSS v4 dengan pendekatan CSS-first (`@theme`, `@utility`). String Tailwind yang berulang atau panjang diekstrak jadi `@utility` reusable di `utilities.css` (pola sidebar/menu) atau `components.css` (komponen UI umum), sedangkan toggle dinamis Alpine yang sepele dibiarkan inline. Yang paling penting: variant breakpoint/dark untuk properti yang juga dikendalikan toggle dinamis di elemen yang sama wajib tetap jadi class Tailwind langsung, tidak boleh dibungkus ke `@utility`, karena urutan compile-nya tidak dijamin benar.
