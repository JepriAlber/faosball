<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerCategory\PlayerCategoryFormRequest;
use App\Models\Academy;
use App\Models\PlayerCategory;
use App\Services\AcademyService;
use App\Services\PlayerCategoryService;

class PlayerCategoryController extends Controller
{
    protected PlayerCategoryService $playerCategoryService;
    protected AcademyService $academyService;

    public function __construct(PlayerCategoryService $playerCategoryService, AcademyService $academyService)
    {
        $this->playerCategoryService = $playerCategoryService;
        $this->academyService = $academyService;
    }

    public function index()
    {
        return view('player-categories.index', [
            'title' => __('Player Category'),
            'breadcrumb' => [
                ['label' => __('Players'), 'url' => route('players.index')],
                ['label' => __('Player Category')],
            ],
            'playerCategories' => $this->playerCategoryService->paginate(),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        return view('player-categories.create', [
            'title' => __('Tambah Player Category'),
            'breadcrumb' => [
                ['label' => __('Player Category'), 'url' => route('player-categories.index')],
                ['label' => __('Tambah Player Category')],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(PlayerCategoryFormRequest $request)
    {
        try {

            $this->playerCategoryService->create($request->validated());

            return redirect()
                ->route('player-categories.index')
                ->with('success', __('Player category berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan player category'));
        }
    }

    public function edit(PlayerCategory $playerCategory)
    {
        return view('player-categories.edit', [
            'title' => __('Edit Player Category'),
            'breadcrumb' => [
                ['label' => __('Player Category'), 'url' => route('player-categories.index')],
                ['label' => __('Edit Player Category')],
            ],
            'playerCategory' => $playerCategory,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(PlayerCategoryFormRequest $request, PlayerCategory $playerCategory)
    {
        try {

            $this->playerCategoryService->update($playerCategory, $request->validated());

            return redirect()
                ->route('player-categories.index')
                ->with('success', __('Player category berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui player category'));
        }
    }

    public function destroy(PlayerCategory $playerCategory)
    {
        try {

            $this->playerCategoryService->delete($playerCategory);

            return redirect()
                ->route('player-categories.index')
                ->with('success', __('Player category berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus player category'), 'player-categories.index');
        }
    }
}
