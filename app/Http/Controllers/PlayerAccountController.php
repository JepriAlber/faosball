<?php

namespace App\Http\Controllers;

use App\Http\Requests\Players\StorePlayerAccountRequest;
use App\Http\Requests\Players\UpdatePlayerAccountRequest;
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

    


    public function edit(Player $player)
    {
        if (!$player->user) {
            return redirect()
                ->route('players.show',$player)
                ->with('error','Player belum memiliki akun.');
        }

        return view('players.account.edit',[
            'title'=>'Edit Akun Player',
            'player'=>$player,
            'user'=>$player->user,
            'breadcrumb'=>[
                [
                    'label'=>'Players',
                    'url'=>route('players.index')
                ],
                [
                    'label'=>$player->name,
                    'url'=>route('players.show',$player)
                ],
                [
                    'label'=>'Edit Account'
                ]
            ],
        ]);
    }


    public function update(UpdatePlayerAccountRequest $request,Player $player)
    {
        try {

            if (!$player->user) {
                return redirect()
                    ->route('players.show',$player)
                    ->with('error','Player belum memiliki akun.');
            }

            $this->accountService->update(
                $player->user,
                $request->validated()
            );

            return redirect()
                ->route('players.show',$player)
                ->with(
                    'success',
                    'Account player berhasil diperbarui.'
                );

        } catch(\Exception $e){

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal update account: '.$e->getMessage()
                );

        }
    }

    public function password(Player $player)
    {
        try {

            if (!$player->user) {
                return redirect()
                    ->route('players.show',$player)
                    ->with('error','Player belum memiliki akun.');
            }


            $newPassword = str()->random(8);


            $this->accountService->resetPassword(
                $player->user,
                $newPassword
            );


            return redirect()
                ->route('players.show',$player)
                ->with(
                    'success',
                    'Password berhasil direset. Password baru: '.$newPassword
                );


        } catch(\Exception $e){

            return redirect()
                ->route('players.show',$player)
                ->with(
                    'error',
                    'Gagal reset password: '.$e->getMessage()
                );

        }
    }
}