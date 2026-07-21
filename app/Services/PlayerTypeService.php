<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\PlayerType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlayerTypeService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Daftar type untuk halaman index.
     *
     * Tidak perlu filter id_academy manual: PlayerType memakai
     * BelongsToAcademy -> AcademyScope, jadi user academy otomatis hanya
     * melihat type miliknya, dan Super Admin melihat seluruhnya.
     */
    public function paginate(?int $perPage = null)
    {
        return PlayerType::with('academy')
            ->withCount('players')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar type untuk dropdown di form Player.
     *
     * $academyId null  -> seluruh academy (dipakai Super Admin di form CREATE
     *                     Player, karena academy-nya baru dipilih di form yang
     *                     sama lalu difilter di sisi Alpine).
     * $includeId       -> dipakai form EDIT Player: type yang sedang dipakai
     *                     player tetap ikut walau sudah dinonaktifkan, supaya
     *                     nilainya tidak hilang saat disimpan.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return PlayerType::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_player_type', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Tentukan id_academy untuk type baru.
     *
     * User academy : otomatis dari academy miliknya, input form DIABAIKAN.
     * Super Admin  : dari pilihan academy di form (wajib, divalidasi
     *                PlayerTypeFormRequest). Tidak ada opsi "type system".
     */
    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): PlayerType
    {
        return DB::transaction(function () use ($data) {

            return PlayerType::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_billable' => $data['is_billable'] ?? false,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(PlayerType $playerType, array $data): PlayerType
    {
        return DB::transaction(function () use ($playerType, $data) {

            // id_academy sengaja TIDAK ikut diubah.
            // Type tidak dapat berpindah academy -- player yang sudah memakainya
            // akan ikut "pindah" secara tidak sengaja.
            $playerType->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_billable' => $data['is_billable'] ?? false,
                'status' => $data['status'] ?? true,
            ]);

            return $playerType;
        });
    }

    public function delete(PlayerType $playerType): bool
    {
        return DB::transaction(function () use ($playerType) {

            // FK players.id_player_type memang nullOnDelete, tapi itu cuma
            // jaring pengaman terakhir. Kalau type dihapus begitu saja,
            // player-nya diam-diam kehilangan type. Blokir di sini.
            // Type yang sudah tidak dipakai lagi: nonaktifkan (status = false),
            // jangan dihapus.
            if ($playerType->players()->exists()) {
                throw new \Exception(__('Type masih digunakan oleh player, tidak dapat dihapus. Nonaktifkan type ini kalau sudah tidak dipakai.'));
            }

            return $playerType->delete();
        });
    }

    /**
     * Buat type default untuk academy baru dari config('faos.player_type_templates').
     *
     * Academy di-pass eksplisit (bukan dari academy aktif) karena yang membuat
     * academy adalah Super Admin, yang id_academy-nya null.
     */
    public function createDefaultPlayerTypes(Academy $academy): void
    {
        foreach (config('faos.player_type_templates') as $name => $attributes) {

            PlayerType::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'is_billable' => $attributes['is_billable'] ?? false,
                'status' => true,
            ]);
        }
    }
}
