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
            'title' => __('Master Posisi Pemain'),
            'breadcrumb' => [
                ['label' => __('Master')],
                ['label' => __('Posisi Pemain')],
            ],
            'playerPositions' => $this->playerPositionService->paginate(),
        ]);
    }

    public function create()
    {
        return view('player-positions.create', [
            'title' => __('Tambah Posisi Pemain'),
            'breadcrumb' => [
                ['label' => __('Posisi Pemain'), 'url' => route('player-positions.index')],
                ['label' => __('Tambah Posisi')],
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
                ->with('success', __('Posisi pemain berhasil ditambahkan.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menambahkan posisi pemain'));
        }
    }

    public function edit(PlayerPosition $playerPosition)
    {
        return view('player-positions.edit', [
            'title' => __('Edit Posisi Pemain'),
            'breadcrumb' => [
                ['label' => __('Posisi Pemain'), 'url' => route('player-positions.index')],
                ['label' => __('Edit Posisi')],
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
                ->with('success', __('Posisi pemain berhasil diperbarui.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui posisi pemain'));
        }
    }

    public function destroy(PlayerPosition $playerPosition)
    {
        try {

            $this->playerPositionService->delete($playerPosition);

            return redirect()
                ->route('player-positions.index')
                ->with('success', __('Posisi pemain berhasil dihapus.'));

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus posisi pemain'), 'player-positions.index');
        }
    }
}
