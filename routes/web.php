<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Trip\ActivityController;
use App\Http\Controllers\Trip\BudgetController;
use App\Http\Controllers\Trip\PublicShareController;
use App\Http\Controllers\Trip\RoomBookingController;
use App\Http\Controllers\Trip\TripController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('trips.index');
    }

    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('trips.index');
})->middleware('auth')->name('dashboard');

Route::get('/healthz', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'disconnected',
            'timestamp' => now()->toIso8601String(),
        ], 500);
    }
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/trips/{trip}/duplicate', [TripController::class, 'duplicate'])->name('trips.duplicate');
    Route::resource('trips', TripController::class);
    Route::resource('trips.activities', ActivityController::class)->only(['store', 'update', 'destroy']);

    // Budget Endpoints (D2-05)
    Route::get('/trips/{trip}/budget', [BudgetController::class, 'show'])->name('trips.budget');

    // Room Bookings Endpoints (D2-03)
    Route::post('/trips/{trip}/rooms', [RoomBookingController::class, 'store'])->name('trips.rooms.store');
    Route::match(['put', 'patch'], '/rooms/{room}', [RoomBookingController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomBookingController::class, 'destroy'])->name('rooms.destroy');
    Route::match(['put', 'patch'], '/trips/{trip}/rooms/{room}', [RoomBookingController::class, 'update']);
    Route::delete('/trips/{trip}/rooms/{room}', [RoomBookingController::class, 'destroy']);

    // Public Share Management (D4-03)
    Route::post('/trips/{trip}/share/toggle', [PublicShareController::class, 'toggleShare'])->name('trips.share.toggle');
    Route::post('/trips/{trip}/share/revoke', [PublicShareController::class, 'revoke'])->name('trips.share.revoke');
    Route::post('/trips/{trip}/share/budget', [PublicShareController::class, 'toggleBudget'])->name('trips.share.budget');
});

// Read-only Public Share (M7)
Route::get('/s/{slug}', [PublicShareController::class, 'show'])->name('trips.share.show');
