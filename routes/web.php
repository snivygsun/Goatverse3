<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureNotBanned;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('landing'))->name('home');
Route::get('/u/{username}', [ProfileController::class, 'show'])->name('profile.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
});

Route::middleware(['auth', EnsureNotBanned::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');
    Route::post('/games/{game}/play', [GameController::class, 'play'])->middleware('throttle:30,1')->name('games.play');
    Route::post('/games/{game}/claim', [GameController::class, 'claim'])->middleware('throttle:10,1')->name('games.claim');

    Route::prefix('admin')->name('admin.')->middleware(EnsureStaff::class)->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}', [AdminController::class, 'user'])->name('user');
        Route::post('/users/{user}/adjust', [AdminController::class, 'adjust'])->name('adjust');
        Route::post('/users/{user}/rewards/{reward}/revoke', [AdminController::class, 'revoke'])->name('revoke');
        Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
    });
});
