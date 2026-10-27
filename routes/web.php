<?php

use App\Http\Controllers\Admin\AdminDestinationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Provider\ProviderDashboardController;
use App\Http\Controllers\Provider\ProviderServiceController;
use App\Http\Controllers\Social\SocialFeedController;
use App\Http\Controllers\Trip\ActivityController;
use App\Http\Controllers\Trip\BookingController;
use App\Http\Controllers\Trip\BudgetController;
use App\Http\Controllers\Trip\MemoryScrapbookController;
use App\Http\Controllers\Trip\OfflinePassportController;
use App\Http\Controllers\Trip\PdfExportController;
use App\Http\Controllers\Trip\ProposalController;
use App\Http\Controllers\Trip\PublicShareController;
use App\Http\Controllers\Trip\RoomBookingController;
use App\Http\Controllers\Trip\TravelRequestController;
use App\Http\Controllers\Trip\TripController;
use App\Http\Controllers\Trip\TripMemberController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
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
    Route::post('/trips/{trip}/members/invite', [TripMemberController::class, 'generateInvite'])->name('trips.members.invite');
    Route::delete('/trips/{trip}/members/{user}', [TripMemberController::class, 'destroy'])->name('trips.members.destroy');
    Route::post('/trips/{trip}/preferences', [TripMemberController::class, 'updatePreferences'])->name('trips.preferences.update');
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

    // Public Share Management (D4-03 Sprint 2)
    Route::post('/trips/{trip}/share/toggle', [PublicShareController::class, 'toggleShare'])->name('trips.share.toggle');
    Route::post('/trips/{trip}/share/revoke', [PublicShareController::class, 'revoke'])->name('trips.share.revoke');
    Route::post('/trips/{trip}/share/budget', [PublicShareController::class, 'toggleBudget'])->name('trips.share.budget');

    // Reverse Marketplace Routes (Sprint 3 - D2-05)
    Route::get('/trips/{trip}/marketplace', [TravelRequestController::class, 'index'])->name('trips.marketplace.index');
    Route::get('/trips/{trip}/marketplace/requests/create', [TravelRequestController::class, 'create'])->name('trips.marketplace.requests.create');
    Route::post('/trips/{trip}/marketplace/requests', [TravelRequestController::class, 'store'])->name('trips.marketplace.requests.store');
    Route::delete('/trips/{trip}/marketplace/requests/{travelRequest}', [TravelRequestController::class, 'destroy'])->name('trips.marketplace.requests.destroy');

    Route::get('/trips/{trip}/marketplace/requests/{travelRequest}/compare', [ProposalController::class, 'compare'])->name('trips.marketplace.compare');
    Route::get('/marketplace/requests/{travelRequest}/proposals/create', [ProposalController::class, 'create'])->name('marketplace.proposals.create');
    Route::post('/marketplace/requests/{travelRequest}/proposals', [ProposalController::class, 'store'])->name('marketplace.proposals.store');

    Route::post('/marketplace/proposals/{proposal}/accept-bundle', [BookingController::class, 'acceptBundle'])->name('marketplace.proposals.accept-bundle');
    Route::post('/marketplace/items/{item}/accept-item', [BookingController::class, 'acceptItem'])->name('marketplace.items.accept-item');
    Route::get('/trips/{trip}/marketplace/bookings', [BookingController::class, 'index'])->name('trips.marketplace.bookings');
    Route::get('/marketplace/bookings/{booking}', [BookingController::class, 'show'])->name('marketplace.bookings.show');

    // ─── Provider Portal & Catalog (Sprint 3 - D3-04) ──────────────────────────
    Route::prefix('provider')->name('provider.')->group(function () {
        Route::get('/register', [ProviderDashboardController::class, 'create'])->name('register');
        Route::post('/register', [ProviderDashboardController::class, 'store'])->name('register.store');
        Route::get('/dashboard', [ProviderDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/calendar', [ProviderDashboardController::class, 'calendar'])->name('calendar');
        Route::post('/calendar/block', [ProviderDashboardController::class, 'blockDate'])->name('calendar.block');
        Route::post('/calendar/unblock', [ProviderDashboardController::class, 'unblockDate'])->name('calendar.unblock');
        Route::get('/bookings', [ProviderDashboardController::class, 'bookings'])->name('bookings.index');
        Route::get('/bookings/{booking}', [ProviderDashboardController::class, 'showBooking'])->name('bookings.show');

        Route::resource('services', ProviderServiceController::class)->except(['show']);
    });

    // ─── PDF Export (D4-04 M7) ─────────────────────────────────────────────────
    Route::get('/trips/{trip}/export-pdf', [PdfExportController::class, 'export'])->name('trips.export-pdf');

    // ─── Offline Travel Passport (Sprint 4 - E4-02) ───────────────────────────
    Route::get('/trips/{trip}/offline-passport', [OfflinePassportController::class, 'show'])->name('trips.offline-passport');
    Route::get('/trips/{trip}/offline-passport/data', [OfflinePassportController::class, 'data'])->name('trips.offline-passport.data');

    // ─── Reverse Memory Scrapbook (Sprint 4 - E4-03) ──────────────────────────
    Route::get('/trips/{trip}/memory-scrapbook', [MemoryScrapbookController::class, 'show'])->name('trips.memory-scrapbook');
    Route::get('/trips/{trip}/memory-scrapbook/data', [MemoryScrapbookController::class, 'data'])->name('trips.memory-scrapbook.data');

    // ─── Social Feed (D4-02 M13) ──────────────────────────────────────────────
    Route::get('/social', [SocialFeedController::class, 'index'])->name('social.feed');
    Route::post('/social/posts', [SocialFeedController::class, 'store'])->name('social.posts.store');
    Route::delete('/social/posts/{post}', [SocialFeedController::class, 'destroy'])->name('social.posts.destroy');
    Route::post('/social/posts/{post}/clone-trip', [SocialFeedController::class, 'cloneTrip'])->name('social.posts.clone-trip');

    // ─── Admin Destination Management (D4-05 M16) ─────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/destinations', [AdminDestinationController::class, 'index'])->name('destinations.index');
        Route::get('/destinations/create', [AdminDestinationController::class, 'createDestination'])->name('destinations.create');
        Route::post('/destinations', [AdminDestinationController::class, 'storeDestination'])->name('destinations.store');
        Route::get('/destinations/{destination}/edit', [AdminDestinationController::class, 'editDestination'])->name('destinations.edit');
        Route::put('/destinations/{destination}', [AdminDestinationController::class, 'updateDestination'])->name('destinations.update');
        Route::delete('/destinations/{destination}', [AdminDestinationController::class, 'destroyDestination'])->name('destinations.destroy');

        Route::get('/destinations/{destination}/zones', [AdminDestinationController::class, 'zones'])->name('destinations.zones');
        Route::post('/destinations/{destination}/zones', [AdminDestinationController::class, 'storeZone'])->name('destinations.zones.store');
        Route::put('/zones/{zone}', [AdminDestinationController::class, 'updateZone'])->name('zones.update');
        Route::delete('/zones/{zone}', [AdminDestinationController::class, 'destroyZone'])->name('zones.destroy');

        Route::get('/zones/{zone}/pois', [AdminDestinationController::class, 'pois'])->name('zones.pois');
        Route::post('/zones/{zone}/pois', [AdminDestinationController::class, 'storePoi'])->name('zones.pois.store');
        Route::put('/pois/{poi}', [AdminDestinationController::class, 'updatePoi'])->name('pois.update');
        Route::delete('/pois/{poi}', [AdminDestinationController::class, 'destroyPoi'])->name('pois.destroy');
    });
});

// Read-only Public Share (M7)
Route::get('/s/{slug}', [PublicShareController::class, 'show'])->name('trips.share.show');

// Trip Collaboration Invite & Join (M8)
Route::get('/trips/join/{token}', [TripMemberController::class, 'showJoin'])->name('trips.join');
Route::post('/trips/join/{token}', [TripMemberController::class, 'processJoin'])->name('trips.join.process')->middleware('auth');
