<?php

namespace App\Services;

use App\Events\BookingConfirmed;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class MarketplaceBookingService
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Accept an entire proposal bundle in an ACID transaction.
     *
     * @return array<Booking>
     *
     * @throws DomainException
     */
    public function acceptFullBundle(Proposal $proposal, User $traveler): array
    {
        if ($proposal->status !== 'pending') {
            throw new DomainException(__('Cannot accept this proposal because its status is :status.', [
                'status' => $proposal->status,
            ]));
        }

        return DB::transaction(function () use ($proposal) {
            $proposal->update(['status' => 'accepted']);

            // Reject all other competing pending proposals for this request
            Proposal::where('request_id', $proposal->request_id)
                ->where('id', '!=', $proposal->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            // Mark the travel request as fulfilled
            if ($proposal->request) {
                $proposal->request->update(['status' => 'fulfilled']);
            }

            $trip = $proposal->request?->trip;
            $destinationCode = $trip?->destination ? substr(preg_replace('/[^a-zA-Z0-9]/', '', $trip->destination), 0, 4) : 'TRIP';

            $createdBookings = [];
            $items = $proposal->items()->get();

            // If proposal has no separate items, create one default booking for the proposal
            if ($items->isEmpty()) {
                $voucherCode = $this->generateVoucherCode($destinationCode);
                $booking = Booking::create([
                    'trip_id' => $trip->id,
                    'provider_id' => $proposal->provider_id,
                    'proposal_id' => $proposal->id,
                    'item_name' => __('Full Package from :provider', ['provider' => $proposal->provider?->business_name ?? __('Provider')]),
                    'price' => (float) $proposal->total_price,
                    'status' => 'confirmed',
                    'voucher_code' => $voucherCode,
                ]);

                if ($trip) {
                    Activity::create([
                        'trip_id' => $trip->id,
                        'day' => 1,
                        'time_slot' => 'morning',
                        'title' => '['.__('Voucher').': '.$voucherCode.'] '.$booking->item_name,
                        'cost' => (float) $proposal->total_price,
                        'category' => 'other',
                        'location' => $proposal->provider?->business_name ?? __('Local Provider'),
                        'address' => $proposal->provider?->address,
                        'notes' => __('Booked via Reverse Marketplace. Voucher: :code', ['code' => $voucherCode]),
                        'type' => 'booking',
                        'position' => 0,
                    ]);
                }

                $createdBookings[] = $booking;
                event(new BookingConfirmed($booking, $proposal));
            } else {
                foreach ($items as $item) {
                    // If this item was already accepted individually, do NOT create duplicate booking or activity
                    if ($item->is_accepted) {
                        $existingBooking = Booking::where('proposal_id', $proposal->id)
                            ->where('item_name', $item->name)
                            ->first();
                        if ($existingBooking) {
                            $createdBookings[] = $existingBooking;
                        }

                        continue;
                    }

                    $voucherCode = $this->generateVoucherCode($destinationCode);

                    $booking = Booking::create([
                        'trip_id' => $trip->id,
                        'provider_id' => $proposal->provider_id,
                        'proposal_id' => $proposal->id,
                        'item_name' => $item->name,
                        'price' => (float) $item->price,
                        'status' => 'confirmed',
                        'voucher_code' => $voucherCode,
                    ]);

                    $item->update(['is_accepted' => true]);

                    if ($trip) {
                        Activity::create([
                            'trip_id' => $trip->id,
                            'day' => 1,
                            'time_slot' => 'morning',
                            'title' => '['.__('Voucher').': '.$voucherCode.'] '.$item->name,
                            'cost' => (float) $item->price,
                            'category' => 'other',
                            'location' => $proposal->provider?->business_name ?? __('Local Provider'),
                            'address' => $proposal->provider?->address,
                            'notes' => __('Booked via Reverse Marketplace. Voucher: :code', ['code' => $voucherCode]),
                            'type' => 'booking',
                            'position' => 0,
                        ]);
                    }

                    $createdBookings[] = $booking;
                    event(new BookingConfirmed($booking, $proposal));
                }
            }

            if ($trip) {
                $this->budgetService->calculateBudgetStatus($trip);
            }

            return $createdBookings;
        });
    }

    /**
     * Accept a single proposal item and convert it into a confirmed booking.
     *
     * @throws DomainException
     */
    public function acceptSingleItem(ProposalItem $item, User $traveler): Booking
    {
        if ($item->is_accepted) {
            throw new DomainException(__('This service item has already been booked.'));
        }

        $proposal = $item->proposal;
        if (! $proposal || in_array($proposal->status, ['rejected', 'expired'])) {
            throw new DomainException(__('Cannot book an item from a rejected or expired proposal.'));
        }

        return DB::transaction(function () use ($item, $proposal) {
            $trip = $proposal->request?->trip;
            $destinationCode = $trip?->destination ? substr(preg_replace('/[^a-zA-Z0-9]/', '', $trip->destination), 0, 4) : 'TRIP';
            $voucherCode = $this->generateVoucherCode($destinationCode);

            $booking = Booking::create([
                'trip_id' => $trip->id,
                'provider_id' => $proposal->provider_id,
                'proposal_id' => $proposal->id,
                'item_name' => $item->name,
                'price' => (float) $item->price,
                'status' => 'confirmed',
                'voucher_code' => $voucherCode,
            ]);

            $item->update(['is_accepted' => true]);

            // If all items of the proposal are now accepted, mark the proposal as accepted
            $remainingUnaccepted = $proposal->items()->where('is_accepted', false)->count();
            if ($remainingUnaccepted === 0) {
                $proposal->update(['status' => 'accepted']);
            }

            if ($trip) {
                Activity::create([
                    'trip_id' => $trip->id,
                    'day' => 1,
                    'time_slot' => 'morning',
                    'title' => '['.__('Voucher').': '.$voucherCode.'] '.$item->name,
                    'cost' => (float) $item->price,
                    'category' => 'other',
                    'location' => $proposal->provider?->business_name ?? __('Local Provider'),
                    'address' => $proposal->provider?->address,
                    'notes' => __('Booked item via Reverse Marketplace. Voucher: :code', ['code' => $voucherCode]),
                    'type' => 'booking',
                    'position' => 0,
                ]);

                $this->budgetService->calculateBudgetStatus($trip);
            }

            event(new BookingConfirmed($booking, $proposal));

            return $booking;
        });
    }

    /**
     * Generate a cryptographically secure, unique alphanumeric voucher code.
     */
    protected function generateVoucherCode(?string $destinationCode = 'TRIP'): string
    {
        $sanitizedPrefix = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $destinationCode ?: 'TRIP'));
        if (empty($sanitizedPrefix)) {
            $sanitizedPrefix = 'TRIP';
        }

        do {
            $random = strtoupper(bin2hex(random_bytes(2)));
            $code = sprintf('WND-%s-%s', substr($sanitizedPrefix, 0, 5), $random);
        } while (Booking::where('voucher_code', $code)->exists());

        return $code;
    }
}
