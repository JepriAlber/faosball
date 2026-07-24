<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamStaff;
use App\Models\TeamStaffPosition;
use Illuminate\Support\Facades\DB;

class TeamStaffService
{
    /** Kode posisi Head Coach yang konvensinya tetap -- lihat issue16.md Bagian 2e. */
    protected const HEAD_COACH_CODE = 'HC';

    protected function lockTeam(Team $team): void
    {
        Team::withoutGlobalScopes()->whereKey($team->id_team)->lockForUpdate()->first();
    }

    public function assign(Team $team, array $data): TeamStaff
    {
        return DB::transaction(function () use ($team, $data) {

            $this->lockTeam($team);

            $position = TeamStaffPosition::findOrFail($data['id_team_staff_position']);

            // Assign Head Coach baru otomatis mengeluarkan Head Coach lama
            // yang masih aktif -- tidak pernah ada 2 Head Coach aktif
            // sesaat (issue16.md Bagian 2e). Kalau academy tidak punya
            // posisi ber-kode HC, guard ini otomatis tidak berlaku.
            if ($position->code === self::HEAD_COACH_CODE) {

                TeamStaff::where('id_team', $team->id_team)
                    ->whereNull('leave_date')
                    ->whereHas('teamStaffPosition', fn ($q) => $q->where('code', self::HEAD_COACH_CODE))
                    ->update(['leave_date' => now()]);
            }

            return TeamStaff::create([
                'id_academy' => $team->id_academy,
                'id_team' => $team->id_team,
                'id_staff' => $data['id_staff'],
                'id_team_staff_position' => $data['id_team_staff_position'],
                'join_date' => $data['join_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * "Keluarkan dari Tim" -- isi leave_date, BUKAN hapus baris.
     * Ganti peran staff di tim = keluarkan + assign baru (issue16.md
     * Bagian 2f), bukan edit di tempat.
     */
    public function leave(TeamStaff $teamStaff, ?string $leaveDate = null): TeamStaff
    {
        if (! $teamStaff->isActive()) {
            throw new \Exception(__('Staff ini sudah tidak aktif di tim ini.'));
        }

        $teamStaff->update(['leave_date' => $leaveDate ?? now()]);

        return $teamStaff->fresh();
    }
}
