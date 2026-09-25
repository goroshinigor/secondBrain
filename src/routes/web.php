<?php

use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PromptController::class, 'index'])->name('home');
Route::post('prompt', [PromptController::class, 'store'])->name('prompt.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
