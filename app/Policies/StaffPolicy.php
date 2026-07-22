<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    /**
     * Lihat nominal gaji staff ini secara utuh (bukan tersamar "*****").
     *
     * User dengan permission salary.view melihat gaji SIAPAPUN. User TANPA
     * permission itu cuma boleh melihat gajinya SENDIRI -- staff.id_user
     * sama dengan user yang sedang login. Super Admin tidak pernah sampai
     * ke method ini -- sudah lolos lebih dulu lewat Gate::before()
     * (AppServiceProvider), sama seperti RolePolicy.
     */
    public function viewSalary(User $user, Staff $staff): bool
    {
        if ($user->can('salary.view')) {
            return true;
        }

        return $staff->id_user !== null && $staff->id_user === $user->id_user;
    }
}
