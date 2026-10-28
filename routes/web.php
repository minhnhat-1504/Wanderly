<?php

use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminBackupController;
use App\Http\Controllers\Admin\AdminBroadcastController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDestinationController;
use App\Http\Controllers\Admin\AdminModerationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Discovery\DiscoveryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\ProviderDashboardController;
use App\Http\Controllers\Provider\ProviderServiceController;
use App\Http\Controllers\Social\ReportController;
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
use App\Http\Controllers\Trip\SplitBillController;
use App\Http\Controllers\Trip\TravelRequestController;
use App\Http\Controllers\Trip\TripController;
use App\Http\Controllers\Trip\TripMemberController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Vietnam Travel Discovery Portal (Sprint 4 - E4-04) ──────────────────────
Route::get('/discovery', [DiscoveryController::class, 'index'])->name('discovery.index');
Route::get('/discovery/{slug}', [DiscoveryController::class, 'show'])->name('discovery.show');

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

    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Email Verification Routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return request()->user()->hasVerifiedEmail()
            ? redirect()->intended('/trips')
            : view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect('/trips')->with('status', 'email-verified');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout')->withoutMiddleware('verified');
    Route::post('/trips/{trip}/duplicate', [TripController::class, 'duplicate'])->name('trips.duplicate');
    Route::post('/trips/{trip}/members/invite', [TripMemberController::class, 'generateInvite'])->name('trips.members.invite');
    Route::delete('/trips/{trip}/members/{user}', [TripMemberController::class, 'destroy'])->name('trips.members.destroy');
    Route::post('/trips/{trip}/preferences', [TripMemberController::class, 'updatePreferences'])->name('trips.preferences.update');
    Route::resource('trips', TripController::class);
    Route::resource('trips.activities', ActivityController::class)->only(['store', 'update', 'destroy']);

    // Budget Endpoints (D2-05)
    Route::get('/trips/{trip}/budget', [BudgetController::class, 'show'])->name('trips.budget');

    // Split-Bill & Settlements Endpoints (Sprint 4 - F2)
    Route::get('/trips/{trip}/settlements', [SplitBillController::class, 'getSettlementPlan'])->name('trips.settlements');
    Route::get('/trips/{trip}/activities/{activity}/split', [SplitBillController::class, 'getActivitySplit'])->name('trips.activities.split');
    Route::post('/trips/{trip}/activities/{activity}/split', [SplitBillController::class, 'assignParticipants'])->name('trips.activities.split.assign');
    Route::post('/trips/{trip}/settlements/vietqr', [SplitBillController::class, 'generateVietQr'])->name('trips.settlements.vietqr');
    Route::post('/trips/{trip}/settlements/confirm', [SplitBillController::class, 'confirmPayment'])->name('trips.settlements.confirm');

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
    Route::post('/trips/{trip}/marketplace/proposals/{proposal}/analyze', [ProposalController::class, 'analyze'])->name('trips.marketplace.proposals.analyze');
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
        Route::get('/contract', [ProviderDashboardController::class, 'showContract'])->name('contract');
        Route::post('/contract', [ProviderDashboardController::class, 'signContract'])->name('contract.sign');
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

    // ─── 1-Click Add to Trip (Sprint 4 - E4-04) ───────────────────────────────
    Route::post('/discovery/{article}/add-to-trip', [DiscoveryController::class, 'addToTrip'])->name('discovery.add-to-trip');

    // ─── Social Feed (D4-02 M13) ──────────────────────────────────────────────
    Route::get('/social', [SocialFeedController::class, 'index'])->name('social.feed');
    Route::post('/social/posts', [SocialFeedController::class, 'store'])->name('social.posts.store');
    Route::delete('/social/posts/{post}', [SocialFeedController::class, 'destroy'])->name('social.posts.destroy');
    Route::post('/social/posts/{post}/clone-trip', [SocialFeedController::class, 'cloneTrip'])->name('social.posts.clone-trip');
    Route::post('/social/posts/{post}/report', [ReportController::class, 'reportPost'])->name('social.posts.report')->middleware('throttle:10,1');
    Route::post('/social/comments/{comment}/report', [ReportController::class, 'reportComment'])->name('social.comments.report')->middleware('throttle:10,1');
    Route::get('/social/reports/{report}', [ReportController::class, 'showReport'])->name('social.reports.show');

    // ─── Admin Platform & Operations (Admin Redesign) ────────────────────────
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('root');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');

        // Users Management (Module 1)
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/role', [AdminUserController::class, 'changeRole'])->name('users.role');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('users.unsuspend')->withTrashed();
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [AdminUserController::class, 'restore'])->name('users.restore');

        // Community Moderation (Module 2)
        Route::get('/moderation', [AdminModerationController::class, 'index'])->name('moderation.index');
        Route::get('/moderation/reports/{report}', [AdminModerationController::class, 'show'])->name('moderation.show');
        Route::post('/moderation/reports/{report}/resolve', [AdminModerationController::class, 'resolve'])->name('moderation.resolve');
        Route::post('/moderation/reports/{report}/dismiss', [AdminModerationController::class, 'dismiss'])->name('moderation.dismiss');
        Route::get('/moderation/content', [AdminModerationController::class, 'content'])->name('moderation.content');
        Route::post('/moderation/posts/{post}/toggle-hide', [AdminModerationController::class, 'toggleHidePost'])->name('moderation.posts.toggle-hide');
        Route::post('/moderation/comments/{comment}/toggle-hide', [AdminModerationController::class, 'toggleHideComment'])->name('moderation.comments.toggle-hide');

        // System Broadcasts & Notifications (Module 3)
        Route::get('/broadcasts', [AdminBroadcastController::class, 'index'])->name('broadcasts.index');
        Route::get('/broadcasts/create', [AdminBroadcastController::class, 'create'])->name('broadcasts.create');
        Route::post('/broadcasts', [AdminBroadcastController::class, 'store'])->name('broadcasts.store');
        Route::get('/broadcasts/{id}', [AdminBroadcastController::class, 'show'])->name('broadcasts.show');

        // System Backups & Disaster Recovery (Module 4)
        Route::get('/backups', [AdminBackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [AdminBackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{backup}/download', [AdminBackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{backup}', [AdminBackupController::class, 'destroy'])->name('backups.destroy');
        Route::get('/backups/{backup}/instructions', [AdminBackupController::class, 'instructions'])->name('backups.instructions');

        // Destinations & POIs
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

    // ─── Account Settings (Profile Module) ──────────────────────────────────
    Route::get('/settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/settings/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::patch('/settings/privacy', [ProfileController::class, 'updatePrivacy'])->name('profile.privacy.update');
    Route::patch('/settings/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/settings/logout-others', [ProfileController::class, 'logoutOthers'])->name('profile.logout-others');
    Route::delete('/settings/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.destroy');
    Route::delete('/settings/cover', [ProfileController::class, 'removeCover'])->name('profile.cover.destroy');
    Route::delete('/settings', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public User Profile (M17)
Route::get('/u/{user:username}', [ProfileController::class, 'show'])->name('profile.show');

// Read-only Public Share (M7)
Route::get('/s/{slug}', [PublicShareController::class, 'show'])->name('trips.share.show');

// Trip Collaboration Invite & Join (M8)
Route::get('/trips/join/{token}', [TripMemberController::class, 'showJoin'])->name('trips.join');
Route::post('/trips/join/{token}', [TripMemberController::class, 'processJoin'])->name('trips.join.process')->middleware('auth');
