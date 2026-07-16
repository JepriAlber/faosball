<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Guard
    |--------------------------------------------------------------------------
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    */

    'super_admin_role' => 'Super Admin',

    /*
    |--------------------------------------------------------------------------
    | Default Academy
    |--------------------------------------------------------------------------
    */

    'default_academy_code' => 'ACA',

    /*
    |--------------------------------------------------------------------------
    | Role Template
    |--------------------------------------------------------------------------
    | Role default yang otomatis dibuat untuk setiap academy baru.
    | Seluruh permission di sini WAJIB sudah ada di RolePermissionSeeder.
    */

    'role_templates' => [

        'Owner' => [
            'player.view', 'player.create', 'player.update', 'player.delete',
            'coach.view', 'coach.create', 'coach.update', 'coach.delete',
            'team.view', 'team.create', 'team.update', 'team.delete',
            'training.view', 'training.create', 'training.update', 'training.delete',
            'attendance.view', 'attendance.create', 'attendance.update',
            'evaluation.view', 'evaluation.create', 'evaluation.update',
            'payment.view', 'payment.create', 'payment.update', 'payment.report',
            'report.view', 'report.export',
            'user.view', 'user.create', 'user.update', 'user.delete',
            'role.view', 'role.create', 'role.update', 'role.delete',
        ],

        'Coach' => [
            'player.view',
            'team.view',
            'training.view', 'training.create', 'training.update',
            'attendance.view', 'attendance.create', 'attendance.update',
            'evaluation.view', 'evaluation.create', 'evaluation.update',
        ],

        'Staff' => [
            'player.view', 'player.create', 'player.update',
            'coach.view',
            'team.view',
            'training.view',
            'attendance.view', 'attendance.create', 'attendance.update',
        ],

        'Finance' => [
            'payment.view', 'payment.create', 'payment.update', 'payment.report',
            'report.view', 'report.export',
        ],

        'Player' => [
            'training.view',
            'attendance.view',
            'evaluation.view',
        ],

        'Parent' => [
            'child.profile.view',
            'child.training.view',
            'child.payment.view',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Directory
    |--------------------------------------------------------------------------
    */

    'upload' => [
        'academy' => 'academy',
        'player' => 'players',
        'coach' => 'coaches',
        'staff' => 'staff',
        'parent' => 'parents',
        'team' => 'teams',
        'training' => 'training',
        'documents' => 'documents',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default' => 10,
    ],

];