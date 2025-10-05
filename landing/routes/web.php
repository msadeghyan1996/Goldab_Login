<?php

use App\Livewire\Auth\AuthPage;
use App\Livewire\Auth\DashboardPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hadi', function () {
    return "Hadi2345";
});
Route::get('/auth', AuthPage::class)->name('auth');
Route::get('/dashboard', DashboardPage::class)->name('dashboard');
