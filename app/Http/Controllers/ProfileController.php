<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateNotificationsRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Requests\UpdatePrivacyRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display public profile.
     */
    public function show(User $user): View
    {
        $viewer = Auth::user();

        // Check view authorization (handles private profile vs owner/admin)
        if (! Gate::allows('view', $user)) {
            abort(403, __('This profile is private.'));
        }

        $profileData = $this->profileService->getProfileData($user, $viewer);

        return view('profile.show', $profileData);
    }

    /**
     * Show the profile settings form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $travelStyle = $this->profileService->getTravelStyle($user);

        return view('profile.edit', [
            'user' => $user,
            'travelStyle' => $travelStyle,
            'activeSection' => $request->query('section', 'profile'),
        ]);
    }

    /**
     * Update user profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $data = $request->safe()->except(['avatar', 'cover_image']);
        $avatarFile = $request->file('avatar');
        $coverFile = $request->file('cover_image');

        $this->profileService->updateProfile($user, $data, $avatarFile, $coverFile);

        return redirect()->route('profile.edit', ['section' => 'profile'])
            ->with('success', __('Profile updated successfully!'));
    }

    /**
     * Update user travel preferences.
     */
    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $this->profileService->updatePreferences($user, $request->validated());

        return redirect()->route('profile.edit', ['section' => 'preferences'])
            ->with('success', __('Travel preferences updated successfully!'));
    }

    /**
     * Update privacy settings.
     */
    public function updatePrivacy(UpdatePrivacyRequest $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $user->update($request->validated());

        return redirect()->route('profile.edit', ['section' => 'privacy'])
            ->with('success', __('Privacy settings updated successfully!'));
    }

    /**
     * Update notification preferences.
     */
    public function updateNotifications(UpdateNotificationsRequest $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $user->update($request->validated());

        return redirect()->route('profile.edit', ['section' => 'notifications'])
            ->with('success', __('Notification preferences updated successfully!'));
    }

    /**
     * Update account password.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $this->profileService->updatePassword($user, $request->validated('password'));

        return redirect()->route('profile.edit', ['section' => 'security'])
            ->with('success', __('Password updated successfully!'));
    }

    /**
     * Logout other browser sessions.
     */
    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Gate::authorize('update', $user);

        $this->profileService->logoutOtherDevices($user, $request->input('password'));

        return redirect()->route('profile.edit', ['section' => 'security'])
            ->with('success', __('Other devices have been logged out.'));
    }

    /**
     * Remove avatar via AJAX/Form.
     */
    public function removeAvatar(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $this->profileService->removeAvatar($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'avatar_url' => $user->fresh()->avatar_url,
                'message' => __('Avatar removed successfully.'),
            ]);
        }

        return back()->with('success', __('Avatar removed successfully.'));
    }

    /**
     * Remove cover image via AJAX/Form.
     */
    public function removeCover(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);

        $this->profileService->removeCover($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cover_url' => null,
                'message' => __('Cover image removed successfully.'),
            ]);
        }

        return back()->with('success', __('Cover image removed successfully.'));
    }

    /**
     * Check username availability in realtime (debounced AJAX).
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $username = strtolower(trim((string) $request->query('username')));
        $currentUserId = $request->user()?->id;

        if (empty($username) || ! preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            return response()->json([
                'available' => false,
                'message' => __('Username must be 3-30 lowercase characters with only letters, numbers and underscores.'),
            ]);
        }

        if (in_array($username, UpdateProfileRequest::RESERVED_USERNAMES)) {
            return response()->json([
                'available' => false,
                'message' => __('This username is reserved.'),
            ]);
        }

        $exists = User::where('username', $username)
            ->when($currentUserId, fn ($q) => $q->where('id', '!=', $currentUserId))
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? __('This username is already taken.') : __('Username is available!'),
        ]);
    }

    /**
     * Get partial HTML content for profile tabs.
     */
    public function loadTab(User $user, string $tab, Request $request): View|JsonResponse
    {
        $viewer = Auth::user();

        if (! Gate::allows('view', $user)) {
            abort(403);
        }

        return match ($tab) {
            'posts' => view('profile.partials.tab-posts', [
                'posts' => $this->profileService->getPosts($user),
                'user' => $user,
            ]),
            'trips' => view('profile.partials.tab-trips', [
                'trips' => $this->profileService->getTrips($user, $viewer),
                'user' => $user,
                'isOwner' => $viewer && $viewer->id === $user->id,
            ]),
            'saved' => ($viewer && $viewer->id === $user->id)
                ? view('profile.partials.tab-saved', [
                    'savedPosts' => $this->profileService->getSavedPosts($user, $viewer),
                    'user' => $user,
                ])
                : abort(403),
            'travel-style' => view('profile.partials.tab-travel-style', [
                'travelStyle' => $this->profileService->getTravelStyle($user),
                'user' => $user,
            ]),
            'services' => ($user->role === 'provider' || $user->provider)
                ? view('profile.partials.tab-services', [
                    'providerProfile' => $this->profileService->getProviderProfile($user),
                    'user' => $user,
                ])
                : abort(404),
            default => abort(404),
        };
    }

    /**
     * Delete user account permanently.
     */
    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('delete', $user);

        Auth::logout();
        $this->profileService->deleteAccount($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', __('Your account has been deleted permanently.'));
    }
}
