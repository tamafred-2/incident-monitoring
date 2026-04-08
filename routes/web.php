<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\SubdivisionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/visitor-notifications', [DashboardController::class, 'notifications'])->middleware('role:admin')->name('admin.visitor-notifications.index');
    Route::post('/admin/visitor-notifications/read-all', [DashboardController::class, 'markNotificationsRead'])->middleware('role:admin')->name('admin.visitor-notifications.read-all');
    Route::post('/admin/visitor-notifications/read-one', [DashboardController::class, 'markNotificationRead'])->middleware('role:admin')->name('admin.visitor-notifications.read-one');
    Route::delete('/admin/visitor-notifications', [DashboardController::class, 'clearNotifications'])->middleware('role:admin')->name('admin.visitor-notifications.clear-all');

    Route::get('/subdivisions', [SubdivisionController::class, 'index'])->name('subdivisions.index');
    Route::post('/subdivisions', [SubdivisionController::class, 'store'])->middleware('role:admin')->name('subdivisions.store');
    Route::put('/subdivisions/{subdivision}', [SubdivisionController::class, 'update'])->middleware(['role:admin', 'subdivision'])->name('subdivisions.update');
    Route::delete('/subdivisions/{subdivision}', [SubdivisionController::class, 'destroy'])->middleware(['role:admin', 'subdivision'])->name('subdivisions.destroy');
    Route::post('/subdivisions/{subdivisionId}/restore', [SubdivisionController::class, 'restore'])->middleware('role:admin')->name('subdivisions.restore');
    Route::delete('/subdivisions/{subdivisionId}/force', [SubdivisionController::class, 'forceDelete'])->middleware('role:admin')->name('subdivisions.force-delete');

    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('role:admin')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('role:admin')->name('users.destroy');
    Route::post('/users/{userId}/restore', [UserController::class, 'restore'])->middleware('role:admin')->name('users.restore');
    Route::delete('/users/{userId}/force', [UserController::class, 'forceDelete'])->middleware('role:admin')->name('users.force-delete');

    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/create', [IncidentController::class, 'create'])->middleware('role:security,staff,investigator')->name('incidents.create');
    Route::post('/incidents', [IncidentController::class, 'store'])->middleware('role:security,staff,investigator')->name('incidents.store');
    Route::get('/incidents/{incidentId}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::get('/incidents/{incidentId}/edit', [IncidentController::class, 'edit'])->middleware('role:admin')->name('incidents.edit');
    Route::put('/incidents/{incidentId}', [IncidentController::class, 'update'])->middleware('role:admin')->name('incidents.update');
    Route::delete('/incidents/{incidentId}', [IncidentController::class, 'destroy'])->middleware('role:admin')->name('incidents.destroy');
    Route::post('/incidents/{incidentId}/restore', [IncidentController::class, 'restore'])->middleware('role:admin')->name('incidents.restore');
    Route::delete('/incidents/{incidentId}/force', [IncidentController::class, 'forceDelete'])->middleware('role:admin')->name('incidents.force-delete');
    Route::get('/residents', [ResidentController::class, 'index'])->middleware('role:staff,investigator')->name('residents.index');
    Route::get('/residents/{resident}/qr-card', [ResidentController::class, 'qrCard'])->middleware(['role:staff,investigator', 'subdivision'])->name('residents.qr-card');
    Route::get('/visitors', [VisitorController::class, 'index'])->middleware('role:security,staff,investigator')->name('visitors.index');
    Route::get('/visitors/{visitor}', [VisitorController::class, 'show'])->middleware(['role:security,staff,investigator', 'subdivision'])->name('visitors.show');
    Route::post('/visitors', [VisitorController::class, 'store'])->middleware('role:security')->name('visitors.store');
    Route::post('/visitors/{visitor}/checkout', [VisitorController::class, 'checkout'])->middleware(['role:security', 'subdivision'])->name('visitors.checkout');
    Route::delete('/visitors/{visitor}', [VisitorController::class, 'destroy'])->middleware(['role:security', 'subdivision'])->name('visitors.destroy');
    Route::post('/visitors/{visitorId}/restore', [VisitorController::class, 'restore'])->middleware('role:security')->name('visitors.restore');
    Route::delete('/visitors/{visitorId}/force', [VisitorController::class, 'forceDelete'])->middleware('role:security')->name('visitors.force-delete');

    Route::match(['get', 'post'], '/api/verify_resident.php', [IncidentController::class, 'verifyResident'])
        ->middleware('role:security,staff,investigator')
        ->name('api.verify-resident');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
