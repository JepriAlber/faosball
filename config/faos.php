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
            'player_type.view', 'player_type.create', 'player_type.update', 'player_type.delete',
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
    | Player Type Template
    |--------------------------------------------------------------------------
    | Player Type default yang otomatis dibuat untuk setiap academy baru.
    |
    | is_billable = true  -> player dengan type ini ditagih iuran/SPP.
    | is_billable = false -> tidak ditagih.
    |
    | Academy bebas menambah/mengubah type sendiri lewat menu Player Type.
    | Daftar di sini hanya titik awal saat academy dibuat.
    */

    'player_type_templates' => [

        'Reguler' => [
            'description' => 'Pemain reguler yang membayar iuran/SPP penuh.',
            'is_billable' => true,
        ],

        'Beasiswa' => [
            'description' => 'Pemain penerima beasiswa, dibebaskan dari iuran/SPP.',
            'is_billable' => false,
        ],

        'Trial' => [
            'description' => 'Pemain masa percobaan, belum dikenakan iuran/SPP.',
            'is_billable' => false,
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