<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlayerService
{
    protected AcademyService $academyService;
    protected AccountService $accountService;

    public function __construct(AcademyService $academyService, AccountService $accountService)
    {
       $this->academyService = $academyService;
       $this->accountService = $accountService;
    }

    protected function uploadPhoto($file, string $playerCode): string
    {
        $filename = $playerCode . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs(
            'players',
            $filename,
            'public'
        );
    }

    protected function deletePhoto(?string $photo): void
    {
        if ($photo && Storage::disk('public')->exists($photo)) {
            Storage::disk('public')->delete($photo);
        }
    }


    /**
     * Tentukan academy pemilik player baru.
     *
     * Super Admin : dari pilihan academy di form (wajib, divalidasi StorePlayerRequest).
     * User academy: selalu dari academy miliknya sendiri, input form diabaikan.
     */
    protected function resolveAcademy(array $data): Academy
    {
        if ($this->academyService->isSuperAdmin()) {

            $academy = Academy::find($data['id_academy'] ?? null);

            if (!$academy) {
                throw new \Exception('Academy tidak ditemukan.');
            }

            return $academy;
        }

        $academy = $this->academyService->current();

        if (!$academy) {
            throw new \Exception('Academy tidak ditemukan.');
        }

        return $academy;
    }


    /*
    |--------------------------------------------------------------------------
    | Create Player
    |--------------------------------------------------------------------------
    */
    public function create(array $data): Player
    {
        return DB::transaction(function () use ($data) {

            $academy = $this->resolveAcademy($data);

            $playerCode = $this->generatePlayerCode($academy);

            $photo = null;

            if (!empty($data['photo'])) {
                $photo = $this->uploadPhoto(
                    $data['photo'],
                    $playerCode
                );
            }

            $player = Player::create([
                'id_academy' => $academy->id_academy,
                'id_player_type' => $data['id_player_type'],
                'id_player_category' => $data['id_player_category'],
                'player_code' => $playerCode,
                'name' => $data['name'],
                'nick_name' => $data['nick_name'] ?? null,
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'preferred_foot' => $data['preferred_foot'] ?? null,
                'id_primary_position' => $data['id_primary_position'],
                'id_secondary_position' => $data['id_secondary_position'] ?? null,
                'join_date' => $data['join_date'] ?? now(),
                'status' => $data['status'] ?? 'active',
                'photo' => $photo,
                'notes' => $data['notes'] ?? null,
            ]);


            if (!empty($data['create_account'])) {

                $user = $this->accountService->create([
                    'id_academy'=>$player->id_academy,
                    'name'=>$player->name,
                    'email'=>$data['email'],
                    'password'=>$data['password'],
                ],'Player');

                $player->update([
                    'id_user'=>$user->id_user
                ]);
            }


            return $player;

        });
    }
 
 
    /*
    |--------------------------------------------------------------------------
    | Generate Player Code
    |--------------------------------------------------------------------------
    */
    protected function generatePlayerCode(Academy $academy): string
    {
        $prefix = strtoupper($academy->code) . now()->format('y');

        $lastPlayer = Player::withoutGlobalScopes()
            ->where('id_academy',$academy->id_academy)
            ->where('player_code','like',$prefix.'%')
            ->orderByDesc('player_code')
            ->lockForUpdate()
            ->first();


        $number = $lastPlayer ? ((int) substr($lastPlayer->player_code,-5)) + 1 : 1;

        do {

            $code = $prefix . str_pad( $number, 5, '0', STR_PAD_LEFT );

            $number++;

        } while (
            Player::withoutGlobalScopes()
                ->where('player_code',$code)
                ->exists()
        );


        return $code;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Player
    |--------------------------------------------------------------------------
    */
    public function update(Player $player, array $data): Player
    {
        return DB::transaction(function () use ($player,$data) {

            $oldPhoto = $player->photo;
            $newPhoto = $oldPhoto;


            if (!empty($data['photo'])) {

                $newPhoto = $this->uploadPhoto(
                    $data['photo'],
                    $player->player_code
                );
            }


            $player->update([
                'id_player_type'=>$data['id_player_type'],
                'id_player_category'=>$data['id_player_category'],
                'name'=>$data['name'],
                'nick_name'=>$data['nick_name'] ?? null,
                'birth_date'=>$data['birth_date'],
                'gender'=>$data['gender'],
                'nationality'=>$data['nationality'] ?? 'Indonesia',
                'height'=>$data['height'] ?? null,
                'weight'=>$data['weight'] ?? null,
                'preferred_foot'=>$data['preferred_foot'] ?? null,
                'id_primary_position'=>$data['id_primary_position'],
                'id_secondary_position'=>$data['id_secondary_position'] ?? null,
                'status'=>$data['status'] ?? 'active',
                'photo'=>$newPhoto,
                'notes'=>$data['notes'] ?? null,
            ]);


            if (!empty($data['photo']) && $oldPhoto) {
                $this->deletePhoto($oldPhoto);
            }


            return $player;

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Player
    |--------------------------------------------------------------------------
    */
    public function delete(Player $player): bool
    {
        return DB::transaction(function () use ($player) {

            if ($player->photo) {
                $this->deletePhoto($player->photo);
            }


            if ($player->id_user) {

                User::where(
                    'id_user',
                    $player->id_user
                )->delete();

            }

            return $player->delete();

        });
    }
}