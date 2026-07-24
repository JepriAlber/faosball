<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamPlayer\StoreTeamPlayerRequest;
use App\Http\Requests\TeamPlayer\UpdateTeamPlayerRequest;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Services\TeamPlayerService;

class TeamPlayerController extends Controller
{
    protected TeamPlayerService $teamPlayerService;

    public function __construct(TeamPlayerService $teamPlayerService)
    {
        $this->teamPlayerService = $teamPlayerService;
    }

    public function store(StoreTeamPlayerRequest $request, Team $team)
    {
        try {
            $this->teamPlayerService->assign($team, $request->validated());

            return redirect()->route('teams.show', $team)->with('success', __('Player berhasil ditambahkan ke tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan player ke tim'), 'teams.show', [$team]);
        }
    }

    public function update(UpdateTeamPlayerRequest $request, Team $team, TeamPlayer $teamPlayer)
    {
        try {
            $this->teamPlayerService->update($teamPlayer, $request->validated());

            return redirect()->route('teams.show', $team)->with('success', __('Nomor punggung/captain berhasil diperbarui.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal memperbarui player'), 'teams.show', [$team]);
        }
    }

    public function leave(Team $team, TeamPlayer $teamPlayer)
    {
        try {
            $this->teamPlayerService->leave($teamPlayer);

            return redirect()->route('teams.show', $team)->with('success', __('Player berhasil dikeluarkan dari tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal mengeluarkan player'), 'teams.show', [$team]);
        }
    }
}
