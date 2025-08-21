<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\ManualNameMatchingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    


    // Manual name matching
    Route::get('settings/manual-name-matching', [ManualNameMatchingController::class, 'index'])->name('settings.manual-name-matching');
    Route::post('settings/manual-name-matching/load', [ManualNameMatchingController::class, 'loadTournament'])->name('settings.manual-name-matching.load');
    Route::post('settings/manual-name-matching/save', [ManualNameMatchingController::class, 'saveNames'])->name('settings.manual-name-matching.save');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
