<?php

namespace App\Services;

use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class PlayerService
{

    protected AcademyService $academyService;


    public function __construct(AcademyService $academyService) {
        
        $this->academyService = $academyService;
    }


    /*
    |--------------------------------------------------------------------------
    | Create Player
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Player
    {

        return DB::transaction(function () use ($data) {


            /*
            |--------------------------------------------------------------------------
            | Create Player
            |--------------------------------------------------------------------------
            */

            $player = Player::create([

                'player_code' => $this->generatePlayerCode(),
                'name' => $data['name'],
                'nick_name' => $data['nick_name'] ?? null,
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'nationality' => $data['nationality']   ?? 'Indonesia',
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'preferred_foot' => $data['preferred_foot'] ?? null,
                'primary_position' => $data['primary_position'],
                'secondary_position' => $data['secondary_position'] ?? null,
                'join_date' => $data['join_date'] ?? now(),
                'status' => $data['status'] ?? 'active',
                'photo' => $data['photo'] ?? null,
                'notes' => $data['notes'] ?? null,

            ]);



            /*
            |--------------------------------------------------------------------------
            | Create Player Account Optional
            |--------------------------------------------------------------------------
            */
                
            if (!empty($data['create_account']) && $data['create_account'] === true) {

                $user = $this->createPlayerAccount( $player, $data );

                $player->update([
                    'id_user' => $user->id_user
                ]);

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

        $academy = $this->academyService->current();

        if (!$academy) {
            throw new \Exception(
                'Academy tidak ditemukan.'
            );
        }

        $year = now()->format('Y');
        $lastPlayer = Player::withoutGlobalScopes()
            ->where(
                'id_academy',
                $academy->id_academy
            )
            ->whereYear(
                'created_at',
                $year
            )
            ->count();

        $number = str_pad($lastPlayer + 1, 5, '0', STR_PAD_LEFT );

        return strtoupper($academy->code) .'-' .$year .'-' .$number;

    }


}