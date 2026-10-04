<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\CustomerRegistrationController;
use App\Http\Controllers\IdCardVerificationController;
use App\Http\Controllers\ParticipantJoinController;
use App\Http\Controllers\Portal\PortalCertificateController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalIdCardController;
use App\Http\Controllers\Portal\PortalLoginController;
use App\Http\Controllers\Portal\PortalPasswordController;
use App\Http\Controllers\Portal\PortalProjectController;
use App\Http\Controllers\Portal\PortalRescheduleRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPortfolioController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/about', [PublicSiteController::class, 'about'])->name('about');
Route::get('/services', [PublicSiteController::class, 'services'])->name('services');
Route::get('/portfolio', [PublicSiteController::class, 'portfolio'])->name('portfolio');
Route::get('/contact', [PublicSiteController::class, 'contact'])->name('contact');
Route::get('/thank-you', [PublicSiteController::class, 'thankYou'])->name('thank-you');

// A customer's own showcase, reached from the QR code printed on their certificates.
Route::middleware('throttle:60,1')->prefix('p')->name('customer-portfolio.')->group(function () {
    Route::get('/{token}', [PublicPortfolioController::class, 'show'])->name('show');
    Route::get('/projects/{project}/download', [PublicPortfolioController::class, 'download'])->name('projects.download');
});

// Public, login-free page a certificate's QR code points to.
Route::get('/verify/{code}', [CertificateVerificationController::class, 'show'])->middleware('throttle:60,1')->name('certificates.verify');

// Public, login-free page an ID card's QR code points to.
Route::get('/id/{token}', [IdCardVerificationController::class, 'show'])->middleware('throttle:60,1')->name('id-cards.verify');

Route::middleware('throttle:30,1')->prefix('customer-registration')->name('customer-registration.')->group(function () {
    Route::get('/done', [CustomerRegistrationController::class, 'done'])->name('done');
    Route::get('/agreement', [CustomerRegistrationController::class, 'agreement'])->name('agreement');
    Route::get('/status/{token}', [CustomerRegistrationController::class, 'status'])->name('status');
    Route::get('/{token}', [CustomerRegistrationController::class, 'show'])->name('show');
    Route::post('/{token}', [CustomerRegistrationController::class, 'store'])->name('store');
});

Route::middleware('throttle:30,1')->prefix('join')->name('participant.')->group(function () {
    Route::get('/done', [ParticipantJoinController::class, 'done'])->name('done');
    Route::get('/{token}', [ParticipantJoinController::class, 'show'])->name('show');
    Route::post('/{token}', [ParticipantJoinController::class, 'store'])->name('store');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [PortalLoginController::class, 'show'])->name('login');
        Route::post('login', [PortalLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth:customer', 'portal.readonly', 'portal.password'])->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');
        Route::get('id-card', PortalIdCardController::class)->name('id-card');
        Route::get('certificates', [PortalCertificateController::class, 'index'])->name('certificates.index');
        Route::get('certificates/{certificate}', [PortalCertificateController::class, 'show'])->name('certificates.show');
        Route::get('certificates/{certificate}/download', [PortalCertificateController::class, 'download'])->name('certificates.download');
        Route::get('password', [PortalPasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [PortalPasswordController::class, 'update'])->name('password.update');
        Route::post('projects', [PortalProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}/download', [PortalProjectController::class, 'download'])->name('projects.download');
        Route::post('reschedule-requests', [PortalRescheduleRequestController::class, 'store'])->name('reschedule-requests.store');
        Route::delete('reschedule-requests/{rescheduleRequest}', [PortalRescheduleRequestController::class, 'destroy'])->name('reschedule-requests.destroy');
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
