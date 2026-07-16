<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Super Admin tidak pernah sampai ke sini — Gate::before() di
     * AppServiceProvider:25 sudah mengembalikan true lebih dulu.
     */
    public function view(User $user, Role $role): bool
    {
        return $this->sameAcademy($user, $role);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->sameAcademy($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->sameAcademy($user, $role);
    }

    /**
     * Role System (id_academy null) hanya boleh disentuh Super Admin,
     * yang sudah lolos lebih dulu lewat Gate::before().
     */
    protected function sameAcademy(User $user, Role $role): bool
    {
        return $role->id_academy !== null
            && $role->id_academy === $user->id_academy;
    }
}
