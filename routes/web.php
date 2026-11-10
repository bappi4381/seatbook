<?php

use App\Http\Controllers\Attendee\BookingController;
use App\Http\Controllers\Attendee\HoldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/events/{event}/seats', [EventController::class, 'seats'])->name('events.seats');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:organizer'])->prefix('organizer')->name('organizer.')->group(function () {
    Route::redirect('/dashboard', '/organizer/events')->name('dashboard');
    Route::resource('events', OrganizerEventController::class)->except(['show']);
});

Route::middleware(['auth', 'role:attendee'])->group(function () {
    Route::post('/events/{event}/holds', [HoldController::class, 'store'])
        ->middleware(['throttle:20,1', 'throttle:seat-hold'])
        ->name('events.holds.store');
    Route::delete('/events/{event}/holds', [HoldController::class, 'destroy'])
        ->name('events.holds.destroy');

    Route::post('/events/{event}/bookings', [BookingController::class, 'store'])
        ->name('events.bookings.store');

    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::match(['post', 'delete'], '/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');
});

require __DIR__.'/auth.php';
