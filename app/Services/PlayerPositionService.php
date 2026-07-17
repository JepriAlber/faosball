<?php

namespace App\Services;

use App\Models\PlayerPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlayerPositionService
{
    /*
    |--------------------------------------------------------------------------
    | Catatan: Service ini SENGAJA tidak menerima AcademyService.
    |--------------------------------------------------------------------------
    | Master posisi bersifat global -- tidak ada "posisi milik academy X".
    | Kalau kamu merasa butuh AcademyService di sini, berarti ada yang salah.
    */

    /**
     * Daftar posisi untuk halaman index (Super Admin).
     *
     * withoutGlobalScopes() pada hitungan player WAJIB: Player memakai
     * AcademyScope, sedangkan posisi ini dipakai lintas academy. Angka
     * "dipakai N player" pada master global harus berarti N player di
     * SELURUH sistem. Lihat Bagian 4.3.
     */
    public function paginate(?int $perPage = null)
    {
        return PlayerPosition::query()
            ->withCount([
                'primaryPlayers' => fn ($query) => $query->withoutGlobalScopes(),
                'secondaryPlayers' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage ?? config('faos.pagination.default'));
    }

    /**
     * Daftar posisi untuk dropdown di form Player.
     *
     * Tidak ada parameter academy -- posisi berlaku untuk seluruh academy.
     *
     * $includeId & $includeSecondId dipakai form EDIT: posisi yang sedang
     * dipakai player tetap ikut walau sudah dinonaktifkan, supaya nilainya
     * tidak hilang saat disimpan.
     */
    public function selectable(?string $includeId = null, ?string $includeSecondId = null): Collection
    {
        return PlayerPosition::query()
            ->where(function ($query) use ($includeId, $includeSecondId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_player_position', $includeId);
                }

                if ($includeSecondId) {
                    $query->orWhere('id_player_position', $includeSecondId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Daftar position_group yang sudah dipakai, untuk opsi datalist di form.
     *
     * Meniru pola PermissionService::existingModules() yang sudah ada.
     */
    public function existingGroups(): array
    {
        return PlayerPosition::query()
            ->select('position_group')
            ->distinct()
            ->orderBy('position_group')
            ->pluck('position_group')
            ->all();
    }

    public function create(array $data): PlayerPosition
    {
        return DB::transaction(function () use ($data) {

            return PlayerPosition::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'position_group' => $data['position_group'],
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(PlayerPosition $playerPosition, array $data): PlayerPosition
    {
        return DB::transaction(function () use ($playerPosition, $data) {

            $playerPosition->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'position_group' => $data['position_group'],
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

            return $playerPosition;
        });
    }

    public function delete(PlayerPosition $playerPosition): bool
    {
        return DB::transaction(function () use ($playerPosition) {

            // WAJIB cek DUA-DUANYA: posisi bisa dipakai sebagai posisi utama
            // ATAU posisi kedua. Kalau cuma primaryPlayers() yang dicek, posisi
            // yang hanya dipakai sebagai posisi kedua akan lolos dihapus, dan
            // player-player itu diam-diam kehilangan posisi keduanya.
            // Lihat Bagian 4.2.
            //
            // withoutGlobalScopes() juga wajib -- lihat Bagian 4.3.
            $dipakai = $playerPosition->primaryPlayers()->withoutGlobalScopes()->exists()
                || $playerPosition->secondaryPlayers()->withoutGlobalScopes()->exists();

            if ($dipakai) {
                throw new \Exception('Posisi masih digunakan oleh player, tidak dapat dihapus. Nonaktifkan posisi ini kalau sudah tidak dipakai.');
            }

            return $playerPosition->delete();
        });
    }
}
