<?php

use App\Http\Controllers\CustomerRegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/about', [PublicSiteController::class, 'about'])->name('about');
Route::get('/services', [PublicSiteController::class, 'services'])->name('services');
Route::get('/portfolio', [PublicSiteController::class, 'portfolio'])->name('portfolio');
Route::get('/contact', [PublicSiteController::class, 'contact'])->name('contact');
Route::get('/thank-you', [PublicSiteController::class, 'thankYou'])->name('thank-you');

Route::middleware('throttle:30,1')->prefix('customer-registration')->name('customer-registration.')->group(function () {
    Route::get('/done', [CustomerRegistrationController::class, 'done'])->name('done');
    Route::get('/{token}', [CustomerRegistrationController::class, 'show'])->name('show');
    Route::post('/{token}', [CustomerRegistrationController::class, 'store'])->name('store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/erp.php';
