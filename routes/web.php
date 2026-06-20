<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\ShuffleController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamNameController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Roster is the home of the app for now (auth gating comes in milestone 6).
Route::redirect('/', '/roster');

Route::controller(PlayerController::class)->group(function () {
    Route::get('/roster', 'index')->name('roster.index');
    Route::post('/roster', 'store')->name('roster.store');
    Route::patch('/roster/{player}', 'update')->name('roster.update');
    Route::delete('/roster/{player}', 'destroy')->middleware('admin')->name('roster.destroy');
    Route::post('/roster/present/all', 'markAllPresent')->name('roster.present.all');
    Route::post('/roster/present/clear', 'clearPresent')->name('roster.present.clear');
});

// Hidden admin gate (intentionally not in the nav).
Route::controller(AdminController::class)->group(function () {
    Route::get('/admin/login', 'show')->name('admin.login');
    Route::post('/admin/login', 'login')->name('admin.login.attempt');
    Route::post('/admin/logout', 'logout')->name('admin.logout');
});

Route::controller(ShuffleController::class)->group(function () {
    Route::get('/shuffle', 'index')->name('shuffle.index');
    Route::post('/shuffle', 'run')->name('shuffle.run');
});

Route::controller(SeriesController::class)->group(function () {
    Route::post('/series/start', 'start')->name('series.start');
    Route::post('/series/reroll', 'reroll')->name('series.reroll');
    Route::post('/series/reset', 'reset')->name('series.reset');
});

Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
Route::post('/matches', [MatchController::class, 'store'])->name('matches.store');

Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

Route::patch('/team-names/{slot}', [TeamNameController::class, 'update'])
    ->whereNumber('slot')->name('team-names.update');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
