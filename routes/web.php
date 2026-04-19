<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\SubdivisionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResidentVisitorController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/branding/favicon.png', [BrandingController::class, 'favicon'])->name('branding.favicon');
Route::get('/subdivision-logo/{subdivision}', [SubdivisionController::class, 'logo'])->name('subdivisions.logo');

Route::middleware(['auth', 'password.change'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/visitor-notifications', [DashboardController::class, 'notifications'])->middleware('role:admin')->name('admin.visitor-notifications.index');
    Route::post('/admin/visitor-notifications/read-all', [DashboardController::class, 'markNotificationsRead'])->middleware('role:admin')->name('admin.visitor-notifications.read-all');
    Route::post('/admin/visitor-notifications/read-one', [DashboardController::class, 'markNotificationRead'])->middleware('role:admin')->name('admin.visitor-notifications.read-one');
    Route::delete('/admin/visitor-notifications', [DashboardController::class, 'clearNotifications'])->middleware('role:admin')->name('admin.visitor-notifications.clear-all');

    Route::get('/subdivisions', [SubdivisionController::class, 'index'])->name('subdivisions.index');
    Route::get('/subdivisions/{subdivision}', [SubdivisionController::class, 'show'])->name('subdivisions.show');
    Route::get('/subdivisions/{subdivision}/edit', [SubdivisionController::class, 'edit'])->middleware('role:admin')->name('subdivisions.edit');
    Route::put('/subdivisions/{subdivision}', [SubdivisionController::class, 'update'])->middleware('role:admin')->name('subdivisions.update');
    Route::get('/houses', [HouseController::class, 'index'])->middleware('role:admin')->name('houses.index');
    Route::get('/houses/{house}', [HouseController::class, 'show'])->middleware('role:admin')->name('houses.show');
    Route::post('/houses', [HouseController::class, 'store'])->middleware('role:admin')->name('houses.store');
    Route::put('/houses/{house}', [HouseController::class, 'update'])->middleware('role:admin')->name('houses.update');
    Route::delete('/houses/{house}', [HouseController::class, 'destroy'])->middleware('role:admin')->name('houses.destroy');

    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin')->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('role:admin')->name('users.show');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('role:admin')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('role:admin')->name('users.destroy');
    Route::post('/users/{userId}/restore', [UserController::class, 'restore'])->middleware('role:admin')->name('users.restore');
    Route::delete('/users/{userId}/force', [UserController::class, 'forceDelete'])->middleware('role:admin')->name('users.force-delete');

    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/create', [IncidentController::class, 'create'])->middleware('role:security,staff,resident')->name('incidents.create');
    Route::post('/incidents', [IncidentController::class, 'store'])->middleware('role:security,staff,resident')->name('incidents.store');
    Route::get('/incident-photos/{path}', [IncidentController::class, 'photo'])
        ->where('path', '.*')
        ->name('incidents.photos.show');
    Route::get('/incidents/report/{reportId}', [IncidentController::class, 'showByReportId'])->name('incidents.show-by-report');
    Route::get('/incidents/{incidentId}/qr-card', [IncidentController::class, 'qrCard'])->name('incidents.qr-card');
    Route::get('/incidents/{incidentId}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::post('/incidents/{incidentId}/verify', [IncidentController::class, 'verifyOnScene'])->middleware('role:admin,security,staff')->name('incidents.verify');
    Route::get('/incidents/{incidentId}/edit', [IncidentController::class, 'edit'])->middleware('role:admin,security,staff')->name('incidents.edit');
    Route::put('/incidents/{incidentId}', [IncidentController::class, 'update'])->middleware('role:admin,security,staff')->name('incidents.update');
    Route::delete('/incidents/{incidentId}', [IncidentController::class, 'destroy'])->middleware('role:admin')->name('incidents.destroy');
    Route::post('/incidents/{incidentId}/restore', [IncidentController::class, 'restore'])->middleware('role:admin')->name('incidents.restore');
    Route::delete('/incidents/{incidentId}/force', [IncidentController::class, 'forceDelete'])->middleware('role:admin')->name('incidents.force-delete');
    Route::get('/residents', [ResidentController::class, 'index'])->middleware('role:admin,staff')->name('residents.index');
    Route::get('/residents/{resident}', [ResidentController::class, 'show'])->middleware(['role:admin,staff', 'subdivision'])->name('residents.show');
    Route::post('/residents', [ResidentController::class, 'store'])->middleware('role:admin')->name('residents.store');
    Route::put('/residents/{resident}', [ResidentController::class, 'update'])->middleware(['role:admin', 'subdivision'])->name('residents.update');
    Route::delete('/residents/{resident}', [ResidentController::class, 'destroy'])->middleware(['role:admin', 'subdivision'])->name('residents.destroy');
    Route::get('/residents/{resident}/qr-card', [ResidentController::class, 'qrCard'])->middleware(['role:admin,staff', 'subdivision'])->name('residents.qr-card');
    Route::get('/visitors/{visitorRequest}/id-photo', [VisitorController::class, 'idPhoto'])->middleware('role:security,staff')->name('visitors.id-photo');
    Route::get('/visitors', [VisitorController::class, 'index'])->middleware('role:security,staff')->name('visitors.index');
    Route::get('/visitors/{visitor}', [VisitorController::class, 'show'])->middleware(['role:security,staff', 'subdivision'])->name('visitors.show');
    Route::post('/visitors', [VisitorController::class, 'store'])->middleware('role:security')->name('visitors.store');
    Route::post('/visitors/{visitor}/checkout', [VisitorController::class, 'checkout'])->middleware(['role:security', 'subdivision'])->name('visitors.checkout');
    Route::delete('/visitors/{visitor}', [VisitorController::class, 'destroy'])->middleware(['role:security', 'subdivision'])->name('visitors.destroy');
    Route::post('/visitors/{visitorId}/restore', [VisitorController::class, 'restore'])->middleware('role:security')->name('visitors.restore');
    Route::delete('/visitors/{visitorId}/force', [VisitorController::class, 'forceDelete'])->middleware('role:security')->name('visitors.force-delete');

    Route::match(['get', 'post'], '/api/verify_resident.php', [IncidentController::class, 'verifyResident'])
        ->middleware('role:security,staff')
        ->name('api.verify-resident');

    Route::get('/api/houses-by-subdivision', [IncidentController::class, 'housesBySubdivision'])
        ->middleware('role:security,staff,resident')
        ->name('api.houses-by-subdivision');

    Route::get('/my-visitors/{visitorRequest}/photo', [ResidentVisitorController::class, 'photo'])->middleware('role:resident')->name('resident.visitors.photo');
    Route::get('/my-visitors', [ResidentVisitorController::class, 'index'])->middleware('role:resident')->name('resident.visitors.index');
    Route::post('/my-visitors/{visitorRequest}/approve', [ResidentVisitorController::class, 'approve'])->middleware('role:resident')->name('resident.visitors.approve');
    Route::post('/my-visitors/{visitorRequest}/decline', [ResidentVisitorController::class, 'decline'])->middleware('role:resident')->name('resident.visitors.decline');

    Route::get('/notifications', [DashboardController::class, 'notificationsPage'])->name('notifications.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
