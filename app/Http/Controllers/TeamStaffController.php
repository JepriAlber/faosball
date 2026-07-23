<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamStaff\StoreTeamStaffRequest;
use App\Models\Team;
use App\Models\TeamStaff;
use App\Services\TeamStaffService;

class TeamStaffController extends Controller
{
    protected TeamStaffService $teamStaffService;

    public function __construct(TeamStaffService $teamStaffService)
    {
        $this->teamStaffService = $teamStaffService;
    }

    public function store(StoreTeamStaffRequest $request, Team $team)
    {
        try {
            $this->teamStaffService->assign($team, $request->validated());

            return redirect()->route('teams.show', $team)->with('success', __('Staff berhasil ditambahkan ke tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan staff ke tim'), 'teams.show', [$team]);
        }
    }

    public function leave(Team $team, TeamStaff $teamStaff)
    {
        try {
            $this->teamStaffService->leave($teamStaff);

            return redirect()->route('teams.show', $team)->with('success', __('Staff berhasil dikeluarkan dari tim.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal mengeluarkan staff'), 'teams.show', [$team]);
        }
    }
}
