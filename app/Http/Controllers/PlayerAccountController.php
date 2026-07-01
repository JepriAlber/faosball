<?php

namespace App\Http\Controllers;

use App\Http\Requests\Players\StorePlayerAccountRequest;
use App\Models\Player;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;

class PlayerAccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService=$accountService;
    }

    public function create(Player $player)
    {
        if ($player->id_user) {
            return redirect()
                ->route('players.index')
                ->with('error','Player sudah memiliki akun.');
        }

        return view('players.account.create',[
            'title'=>'Buat Akun Player',
            'player'=>$player,
            'breadcrumb'=>[
                [
                    'label'=>'Players',
                    'url'=>route('players.index')
                ],
                [
                    'label'=>'Buat Akun'
                ]
            ],
        ]);
    }

    public function store(StorePlayerAccountRequest $request,Player $player)
    {
        try {

            if ($player->id_user) {
                return redirect()
                    ->route('players.index')
                    ->with('error','Player sudah memiliki akun.');
            }

            DB::transaction(function() use ($request,$player){

                $user=$this->accountService->create([
                    'id_academy'=>$player->id_academy,
                    'name'=>$player->name,
                    'email'=>$request->email,
                    'password'=>$request->password,
                ],'Player');

                $player->update([
                    'id_user'=>$user->id_user
                ]);
            });

            return redirect()
                ->route('players.index')
                ->with(
                    'success',
                    'Akun player berhasil dibuat.'
                );

        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal membuat akun player: '.$e->getMessage()
                );
        }
    }
}