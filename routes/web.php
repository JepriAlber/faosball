<?php

use App\Http\Controllers\AcademyAccountController;
use App\Http\Controllers\AcademyController;
use App\Http\Controllers\AcademyProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmploymentContractController;
use App\Http\Controllers\EmploymentTypeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlayerAccountController;
use App\Http\Controllers\PlayerCategoryController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerPositionController;
use App\Http\Controllers\PlayerTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\StaffAccountController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffPositionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamPlayerController;
use App\Http\Controllers\TeamStaffController;
use App\Http\Controllers\TeamStaffPositionController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {

    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');

});

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

 

Route::middleware('auth')->group(function () {


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', function () {

        return view('dashboard');

    })
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Academy Management
    |--------------------------------------------------------------------------
    | academy.* SENGAJA tidak ada di config('faos.role_templates') manapun,
    | termasuk Owner -- modul ini Super-Admin-only. Lihat issue.md Bagian 4.4.
    */
    Route::resource('academies', AcademyController::class)
        ->middlewareFor(['index', 'show'], 'permission:academy.view')
        ->middlewareFor(['create', 'store'], 'permission:academy.create')
        ->middlewareFor(['edit', 'update'], 'permission:academy.update')
        ->middlewareFor('destroy', 'permission:academy.delete');

    /*
    |--------------------------------------------------------------------------
    | Academy Owner Account Management
    |--------------------------------------------------------------------------
    | Sub-resource dari academies.* -- SENGAJA pakai permission academy.update
    | (bukan user.* seperti Player Account), karena Academy Management memang
    | tidak pernah didelegasikan ke role manapun selain Super Admin. Lihat
    | issue2.md Bagian 2d.
    */
    Route::prefix('academies/{academy}/account')
        ->name('academies.account.')
        ->middleware('permission:academy.update')
        ->group(function () {

            Route::get('/create', [AcademyAccountController::class, 'create'])->name('create');
            Route::post('/', [AcademyAccountController::class, 'store'])->name('store');

            Route::get('/edit', [AcademyAccountController::class, 'edit'])->name('edit');
            Route::put('/', [AcademyAccountController::class, 'update'])->name('update');
            Route::patch('/status', [AcademyAccountController::class, 'status'])->name('status');
            Route::patch('/password', [AcademyAccountController::class, 'password'])->name('password');

        });

    /*
    |--------------------------------------------------------------------------
    | Academy Profile (Self-Service, Owner)
    |--------------------------------------------------------------------------
    | Singleton -- TANPA {id}, selalu beroperasi pada academy milik user yang
    | login. BEDA TOTAL dari academies.* (CRUD lintas academy, Super Admin).
    */
    Route::prefix('academy-profile')
        ->name('academy.profile.')
        ->middleware('permission:academy_profile.update')
        ->group(function () {
            Route::get('/', [AcademyProfileController::class, 'edit'])->name('edit');
            Route::patch('/', [AcademyProfileController::class, 'update'])->name('update');
        });

    /*
    |--------------------------------------------------------------------------
    | Player Account Management
    |--------------------------------------------------------------------------
    */ 
    Route::prefix('players/{player}/account')
    ->name('players.account.')
    ->group(function(){

        Route::middleware('permission:user.create')->group(function () {

            Route::get('/create',
                [PlayerAccountController::class,'create']
            )->name('create');

            Route::post('/',
                [PlayerAccountController::class,'store']
            )->name('store');

        });

        Route::middleware('permission:user.update')->group(function () {

            Route::get('/edit',
                [PlayerAccountController::class,'edit']
            )->name('edit');

            Route::put('/',
                [PlayerAccountController::class,'update']
            )->name('update');

            Route::patch('/status',
                [PlayerAccountController::class,'status']
            )->name('status');

            Route::patch('/password',
                [PlayerAccountController::class,'password']
            )->name('password');

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Player Management
    |--------------------------------------------------------------------------
    */
    Route::resource('players', PlayerController::class)
        ->middlewareFor(['index', 'show'], 'permission:player.view')
        ->middlewareFor(['create', 'store'], 'permission:player.create')
        ->middlewareFor(['edit', 'update'], 'permission:player.update')
        ->middlewareFor('destroy', 'permission:player.delete');

    /*
    |--------------------------------------------------------------------------
    | Document (Player) -- reusable Document, lihat issue15.md
    |--------------------------------------------------------------------------
    */
    Route::post('players/{player}/documents', [DocumentController::class, 'storeForPlayer'])
        ->name('players.documents.store')
        ->middleware('permission:player.update');

    /*
    |--------------------------------------------------------------------------
    | Player Type Management
    |--------------------------------------------------------------------------
    */
    Route::resource('player-types', PlayerTypeController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:player_type.view')
        ->middlewareFor(['create', 'store'], 'permission:player_type.create')
        ->middlewareFor(['edit', 'update'], 'permission:player_type.update')
        ->middlewareFor('destroy', 'permission:player_type.delete');

    /*
    |--------------------------------------------------------------------------
    | Player Category Management
    |--------------------------------------------------------------------------
    */
    Route::resource('player-categories', PlayerCategoryController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:player_category.view')
        ->middlewareFor(['create', 'store'], 'permission:player_category.create')
        ->middlewareFor(['edit', 'update'], 'permission:player_category.update')
        ->middlewareFor('destroy', 'permission:player_category.delete');

    /*
    |--------------------------------------------------------------------------
    | Season Management
    |--------------------------------------------------------------------------
    */
    Route::resource('seasons', SeasonController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:season.view')
        ->middlewareFor(['create', 'store'], 'permission:season.create')
        ->middlewareFor(['edit', 'update'], 'permission:season.update')
        ->middlewareFor('destroy', 'permission:season.delete');

    /*
    |--------------------------------------------------------------------------
    | Team Staff Position Management
    |--------------------------------------------------------------------------
    */
    Route::resource('team-staff-positions', TeamStaffPositionController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:team_staff_position.view')
        ->middlewareFor(['create', 'store'], 'permission:team_staff_position.create')
        ->middlewareFor(['edit', 'update'], 'permission:team_staff_position.update')
        ->middlewareFor('destroy', 'permission:team_staff_position.delete');

    /*
    |--------------------------------------------------------------------------
    | Team Management
    |--------------------------------------------------------------------------
    | Reuse permission team.* yang SUDAH ADA di seeder/role template sejak
    | awal (placeholder), BUKAN permission baru -- lihat issue16.md.
    */
    Route::resource('teams', TeamController::class)
        ->middlewareFor(['index', 'show'], 'permission:team.view')
        ->middlewareFor(['create', 'store'], 'permission:team.create')
        ->middlewareFor(['edit', 'update'], 'permission:team.update')
        ->middlewareFor('destroy', 'permission:team.delete');

    /*
    |--------------------------------------------------------------------------
    | Team Player & Team Staff (nested di bawah Team) -- reuse team.update,
    | BUKAN permission baru. TIDAK ADA route destroy -- "keluar tim" adalah
    | leave_date, bukan hapus baris (issue16.md Rule/Aturan Emas).
    |--------------------------------------------------------------------------
    */
    Route::prefix('teams/{team}/players')
        ->name('teams.players.')
        ->middleware('permission:team.update')
        ->group(function () {
            Route::post('/', [TeamPlayerController::class, 'store'])->name('store');
            Route::put('/{teamPlayer}', [TeamPlayerController::class, 'update'])->name('update');
            Route::patch('/{teamPlayer}/leave', [TeamPlayerController::class, 'leave'])->name('leave');
        });

    Route::prefix('teams/{team}/staff')
        ->name('teams.staff.')
        ->middleware('permission:team.update')
        ->group(function () {
            Route::post('/', [TeamStaffController::class, 'store'])->name('store');
            Route::patch('/{teamStaff}/leave', [TeamStaffController::class, 'leave'])->name('leave');
        });

    /*
    |--------------------------------------------------------------------------
    | Employment Type Management
    |--------------------------------------------------------------------------
    */
    Route::resource('employment-types', EmploymentTypeController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:employment_type.view')
        ->middlewareFor(['create', 'store'], 'permission:employment_type.create')
        ->middlewareFor(['edit', 'update'], 'permission:employment_type.update')
        ->middlewareFor('destroy', 'permission:employment_type.delete');

    /*
    |--------------------------------------------------------------------------
    | Staff Position Management
    |--------------------------------------------------------------------------
    */
    Route::resource('staff-positions', StaffPositionController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:staff_position.view')
        ->middlewareFor(['create', 'store'], 'permission:staff_position.create')
        ->middlewareFor(['edit', 'update'], 'permission:staff_position.update')
        ->middlewareFor('destroy', 'permission:staff_position.delete');

    /*
    |--------------------------------------------------------------------------
    | Staff Account Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff/{staff}/account')
    ->name('staff.account.')
    ->group(function () {

        Route::middleware('permission:user.create')->group(function () {

            Route::get('/create', [StaffAccountController::class, 'create'])->name('create');
            Route::post('/', [StaffAccountController::class, 'store'])->name('store');

        });

        Route::middleware('permission:user.update')->group(function () {

            Route::get('/edit', [StaffAccountController::class, 'edit'])->name('edit');
            Route::put('/', [StaffAccountController::class, 'update'])->name('update');
            Route::patch('/status', [StaffAccountController::class, 'status'])->name('status');
            Route::patch('/password', [StaffAccountController::class, 'password'])->name('password');

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Staff Management
    |--------------------------------------------------------------------------
    */
    Route::resource('staff', StaffController::class)
        ->middlewareFor(['index', 'show'], 'permission:staff.view')
        ->middlewareFor(['create', 'store'], 'permission:staff.create')
        ->middlewareFor(['edit', 'update'], 'permission:staff.update')
        ->middlewareFor('destroy', 'permission:staff.delete');

    /*
    |--------------------------------------------------------------------------
    | Employment Contract Management (nested di bawah Staff)
    |--------------------------------------------------------------------------
    | Index (`employment-contracts.index`) TIDAK nested -- daftar lintas-staff,
    | reuse staff.view (bukan staff.update, karena cuma baca, lihat
    | issue14.md). Aksi create/edit/activate/dst TETAP nested di bawah
    | staff/{staff}/contracts/* seperti sebelumnya.
    | TIDAK ADA route destroy -- Contract tidak pernah dihapus (Rule 3).
    | Reuse permission staff.view/staff.create/staff.update, BUKAN permission baru.
    */
    Route::get('employment-contracts', [EmploymentContractController::class, 'index'])
        ->name('employment-contracts.index')
        ->middleware('permission:staff.view');

    Route::prefix('staff/{staff}/contracts')
        ->name('staff.contracts.')
        ->group(function () {

            Route::middleware('permission:staff.update')->group(function () {

                Route::get('/create', [EmploymentContractController::class, 'create'])->name('create');
                Route::post('/', [EmploymentContractController::class, 'store'])->name('store');

                Route::get('/{contract}/edit', [EmploymentContractController::class, 'edit'])->name('edit');
                Route::put('/{contract}', [EmploymentContractController::class, 'update'])->name('update');

                Route::patch('/{contract}/activate', [EmploymentContractController::class, 'activate'])->name('activate');
                Route::patch('/{contract}/complete', [EmploymentContractController::class, 'complete'])->name('complete');
                Route::patch('/{contract}/terminate', [EmploymentContractController::class, 'terminate'])->name('terminate');
                Route::patch('/{contract}/cancel', [EmploymentContractController::class, 'cancel'])->name('cancel');

            });

        });

    /*
    |--------------------------------------------------------------------------
    | Document (Staff) -- reusable Document, lihat issue15.md
    |--------------------------------------------------------------------------
    */
    Route::post('staff/{staff}/documents', [DocumentController::class, 'storeForStaff'])
        ->name('staff.documents.store')
        ->middleware('permission:staff.update');

    /*
    |--------------------------------------------------------------------------
    | Document -- flat routes, otorisasi dinamis lewat DocumentPolicy
    |--------------------------------------------------------------------------
    | TIDAK ada middleware permission di sini SENGAJA -- lihat
    | DocumentController::show()/destroy() yang authorize() manual
    | berdasarkan documentable_type (issue15.md Aturan Emas).
    */
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [
        ProfileController::class,
        'edit'
    ])
    ->name('profile.edit');


    Route::patch('/profile', [
        ProfileController::class,
        'update'
    ])
    ->name('profile.update');


    Route::delete('/profile', [
        ProfileController::class,
        'destroy'
    ])
    ->name('profile.destroy');

    
    Route::resource('roles', RoleController::class)
        ->middlewareFor(['index', 'show'], 'permission:role.view')
        ->middlewareFor(['create', 'store'], 'permission:role.create')
        ->middlewareFor(['edit', 'update'], 'permission:role.update')
        ->middlewareFor('destroy', 'permission:role.delete');

    Route::resource('permissions', PermissionController::class)
        ->except(['edit', 'update'])
        ->middlewareFor(['index', 'show'], 'permission:permission.view')
        ->middlewareFor(['create', 'store'], 'permission:permission.create')
        ->middlewareFor('destroy', 'permission:permission.delete');

    /*
    |--------------------------------------------------------------------------
    | Master: Player Position (global, Super Admin only)
    |--------------------------------------------------------------------------
    */
    Route::resource('player-positions', PlayerPositionController::class)
        ->except(['show'])
        ->middlewareFor('index', 'permission:player_position.view')
        ->middlewareFor(['create', 'store'], 'permission:player_position.create')
        ->middlewareFor(['edit', 'update'], 'permission:player_position.update')
        ->middlewareFor('destroy', 'permission:player_position.delete');

});



require __DIR__.'/auth.php';