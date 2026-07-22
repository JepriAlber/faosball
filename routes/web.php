<?php

use App\Http\Controllers\AcademyAccountController;
use App\Http\Controllers\AcademyController;
use App\Http\Controllers\AcademyProfileController;
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
use App\Http\Controllers\StaffPositionController;
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