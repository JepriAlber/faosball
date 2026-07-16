<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Academy;
use App\Models\Role;
use App\Services\AccountService;
use App\Services\RoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;


class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // Academy
            'academy.view',
            'academy.create',
            'academy.update',
            'academy.delete',
 
            // Role
            'role.view',
            'role.create',
            'role.update',
            'role.delete',

            // Permission
            'permission.view',
            'permission.create',
            'permission.update',
            'permission.delete',

            // User Management
            'user.view',
            'user.create',
            'user.update',
            'user.delete',

            // Player
            'player.view',
            'player.create',
            'player.update',
            'player.delete',

            // Coach
            'coach.view',
            'coach.create',
            'coach.update',
            'coach.delete',

            // Team
            'team.view',
            'team.create',
            'team.update',
            'team.delete',

            // Training
            'training.view',
            'training.create',
            'training.update',
            'training.delete',

            // Attendance
            'attendance.view',
            'attendance.create',
            'attendance.update',

            // Evaluation
            'evaluation.view',
            'evaluation.create',
            'evaluation.update',

            // Payment
            'payment.view',
            'payment.create',
            'payment.update',
            'payment.report',

            // Report
            'report.view',
            'report.export',

            // Parent
            'child.profile.view',
            'child.training.view',
            'child.payment.view', 

        ];



        foreach ($permissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',

            ]);

        }




        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
            'id_academy' => null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Academy FAOS Academy
        |--------------------------------------------------------------------------
        */

        $academy = Academy::firstOrCreate(
            [
                'slug' => 'faos-academy',
            ],

            [

                'id_academy' => (string) Str::uuid(),
                'name' => 'FAOS Academy',
                'code' => 'FAOS',
                'phone' => '081234567890',
                'email' => 'academy@faosball.com',
                'address' => 'FAOS Academy Indonesia',
                'tagline' => 'Football Academy Operating System',
                'status' => true,
                'description' => 'Default academy FAOSBall',

            ]

        );

        app(RoleService::class)->createDefaultRoles($academy);

        $ownerRole = Role::where('id_academy', $academy->id_academy)
            ->where('name', 'Owner')
            ->firstOrFail();

        $staffRole = Role::where('id_academy', $academy->id_academy)
            ->where('name', 'Staff')
            ->firstOrFail();



        /*
        |--------------------------------------------------------------------------
        | Super Admin FAOSBall
        |--------------------------------------------------------------------------
        */

        $superAdminUser = User::firstOrCreate(
            [
                'email' => 'superadmin@faosball.com',
            ],

            [
                'id_user' => (string) Str::uuid(),
                'id_academy' => null,
                'name' => 'Super Admin FAOSBall',
                'password' => Hash::make('password'),
                'status' => true,
            ]
        );


        $superAdminUser->assignRole($superAdmin);
        $superAdmin->syncPermissions(Permission::all());

        /*
        |--------------------------------------------------------------------------
        | Academy Owner
        |--------------------------------------------------------------------------
        */

        $academyOwnerUser = User::firstOrCreate(
            [
                'email' => 'owner@faosacademy.com',
            ],

            [
                'id_user' => (string) Str::uuid(),
                'id_academy' => $academy->id_academy,
                'name' => 'Owner FAOS Academy',
                'password' => Hash::make('password'),
                'status' => true,
            ]

        );


        app(AccountService::class)->assignRole($academyOwnerUser, $ownerRole);

        /*
        |--------------------------------------------------------------------------
        | Academy Admin
        |--------------------------------------------------------------------------
        */

        $academyAdminUser = User::firstOrCreate(
            [
                'email' => 'admin@faosacademy.com',
            ],

            [
                'id_user' => (string) Str::uuid(),
                'id_academy' => $academy->id_academy,
                'name' => 'Admin FAOS Academy',
                'password' => Hash::make('password'),
                'status' => true,

            ]

        );

        app(AccountService::class)->assignRole($academyAdminUser, $staffRole);

    }
}