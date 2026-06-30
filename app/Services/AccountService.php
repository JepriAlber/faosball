<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountService
{
    public function create(array $data, string $role): User
    {
        return DB::transaction(function () use ($data,$role) {

            $user = User::create([
                'id_academy'=>$data['id_academy'],
                'name'=>$data['name'],
                'email'=>$data['email'],
                'password'=>Hash::make($data['password']),
                'status'=>true,
            ]);

            $user->assignRole($role);

            return $user;

        });
    }


    public function update(User $user,array $data): User
    {
        return DB::transaction(function () use ($user,$data) {

            $user->update([
                'name'=>$data['name'],
                'email'=>$data['email'],
            ]);

            return $user;

        });
    }


    public function updatePassword(User $user,string $password): bool
    {
        return $user->update([
            'password'=>Hash::make($password)
        ]);
    }


    public function changeStatus(User $user,bool $status): bool
    {
        return $user->update([
            'status'=>$status
        ]);
    }
}