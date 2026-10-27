<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\ProviderDashboardController;
use App\Http\Controllers\Social\NotificationController;
use App\Http\Controllers\Social\SocialFeedController;
use App\Http\Controllers\Trip\ActivityController;
use App\Http\Controllers\Trip\BookingController;
use App\Http\Controllers\Trip\ChecklistController;
use App\Http\Controllers\Trip\MatchEngineController;
use App\Http\Controllers\Trip\ProposalController;
use App\Http\Controllers\Trip\RoomBookingController;
use App\Http\Controllers\Trip\SplitBillController;
use App\Http\Controllers\Trip\TravelRequestController;
use App\Http\Controllers\Trip\TripMemberController;
use App\Http\Controllers\Trip\VoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AJAX Routes for Wanderly
|--------------------------------------------------------------------------
|
| These routes are loaded by bootstrap/app.php with the "web" middleware group
| and prefix "ajax" and name "ajax.".
|
*/

Route::middleware('auth')->group(function () {
    // Activities AJAX routes
    Route::put('/trips/{trip}/activities/reorder', [ActivityController::class, 'reorder'])
        ->name('trips.activities.reorder');

    Route::post('/trips/{trip}/activities', [ActivityController::class, 'store'])
        ->name('trips.activities.store');

    Route::match(['put', 'patch'], '/trips/{trip}/activities/{activity}', [ActivityController::class, 'update'])
        ->name('trips.activities.update');

    Route::delete('/trips/{trip}/activities/{activity}', [ActivityController::class, 'destroy'])
        ->name('trips.activities.destroy');

    // Room Booking AJAX routes
    Route::patch('/rooms/{room}/toggle-budget', [RoomBookingController::class, 'toggleBudget'])
        ->name('rooms.toggle-budget');

    // Checklist AJAX routes (D4-01, D4-02)
    Route::get('/trips/{trip}/checklist', [ChecklistController::class, 'index'])
        ->name('trips.checklist.index');
    Route::post('/trips/{trip}/checklist', [ChecklistController::class, 'store'])
        ->name('trips.checklist.store');
    Route::patch('/trips/{trip}/checklist/{item}/toggle', [ChecklistController::class, 'toggle'])
        ->name('trips.checklist.toggle');
    Route::delete('/trips/{trip}/checklist/{item}', [ChecklistController::class, 'destroy'])
        ->name('trips.checklist.destroy');
    Route::post('/trips/{trip}/checklist/apply-template', [ChecklistController::class, 'applyTemplate'])
        ->name('trips.checklist.apply-template');

    // Marketplace AJAX routes (D2-05)
    Route::post('/trips/{trip}/marketplace/requests', [TravelRequestController::class, 'store'])
        ->name('trips.marketplace.requests.store');
    Route::get('/trips/{trip}/marketplace/requests/{travelRequest}/compare', [ProposalController::class, 'compare'])
        ->name('trips.marketplace.compare');
    Route::post('/marketplace/proposals/{proposal}/accept-bundle', [BookingController::class, 'acceptBundle'])
        ->name('marketplace.proposals.accept-bundle');
    Route::post('/marketplace/items/{item}/accept-item', [BookingController::class, 'acceptItem'])
        ->name('marketplace.items.accept-item');

    // Notification AJAX routes (D4-03 M15)
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.mark-all-read');

    // Social Feed AJAX routes (D4-02 M13)
    Route::post('/social/posts/{post}/like', [SocialFeedController::class, 'toggleLike'])
        ->name('social.posts.like');
    Route::post('/social/posts/{post}/comments', [SocialFeedController::class, 'addComment'])
        ->name('social.posts.comments.store');
    Route::post('/social/posts/{post}/save', [SocialFeedController::class, 'toggleSave'])
        ->name('social.posts.save');

    // Collaboration & Group Mood AJAX routes (Sprint 3 - M8)
    Route::get('/trips/{trip}/mood', [TripMemberController::class, 'groupMood'])
        ->name('trips.mood');
    Route::post('/trips/{trip}/members/invite', [TripMemberController::class, 'generateInvite'])
        ->name('trips.members.invite');
    Route::delete('/trips/{trip}/members/{user}', [TripMemberController::class, 'destroy'])
        ->name('trips.members.destroy');
    Route::post('/trips/{trip}/preferences', [TripMemberController::class, 'updatePreferences'])
        ->name('trips.preferences.update');

    // Voting Engine AJAX routes (Sprint 3 - M8)
    Route::get('/trips/{trip}/candidates', [VoteController::class, 'index'])
        ->name('trips.candidates.index');
    Route::post('/trips/{trip}/votes', [VoteController::class, 'store'])
        ->name('trips.votes.store');
    Route::get('/trips/{trip}/votes/leaderboard', [VoteController::class, 'leaderboard'])
        ->name('trips.votes.leaderboard');

    // Match Engine AJAX routes (Sprint 3 - M9)
    Route::get('/trips/{trip}/match-engine/suggest', [MatchEngineController::class, 'suggest'])
        ->name('trips.match-engine.suggest');
    Route::post('/trips/{trip}/match-engine/apply', [MatchEngineController::class, 'apply'])
        ->name('trips.match-engine.apply');

    // Chat AJAX routes (Sprint 3 - D3-03, D3-05)
    Route::get('/trips/{trip}/messages', [ChatController::class, 'tripMessages'])
        ->name('trips.messages');
    Route::post('/trips/{trip}/messages', [ChatController::class, 'sendTripMessage'])
        ->name('trips.messages.store');
    Route::get('/trips/{trip}/channels', [ChatController::class, 'tripChannels'])
        ->name('trips.channels');
    Route::get('/bookings/{booking}/messages', [ChatController::class, 'bookingMessages'])
        ->name('bookings.messages');
    Route::post('/bookings/{booking}/messages', [ChatController::class, 'sendBookingMessage'])
        ->name('bookings.messages.store');
    Route::get('/messages/unread-count', [ChatController::class, 'unreadCount'])
        ->name('messages.unread-count');

    // Provider Calendar AJAX routes (Sprint 3 - D3-04)
    Route::post('/provider/calendar/block', [ProviderDashboardController::class, 'blockDate'])
        ->name('provider.calendar.block');
    Route::post('/provider/calendar/unblock', [ProviderDashboardController::class, 'unblockDate'])
        ->name('provider.calendar.unblock');

    // Profile Module AJAX routes
    Route::get('/settings/check-username', [ProfileController::class, 'checkUsername'])
        ->name('profile.check-username');

    // Split-Bill & Settlements AJAX routes (Sprint 4 - F2)
    Route::get('/trips/{trip}/settlements', [SplitBillController::class, 'getSettlementPlan'])
        ->name('trips.settlements');
    Route::get('/trips/{trip}/activities/{activity}/split', [SplitBillController::class, 'getActivitySplit'])
        ->name('trips.activities.split.get');
    Route::post('/trips/{trip}/activities/{activity}/split', [SplitBillController::class, 'assignParticipants'])
        ->name('trips.activities.split.assign');
    Route::post('/trips/{trip}/settlements/vietqr', [SplitBillController::class, 'generateVietQr'])
        ->name('trips.settlements.vietqr');
    Route::post('/trips/{trip}/settlements/confirm', [SplitBillController::class, 'confirmPayment'])
        ->name('trips.settlements.confirm');

    // AI Co-pilot & Action Engine AJAX routes (Sprint 4 - F1)
    Route::get('/trips/{trip}/ai-copilot/messages', [\App\Http\Controllers\Trip\AiCopilotController::class, 'getMessages'])
        ->name('trips.ai-copilot.messages');
    Route::post('/trips/{trip}/ai-copilot/chat', [\App\Http\Controllers\Trip\AiCopilotController::class, 'sendMessage'])
        ->name('trips.ai-copilot.chat');
    Route::post('/trips/{trip}/ai-copilot/apply-action/{message}', [\App\Http\Controllers\Trip\AiCopilotController::class, 'applyAction'])
        ->name('trips.ai-copilot.apply-action');
    Route::post('/trips/{trip}/ai-copilot/ingest-review', [\App\Http\Controllers\Trip\AiCopilotController::class, 'ingestReview'])
        ->name('trips.ai-copilot.ingest-review');
    Route::get('/trips/{trip}/ai-copilot/weather-alerts', [\App\Http\Controllers\Trip\AiCopilotController::class, 'getWeatherAlerts'])
        ->name('trips.ai-copilot.weather-alerts');
});

// Profile Tab lazy loading (Accessible publicly or authenticated based on profile visibility)
Route::get('/u/{user:username}/tab/{tab}', [ProfileController::class, 'loadTab'])
    ->name('profile.tab');
