# Brief: Tombol "Jadikan Kapten" + Modal Konfirmasi "Keluarkan" (ganti native `confirm()`)

> **Untuk**: Programmer junior / AI agent
> **Prasyarat**: Module Team/Team Player (`issue16.md`), rebuild `teams/show.blade.php` (`issue19.md` Tahap 9) **wajib sudah selesai**. Baca `docs/frontend-standard.md` bagian *Reusable View dengan Data Dinamis* dan `docs/permission-reference.md` bagian *Module: Team (+ Team Player, Team Staff)*. Modul referensi paling mirip: `resources/views/components/button/delete.blade.php` + `resources/views/components/modal/delete.blade.php` + `resources/js/components/delete-modal.js` (pola dasar modal konfirmasi Alpine yang DIBACA ULANG di brief ini), `resources/views/components/modal/status.blade.php` (referensi modal non-destruktif dengan method `PATCH`).
> **Bukan module baru** — ini 2 perbaikan kecil di halaman yang sudah ada (`teams/show.blade.php`), dipicu 2 temuan saat user tanya soal fitur captain. **Cara pakai brief ini**: Kerjakan Tahap 1 → 7 berurutan. Tiap tahap ada blok **✅ Cek dulu** — jangan lanjut sebelum blok itu lulus.
> **Scope**: (a) Tambah tombol "Jadikan Kapten" di kolom Aksi roster Player (tabel & card) di `teams/show.blade.php` — reuse endpoint `teams.players.update` yang **sudah ada** sejak `issue16.md` tapi belum pernah dipanggil UI manapun; (b) Ganti 4 titik tombol "Keluarkan" (Player tabel/card, Staff tabel/card) yang masih pakai native `onsubmit="return confirm(...)"` dengan modal konfirmasi Alpine baru (`leaveTeamModal`), pola sama seperti modal Hapus/Status/Reset Password yang sudah baku di 12+ halaman index lain. **Bukan scope**: bikin endpoint/Service baru, ubah `TeamPlayerService`/`TeamPlayerController` (sudah benar & cukup, lihat Bagian 1), ubah tombol "Keluarkan" di module lain (cuma ada di `teams/show.blade.php`), edit nomor punggung (field lain di endpoint yang sama, tidak disentuh).

---

> **Addendum (pasca-implementasi Tahap 1-7)**: Keputusan awal di Bagian 2c (bawah) dan Aturan Emas baris "Kasih modal konfirmasi juga ke tombol Jadikan Kapten" — **direvisi** atas permintaan user langsung setelah Tahap 1-7 selesai & di-review di browser: "Jadikan Kapten" sekarang **juga** pakai modal konfirmasi (varian ke-6, `makeCaptainModal`), pola sama persis `leaveTeamModal` tapi method `PUT` + bawa parameter tambahan `jerseyNumber`. Lihat **Tahap 8** untuk detail implementasinya. Bagian 2c dibiarkan apa adanya di bawah (bukan dihapus) supaya jejak alasan awal tetap kebaca — jangan bingung kenapa isinya kontradiktif dengan kode final, itu memang keputusan yang berubah.

---

## Progress Implementasi

> Dicentang begitu tahap selesai dikerjakan **dan** lolos blok ✅ Cek dulu masing-masing. Update checklist ini tiap kali sebuah tahap selesai.

- [x] Tahap 1 — Alpine helper `leaveTeamModal` (`resources/js/components/leave-team-modal.js`) + registrasi di `app.js` (`npm run build` sukses)
- [x] Tahap 2 — Komponen `<x-modal.leave-team>` + `<x-button.leave-team>` (compile check OK)
- [x] Tahap 3 — Wire ke `teams/show.blade.php`: ganti 4 titik native `confirm()` jadi `<x-button.leave-team>` (compile + build OK, grep `onsubmit="return confirm` bersih)
- [x] Tahap 4 — Tombol "Jadikan Kapten" di kolom Aksi roster Player (tabel & card) (compile + build OK)
- [x] Tahap 5 — Multi-Language (`lang/en.json`) (valid JSON, 2 key basi dihapus setelah grep bersih)
- [x] Tahap 6 — Dokumentasi (`docs/frontend-standard.md` section baru + TOC + Development Rules, `docs/permission-reference.md` catatan reuse `team.update`)
- [x] Tahap 7 — Tests (2 test baru di `TeamTest.php`, `php artisan test --filter=TeamTest` 12/12 lulus; full suite 187 test/180 passed, baseline 5 failure + 2 error Breeze tidak bertambah)
- [x] Tahap 8 — (Addendum) Modal konfirmasi `makeCaptainModal` untuk "Jadikan Kapten" (revisi keputusan Bagian 2c) — 1 test tambahan, `TeamTest` 13/13 lulus

---

## 0. Aturan Emas

| ❌ Jangan | Kenapa singkatnya | Detail |
|----------|-------------------|--------|
| Reuse `<x-modal.delete>`/`<x-button.delete>` apa adanya untuk "Keluarkan" | `modal/delete.blade.php` hardcode `@method('DELETE')` dan teks "Hapus Permanen" — aksi Keluarkan pakai method **`PATCH`** (`teams.players.leave`/`teams.staff.leave`) dan **bukan** hapus permanen (baris tetap ada, `leave_date` cuma diisi — histori roster tetap utuh). Reuse tanpa modifikasi = method salah / pesan yang menakut-nakuti user seolah data hilang | [Bagian 2a](#2a-kenapa-varian-modal-baru-bukan-reuse-delete), [Tahap 1-2](#tahap-1--alpine-helper-leaveteammodal) |
| Bikin endpoint/route/Service baru untuk "Jadikan Kapten" | `teams.players.update` (`TeamPlayerController::update()` → `TeamPlayerService::update()`) **sudah bisa** terima `is_captain` sejak `issue16.md`, lengkap dengan logic auto-unset captain lama (`setCaptain()`). Yang belum ada cuma UI-nya. Endpoint ini **wajib** juga dikirim `jersey_number` (required di `UpdateTeamPlayerRequest`) — kirim value existing player itu lewat hidden input, **jangan** bikin endpoint captain-only baru | [Bagian 2b](#2b-jadikan-kapten-reuse-endpoint-lama-bukan-bikin-baru), [Tahap 4](#tahap-4--tombol-jadikan-kapten-di-kolom-aksi-player) |
| Pakai class `modal-icon-success` / `modal-icon-warning` di komponen modal baru | Class ini dipakai di `modal/status.blade.php` & `modal/reset-password.blade.php` tapi **tidak pernah didefinisikan** di `resources/css/theme/components.css` (cuma `modal-icon` & `modal-icon-danger` yang benar-benar ada) — bug lama, **di luar scope** brief ini untuk diperbaiki. Modal baru brief ini pakai `modal-icon-danger` yang sudah pasti bergaya | [Tahap 2](#tahap-2--komponen-x-modalleave-team--x-buttonleave-team) |
| ~~Kasih modal konfirmasi juga ke tombol "Jadikan Kapten"~~ **(DIREVISI, lihat Addendum di atas)** | Keputusan awal: aksi ini reversible & idempotent jadi tidak perlu jeda konfirmasi. **Direvisi** setelah Tahap 1-7 di-review — user minta modal konfirmasi juga, konsisten dengan "Keluarkan" | [Bagian 2c](#2c-kenapa-jadikan-kapten-tidak-pakai-modal-konfirmasi-keputusan-awal-direvisi-lihat-tahap-8), [Tahap 8](#tahap-8--addendum-modal-konfirmasi-untuk-jadikan-kapten) |
| Hapus key `Keluarkan player ini dari tim?` / `Keluarkan staff ini dari tim?` dari `lang/en.json` tanpa grep ulang | Setelah Tahap 3, 2 string lama ini jadi orphan (diganti teks modal baru) — **wajib** grep dulu pastikan tidak dipakai di file lain sebelum dihapus, baru hapus di Tahap 5 | [Tahap 5](#tahap-5--multi-language) |
| Bikin notifikasi sukses/gagal sendiri (native `alert()`, toast custom, `console.log`, dsb) untuk aksi Keluarkan/Jadikan Kapten | `teams/show.blade.php:8` **sudah** punya `<x-alert />` (komponen class-based `app/View/Components/Alert.php`, baca session flash `success`/`error`) — `TeamPlayerController::update()`/`leave()` **sudah** `redirect()->with('success', ...)` / `handleException()` yang otomatis flash `error`. Brief ini **tidak menambah** controller/redirect baru sama sekali (Tahap 3 & 4 cuma ganti markup tombol + reuse endpoint lama), jadi alert sukses/gagal **otomatis** ikut standar tanpa kerja tambahan — jangan tergoda menambah notifikasi manual apapun | [Tahap 3](#tahap-3--wire-ke-teamsshowbladephp-ganti-native-confirm), [Tahap 4](#tahap-4--tombol-jadikan-kapten-di-kolom-aksi-player) |

---

## 1. Konteks & Tujuan

Ditemukan saat user bertanya "dimana bisa edit captain pemain di Team?" dan komplain tombol "Keluarkan" masih pakai `confirm()` bawaan browser yang tidak konsisten dengan pola alert project ini:

1. **Field & backend `is_captain` sudah lengkap, tapi tidak ada UI-nya sama sekali.** `team_players.is_captain` (migration `issue16.md`), `TeamPlayerService::setCaptain()` (auto-unset captain lama dalam 1 transaksi), `UpdateTeamPlayerRequest` (validasi `is_captain`), dan `TeamPlayerController::update()` (route `PUT teams/{team}/players/{teamPlayer}`, name `teams.players.update`, tergerbang `permission:team.update`) semuanya **sudah ada dan benar** — tapi `teams/show.blade.php` cuma **menampilkan** badge "Captain", tidak pernah memanggil route ini. Endpoint ini "nganggur" dari sisi UI.
2. **Tombol "Keluarkan" pakai native `confirm()`.** Ada di 4 titik: baris tabel Player, card mobile Player, baris tabel Staff, card mobile Staff — semua `onsubmit="return confirm('...')"`. Project ini **sudah punya** pola modal konfirmasi Alpine baku (`$dispatch` event → `x-data` modal listen `@event.window` → tampilkan modal → submit form), dipakai konsisten di 13 halaman index lewat `<x-button.delete>`/`<x-modal.delete>`, plus 2 varian non-destruktif lain (`status`, `reset-password`) untuk kasus method `PATCH`. Tombol "Keluarkan" **tidak** ikut pola ini.

## 2. Cara Kerja Solusi

### 2a. Kenapa varian modal baru, bukan reuse `delete`

`modal/delete.blade.php` didesain spesifik untuk hapus permanen: `@method('DELETE')` hardcode, judul "Konfirmasi Hapus", teks "Tindakan ini tidak dapat dibatalkan" + "akan dihapus secara permanen", tombol "Hapus Permanen". Aksi "Keluarkan" beda total secara semantik — method `PATCH`, data **tidak** hilang (`leave_date` cuma diisi, riwayat roster tetap kebaca). Preseden yang benar untuk kasus "method PATCH, bukan delete permanen" adalah `modal/status.blade.php` dan `modal/reset-password.blade.php` — brief ini bikin **varian ke-3** dengan pola arsitektur identik (state `show`/`action`/`name`, method `open()`/`close()`, `Alpine.data()` registration di `app.js`), bukan pola baru dari nol.

### 2b. "Jadikan Kapten": reuse endpoint lama, bukan bikin baru

`UpdateTeamPlayerRequest::rules()` mewajibkan `jersey_number` (`required|integer|min:1|max:99`) di setiap request ke `teams.players.update`, walau yang mau diubah cuma `is_captain`. Solusinya **bukan** melonggarkan validasi atau bikin endpoint captain-only baru — cukup kirim `jersey_number` milik `$teamPlayer` itu sendiri lewat `<input type="hidden">` di form kecil per baris. Karena `TeamPlayerService::update()` cuma memicu `assertJerseyAvailable()` kalau nilainya **berubah** (`(int) $data['jersey_number'] !== $teamPlayer->jersey_number`), mengirim nilai yang sama selalu lolos tanpa efek samping ke nomor punggung.

### 2c. Kenapa "Jadikan Kapten" tidak pakai modal konfirmasi (keputusan awal, DIREVISI — lihat Tahap 8)

Beda karakter dari "Keluarkan" (mengeluarkan orang dari tim, perlu jeda mikir) — "Jadikan Kapten" cuma memindahkan status ke pemain lain di tim yang sama, dampaknya kecil dan gampang dibalik (klik pemain lain = captain pindah lagi, `setCaptain()` di `TeamPlayerService.php:129-136` sudah menjamin cuma 1 captain aktif per tim). Sesuai arahan user: klik langsung submit, tanpa modal.

---

## 3. Peta Perubahan File

| File | Aksi | Tahap |
|------|------|-------|
| `resources/js/components/leave-team-modal.js`, `resources/js/app.js` | 🆕/✏️ | 1 |
| `resources/views/components/modal/leave-team.blade.php`, `resources/views/components/button/leave-team.blade.php` | 🆕 | 2 |
| `resources/views/teams/show.blade.php` | ✏️ | 3 |
| `resources/views/teams/show.blade.php` | ✏️ | 4 |
| `lang/en.json` | ✏️ | 5 |
| `docs/frontend-standard.md`, `docs/permission-reference.md` | ✏️ | 6 |
| `tests/Feature/TeamTest.php` | ✏️ | 7 |
| `resources/js/components/make-captain-modal.js`, `resources/js/app.js`, `resources/css/theme/components.css`, `resources/views/components/modal/make-captain.blade.php`, `resources/views/components/button/make-captain.blade.php`, `resources/views/teams/show.blade.php`, `lang/en.json`, `docs/frontend-standard.md`, `tests/Feature/TeamTest.php` | 🆕/✏️ | 8 (addendum) |

---

## Tahap 1 — Alpine helper `leaveTeamModal`

`resources/js/components/leave-team-modal.js` (🆕) — struktur identik `delete-modal.js`, event & registrasi beda supaya tidak tabrakan dengan modal delete di halaman yang sama:

```js
export default () => ({
    show: false,
    action: '',
    name: '',

    open(action, name) {
        this.action = action;
        this.name = name;
        this.show = true;
    },

    close() {
        this.show = false;
        this.action = '';
        this.name = '';
    }
})
```

`resources/js/app.js` — tambah 1 baris import setelah `import academyCascade from './components/academy-cascade';` (baris 12), dan 1 baris registrasi setelah `Alpine.data('academyCascade', academyCascade);` (baris 24):

```js
import leaveTeamModal from './components/leave-team-modal'
```

```js
Alpine.data('leaveTeamModal', leaveTeamModal)
```

**✅ Cek dulu**: `npm run build` tidak error.

---

## Tahap 2 — Komponen `<x-modal.leave-team>` + `<x-button.leave-team>`

`resources/views/components/modal/leave-team.blade.php` (🆕):

```blade
<div x-data="leaveTeamModal" @leave-team-confirm.window="open($event.detail.action,$event.detail.name)"
    x-show="show" class="modal-overlay flex items-center justify-center p-4" x-transition x-cloak>

    <div class="modal-container modal-md" @click.away="close()" x-transition>

        <div class="modal-header">
            <div class="flex items-center gap-4">
                <span class="modal-icon modal-icon-danger">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </span>

                <div>
                    <h3 class="modal-title">
                        {{ __('Keluarkan dari Tim') }}
                    </h3>

                    <p class="modal-description">
                        {{ __('Riwayat keanggotaan tetap tersimpan.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                {{ __('Apakah Anda yakin ingin mengeluarkan') }}
                <strong class="font-semibold text-gray-800 dark:text-white" x-text="name"></strong>
                {{ __('dari tim ini?') }}
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close()">
                {{ __('Batal') }}
            </button>

            <form :action="action" method="POST">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn btn-danger">
                    {{ __('Keluarkan') }}
                </button>
            </form>
        </div>

    </div>
</div>
```

`resources/views/components/button/leave-team.blade.php` (🆕) — pola sama persis `button/delete.blade.php`, event beda:

```blade
@props(['action', 'name'])

<button type="button"
    @click="$dispatch('leave-team-confirm',{action:'{{ $action }}',name:'{{ addslashes($name) }}'})"
    class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">

    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <path
            d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</button>
```

**✅ Cek dulu**:

```bash
php artisan tinker --execute="Illuminate\Support\Facades\Blade::compileString(file_get_contents(resource_path('views/components/modal/leave-team.blade.php'))); echo 'compiled OK';"
php artisan tinker --execute="Illuminate\Support\Facades\Blade::compileString(file_get_contents(resource_path('views/components/button/leave-team.blade.php'))); echo 'compiled OK';"
```

---

## Tahap 3 — Wire ke `teams/show.blade.php`: ganti native `confirm()`

Ganti **4 titik** (tabel Player ~baris 154-168, card Player ~baris 217-231, tabel Staff ~baris 316-330, card Staff ~baris 373-387). Contoh tabel Player — dari:

```blade
<form action="{{ route('teams.players.leave', [$team, $teamPlayer]) }}" method="POST"
    onsubmit="return confirm('{{ __('Keluarkan player ini dari tim?') }}')" class="inline">
    @csrf @method('PATCH')
    <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
</form>
```

jadi:

```blade
<x-button.leave-team :action="route('teams.players.leave', [$team, $teamPlayer])"
    :name="$teamPlayer->player->name" />
```

Untuk Staff, `:name="$ts->staff->full_name"` dan `:action="route('teams.staff.leave', [$team, $ts])"`. Card mobile identik (cuma versi `<div class="table-card-actions">`, bukan `<td>`).

Tambah `<x-modal.leave-team />` **sekali** di akhir file, sebelum `@endsection` (pola sama `<x-modal.delete />` di `teams/index.blade.php:320`):

```blade
    </div>

    <x-modal.leave-team />

@endsection
```

**✅ Cek dulu**: `npm run build` sukses. Manual: buka `/teams/{team}`, klik "Keluarkan" di baris Player — modal Alpine muncul (bukan popup browser bawaan), nama player tampil di teks konfirmasi, tombol "Batal" menutup modal tanpa submit, tombol "Keluarkan" submit form `PATCH` dan player pindah status jadi "Keluar". Ulangi untuk card mobile & tab Staff (tabel + card).

---

## Tahap 4 — Tombol "Jadikan Kapten" di kolom Aksi Player

Di kolom Aksi tabel Player (dalam blok `@can('team.update') @if ($teamPlayer->isActive())`, **sebelum** `<x-button.leave-team>`), tambah — cuma tampil kalau player ini **belum** captain:

```blade
@if (! $teamPlayer->is_captain)
    <form action="{{ route('teams.players.update', [$team, $teamPlayer]) }}" method="POST" class="inline">
        @csrf @method('PUT')
        <input type="hidden" name="jersey_number" value="{{ $teamPlayer->jersey_number }}">
        <input type="hidden" name="is_captain" value="1">
        <button type="submit" class="btn-icon btn-icon-primary" title="{{ __('Jadikan Kapten') }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
    </form>
@endif
```

**Catatan icon**: path di atas adalah icon "star" resmi dari Lucide (dipakai apa adanya, viewBox native `0 0 24 24` — **jangan** di-scale manual ke `0 0 20 20`, cukup ubah `width`/`height` attribute-nya seperti contoh, viewBox yang urus penyesuaian skalanya). Ini supaya bentuknya dijamin simetris tanpa perlu hitung ulang koordinat.

Ulangi identik di card mobile Player (`table-card-actions`), **sebelum** `<x-button.leave-team>` di situ juga. Class `btn-icon-primary` sudah ada di `resources/css/theme/components.css:85`.

**Catatan**: jangan tambahkan tombol ini untuk player yang **sudah** captain (kolom "Captain" sudah menampilkan badge-nya) atau player yang `! isActive()` (di luar blok `@if ($teamPlayer->isActive())` yang sudah ada, jadi otomatis tidak tampil).

**✅ Cek dulu**: `npm run build` sukses. Manual: buka tab Player, pastikan icon "Jadikan Kapten" tampil sebagai bentuk bintang yang simetris (bukan bentuk aneh/pecah — kalau iya, path SVG-nya ke-copy salah, cek ulang karakter per karakter). Di tab Player yang punya ≥ 2 anggota aktif, klik "Jadikan Kapten" di player B (padahal player A captain sekarang) — setelah redirect, badge "Captain" pindah dari A ke B, flash message "Nomor punggung/captain berhasil diperbarui." muncul lewat `<x-alert/>` (bukan notifikasi lain), nomor punggung A/B **tidak berubah**. Player yang sudah captain tidak lagi menampilkan tombol ini.

---

## Tahap 5 — Multi-Language

`lang/en.json` — tambah string baru dari Tahap 2 & 4:

```json
"Keluarkan dari Tim": "Remove from Team",
"Riwayat keanggotaan tetap tersimpan.": "Membership history is kept.",
"Apakah Anda yakin ingin mengeluarkan": "Are you sure you want to remove",
"dari tim ini?": "from this team?",
"Jadikan Kapten": "Make Captain",
```

Sebelum hapus 2 key basi, grep dulu pastikan tidak dipakai di file lain:

```bash
grep -rn "Keluarkan player ini dari tim\|Keluarkan staff ini dari tim" resources/ app/
```

Kalau grep bersih (cuma bekas Tahap 3 yang sudah diganti), hapus 2 key ini dari `lang/en.json`:

```json
"Keluarkan player ini dari tim?": "Remove this player from the team?",
"Keluarkan staff ini dari tim?": "Remove this staff member from the team?",
```

**✅ Cek dulu**: `php artisan tinker --execute="json_decode(file_get_contents(base_path('lang/en.json')), true); echo 'valid JSON';"` — pastikan tidak ada trailing comma / duplikat key.

---

## Tahap 6 — Dokumentasi

`docs/frontend-standard.md` — tambah section baru **"Modal Konfirmasi (Pola Alpine Dispatch)"**, ditaruh **sebelum** section `Development Rules` (baris ~26 di Table of Contents saat ini, urutan sama seperti section-section lain yang ditambah belakangan seperti *Cascading Dropdown Academy-Scoped* dan *`table-card` Tanpa `table-card-list`*) — tambahkan juga link-nya di Table of Contents. Isi section-nya dokumentasikan pola yang selama ini cuma hidup di kode (delete/status/reset-password/logout + varian baru leave-team brief ini): kapan pakai modal Alpine vs kapan native `confirm()`/`alert()` **tidak boleh** dipakai untuk aksi apapun yang mengubah data atau butuh feedback ke user, cara kerja `$dispatch(eventName, {action, name, ...})` → `x-data` modal listen `@eventName.window="open(...)"` → submit form dengan method HTTP sesuai kebutuhan (`DELETE` untuk hapus permanen, `PATCH` untuk aksi non-destruktif), dan daftar varian yang sudah ada (`deleteModal`, `statusModal`, `resetAkunModal`, `logoutModal`, `leaveTeamModal`) supaya developer berikutnya reuse/extend, bukan bikin `confirm()` baru. Sebut juga bahwa notifikasi hasil aksi (sukses/gagal) **selalu** lewat `<x-alert />` (flash session), bukan `alert()`/toast custom — lihat Aturan Emas brief ini.

`docs/permission-reference.md` — di section **Module: Team (+ Team Player, Team Staff)**, tambah catatan: aksi "Jadikan Kapten" (`teams.players.update`) reuse `team.update`, bukan permission baru — endpoint ini sudah tergerbang sejak `issue16.md`, brief ini cuma menyambungkan UI-nya.

**✅ Cek dulu**: baca ulang section baru, pastikan konsisten format dengan section lain (heading level, gaya bahasa).

---

## Tahap 7 — Tests

`tests/Feature/TeamTest.php` — tambah 2 test baru **persis** di dalam `class TeamTest` yang sudah ada, **pakai helper yang sudah ada** di file ini (`actingAsOwner()`, `makeTeam()`, `makePlayer()` — lihat baris 33-86) — **jangan** bikin variabel/helper baru:

1. **HTTP request `teams.players.update` set captain** (selama ini cuma dites lewat Service langsung di `test_set_captain_baru_otomatis_melepas_captain_lama`, belum pernah lewat HTTP/route penuh — jadi belum ketahuan kalau ternyata ada yang salah di sisi Controller/Request/permission gate):

```php
public function test_http_set_captain_lewat_form_jadikan_kapten(): void
{
    $academy = Academy::factory()->create();
    $team = $this->makeTeam($academy);
    $playerA = $this->makePlayer($academy, 'Player A');
    $playerB = $this->makePlayer($academy, 'Player B');

    $service = app(TeamPlayerService::class);
    $tpA = $service->assign($team, ['id_player' => $playerA->id_player, 'jersey_number' => 10, 'is_captain' => true]);
    $tpB = $service->assign($team, ['id_player' => $playerB->id_player, 'jersey_number' => 7]);

    $this->actingAsOwner($academy);

    $response = $this->put(route('teams.players.update', [$team, $tpB]), [
        'jersey_number' => $tpB->jersey_number,
        'is_captain' => 1,
    ]);

    $response->assertRedirect(route('teams.show', $team));
    $this->assertTrue($tpB->fresh()->is_captain);
    $this->assertFalse($tpA->fresh()->is_captain);
}
```

2. **Halaman show tidak lagi mengandung `onsubmit="return confirm`** (guard regresi supaya tidak balik ke native confirm tanpa sadar):

```php
public function test_halaman_show_team_tidak_pakai_native_confirm_lagi(): void
{
    $academy = Academy::factory()->create();
    $team = $this->makeTeam($academy);
    $player = $this->makePlayer($academy);

    app(TeamPlayerService::class)->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);

    $this->actingAsOwner($academy);

    $response = $this->get(route('teams.show', $team));

    $response->assertOk();
    $response->assertDontSee('onsubmit="return confirm', false);
    $response->assertSee('leave-team-confirm', false);
}
```

Catatan: `actingAsOwner($academy)` **sudah** memanggil `$this->actingAs($owner)` di dalamnya (baris 44) — jangan panggil `actingAs()` lagi setelahnya, langsung lanjut `$this->put(...)`/`$this->get(...)`, pola sama seperti `test_halaman_show_team_memuat_table_card_list_untuk_responsif` yang sudah ada.

**✅ Cek dulu**:

```bash
php artisan test --filter=TeamTest
```

Semua test lulus, tidak ada regresi di baseline suite (`php artisan test` penuh — jumlah failure/error tidak bertambah dari baseline sebelum brief ini).

---

## Tahap 8 — (Addendum) Modal Konfirmasi untuk "Jadikan Kapten"

Revisi Bagian 2c/Aturan Emas (lihat Addendum di atas). Pola arsitekturnya **identik** `leaveTeamModal` (Tahap 1-2), bedanya: method `PUT` bukan `PATCH`, dan modal butuh 1 parameter tambahan (`jerseyNumber`) karena endpoint `teams.players.update` mewajibkan `jersey_number` di setiap request (Bagian 2b).

`resources/js/components/make-captain-modal.js` (🆕) — state tambahan `jerseyNumber` dibanding `leaveTeamModal`:

```js
export default () => ({
    show: false,
    action: '',
    name: '',
    jerseyNumber: '',

    open(action, name, jerseyNumber) {
        this.action = action;
        this.name = name;
        this.jerseyNumber = jerseyNumber;
        this.show = true;
    },

    close() {
        this.show = false;
        this.action = '';
        this.name = '';
        this.jerseyNumber = '';
    }
})
```

Registrasi di `app.js` — pola sama `leaveTeamModal`.

`resources/css/theme/components.css` — tambah utility **`modal-icon-primary`** (setelah `modal-icon-danger`) karena "Jadikan Kapten" bukan aksi destruktif, tidak cocok warna merah `modal-icon-danger`. **Bukan** `modal-icon-success`/`modal-icon-warning` (Gotcha di `docs/frontend-standard.md` — dua class itu dipakai `statusModal`/`resetAkunModal` tapi tidak pernah didefinisikan):

```css
@utility modal-icon-primary {
    @apply bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400;
}
```

`resources/views/components/modal/make-captain.blade.php` (🆕) — pola sama `modal/leave-team.blade.php`, form-nya bawa 2 hidden input (`is_captain`, dan `jersey_number` via `:value="jerseyNumber"`):

```blade
<div x-data="makeCaptainModal"
    @make-captain-confirm.window="open($event.detail.action,$event.detail.name,$event.detail.jerseyNumber)"
    x-show="show" class="modal-overlay flex items-center justify-center p-4" x-transition x-cloak>

    <div class="modal-container modal-md" @click.away="close()" x-transition>

        <div class="modal-header">
            <div class="flex items-center gap-4">
                <span class="modal-icon modal-icon-primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </span>

                <div>
                    <h3 class="modal-title">{{ __('Jadikan Kapten') }}</h3>
                    <p class="modal-description">{{ __('Status kapten akan berpindah dari kapten sebelumnya.') }}</p>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                {{ __('Jadikan') }}
                <strong class="font-semibold text-gray-800 dark:text-white" x-text="name"></strong>
                {{ __('sebagai kapten tim ini?') }}
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close()">{{ __('Batal') }}</button>

            <form :action="action" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_captain" value="1">
                <input type="hidden" name="jersey_number" :value="jerseyNumber">

                <button type="submit" class="btn btn-primary">{{ __('Jadikan Kapten') }}</button>
            </form>
        </div>

    </div>
</div>
```

`resources/views/components/button/make-captain.blade.php` (🆕) — trigger, pola sama `button/leave-team.blade.php`, dispatch bawa `jerseyNumber` tambahan:

```blade
@props(['action', 'name', 'jerseyNumber'])

<button type="button"
    @click="$dispatch('make-captain-confirm',{action:'{{ $action }}',name:'{{ addslashes($name) }}',jerseyNumber:'{{ $jerseyNumber }}'})"
    class="btn-icon btn-icon-primary" title="{{ __('Jadikan Kapten') }}">
    {{-- svg icon star, sama persis Tahap 4 --}}
</button>
```

`resources/views/teams/show.blade.php` — ganti form direct-submit hasil Tahap 4 (tabel & card) jadi:

```blade
@if (! $teamPlayer->is_captain)
    <x-button.make-captain :action="route('teams.players.update', [$team, $teamPlayer])"
        :name="$teamPlayer->player->name" :jersey-number="$teamPlayer->jersey_number" />
@endif
```

Catatan penamaan prop: `:jersey-number` (kebab-case di tag) otomatis map ke `$jerseyNumber` (camelCase) di `@props([...])` — konvensi standar Blade component, bukan typo.

Tambah `<x-modal.make-captain />` di akhir file, setelah `<x-modal.leave-team />`.

`lang/en.json` — tambah 3 string baru: `"Status kapten akan berpindah dari kapten sebelumnya."`, `"Jadikan"`, `"sebagai kapten tim ini?"` (key `"Jadikan Kapten"` sudah ada dari Tahap 5, reuse).

`docs/frontend-standard.md` — update tabel varian modal (tambah baris `makeCaptainModal`) dan Gotcha (`modal-icon-primary` sekarang jadi opsi resmi untuk aksi non-destruktif, bukan cuma `modal-icon-danger`).

`tests/Feature/TeamTest.php` — tambah 1 test guard regresi:

```php
public function test_tombol_jadikan_kapten_pakai_modal_konfirmasi(): void
{
    $academy = Academy::factory()->create();
    $team = $this->makeTeam($academy);
    $player = $this->makePlayer($academy);

    app(TeamPlayerService::class)->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);

    $this->actingAsOwner($academy);

    $response = $this->get(route('teams.show', $team));

    $response->assertOk();
    $response->assertSee('make-captain-confirm', false);
}
```

**✅ Cek dulu**: `npm run build` sukses, `php artisan test --filter=TeamTest` — 13/13 lulus. Manual: klik "Jadikan Kapten" — modal muncul (bukan langsung submit), nama player tampil di teks konfirmasi, tombol "Batal" menutup modal tanpa submit, tombol "Jadikan Kapten" di modal submit `PUT` dan badge Captain berpindah, nomor punggung player **tidak berubah**.
