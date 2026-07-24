<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\PlayerCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlayerCategoryService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Daftar kategori untuk halaman index.
     *
     * Tidak perlu filter id_academy manual: PlayerCategory memakai
     * BelongsToAcademy -> AcademyScope.
     *
     * orderBy('min_age') -- BUKAN latest(). Kelompok umur harus tampil urut
     * umur (U-12, U-15, U-17), bukan urut tanggal dibuat.
     */
    public function paginate(?int $perPage = null)
    {
        return PlayerCategory::with('academy')
            ->withCount('players')
            ->orderBy('min_age')
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar kategori untuk dropdown di form Player.
     *
     * $academyId null  -> seluruh academy (Super Admin di form CREATE Player,
     *                     karena academy-nya baru dipilih di form yang sama).
     * $includeId       -> form EDIT Player: kategori yang sedang dipakai player
     *                     tetap ikut walau sudah dinonaktifkan.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return PlayerCategory::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_player_category', $includeId);
                }
            })
            ->orderBy('min_age')
            ->get();
    }

    /**
     * Saran kategori berdasarkan umur pemain.
     *
     * INI HANYA SARAN. Hasilnya dipakai untuk mengisi dropdown di form,
     * dan coach bebas menimpanya. Tidak ada satupun tempat yang boleh
     * MEMAKSA player memakai hasil method ini. Lihat Bagian 4.2.
     *
     * orderBy('min_age') WAJIB: kalau academy membuat rentang yang tumpang
     * tindih (mis. U-12 = 10-12 dan U-13 = 12-13), umur 12 cocok ke dua-duanya.
     * Tanpa orderBy, database bebas mengembalikan yang mana saja -- sarannya
     * jadi berubah-ubah tanpa sebab. Lihat Bagian 4.3.
     */
    public function suggestFor(Carbon|string|null $birthDate, string $academyId): ?PlayerCategory
    {
        if (! $birthDate) {
            return null;
        }

        $age = Carbon::parse($birthDate)->age;

        return PlayerCategory::query()
            ->where('id_academy', $academyId)
            ->where('status', true)
            ->where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->orderBy('min_age')
            ->first();
    }

    /**
     * Versi in-memory dari suggestFor() -- beroperasi di atas collection
     * kategori yang SUDAH di-fetch (mis. $playerCategoryOptions di halaman
     * index Players), TIDAK query database lagi. Wajib dipakai kalau
     * pemanggilnya di dalam loop banyak baris (issue17.md Aturan Emas --
     * cegah N+1, lihat docs/query-performance.md).
     *
     * $categories HARUS sudah difilter ke id_academy yang benar oleh
     * pemanggil sebelum dipassing ke sini -- method ini tidak melakukan
     * filter academy sendiri.
     */
    public function suggestFromCollection(Collection $categories, Carbon|string|null $birthDate): ?PlayerCategory
    {
        if (! $birthDate) {
            return null;
        }

        $age = Carbon::parse($birthDate)->age;

        return $categories
            ->where('status', true)
            ->filter(fn (PlayerCategory $category) => $category->min_age <= $age && $category->max_age >= $age)
            ->sortBy('min_age')
            ->first();
    }

    /**
     * Tentukan id_academy untuk kategori baru.
     *
     * User academy : otomatis dari academy miliknya, input form DIABAIKAN.
     * Super Admin  : dari pilihan academy di form (wajib).
     */
    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): PlayerCategory
    {
        return DB::transaction(function () use ($data) {

            return PlayerCategory::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(PlayerCategory $playerCategory, array $data): PlayerCategory
    {
        return DB::transaction(function () use ($playerCategory, $data) {

            // id_academy sengaja TIDAK ikut diubah.
            // Kategori tidak dapat berpindah academy -- player yang memakainya
            // akan ikut "pindah" secara tidak sengaja.
            $playerCategory->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'status' => $data['status'] ?? true,
            ]);

            return $playerCategory;
        });
    }

    public function delete(PlayerCategory $playerCategory): bool
    {
        return DB::transaction(function () use ($playerCategory) {

            // FK players.id_player_category memang nullOnDelete, tapi itu cuma
            // jaring pengaman terakhir. Kalau kategori dihapus begitu saja,
            // player-nya diam-diam kehilangan kelompok umur.
            // Kategori yang sudah tidak dipakai: nonaktifkan, jangan dihapus.
            if ($playerCategory->players()->exists()) {
                throw new \Exception(__('Kategori masih digunakan oleh player, tidak dapat dihapus. Nonaktifkan kategori ini kalau sudah tidak dipakai.'));
            }

            return $playerCategory->delete();
        });
    }

    /**
     * Buat kategori default untuk academy baru dari
     * config('faos.player_category_templates').
     */
    public function createDefaultPlayerCategories(Academy $academy): void
    {
        foreach (config('faos.player_category_templates') as $name => $attributes) {

            PlayerCategory::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'min_age' => $attributes['min_age'],
                'max_age' => $attributes['max_age'],
                'status' => true,
            ]);
        }
    }
}
