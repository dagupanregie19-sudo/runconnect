<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);

    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

    // AJAX endpoints for registration steps
    Route::post('/register/check-username', [App\Http\Controllers\Auth\RegisterController::class, 'checkUsername'])->name('register.check-username');
    Route::post('/register/send-code', [App\Http\Controllers\Auth\RegisterController::class, 'sendVerificationCode'])->name('register.send-code');
    Route::post('/register/verify-code', [App\Http\Controllers\Auth\RegisterController::class, 'verifyCode'])->name('register.verify-code');

    // Password Reset Routes
    Route::get('password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/profile', [App\Http\Controllers\DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::put('/dashboard/profile', [App\Http\Controllers\DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::delete('/dashboard/profile', [App\Http\Controllers\DashboardController::class, 'deleteAccount'])->name('dashboard.profile.delete');
    Route::delete('/dashboard/users/{user}', [App\Http\Controllers\DashboardController::class, 'deleteUser'])->name('dashboard.users.delete');
    Route::post('/dashboard/setup-profile', [App\Http\Controllers\DashboardController::class, 'setupProfile'])->name('dashboard.setup-profile');
    Route::get('/dashboard/settings', [App\Http\Controllers\DashboardController::class, 'settings'])->name('dashboard.settings');

    // Location Routes
    Route::get('/api/locations/cities', [App\Http\Controllers\LocationController::class, 'getCities'])->name('locations.cities');
    Route::get('/api/locations/barangays', [App\Http\Controllers\LocationController::class, 'getBarangays'])->name('locations.barangays');

    // Event Routes
    Route::post('/events', [App\Http\Controllers\EventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [App\Http\Controllers\EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [App\Http\Controllers\EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{event}/toggle-status', [App\Http\Controllers\EventController::class, 'toggleStatus'])->name('events.toggle-status');

    // Event Registration Routes
    Route::post('/events/{event}/register', [App\Http\Controllers\EventRegistrationController::class, 'register'])->name('events.register');
    Route::post('/events/{event}/cancel', [App\Http\Controllers\EventRegistrationController::class, 'cancel'])->name('events.cancel');

    // Challenge Routes
    Route::post('/challenges/start', [App\Http\Controllers\ChallengeController::class, 'start'])->name('challenges.start');
    Route::post('/challenges/log', [App\Http\Controllers\ChallengeController::class, 'logRun'])->name('challenges.log');
    Route::post('/challenges/advance', [App\Http\Controllers\ChallengeController::class, 'advance'])->name('challenges.advance');
    Route::post('/challenges/redo', [App\Http\Controllers\ChallengeController::class, 'redo'])->name('challenges.redo');
    Route::post('/challenges/check-expiry', [App\Http\Controllers\ChallengeController::class, 'checkExpiry'])->name('challenges.check-expiry');

    // Live Tracking Routes
    Route::post('/tracking/update', [App\Http\Controllers\TrackingController::class, 'updateLocation'])->name('tracking.update');
    Route::get('/tracking/event/{event}/locations', [App\Http\Controllers\TrackingController::class, 'getLocations'])->name('tracking.locations');
    Route::post('/tracking/emergency', [App\Http\Controllers\TrackingController::class, 'reportEmergency'])->name('tracking.emergency');
    Route::post('/tracking/emergency/{event}/resolve/{user_id}', [App\Http\Controllers\TrackingController::class, 'resolveEmergency'])->name('tracking.emergency.resolve');
});
