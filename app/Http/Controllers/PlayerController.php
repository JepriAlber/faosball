<?php

namespace App\Http\Controllers;

use App\Http\Requests\Players\StorePlayerRequest;
use App\Http\Requests\Players\UpdatePlayerRequest;
use App\Models\Academy;
use App\Models\Player;
use App\Services\AcademyService;
use App\Services\PlayerService;
use App\Services\PlayerTypeService;

class PlayerController extends Controller
{
    protected PlayerService $playerService;
    protected AcademyService $academyService;
    protected PlayerTypeService $playerTypeService;

    public function __construct(
        PlayerService $playerService,
        AcademyService $academyService,
        PlayerTypeService $playerTypeService
    ) {
        $this->playerService = $playerService;
        $this->academyService = $academyService;
        $this->playerTypeService = $playerTypeService;
    }

    public function index()
    {
        return view('players.index',[
            'title'=>'Players',
            'breadcrumb'=>[
                [
                    'label'=>'Players'
                ]
            ],
            'players'=>Player::with('playerType')->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return view('players.create',[
            'title'=>'Create Player',
            'breadcrumb'=>[
                [
                    'label'=>'Players',
                    'url'=>route('players.index')
                ],
                [
                    'label'=>'Create'
                ]
            ],
            'isSuperAdmin'=>$this->academyService->isSuperAdmin(),
            'academies'=>$this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
            // Super Admin: seluruh academy (difilter di sisi Alpine mengikuti academy
            // yang dipilih). User academy: cukup type miliknya sendiri.
            'playerTypes' => $this->playerTypeService->selectable(
                $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId()
            ),
        ]);
    }

    public function store(StorePlayerRequest $request)
    {
        try {

            $this->playerService->create(
                $request->validated()
            );

            return redirect()
                ->route('players.index')
                ->with(
                    'success',
                    'Player berhasil dibuat.'
                );

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal membuat player');
        }
    }

    public function show(Player $player)
    {
        $player->load([
            'academy',
            'playerType',
            'user.roles'
        ]);

        return view('players.show',[
            'title'=>'Detail Player',
            'breadcrumb'=>[
                [
                    'label'=>'Players',
                    'url'=>route('players.index')
                ],
                [
                    'label'=>'Detail Player'
                ]
            ],
            'player'=>$player
        ]);
    }

    public function edit(Player $player)
    {
        return view('players.edit',[
            'title'=>'Edit Player',
            'breadcrumb'=>[
                [
                    'label'=>'Players',
                    'url'=>route('players.index')
                ],
                [
                    'label'=>'Edit'
                ]
            ],
            'player'=>$player,
            'isSuperAdmin'=>$this->academyService->isSuperAdmin(),
            // Academy player tidak berubah, jadi cukup type milik academy player itu.
            // includeId dipakai supaya type yang sudah dinonaktifkan tetap muncul kalau
            // player ini memang sedang memakainya.
            'playerTypes' => $this->playerTypeService->selectable(
                $player->id_academy,
                $player->id_player_type
            ),
        ]);
    }

    public function update(UpdatePlayerRequest $request, Player $player)
    {
        try {

            $this->playerService->update(
                $player,
                $request->validated()
            );

            return redirect()
                ->route('players.index')
                ->with(
                    'success',
                    'Player berhasil diperbarui.'
                );

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal memperbarui player');
        }
    }


    public function destroy(Player $player)
    {
        try {

            $this->playerService->delete(
                $player
            );

            return redirect()
                ->route('players.index')
                ->with(
                    'success',
                    'Player berhasil dihapus.'
                );

        } catch (\Exception $e) {

            return $this->handleException($e, 'Gagal menghapus player', 'players.index');
        }
    }

}