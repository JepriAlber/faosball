<?php
 
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');

Route::get('/profile', function () {
    return view('profil-test.index');
})->name('profile');