<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamPlayer;
use Illuminate\Support\Facades\DB;

class TeamPlayerService
{
    /**
     * Kunci baris team sebagai mutex -- mencegah race condition 2 admin
     * assign player ke tim yang sama nyaris bersamaan (pola sama
     * EmploymentContractService::lockStaff(), issue12.md Bagian 2d).
     */
    protected function lockTeam(Team $team): void
    {
        Team::withoutGlobalScopes()->whereKey($team->id_team)->lockForUpdate()->first();
    }

    public function assign(Team $team, array $data): TeamPlayer
    {
        return DB::transaction(function () use ($team, $data) {

            $this->lockTeam($team);

            if (TeamPlayer::where('id_team', $team->id_team)
                ->where('id_player', $data['id_player'])
                ->whereNull('leave_date')
                ->exists()) {
                throw new \Exception(__('Player ini sudah aktif terdaftar di tim ini.'));
            }

            $this->assertJerseyAvailable($team, $data['jersey_number']);

            $teamPlayer = TeamPlayer::create([
                'id_academy' => $team->id_academy,
                'id_team' => $team->id_team,
                'id_player' => $data['id_player'],
                'jersey_number' => $data['jersey_number'],
                'is_captain' => false,
                'join_date' => $data['join_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['is_captain'])) {
                $this->setCaptain($teamPlayer);
            }

            return $teamPlayer->fresh();
        });
    }

    /**
     * Ubah nomor punggung/captain -- HANYA untuk keanggotaan yang masih
     * aktif (issue16.md Bagian 2f). Ganti peran/tim = keluarkan + assign
     * baru, bukan edit histori lama.
     */
    public function update(TeamPlayer $teamPlayer, array $data): TeamPlayer
    {
        return DB::transaction(function () use ($teamPlayer, $data) {

            if (! $teamPlayer->isActive()) {
                throw new \Exception(__('Keanggotaan yang sudah keluar dari tim tidak dapat diubah.'));
            }

            $this->lockTeam($teamPlayer->team);

            if ((int) $data['jersey_number'] !== $teamPlayer->jersey_number) {
                $this->assertJerseyAvailable($teamPlayer->team, $data['jersey_number'], $teamPlayer);
            }

            $teamPlayer->update([
                'jersey_number' => $data['jersey_number'],
                'notes' => $data['notes'] ?? $teamPlayer->notes,
            ]);

            if (! empty($data['is_captain'])) {
                $this->setCaptain($teamPlayer);
            } elseif ($teamPlayer->is_captain) {
                $teamPlayer->update(['is_captain' => false]);
            }

            return $teamPlayer->fresh();
        });
    }

    /**
     * "Keluarkan dari Tim" -- isi leave_date, BUKAN hapus baris
     * (Aturan Emas, histori tetap utuh untuk laporan roster musim lalu).
     */
    public function leave(TeamPlayer $teamPlayer, ?string $leaveDate = null): TeamPlayer
    {
        if (! $teamPlayer->isActive()) {
            throw new \Exception(__('Player ini sudah tidak aktif di tim ini.'));
        }

        $teamPlayer->update(['leave_date' => $leaveDate ?? now()]);

        return $teamPlayer->fresh();
    }

    /**
     * Nomor punggung unik di antara anggota AKTIF tim ini saja -- boleh
     * dipakai ulang setelah pemain lama keluar (Aturan Emas, MySQL tidak
     * punya partial unique index).
     */
    protected function assertJerseyAvailable(Team $team, int $jerseyNumber, ?TeamPlayer $except = null): void
    {
        $query = TeamPlayer::where('id_team', $team->id_team)
            ->whereNull('leave_date')
            ->where('jersey_number', $jerseyNumber);

        if ($except) {
            $query->where('id_team_player', '!=', $except->id_team_player);
        }

        if ($query->exists()) {
            throw new \Exception(__('Nomor punggung ini sudah dipakai pemain aktif lain di tim ini.'));
        }
    }

    /**
     * Set 1 player jadi captain -- otomatis melepas status captain dari
     * player aktif lain di tim yang sama DALAM transaksi yang sama
     * (pola sama EmploymentContractService::activate() menutup contract
     * lama, issue12.md Bagian 2c).
     */
    protected function setCaptain(TeamPlayer $teamPlayer): void
    {
        TeamPlayer::where('id_team', $teamPlayer->id_team)
            ->whereNull('leave_date')
            ->where('id_team_player', '!=', $teamPlayer->id_team_player)
            ->update(['is_captain' => false]);

        $teamPlayer->update(['is_captain' => true]);
    }
}
