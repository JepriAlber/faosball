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

        $players = Player::latest() ->paginate(10);
        return view('players.index', [
            'players' => $players,
            'title' => 'Players'
        ]); 

    }


    public function create()
    {
        return view(
            'players.create',
            ['title' => 'Tambah Player']
        );
    }


    public function store(StorePlayerRequest $request)
    {

        $this->playerService->create(
            $request->validated()
        );


        return redirect()
            ->route('players.index')
            ->with(
                'success',
                'Player berhasil dibuat.'
            );

    }


    public function edit(Player $player)
    {

        return view(
            'players.edit',
            compact('player')
        );

    }


    public function update(UpdatePlayerRequest $request, Player $player)
    {

        $player->update(
            $request->validated()
        );


        return redirect()
            ->route('players.index')
            ->with(
                'success',
                'Player berhasil diperbarui.'
            );

    }


    public function destroy(Player $player)
    {

        $player->delete();


        return redirect()
            ->route('players.index')
            ->with(
                'success',
                'Player berhasil dihapus.'
            );

    }

    public function createAccount(Player $player)
    {
        return view('players.account.create', [
            'title' => 'Buat Akun Player',
            'player' => $player
        ]);
    }


}