<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountService
{

    public function create(array $data,string $role): User
    {
        return DB::transaction(function () use ($data,$role){

            $user = User::create([
                'id_academy'=>$data['id_academy'],
                'name'=>$data['name'],
                'email'=>$data['email'],
                'password'=>Hash::make($data['password']),
                'status'=>true,
            ]);


            $this->assignRole($user,$role);


            return $user;

        });
    }


    public function update(User $user,array $data): User
    {
        $user->update([
            'name'=>$data['name'],
            'email'=>$data['email'],
        ]);


        return $user;
    }


    public function resetPassword(User $user,string $password): User
    {
        $user->update([
            'password'=>Hash::make($password),
        ]);


        return $user;
    }


    public function generatePassword(int $length = 8): string
    {
        return Str::random($length);
    }


    public function changeStatus(User $user,bool $status): User
    {
        $user->update([
            'status'=>$status,
        ]);


        return $user;
    }


    public function assignRole(User $user,string $role): User
    {
        $user->syncRoles([
            $role
        ]);


        return $user;
    }

}