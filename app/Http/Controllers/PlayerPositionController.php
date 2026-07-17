<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerPosition\PlayerPositionFormRequest;
use App\Models\PlayerPosition;
use App\Services\PlayerPositionService;

class PlayerPositionController extends Controller
{
    protected PlayerPositionService $playerPositionService;

    public function __construct(PlayerPositionService $playerPositionService)
    {
        $this->playerPositionService = $playerPositionService;
    }

    public function index()
    {
        return view('player-positions.index', [
            'title' => 'Master Posisi Pemain',
            'breadcrumb' => [
                ['label' => 'Master'],
                ['label' => 'Posisi Pemain'],
            ],
            'playerPositions' => $this->playerPositionService->paginate(),
        ]);
    }

    public function create()
    {
        return view('player-positions.create', [
            'title' => 'Tambah Posisi Pemain',
            'breadcrumb' => [
                ['label' => 'Posisi Pemain', 'url' => route('player-positions.index')],
                ['label' => 'Tambah Posisi'],
            ],
            'existingGroups' => $this->playerPositionService->existingGroups(),
        ]);
    }

    public function store(PlayerPositionFormRequest $request)
    {
        try {

            $this->playerPositionService->create($request->validated());

            return redirect()
                ->route('player-positions.index')
                ->with('success', 'Posisi pemain berhasil ditambahkan.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menambahkan posisi pemain');
        }
    }

    public function edit(PlayerPosition $playerPosition)
    {
        return view('player-positions.edit', [
            'title' => 'Edit Posisi Pemain',
            'breadcrumb' => [
                ['label' => 'Posisi Pemain', 'url' => route('player-positions.index')],
                ['label' => 'Edit Posisi'],
            ],
            'playerPosition' => $playerPosition,
            'existingGroups' => $this->playerPositionService->existingGroups(),
        ]);
    }

    public function update(PlayerPositionFormRequest $request, PlayerPosition $playerPosition)
    {
        try {

            $this->playerPositionService->update($playerPosition, $request->validated());

            return redirect()
                ->route('player-positions.index')
                ->with('success', 'Posisi pemain berhasil diperbarui.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal memperbarui posisi pemain');
        }
    }

    public function destroy(PlayerPosition $playerPosition)
    {
        try {

            $this->playerPositionService->delete($playerPosition);

            return redirect()
                ->route('player-positions.index')
                ->with('success', 'Posisi pemain berhasil dihapus.');

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menghapus posisi pemain', 'player-positions.index');
        }
    }
}
