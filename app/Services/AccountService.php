<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountService
{

    public function create(array $data, Role|string $role): User
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


    /**
     * Assign role ke user.
     *
     * Role wajib berasal dari academy yang sama dengan user
     * (atau Role System untuk user tanpa academy).
     */
    public function assignRole(User $user, Role|string $role): User
    {
        $user->syncRoles([
            $this->resolveRole($user, $role),
        ]);

        return $user;
    }

    /**
     * Terjemahkan nama role menjadi baris Role milik academy user.
     *
     * JANGAN diganti dengan Role::findByName(). Method itu mengambil baris
     * pertama yang cocok tanpa peduli academy. Lihat Bagian 4.2.
     */
    protected function resolveRole(User $user, Role|string $role): Role
    {
        if ($role instanceof Role) {

            if ($role->id_academy !== $user->id_academy) {
                throw new \Exception(__('Role tidak berasal dari academy yang sama dengan user.'));
            }

            return $role;
        }

        $query = Role::query()
            ->where('name', $role)
            ->where('guard_name', config('faos.guard'));

        // Perhatikan: where('id_academy', null) tidak pernah cocok di SQL.
        $user->id_academy === null
            ? $query->whereNull('id_academy')
            : $query->where('id_academy', $user->id_academy);

        $resolved = $query->first();

        if (! $resolved) {
            throw new \Exception(__('Role ":role" tidak ditemukan pada academy user.', ['role' => $role]));
        }

        return $resolved;
    }

}