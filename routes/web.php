<?php

use App\Http\Controllers\AcademyController;
use App\Http\Controllers\PlayerAccountController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {

    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');

});



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
    */
    Route::resource(
        'academies',
        AcademyController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Player Account Management
    |--------------------------------------------------------------------------
    */ 
    Route::prefix('players/{player}/account')
    ->name('players.account.')
    ->group(function(){

        Route::get('/create',
            [PlayerAccountController::class,'create']
        )->name('create');

        Route::post('/',
            [PlayerAccountController::class,'store']
        )->name('store');
 
        Route::get('/edit',
            [PlayerAccountController::class,'edit']
        )->name('edit');

        Route::put('/',
            [PlayerAccountController::class,'update']
        )->name('update');

        
        // Route::patch('/status',
        //     [PlayerAccountController::class,'status']
        // )->name('status');

        Route::patch('/password',
            [PlayerAccountController::class,'password']
        )->name('password');

    });

    /*
    |--------------------------------------------------------------------------
    | Player Management
    |--------------------------------------------------------------------------
    */ 
    Route::resource(
        'players',
        PlayerController::class
    );

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

});



require __DIR__.'/auth.php';