<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Academy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
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
        ]);

        $academyOwner = Role::firstOrCreate([
            'name' => 'Academy Owner',
            'guard_name' => 'web',

        ]);

        $academyAdmin = Role::firstOrCreate([
            'name' => 'Academy Admin',
            'guard_name' => 'web',
        ]);

        $coach = Role::firstOrCreate([
            'name' => 'Coach',
            'guard_name' => 'web',
        ]);

        $player = Role::firstOrCreate([
            'name' => 'Player',
            'guard_name' => 'web',
        ]);

        $parent = Role::firstOrCreate([
            'name' => 'Parent',
            'guard_name' => 'web',
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

                'id_academy' => Str::uuid(),
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
                'id_user' => Str::uuid(),
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
                'id_user' => Str::uuid(),
                'id_academy' => $academy->id_academy,
                'name' => 'Owner FAOS Academy',
                'password' => Hash::make('password'),
                'status' => true,
            ]

        );


        $academyOwnerUser->assignRole($academyOwner);

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
                'id_user' => Str::uuid(),
                'id_academy' => $academy->id_academy,
                'name' => 'Admin FAOS Academy',
                'password' => Hash::make('password'),
                'status' => true,

            ]

        );

        $academyAdminUser->assignRole($academyAdmin);

    }
}