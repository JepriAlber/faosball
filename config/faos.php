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
            'academy_profile.update',
            'player.view', 'player.create', 'player.update', 'player.delete',
            'player_type.view', 'player_type.create', 'player_type.update', 'player_type.delete',
            'player_category.view', 'player_category.create', 'player_category.update', 'player_category.delete',
            'employment_type.view', 'employment_type.create', 'employment_type.update', 'employment_type.delete',
            'staff_position.view', 'staff_position.create', 'staff_position.update', 'staff_position.delete',
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
            'salary.view',
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
            'salary.view',
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
    | Player Category Template
    |--------------------------------------------------------------------------
    | Kelompok umur default yang otomatis dibuat untuk setiap academy baru.
    |
    | min_age & max_age bersifat INKLUSIF, dan hanya dipakai untuk MENYARANKAN
    | kategori saat menambah player -- bukan aturan yang memaksa. Pemain boleh
    | ditempatkan di kategori yang umurnya di luar rentang ("main naik kelas").
    |
    | Academy bebas menambah/mengubah kategori & rentangnya lewat menu
    | Player Category. Daftar di sini hanya titik awal saat academy dibuat.
    */

    'player_category_templates' => [

        'U-12' => [
            'description' => 'Kelompok umur di bawah 12 tahun.',
            'min_age' => 10,
            'max_age' => 12,
        ],

        'U-15' => [
            'description' => 'Kelompok umur di bawah 15 tahun.',
            'min_age' => 13,
            'max_age' => 15,
        ],

        'U-17' => [
            'description' => 'Kelompok umur di bawah 17 tahun.',
            'min_age' => 16,
            'max_age' => 17,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Employment Type Template
    |--------------------------------------------------------------------------
    | Employment Type default yang otomatis dibuat untuk setiap academy baru.
    | Academy bebas menambah/mengubah lewat menu Employment Type.
    */

    'employment_type_templates' => [

        'Permanent' => ['description' => 'Staff tetap dengan kontrak jangka panjang.'],
        'Contract' => ['description' => 'Staff kontrak dengan jangka waktu tertentu.'],
        'Intern' => ['description' => 'Staff magang/on-the-job training.'],
        'Volunteer' => ['description' => 'Staff sukarelawan, tanpa gaji tetap.'],
        'Part Time' => ['description' => 'Staff paruh waktu.'],
        'Freelance' => ['description' => 'Staff lepas, dibayar per proyek/sesi.'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Staff Position Template
    |--------------------------------------------------------------------------
    | Staff Position default yang otomatis dibuat untuk setiap academy baru.
    |
    | 'default_role' merujuk ke NAMA role di config('role_templates') di atas
    | (Owner/Coach/Staff/Finance/Player/Parent) -- di-resolve jadi role_id
    | (bigint) saat academy baru dibuat, lihat
    | StaffPositionService::createDefaultStaffPositions().
    |
    | Tidak ada role default bernama "Admin" -- posisi "Admin" sengaja
    | dipetakan ke role "Staff" (cakupan izin paling dekat), BUKAN dibiarkan
    | null dan BUKAN bikin role baru.
    */

    'staff_position_templates' => [

        'Head Coach' => [
            'code' => 'HC', 'is_coach' => true, 'default_role' => 'Coach',
            'description' => 'Pelatih kepala, penanggung jawab utama program latihan.',
        ],

        'Assistant Coach' => [
            'code' => 'AC', 'is_coach' => true, 'default_role' => 'Coach',
            'description' => 'Pelatih asisten, membantu Head Coach.',
        ],

        'Goalkeeper Coach' => [
            'code' => 'GK', 'is_coach' => true, 'default_role' => 'Coach',
            'description' => 'Pelatih khusus penjaga gawang.',
        ],

        'Finance Manager' => [
            'code' => 'FM', 'is_coach' => false, 'default_role' => 'Finance',
            'description' => 'Penanggung jawab keuangan academy.',
        ],

        'Finance Staff' => [
            'code' => 'FS', 'is_coach' => false, 'default_role' => 'Finance',
            'description' => 'Staff administrasi keuangan.',
        ],

        'Academy Director' => [
            'code' => 'AD', 'is_coach' => false, 'default_role' => 'Owner',
            'description' => 'Direktur/penanggung jawab academy.',
        ],

        'Admin' => [
            'code' => 'ADM', 'is_coach' => false, 'default_role' => 'Staff',
            'description' => 'Staff administrasi umum academy.',
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Types (per module)
    |--------------------------------------------------------------------------
    | Dipakai dropdown "Jenis Dokumen" di <x-document-manager>. Module baru
    | yang mengintegrasikan Document (issue15.md) tinggal tambah key baru
    | di sini, mis. 'payment' => ['bukti_transfer' => 'Bukti Transfer'].
    */
    'document_types' => [
        'staff' => [
            'ktp' => 'KTP',
            'ijazah' => 'Ijazah',
            'akte' => 'Akte Kelahiran',
            'lainnya' => 'Lainnya',
        ],
        'player' => [
            'akte' => 'Akte Kelahiran',
            'kk' => 'Kartu Keluarga',
            'ijazah' => 'Ijazah',
            'lainnya' => 'Lainnya',
        ],
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