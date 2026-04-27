<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\DonorAuthController;
use App\Http\Controllers\BloodBankLocationController;
use App\Http\Controllers\BloodInventoryController;
use App\Http\Controllers\BloodReleaseController;
use App\Http\Controllers\BloodlettingRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationRecordController;
use App\Http\Controllers\DonationScheduleController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\DonorEventRegistrationController;
use App\Http\Controllers\DonorPortalController;
use App\Http\Controllers\FacilityApplicationController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffUserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/portal/map');
Route::get('/portal', [PublicPortalController::class, 'index'])->name('public.index');
Route::redirect('/portal/events', '/portal/map')->name('public.events');
Route::get('/portal/map', [PublicPortalController::class, 'map'])->name('public.map');
Route::get('/portal/availability', [PublicPortalController::class, 'availability'])->name('public.availability');
Route::get('/facility/apply', [FacilityApplicationController::class, 'create'])->name('facility-application.create');
Route::post('/facility/apply', [FacilityApplicationController::class, 'store'])
    ->middleware('throttle:facility-apply')
    ->name('facility-application.store');

Route::middleware('guest:web,donor')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::prefix('donor')->group(function () {
    Route::middleware('guest:web,donor')->group(function () {
        Route::get('/login', fn () => redirect()->route('login'))->name('donor.login');
        Route::post('/login', fn () => redirect()->route('login'))->name('donor.login.store');
        Route::get('/register', [DonorAuthController::class, 'showRegister'])->name('donor.register');
        Route::post('/register', [DonorAuthController::class, 'register'])
            ->middleware('throttle:donor-register')
            ->name('donor.register.store');
    });

    Route::middleware('auth:donor')->group(function () {
        Route::get('/portal/profile', [DonorPortalController::class, 'profile'])->name('donor.portal.profile');
        Route::put('/portal/profile', [DonorPortalController::class, 'update'])->name('donor.portal.profile.update');
        Route::get('/events', [DonorEventRegistrationController::class, 'index'])->name('donor.events.index');
        Route::get('/events/{donationSchedule}/join', [DonorEventRegistrationController::class, 'join'])->name('donor.events.join');
        Route::post('/events/{donationSchedule}/register', [DonorEventRegistrationController::class, 'store'])->name('donor.events.register');
        Route::delete('/events/{donationSchedule}/register', [DonorEventRegistrationController::class, 'destroy'])->name('donor.events.cancel');
    });
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth:web,donor')
    ->name('logout');

Route::middleware('auth:web,donor')->group(function () {
    Route::get('/change-password', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/change-password', [PasswordController::class, 'update'])
        ->middleware('throttle:password-change')
        ->name('password.update');
});

Route::middleware(['auth', 'facility.access'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('facilities', FacilityController::class)
        ->middleware('central.control');

    Route::get('/staff-users', [StaffUserController::class, 'index'])
        ->middleware('role_or_permission:Super Administrator|manage users')
        ->name('staff-users.index');
    Route::get('/staff-users/create', [StaffUserController::class, 'create'])
        ->middleware(['permission:manage users', 'facility.operator'])
        ->name('staff-users.create');
    Route::post('/staff-users', [StaffUserController::class, 'store'])
        ->middleware(['permission:manage users', 'facility.operator'])
        ->name('staff-users.store');

    Route::get('/facility-applications', [FacilityApplicationController::class, 'index'])
        ->middleware('central.control')
        ->name('facility-applications.index');
    Route::get('/facility-applications/{facilityApplication}', [FacilityApplicationController::class, 'show'])
        ->middleware('central.control')
        ->name('facility-applications.show');
    Route::get('/facility-applications/{facilityApplication}/proof/{type}', [FacilityApplicationController::class, 'proof'])
        ->middleware('central.control')
        ->whereIn('type', ['legitimacy', 'doh'])
        ->name('facility-applications.proof');
    Route::put('/facility-applications/{facilityApplication}/review', [FacilityApplicationController::class, 'review'])
        ->middleware('central.control')
        ->name('facility-applications.review');

    Route::resource('donors', DonorController::class)
        ->except(['index', 'show'])
        ->middleware(['permission:manage donors', 'facility.operator']);
    Route::resource('donors', DonorController::class)
        ->only(['index', 'show'])
        ->middleware('role_or_permission:Super Administrator|manage donors');

    Route::resource('donation-records', DonationRecordController::class)
        ->except(['index', 'show'])
        ->middleware(['permission:manage donation records', 'facility.operator']);
    Route::resource('donation-records', DonationRecordController::class)
        ->only(['index', 'show'])
        ->middleware('role_or_permission:Super Administrator|manage donation records');

    Route::resource('bloodletting-records', BloodlettingRecordController::class)
        ->except(['index', 'show'])
        ->middleware(['permission:manage bloodletting records', 'facility.operator']);
    Route::resource('bloodletting-records', BloodlettingRecordController::class)
        ->only(['index', 'show'])
        ->middleware('role_or_permission:Super Administrator|manage bloodletting records');

    Route::resource('blood-inventory', BloodInventoryController::class)
        ->except(['index', 'show'])
        ->middleware(['permission:manage inventory', 'facility.operator']);
    Route::resource('blood-inventory', BloodInventoryController::class)
        ->only(['index', 'show'])
        ->middleware('role_or_permission:Super Administrator|manage inventory');

    Route::resource('blood-releases', BloodReleaseController::class)
        ->except(['index', 'show'])
        ->middleware(['permission:manage blood releases', 'facility.operator']);
    Route::resource('blood-releases', BloodReleaseController::class)
        ->only(['index', 'show'])
        ->middleware('role_or_permission:Super Administrator|manage blood releases');

    Route::resource('donation-schedules', DonationScheduleController::class)
        ->except(['index', 'show'])
        ->middleware(['permission:manage schedules', 'facility.operator']);
    Route::resource('donation-schedules', DonationScheduleController::class)
        ->only(['index', 'show'])
        ->middleware('role_or_permission:Super Administrator|manage schedules');

    Route::resource('blood-bank-locations', BloodBankLocationController::class)
        ->middleware('role_or_permission:Super Administrator|manage locations');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('role_or_permission:Super Administrator|view reports')
        ->name('reports.index');

    Route::get('/reports/pdf', [ReportController::class, 'pdf'])
        ->middleware('role_or_permission:Super Administrator|view reports')
        ->name('reports.pdf');

    Route::get('/reports/excel', [ReportController::class, 'excel'])
        ->middleware('role_or_permission:Super Administrator|view reports')
        ->name('reports.excel');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->middleware('role_or_permission:Super Administrator|manage inventory')
        ->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->middleware('role_or_permission:Super Administrator|manage inventory')
        ->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->middleware('role_or_permission:Super Administrator|manage inventory')
        ->name('notifications.read-all');
});
