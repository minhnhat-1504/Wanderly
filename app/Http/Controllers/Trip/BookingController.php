<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Trip;
use App\Services\MarketplaceBookingService;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected MarketplaceBookingService $bookingService
    ) {}

    /**
     * Accept a full proposal package bundle.
     */
    public function acceptBundle(Request $request, Proposal $proposal): RedirectResponse|JsonResponse
    {
        $trip = $proposal->request?->trip;
        if (! $trip) {
            abort(404, __('Associated trip not found.'));
        }

        $this->authorize('manageMarketplace', $trip);

        try {
            $bookings = $this->bookingService->acceptFullBundle($proposal, $request->user());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Proposal accepted successfully! :count booking(s) created and added to your timeline.', ['count' => count($bookings)]),
                    'data' => $bookings,
                ]);
            }

            return redirect()->route('trips.marketplace.bookings', $trip)
                ->with('success', __('Proposal accepted! :count booking(s) created with vouchers and added to your itinerary.', ['count' => count($bookings)]));
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Accept a single proposal item and convert to a booking.
     */
    public function acceptItem(Request $request, ProposalItem $item): RedirectResponse|JsonResponse
    {
        $trip = $item->proposal?->request?->trip;
        if (! $trip) {
            abort(404, __('Associated trip not found.'));
        }

        $this->authorize('manageMarketplace', $trip);

        try {
            $booking = $this->bookingService->acceptSingleItem($item, $request->user());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Service item accepted! Voucher created: :code', ['code' => $booking->voucher_code]),
                    'data' => $booking,
                ]);
            }

            return redirect()->back()
                ->with('success', __('Item booked successfully! Voucher: :code has been added to your timeline.', ['code' => $booking->voucher_code]));
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display a listing of bookings & vouchers for a trip.
     */
    public function index(Request $request, Trip $trip): View|JsonResponse
    {
        $this->authorize('view', $trip);

        $bookings = $trip->bookings()
            ->with(['provider', 'proposal'])
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'bookings' => $bookings,
            ]);
        }

        return view('marketplace.bookings', compact('trip', 'bookings'));
    }

    /**
     * Display detailed voucher card for a booking.
     */
    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);

        return view('marketplace.voucher', compact('booking'));
    }
}
