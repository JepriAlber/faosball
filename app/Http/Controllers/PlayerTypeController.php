<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerType\PlayerTypeFormRequest;
use App\Models\Academy;
use App\Models\PlayerType;
use App\Services\AcademyService;
use App\Services\PlayerTypeService;

class PlayerTypeController extends Controller
{
    protected PlayerTypeService $playerTypeService;
    protected AcademyService $academyService;

    public function __construct(PlayerTypeService $playerTypeService, AcademyService $academyService)
    {
        $this->playerTypeService = $playerTypeService;
        $this->academyService = $academyService;
    }

    public function index()
    {
        return view('player-types.index', [
            'title' => 'Player Type',
            'breadcrumb' => [
                ['label' => 'Players', 'url' => route('players.index')],
                ['label' => 'Player Type'],
            ],
            'playerTypes' => $this->playerTypeService->paginate(),
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        return view('player-types.create', [
            'title' => 'Tambah Player Type',
            'breadcrumb' => [
                ['label' => 'Player Type', 'url' => route('player-types.index')],
                ['label' => 'Tambah Player Type'],
            ],
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
            'academies' => $this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(PlayerTypeFormRequest $request)
    {
        try {

            $this->playerTypeService->create($request->validated());

            return redirect()
                ->route('player-types.index')
                ->with('success', 'Player type berhasil ditambahkan.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menambahkan player type');
        }
    }

    public function edit(PlayerType $playerType)
    {
        return view('player-types.edit', [
            'title' => 'Edit Player Type',
            'breadcrumb' => [
                ['label' => 'Player Type', 'url' => route('player-types.index')],
                ['label' => 'Edit Player Type'],
            ],
            'playerType' => $playerType,
            'isSuperAdmin' => $this->academyService->isSuperAdmin(),
        ]);
    }

    public function update(PlayerTypeFormRequest $request, PlayerType $playerType)
    {
        try {

            $this->playerTypeService->update($playerType, $request->validated());

            return redirect()
                ->route('player-types.index')
                ->with('success', 'Player type berhasil diperbarui.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal memperbarui player type');
        }
    }

    public function destroy(PlayerType $playerType)
    {
        try {

            $this->playerTypeService->delete($playerType);

            return redirect()
                ->route('player-types.index')
                ->with('success', 'Player type berhasil dihapus.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menghapus player type', 'player-types.index');
        }
    }
}
