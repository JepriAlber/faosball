# FAOSBall — Instruksi untuk Claude/AI Agent

Sebelum mengerjakan tugas apapun di project ini (fitur baru, refactor, bug fix), **wajib baca dulu**:

1. `README.md` — gambaran umum project, tech stack, arsitektur inti, roadmap module.
2. Seluruh file di folder `docs/`:
   - `docs/setup.md` — instalasi & setup project.
   - `docs/architecture.md` — Service Layer Architecture, Thin Controller, Dependency Injection, UUID Primary Key, Database Transaction.
   - `docs/multi-tenancy.md` — Single Database Multi-Tenant: `id_academy`, `AcademyScope`, `BelongsToAcademy`, `AcademyService`.
   - `docs/authorization.md` — Spatie Laravel Permission: format permission `module.action`, `Gate::before()` untuk Super Admin, Route/Blade permission checking.
   - `docs/coding-standard.md` — konvensi penamaan class/method/variable/route, struktur folder, dan **Bahasa Pesan & Multi-Language** (pesan user-facing wajib Bahasa Indonesia sebagai string asli, dibungkus `__()`, terjemahan Inggris didaftarkan di `lang/en.json` — bukan hardcode tanpa `__()`, dan bukan key-based seperti `lang/id.json`).
   - `docs/development-guide.md` — alur pengembangan module (Migration → Model → Form Request → Service → Controller → Route → View → **Multi-Language** → Permission → Menu) beserta checklist-nya.
   - `docs/module-standard.md` — struktur wajib tiap module baru, termasuk Multi-Language Standard.
   - `docs/frontend-standard.md` — seluruh konvensi CSS/Tailwind v4 & pola UI reusable (Table + Card List responsif, Tabs + Toolbar filter, urutan field form, Blade Component vs View Composer, cascading dropdown academy-scoped, dst) — dokumen ini terus bertambah tiap ada pola baru, **selalu cek Table of Contents-nya**, jangan asumsikan isinya cuma yang disebut di sini.
   - `docs/query-performance.md` — standar query & performa database: N+1, eager loading (`with`/`load`/`withCount`), pagination wajib, index database (terutama `id_academy` karena `AcademyScope`), dan cara deteksi lewat Laravel Debugbar.
   - `docs/permission-reference.md` — peta module → permission yang benar-benar digerbang di kode (route middleware, `authorize()`, `@can()`), plus module mana yang permission-nya belum ditegakkan. **Wajib diupdate** setiap kali module baru menambahkan permission checking.

## Aturan Utama

- Dokumen-dokumen di atas adalah **standar wajib**, bukan referensi opsional. Ikuti pola yang sudah ditetapkan di sana.
- Kalau ada permintaan yang tampak menyimpang dari standar yang sudah ada (mis. bikin pola baru padahal sudah ada yang serupa, taruh business logic di Model/Controller, hardcode warna yang sudah ada token-nya, dsb), diskusikan dulu dengan user sebelum melanjutkan — jangan asumsikan standarnya boleh dilanggar begitu saja.
- Kalau menemukan pola/keputusan baru yang layak jadi standar (seperti kasus Blade Component vs View Composer, atau lokalisasi pesan Breeze), tambahkan ke dokumen `docs/` yang relevan supaya pengembangan berikutnya bisa berujuk ke situ juga.
- **Konvensi brief `issueN.md`**: fitur baru/perubahan yang tidak sepele ditulis dulu sebagai brief (`issueN.md` di root project, angka lanjut dari file tertinggi yang ada) sebelum diimplementasikan — format bakunya: `Aturan Emas` (tabel ❌ Jangan/Kenapa/Detail), `Konteks & Tujuan`, `Cara Kerja Solusi`, `Peta Perubahan File`, lalu `Tahap 1..N` (tiap tahap punya blok **✅ Cek dulu** yang wajib lolos sebelum lanjut) dan checklist `Progress Implementasi` di bagian atas yang dicentang tiap tahap selesai — supaya sesi/agent lain yang melanjutkan tahu persis titik berhentinya. Brief yang sudah selesai diimplementasikan **dan** di-merge biasanya dihapus dari working tree (lihat riwayat commit `chore: hapus issueN.md`) — kalau tidak menemukan `issueN.md` untuk fitur yang sedang dikerjakan, base kerja tetap `README.md`+`docs/` seperti biasa, bukan berarti standarnya hilang. Sebelum menuliskan implementasi apapun untuk permintaan yang cukup besar, tawarkan dulu untuk didiskusikan/ditulis sebagai brief kalau user belum memintanya secara eksplisit.
