<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\MemberPreference;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\CollaborationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TripMemberController extends Controller
{
    protected CollaborationService $collaborationService;

    public function __construct(CollaborationService $collaborationService)
    {
        $this->collaborationService = $collaborationService;
    }

    /**
     * Generate an invite link for the trip.
     */
    public function generateInvite(Request $request, Trip $trip): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $trip);

        $role = $request->input('role', 'editor');
        $inviteUrl = $this->collaborationService->generateInviteLink($trip, $role);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Invite link generated successfully.'),
                'invite_url' => $inviteUrl,
            ]);
        }

        return back()->with('success', __('Invite link generated: :url', ['url' => $inviteUrl]));
    }

    /**
     * Show join trip invitation page.
     */
    public function showJoin(string $token): View|RedirectResponse
    {
        $invitation = TripMember::where('invite_token', $token)
            ->where(function ($q) {
                $q->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '>=', now());
            })
            ->first();

        if (! $invitation) {
            return redirect()->route('trips.index')
                ->with('error', __('The invite link is invalid or has expired.'));
        }

        $trip = $invitation->trip;

        if (auth()->check()) {
            $isMember = $trip->members()->where('user_id', auth()->id())->exists();
            if ($isMember) {
                return redirect()->route('trips.show', $trip)
                    ->with('info', __('You are already a member of this trip.'));
            }
        }

        return view('trips.join', [
            'trip' => $trip,
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    /**
     * Process accepting invite and setting preferences.
     */
    public function processJoin(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'max:50'],
            'pace' => ['nullable', 'in:relaxed,balanced,packed'],
        ]);

        try {
            $trip = $this->collaborationService->acceptInvite(
                $token,
                auth()->user(),
                $request->only(['interests', 'pace'])
            );

            return redirect()->route('trips.show', $trip)
                ->with('success', __('Welcome to :name! You have successfully joined the trip.', ['name' => $trip->name]));
        } catch (\Exception $e) {
            return redirect()->route('trips.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove a member from the trip.
     */
    public function destroy(Trip $trip, User $user): JsonResponse|RedirectResponse
    {
        // Must be leader, owner, or leaving oneself
        $isLeader = $trip->members()->where('user_id', auth()->id())->where('role', 'leader')->exists()
            || $trip->owner_id === auth()->id();

        if (! $isLeader && auth()->id() !== $user->id) {
            abort(403, __('You do not have permission to remove this member.'));
        }

        try {
            $this->collaborationService->removeMember($trip, $user, auth()->user());

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Member removed successfully.'),
                ]);
            }

            return back()->with('success', __('Member removed successfully.'));
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Save or update current member preferences.
     */
    public function updatePreferences(Request $request, Trip $trip): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $trip);

        $validated = $request->validate([
            'interests' => ['required', 'array'],
            'interests.*' => ['string', 'max:50'],
            'pace' => ['required', 'in:relaxed,balanced,packed'],
        ]);

        MemberPreference::updateOrCreate(
            [
                'trip_id' => $trip->id,
                'user_id' => auth()->id(),
            ],
            [
                'interests' => $validated['interests'],
                'pace' => $validated['pace'],
            ]
        );

        $mood = $this->collaborationService->calculateGroupMood($trip);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Preferences updated successfully.'),
                'group_mood' => $mood,
            ]);
        }

        return back()->with('success', __('Preferences updated successfully.'));
    }

    /**
     * Get live group mood data.
     */
    public function groupMood(Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        $mood = $this->collaborationService->calculateGroupMood($trip);

        return response()->json([
            'success' => true,
            'group_mood' => $mood,
        ]);
    }
}
