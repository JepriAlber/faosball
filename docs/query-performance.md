# Query & Database Performance

## Overview

Dokumen ini menjelaskan standar penulisan query pada FAOSBall: eager loading, N+1, pagination, indexing, dan kapan pakai join manual. Fokusnya performa, karena `AcademyScope` menambahkan filter `id_academy` ke **hampir semua query** tenant di sistem ini (lihat `docs/multi-tenancy.md`) — kalau query dasarnya lambat atau boros, semua module ikut terkena, bukan cuma satu fitur.

---

## Table of Contents

- [N+1 Query Problem](#n1-query-problem)
- [Eager Loading: with(), load(), withCount()](#eager-loading-with-load-withcount)
- [Pagination Wajib untuk List](#pagination-wajib-untuk-list)
- [Select Kolom Secukupnya](#select-kolom-secukupnya)
- [Index Database](#index-database)
- [Query di Dalam Loop](#query-di-dalam-loop)
- [Join Manual vs Eloquent Relationship](#join-manual-vs-eloquent-relationship)
- [Tools untuk Deteksi: Laravel Debugbar](#tools-untuk-deteksi-laravel-debugbar)
- [Checklist Sebelum PR](#checklist-sebelum-pr)
- [Summary](#summary)

---

## N+1 Query Problem

N+1 terjadi kalau 1 query awal (mis. ambil list Player) diikuti N query tambahan (mis. akses `$player->academy->name` di dalam `@forelse`, jadi 1 query per baris).

**Contoh yang salah** (query relasi dipanggil di dalam loop Blade tanpa eager load):

```php
// Controller
'players' => Player::latest()->paginate(10),
```

```blade
{{-- Blade: setiap baris trigger query baru ke tabel academies --}}
@foreach ($players as $player)
    {{ $player->academy->name }}
@endforeach
```

10 baris data = 1 query list + 10 query academy = 11 query. 100 baris = 101 query. Ini yang disebut N+1.

**Contoh yang benar** — eager load relasi yang dipakai di view lewat `with()` di Controller/Service:

```php
'players' => Player::with('academy')->latest()->paginate(10),
```

Aturan wajib: **kalau Blade view mengakses relasi (`$model->relasi->field`), relasi itu wajib di-eager-load di Controller/Service pemanggilnya** — jangan biarkan Blade jadi satu-satunya tempat yang "tahu" query apa yang sebenarnya jalan.

---

## Eager Loading: with(), load(), withCount()

Tiga cara eager load yang dipakai di FAOSBall, pilih sesuai konteks:

- **`with([...])`** — dipakai saat query masih dibentuk (list/index), sebelum `paginate()`/`get()`. Contoh nyata di `PlayerController@show`:

  ```php
  $player->load([
      'academy',
      'user.roles',
  ]);
  ```

  (`load()` dipakai di sini karena `$player` sudah berupa model hasil route-model-binding, bukan query builder — kalau masih query builder, pakai `with()`.)

- **`withCount('relasi')`** — dipakai kalau Blade cuma butuh **jumlah** relasi, bukan datanya (mis. badge "12 Permission" di halaman Role/Permission list). Jangan load seluruh relasi cuma untuk `count()`-nya. Contoh nyata di `RoleService::paginate()`:

  ```php
  return Role::withCount([
      'permissions',
      'users',
  ])->latest()->paginate($perPage);
  ```

  dan `PermissionService::paginate()`:

  ```php
  return Permission::withCount('roles')->latest()->paginate($perPage);
  ```

  Hasilnya bisa langsung dipakai di Blade sebagai `$role->permissions_count` / `$permission->roles_count` — tanpa query tambahan, tanpa load seluruh relasi ke memory.

- **Nested relation** (`user.roles`) — pakai dot notation, bukan nested `with()` bersarang, supaya tetap 1 query per level relasi.

**Kapan wajib pakai yang mana**: kalau Blade cuma menampilkan angka (jumlah relasi) → `withCount()`. Kalau Blade menampilkan field dari relasi (nama, tanggal, dst) → `with()`/`load()`. Jangan `with()` relasi penuh kalau yang dipakai cuma count-nya.

---

## Pagination Wajib untuk List

Semua halaman index/list module **wajib** pakai `paginate()`, tidak boleh `get()`/`all()` untuk data yang berpotensi tumbuh (players, academies, roles, permissions, dst). Ini pola yang sudah konsisten dipakai di seluruh Controller/Service yang ada (`Player::latest()->paginate(10)`, `Academy::latest()->paginate(10)`, `RoleService::paginate()`, `PermissionService::paginate()`) — pertahankan pola ini untuk module baru.

`get()`/`all()` cuma boleh dipakai untuk data yang secara desain terbatas jumlahnya (mis. `permissionGroups()` untuk populate form, daftar module untuk datalist).

---

## Select Kolom Secukupnya

Untuk query yang datanya besar atau dipakai berulang (laporan, export, dropdown/select berisi banyak baris), pakai `select()` untuk kolom yang benar-benar dipakai, jangan `SELECT *` default kalau modelnya banyak kolom (mis. `Player` punya 20+ kolom tapi dropdown pemain cuma butuh `id_player`, `name`, `player_code`).

```php
Player::query()
    ->select(['id_player', 'name', 'player_code'])
    ->orderBy('name')
    ->get();
```

Untuk halaman index/list biasa (10 baris per halaman lewat `paginate()`), ini **tidak wajib** — overhead-nya kecil dan `table-card-list` (lihat `docs/frontend-standard.md`) kadang butuh kolom yang lebih lengkap dari yang kelihatan di tabel. Prioritaskan `select()` untuk query yang memang berat: laporan, export, atau query yang dipanggil di banyak tempat (mis. helper di Service yang dipakai lintas Controller).

---

## Index Database

Karena `AcademyScope` otomatis menambahkan `WHERE id_academy = ?` ke hampir semua query model tenant, kolom `id_academy` **wajib** punya index di setiap tabel tenant — tanpa index ini, setiap query di module manapun melakukan full table scan begitu data bertambah banyak.

Migration FAOSBall sudah konsisten menaruh section index eksplisit sebelum foreign key, contoh dari `create_players_table`:

```php
/*
|--------------------------------------------------------------------------
| Index
|--------------------------------------------------------------------------
*/
$table->index('id_academy');
$table->index('id_user');
```

Aturan wajib untuk migration module baru:

- `id_academy` **selalu** diberi index (kalau tabelnya tenant-scoped).
- Foreign key lain yang sering dipakai untuk filter/join (`id_user`, `id_role`, dst) diberi index juga.
- Kolom yang sering dipakai di `WHERE`/`ORDER BY`/pencarian unik (mis. `player_code`, `slug` pada academies) sudah otomatis ter-index kalau pakai `->unique()` — tidak perlu index ganda.
- Kalau nanti ada fitur filter/search kombinasi (mis. filter Player berdasarkan `status` + `primary_position` sekaligus), pertimbangkan composite index, tapi diskusikan dulu dengan user sebelum menambah index baru di tabel existing (index menambah biaya di setiap insert/update).

---

## Query di Dalam Loop

Selain N+1 lewat relasi Eloquent, hindari juga query manual (`Model::where(...)->first()`, `DB::table(...)`) di dalam `foreach`/`for` — pola ini sama buruknya walau tidak lewat relasi. Contoh yang salah:

```php
foreach ($players as $player) {
    $academy = Academy::find($player->id_academy); // query per iterasi
}
```

Kalau butuh data terkait untuk banyak baris sekaligus, ambil semua dulu di luar loop (`whereIn`, atau eager load relasi), baru diproses di dalam loop.

Untuk operasi massal pada data besar (mis. job/command yang memproses ribuan baris), pakai `chunk()`/`lazy()` supaya tidak menarik seluruh tabel ke memory sekaligus — bukan `get()` lalu `foreach`.

---

## Join Manual vs Eloquent Relationship

Default di FAOSBall: pakai Eloquent relationship (`belongsTo`, `hasMany`, `with()`, `withCount()`) — ini yang dipakai konsisten di seluruh Service saat ini, dan paling mudah dibaca/dipelihara sesuai `docs/architecture.md` (Service Layer, Thin Controller).

Pertimbangkan `join()`/query builder manual **hanya** untuk kasus laporan/aggregasi lintas tabel yang berat (mis. rekap statistik akademi lintas banyak tabel sekaligus), di mana N query relasi akan jauh lebih mahal daripada 1 query join. Ini pola yang belum ada contohnya di codebase — kalau muncul kebutuhan seperti ini, diskusikan dulu dengan user sebelum memutuskan pindah dari pola Eloquent yang sudah konsisten dipakai, sesuai Aturan Utama di `CLAUDE.md`.

`whereHas()`/`whereDoesntHave()` boleh dipakai untuk filter berdasarkan keberadaan relasi (mis. "player yang belum punya akun user"), tapi ingat ini menghasilkan subquery — untuk data besar, cek dulu lewat Debugbar apakah query-nya masih wajar.

---

## Tools untuk Deteksi: Laravel Debugbar

Project ini sudah terpasang `barryvdh/laravel-debugbar` (lihat `composer.json`). Sebelum menganggap fitur list/report/detail selesai:

1. Buka halaman terkait di browser (local, dengan Debugbar aktif).
2. Cek tab **Queries** — pastikan jumlah query **tidak bertambah proporsional** dengan jumlah baris data (indikasi N+1: 10 baris → query > 12, 20 baris → query > 22, dst).
3. Kalau ada query berulang dengan pola yang sama (cuma beda parameter), itu N+1 — cari relasi yang belum di-eager-load.

Ini wajib dicek terutama untuk halaman index/list module baru (yang juga sudah punya Card List responsif, lihat `docs/frontend-standard.md`) dan halaman detail (`show`) yang menampilkan banyak relasi.

---

## Checklist Sebelum PR

- [ ] Semua relasi yang diakses di Blade (`$model->relasi->field`) sudah di-eager-load lewat `with()`/`load()` di Controller/Service.
- [ ] Kalau Blade cuma butuh jumlah relasi, pakai `withCount()`, bukan load relasi penuh.
- [ ] Halaman index/list pakai `paginate()`, bukan `get()`/`all()`.
- [ ] Tidak ada query (Eloquent atau `DB::table`) di dalam `foreach`/`for`.
- [ ] Migration tabel tenant baru sudah punya index pada `id_academy` dan foreign key lain yang sering dipakai untuk filter/join.
- [ ] Sudah dicek lewat tab Queries di Laravel Debugbar — jumlah query tidak proporsional dengan jumlah baris data.

---

## Summary

Karena `AcademyScope` menambahkan filter `id_academy` ke hampir semua query tenant, performa query bukan concern satu module saja — pola yang dipakai (index yang benar, eager loading yang tepat, pagination) harus konsisten di semua module. Default-nya: pakai Eloquent relationship dengan `with()`/`load()`/`withCount()` sesuai kebutuhan Blade, `paginate()` untuk semua list, index wajib untuk `id_academy` dan foreign key lain, dan selalu cek tab Queries di Laravel Debugbar sebelum fitur list/detail dianggap selesai. Join manual/query builder mentah adalah pengecualian untuk laporan berat, bukan default.
