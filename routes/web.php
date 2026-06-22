<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academy\AcademyController;

Route::resource('academy', AcademyController::class);

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');

Route::get('/profile', function () {
    return view('profil-test.index');
})->name('profile');