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
    Route::get('/players/{player}/account/create',
        [PlayerAccountController::class, 'create']
    )->name('players.account.create');

    Route::post('/players/{player}/account',
        [PlayerAccountController::class,'store']
    )->name('players.account.store');

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