# FAOSBall — Instruksi untuk Claude/AI Agent

Sebelum mengerjakan tugas apapun di project ini (fitur baru, refactor, bug fix), **wajib baca dulu**:

1. `README.md` — gambaran umum project, tech stack, arsitektur inti, roadmap module.
2. Seluruh file di folder `docs/`:
   - `docs/setup.md` — instalasi & setup project.
   - `docs/architecture.md` — Service Layer Architecture, Thin Controller, Dependency Injection, UUID Primary Key, Database Transaction.
   - `docs/multi-tenancy.md` — Single Database Multi-Tenant: `id_academy`, `AcademyScope`, `BelongsToAcademy`, `AcademyService`.
   - `docs/authorization.md` — Spatie Laravel Permission: format permission `module.action`, `Gate::before()` untuk Super Admin, Route/Blade permission checking.
   - `docs/coding-standard.md` — konvensi penamaan class/method/variable/route, struktur folder, dan **Bahasa Pesan** (pesan user-facing wajib Indonesia, di-hardcode langsung tanpa folder `lang/`).
   - `docs/development-guide.md` — alur pengembangan module (Migration → Model → Form Request → Service → Controller → Route → View → Permission → Menu) beserta checklist-nya.
   - `docs/module-standard.md` — struktur wajib tiap module baru.
   - `docs/frontend-standard.md` — konvensi CSS/Tailwind v4 (`@theme`/`@utility`), kapan bikin class reusable baru, gotcha varian breakpoint vs toggle dinamis, kapan pakai Blade Component vs View Composer, dan pola Table + Card List responsif untuk halaman index/list.
   - `docs/query-performance.md` — standar query & performa database: N+1, eager loading (`with`/`load`/`withCount`), pagination wajib, index database (terutama `id_academy` karena `AcademyScope`), dan cara deteksi lewat Laravel Debugbar.
   - `docs/permission-reference.md` — peta module → permission yang benar-benar digerbang di kode (route middleware, `authorize()`, `@can()`), plus module mana yang permission-nya belum ditegakkan. **Wajib diupdate** setiap kali module baru menambahkan permission checking.

## Aturan Utama

- Dokumen-dokumen di atas adalah **standar wajib**, bukan referensi opsional. Ikuti pola yang sudah ditetapkan di sana.
- Kalau ada permintaan yang tampak menyimpang dari standar yang sudah ada (mis. bikin pola baru padahal sudah ada yang serupa, taruh business logic di Model/Controller, hardcode warna yang sudah ada token-nya, dsb), diskusikan dulu dengan user sebelum melanjutkan — jangan asumsikan standarnya boleh dilanggar begitu saja.
- Kalau menemukan pola/keputusan baru yang layak jadi standar (seperti kasus Blade Component vs View Composer, atau lokalisasi pesan Breeze), tambahkan ke dokumen `docs/` yang relevan supaya pengembangan berikutnya bisa berujuk ke situ juga.
