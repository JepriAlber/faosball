<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\TeamFormRequest;
use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerCategory;
use App\Models\Season;
use App\Models\Staff;
use App\Models\Team;
use App\Models\TeamStaffPosition;
use App\Services\AcademyService;
use App\Services\PlayerCategoryService;
use App\Services\SeasonService;
use App\Services\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    protected TeamService $teamService;
    protected SeasonService $seasonService;
    protected PlayerCategoryService $playerCategoryService;
    protected AcademyService $academyService;

    public function __construct(
        TeamService $teamService,
        SeasonService $seasonService,
        PlayerCategoryService $playerCategoryService,
        AcademyService $academyService
    ) {
        $this->teamService = $teamService;
        $this->seasonService = $seasonService;
        $this->playerCategoryService = $playerCategoryService;
        $this->academyService = $academyService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'id_academy', 'id_season', 'id_player_category', 'sort']));
        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('teams.index', [
            'title' => __('Team'),
            'breadcrumb' => [
                ['label' => __('Football Academy')],
                ['label' => __('Team')],
            ],
            'teams' => $this->teamService->paginate($filters),
            'statusCounts' => $this->teamService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
            'seasons' => Season::query()->orderByDesc('name')->get(),
            'playerCategories' => PlayerCategory::query()->orderBy('min_age')->get(),
        ]);
    }

    public function create()
    {
        $academyId = $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId();

        return view('teams.create', [
            'title' => __('Tambah Team'),
            'breadcrumb' => [
                ['label' => __('Team'), 'url' => route('teams.index')],
                ['label' => __('Tambah Team')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin() ? Academy::orderBy('name')->get() : collect(),
            // Super Admin: dropdown ini KOSONG di render awal, diisi AJAX oleh
            // academyCascade() begitu Academy dipilih (issue19.md/issue18.md
            // Temuan 3-5). User academy biasa: tetap ter-scope penuh seperti
            // sebelumnya, TIDAK terpengaruh perubahan ini.
            'seasons' => $this->academyService->isSuperAdmin() ? collect() : $this->seasonService->selectable($academyId),
            'playerCategories' => $this->academyService->isSuperAdmin() ? collect() : $this->playerCategoryService->selectable($academyId),
        ]);
    }

    public function cascadeOptions(Request $request)
    {
        $academyId = $this->academyService->isSuperAdmin()
            ? $request->query('id_academy')
            : $this->academyService->currentId();

        return response()->json([
            'id_season' => $this->seasonService->selectable($academyId)
                ->map(fn ($season) => ['value' => $season->id_season, 'label' => $season->name])
                ->values(),
            'id_player_category' => $this->playerCategoryService->selectable($academyId)
                ->map(fn ($category) => ['value' => $category->id_player_category, 'label' => $category->name])
                ->values(),
        ]);
    }

    public function store(TeamFormRequest $request)
    {
        try {
            $this->teamService->create($request->validated());

            return redirect()->route('teams.index')->with('success', __('Team berhasil ditambahkan.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan team'));
        }
    }

    public function show(Team $team)
    {
        $team->load(['season', 'playerCategory', 'academy']);

        return view('teams.show', [
            'title' => $team->name,
            'breadcrumb' => [
                ['label' => __('Team'), 'url' => route('teams.index')],
                ['label' => $team->name],
            ],
            'team' => $team,
            'teamPlayers' => $team->teamPlayers()->with('player')->get(),
            'teamStaff' => $team->teamStaff()->with(['staff', 'teamStaffPosition'])->get(),
            // Dipindah dari view (issue18.md Temuan 2 -- query di Blade
            // melanggar Thin Controller, docs/architecture.md). Exclude
            // player/staff yang SUDAH aktif di tim ini supaya tidak bisa
            // dipilih dobel di form "Add Player"/"Assign Staff" (TeamPlayerService/
            // TeamStaffService sudah menolaknya, ini cuma mencegah percobaan sia-sia).
            'availablePlayers' => Player::where('id_academy', $team->id_academy)
                ->whereNotIn('id_player', $team->activeTeamPlayers()->pluck('id_player'))
                ->orderBy('name')
                ->get(),
            'availableStaff' => Staff::where('id_academy', $team->id_academy)
                ->whereNotIn('id_staff', $team->activeTeamStaff()->pluck('id_staff'))
                ->orderBy('full_name')
                ->get(),
            'teamStaffPositions' => TeamStaffPosition::where('id_academy', $team->id_academy)
                ->where('status', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function edit(Team $team)
    {
        return view('teams.edit', [
            'title' => __('Edit Team'),
            'breadcrumb' => [
                ['label' => __('Team'), 'url' => route('teams.index')],
                ['label' => __('Edit Team')],
            ],
            'team' => $team,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'seasons' => $this->seasonService->selectable($team->id_academy, $team->id_season),
            'playerCategories' => $this->playerCategoryService->selectable($team->id_academy, $team->id_player_category),
        ]);
    }

    public function update(TeamFormRequest $request, Team $team)
    {
        try {
            $this->teamService->update($team, $request->validated());

            return redirect()->route('teams.index')->with('success', __('Team berhasil diperbarui.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal memperbarui team'));
        }
    }

    public function destroy(Team $team)
    {
        try {
            $this->teamService->delete($team);

            return redirect()->route('teams.index')->with('success', __('Team berhasil dihapus.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menghapus team'), 'teams.index');
        }
    }
}
