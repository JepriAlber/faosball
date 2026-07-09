<?php

namespace App\Http\Controllers;

use App\Http\Requests\Players\StorePlayerRequest;
use App\Http\Requests\Players\UpdatePlayerRequest;
use App\Models\Player;
use App\Services\PlayerService;

class PlayerController extends Controller
{
    protected PlayerService $playerService;

    public function __construct(PlayerService $playerService)
    {
        $this->playerService = $playerService;
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
            'players'=>Player::latest()->paginate(10),
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