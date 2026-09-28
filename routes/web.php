<?php

use App\Http\Controllers\CustomerRegistrationController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalLoginController;
use App\Http\Controllers\Portal\PortalPasswordController;
use App\Http\Controllers\Portal\PortalProjectController;
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

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [PortalLoginController::class, 'show'])->name('login');
        Route::post('login', [PortalLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth:customer', 'portal.password'])->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');
        Route::get('password', [PortalPasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [PortalPasswordController::class, 'update'])->name('password.update');
        Route::post('projects', [PortalProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}/download', [PortalProjectController::class, 'download'])->name('projects.download');
        Route::post('logout', [PortalLoginController::class, 'destroy'])->name('logout');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/erp.php';
