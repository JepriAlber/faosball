<?php

namespace App\Services;

use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PlayerService
{

    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService) {
        
        $this->academyService = $academyService;
    }

    /**
     * hendel upload foto player
     * 
     * @param $file
     * 
     * @return string
     */
    protected function uploadPhoto($file, string $playerCode): string
    {
        $filename = $playerCode . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs(
            'players',
            $filename,
            'public'
        );
    }

    /**
     * hendel delete foto players 
     */
    protected function deletePhoto(?string $photo): void
    {
        if ($photo && Storage::disk('public')->exists($photo)) {
            Storage::disk('public')->delete($photo);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Player
    |--------------------------------------------------------------------------
    */
    public function create(array $data): Player
    {
        return DB::transaction(function () use ($data) {

            /* Create Player */
            $playerCode = $this->generatePlayerCode();

            $photo = null;
            if (isset($data['photo'])) {
                $photo = $this->uploadPhoto($data['photo'], $playerCode);
            }

            $player = Player::create([
                'player_code' => $playerCode,
                'name' => $data['name'],
                'nick_name' => $data['nick_name'] ?? null,
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'preferred_foot' => $data['preferred_foot'] ?? null,
                'primary_position' => $data['primary_position'],
                'secondary_position' => $data['secondary_position'] ?? null,
                'join_date' => $data['join_date'] ?? now(),
                'status' => $data['status'] ?? 'active',
                'photo' => $photo,
                'notes' => $data['notes'] ?? null,
            ]);

            /* Create Player Account */
            if (!empty($data['create_account'])) {
                $user = $this->createPlayerAccount($player, $data);
                $player->update(['id_user' => $user->id_user]);
            }

            return $player;
        });
    }



    /*
    |--------------------------------------------------------------------------
    | Create Player User Account
    |--------------------------------------------------------------------------
    */

    protected function createPlayerAccount( Player $player, array $data): User {
        $user = User::create([
            'id_academy' => $player->id_academy,
            'name' => $player->name,
            'email' => $data['email'],
            'password' => Hash::make(
                $data['password']
            ),
            'status' => true,
        ]);

        $user->assignRole('Player');

        return $user;

    }





    /*
    |--------------------------------------------------------------------------
    | Generate Player Code
    |--------------------------------------------------------------------------
    */

    protected function generatePlayerCode(): string
    {
        $academy=$this->academyService->current();

        if(!$academy){
            throw new \Exception('Academy tidak ditemukan.');
        }

        $prefix=strtoupper($academy->code).now()->format('y');

        $lastPlayer=Player::withoutGlobalScopes()
            ->where('id_academy',$academy->id_academy)
            ->where('player_code','like',$prefix.'%')
            ->orderByDesc('player_code')
            ->lockForUpdate()
            ->first();

        $number=1;

        if($lastPlayer){
            $number=((int)substr($lastPlayer->player_code,-5))+1;
        }

        $code=$prefix.str_pad($number,5,'0',STR_PAD_LEFT);

        if(Player::withoutGlobalScopes()->where('player_code',$code)->exists()){
            return $this->generatePlayerCode();
        }

        return $code;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Player
    |--------------------------------------------------------------------------
    */
    public function update(Player $player, array $data): Player
    {
        return DB::transaction(function () use ($player, $data) {

            $oldPhoto = $player->photo;
            $newPhoto = $oldPhoto;

            if (!empty($data['photo'])) {

                $newPhoto = $this->uploadPhoto(
                    $data['photo'],
                    $player->player_code
                );

            }

            $player->update([
                'name' => $data['name'],
                'nick_name' => $data['nick_name'] ?? null,
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'nationality' => $data['nationality'] ?? 'Indonesia',
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'preferred_foot' => $data['preferred_foot'] ?? null,
                'primary_position' => $data['primary_position'],
                'secondary_position' => $data['secondary_position'] ?? null,
                'status' => $data['status'] ?? 'active',
                'photo' => $newPhoto,
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($data['photo']) && $oldPhoto) {

                if (Storage::disk('public')->exists($oldPhoto)) {
                    Storage::disk('public')->delete($oldPhoto);
                }

            }

            return $player;
        });
    }


}