<?php

namespace App\Http\Controllers;

use App\Http\Requests\Season\SeasonFormRequest;
use App\Models\Academy;
use App\Models\Season;
use App\Services\AcademyService;
use App\Services\SeasonService;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    protected SeasonService $seasonService;
    protected AcademyService $academyService;

    public function __construct(SeasonService $seasonService, AcademyService $academyService)
    {
        $this->seasonService = $seasonService;
        $this->academyService = $academyService;
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'id_academy', 'sort']));
        $isSuperAdmin = $this->academyService->isSuperAdmin();

        return view('seasons.index', [
            'title' => __('Season'),
            'breadcrumb' => [
                ['label' => __('Football Academy')],
                ['label' => __('Season')],
            ],
            'seasons' => $this->seasonService->paginate($filters),
            'statusCounts' => $this->seasonService->statusCounts($filters),
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            'academies' => $isSuperAdmin ? Academy::orderBy('name')->get() : collect(),
        ]);
    }

    public function create()
    {
        return view('seasons.create', [
            'title' => __('Tambah Season'),
            'breadcrumb' => [
                ['label' => __('Season'), 'url' => route('seasons.index')],
                ['label' => __('Tambah Season')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin() ? Academy::orderBy('name')->get() : collect(),
        ]);
    }

    public function store(SeasonFormRequest $request)
    {
        try {
            $this->seasonService->create($request->validated());

            return redirect()->route('seasons.index')->with('success', __('Season berhasil ditambahkan.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menambahkan season'));
        }
    }

    public function edit(Season $season)
    {
        return view('seasons.edit', [
            'title' => __('Edit Season'),
            'breadcrumb' => [
                ['label' => __('Season'), 'url' => route('seasons.index')],
                ['label' => __('Edit Season')],
            ],
            'season' => $season,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(SeasonFormRequest $request, Season $season)
    {
        try {
            $this->seasonService->update($season, $request->validated());

            return redirect()->route('seasons.index')->with('success', __('Season berhasil diperbarui.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal memperbarui season'));
        }
    }

    public function destroy(Season $season)
    {
        try {
            $this->seasonService->delete($season);

            return redirect()->route('seasons.index')->with('success', __('Season berhasil dihapus.'));
        } catch (\Exception $e) {
            return $this->handleException($e, __('Gagal menghapus season'), 'seasons.index');
        }
    }
}
