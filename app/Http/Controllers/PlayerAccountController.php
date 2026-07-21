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
                ->with('error',__('Player sudah memiliki akun.'));
        }

        return view('players.account.create',[
            'title'=>__('Buat Akun Player'),
            'player'=>$player,
            'breadcrumb'=>[
                [
                    'label'=>__('Players'),
                    'url'=>route('players.index')
                ],
                [
                    'label'=>__('Buat Akun')
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
                    ->with('error',__('Player sudah memiliki akun.'));
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
                    __('Akun player berhasil dibuat.')
                );

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membuat akun player'));
        }
    }


    public function edit(Player $player)
    {
        if (!$player->user) {
            return redirect()
                ->route('players.show',$player)
                ->with('error',__('Player belum memiliki akun.'));
        }

        return view('players.account.edit',[
            'title'=>__('Edit Akun Player'),
            'player'=>$player,
            'user'=>$player->user,
            'breadcrumb'=>[
                [
                    'label'=>__('Players'),
                    'url'=>route('players.index')
                ],
                [
                    'label'=>$player->name,
                    'url'=>route('players.show',$player)
                ],
                [
                    'label'=>__('Edit Account')
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
                    ->with('error',__('Player belum memiliki akun.'));
            }

            $this->accountService->update(
                $player->user,
                $request->validated()
            );

            return redirect()
                ->route('players.show',$player)
                ->with(
                    'success',
                    __('Account player berhasil diperbarui.')
                );

        } catch(\Exception $e){

            return $this->handleException($e, __('Gagal update account'));

        }
    }

    public function password(Player $player)
    {
        try {

            if (!$player->user) {
                return redirect()
                    ->route('players.show',$player)
                    ->with('error',__('Player belum memiliki akun.'));
            }


            $newPassword=$this->accountService->generatePassword();


            $this->accountService->resetPassword(
                $player->user,
                $newPassword
            );


            return redirect()
                ->route('players.show',$player)
                ->with(
                    'success',
                    __('Password berhasil direset. Password baru: ').$newPassword
                );


        } catch(\Exception $e){

            return $this->handleException($e, __('Gagal reset password'), 'players.show', [$player]);

        }
    }

    public function status(Player $player)
    {
        try {

            if (!$player->user) {
                return redirect()
                    ->route('players.show',$player)
                    ->with('error',__('Player belum memiliki akun.'));
            }


            $status = !$player->user->status;


            $this->accountService->changeStatus(
                $player->user,
                $status
            );


            return redirect()
                ->route('players.show',$player)
                ->with(
                    'success',
                    $status
                        ? __('Account player berhasil diaktifkan.')
                        : __('Account player berhasil dinonaktifkan.')
                );


        } catch(\Exception $e){

            return $this->handleException($e, __('Gagal mengubah status account'), 'players.show', [$player]);

        }
    }
}