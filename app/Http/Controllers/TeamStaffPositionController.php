<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamStaffPosition\TeamStaffPositionFormRequest;
use App\Models\Academy;
use App\Models\TeamStaffPosition;
use App\Services\AcademyService;
use App\Services\TeamStaffPositionService;
use Illuminate\Http\Request;

class TeamStaffPositionController extends Controller
{
    protected TeamStaffPositionService $teamStaffPositionService;
    protected AcademyService $academyService;

    public function __construct(TeamStaffPositionService $teamStaffPositionService, AcademyService $academyService)
    {
        $this->teamStaffPositionService = $teamStaffPositionService;
        $this->academyService = $academyService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'id_academy', 'sort']));

        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('team-staff-positions.index', [
            'title' => __('Team Staff Position'),
            'breadcrumb' => [
                ['label' => __('Football Academy')],
                ['label' => __('Team Staff Position')],
            ],
            'teamStaffPositions' => $this->teamStaffPositionService->paginate($filters),
            'statusCounts' => $this->teamStaffPositionService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
        ]);
    }

    public function create()
    {
        return view('team-staff-positions.create', [
            'title' => __('Tambah Team Staff Position'),
            'breadcrumb' => [
                ['label' => __('Team Staff Position'), 'url' => route('team-staff-positions.index')],
                ['label' => __('Tambah Team Staff Position')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(TeamStaffPositionFormRequest $request)
    {
        try {

            $this->teamStaffPositionService->create($request->validated());

            return redirect()
                ->route('team-staff-positions.index')
                ->with('success', __('Team staff position berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan team staff position'));
        }
    }

    public function edit(TeamStaffPosition $teamStaffPosition)
    {
        return view('team-staff-positions.edit', [
            'title' => __('Edit Team Staff Position'),
            'breadcrumb' => [
                ['label' => __('Team Staff Position'), 'url' => route('team-staff-positions.index')],
                ['label' => __('Edit Team Staff Position')],
            ],
            'teamStaffPosition' => $teamStaffPosition,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(TeamStaffPositionFormRequest $request, TeamStaffPosition $teamStaffPosition)
    {
        try {

            $this->teamStaffPositionService->update($teamStaffPosition, $request->validated());

            return redirect()
                ->route('team-staff-positions.index')
                ->with('success', __('Team staff position berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui team staff position'));
        }
    }

    public function destroy(TeamStaffPosition $teamStaffPosition)
    {
        try {

            $this->teamStaffPositionService->delete($teamStaffPosition);

            return redirect()
                ->route('team-staff-positions.index')
                ->with('success', __('Team staff position berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus team staff position'), 'team-staff-positions.index');
        }
    }
}
