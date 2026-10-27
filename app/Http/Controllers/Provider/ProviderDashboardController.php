<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\BlockDateRequest;
use App\Http\Requests\Provider\RegisterProviderRequest;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Provider;
use App\Services\ChatService;
use App\Services\ProviderService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderDashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProviderService $providerService,
        protected ChatService $chatService
    ) {}

    /**
     * Show the "become a provider" registration form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->provider) {
            return redirect()->route('provider.dashboard');
        }

        $destinations = Destination::orderBy('name')->get(['id', 'name']);

        return view('provider.register', compact('destinations'));
    }

    /**
     * Register the authenticated user as a service provider.
     */
    public function store(RegisterProviderRequest $request): RedirectResponse
    {
        try {
            $this->providerService->registerProvider($request->user(), $request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.contract')
            ->with('success', __('Profile submitted! Please review and sign the Partnership Contract to activate your account.'));
    }

    /**
     * Show the partnership contract signing page.
     */
    public function showContract(Request $request): View|RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->route('provider.register');
        }

        $provider->load('destination');

        return view('provider.contract', compact('provider'));
    }

    /**
     * Process contract acceptance and activation.
     */
    public function signContract(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->route('provider.register');
        }

        $request->validate([
            'agree_terms' => 'accepted',
            'signature_name' => 'required|string|max:100',
        ]);

        $provider->update([
            'is_verified' => true,
        ]);

        return redirect()->route('provider.dashboard')
            ->with('success', __('Partnership contract signed! Your provider portal is fully activated.'));
    }

    /**
     * Provider dashboard with KPIs, revenue chart, open requests and upcoming bookings.
     */
    public function dashboard(Request $request): View
    {
        $provider = $this->currentProvider($request);
        $provider->load('destination');

        $stats = $this->providerService->getProviderStats($provider);

        return view('provider.dashboard', compact('provider', 'stats'));
    }

    /**
     * Monthly availability calendar.
     */
    public function calendar(Request $request): View
    {
        $provider = $this->currentProvider($request);

        $month = $this->resolveMonth($request->query('month'));

        return view('provider.calendar', [
            'provider' => $provider,
            'month' => $month,
            'blockedDates' => $this->providerService->getBlockedDatesForMonth($provider, $month),
            'bookedDates' => $this->providerService->getBookedDatesForMonth($provider, $month),
        ]);
    }

    /**
     * Block a calendar date (AJAX or form).
     */
    public function blockDate(BlockDateRequest $request): JsonResponse|RedirectResponse
    {
        $provider = $this->currentProvider($request);

        try {
            $schedule = $this->providerService->blockDate($provider, $request->validated('date'), $request->validated('reason'));
        } catch (DomainException $e) {
            return $this->respond($request, false, $e->getMessage(), [], 422);
        }

        return $this->respond($request, true, __('Date blocked. You will not receive matching requests for this day.'), [
            'date' => $schedule->blocked_date->toDateString(),
            'reason' => $schedule->reason,
        ]);
    }

    /**
     * Re-open a blocked calendar date (AJAX or form).
     */
    public function unblockDate(BlockDateRequest $request): JsonResponse|RedirectResponse
    {
        $provider = $this->currentProvider($request);

        $removed = $this->providerService->unblockDate($provider, $request->validated('date'));

        return $removed
            ? $this->respond($request, true, __('Date re-opened for bookings.'), ['date' => $request->validated('date')])
            : $this->respond($request, false, __('This date was not blocked.'), [], 404);
    }

    /**
     * All bookings received by the provider with direct-chat entry points.
     */
    public function bookings(Request $request): View
    {
        $provider = $this->currentProvider($request);

        $bookings = $provider->bookings()
            ->with('trip:id,name,destination,start_date,end_date,owner_id', 'trip.owner:id,name')
            ->latest()
            ->paginate(15);

        $channels = $this->chatService->getProviderDirectChannels($provider, $request->user())->keyBy('booking_id');

        return view('provider.bookings', compact('provider', 'bookings', 'channels'));
    }

    /**
     * Single booking detail with the 1-1 conversation with the traveler.
     */
    public function showBooking(Request $request, Booking $booking): View
    {
        $provider = $this->currentProvider($request);

        abort_unless($booking->provider_id === $provider->id, 403, __('This booking does not belong to your business.'));

        $booking->load('trip.owner');

        return view('provider.booking-show', compact('provider', 'booking'));
    }

    /**
     * Resolve the provider profile of the authenticated user or send them to registration.
     */
    protected function currentProvider(Request $request): Provider
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            throw new HttpResponseException(
                $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => __('Please register as a service provider first.')], 403)
                    : redirect()->route('provider.register')->with('info', __('Please register as a service provider first.'))
            );
        }

        $this->authorize('manage', $provider);

        return $provider;
    }

    protected function resolveMonth(?string $month): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        }

        return Carbon::today()->startOfMonth();
    }

    protected function respond(Request $request, bool $success, string $message, array $data = [], int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['success' => $success, 'message' => $message], $data), $status);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }
}
