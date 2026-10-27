<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\Service;
use App\Models\TravelRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProviderService
{
    public const MAX_SPEC_ENTRIES = 20;

    public const OCCUPANCY_WINDOW_DAYS = 30;

    public const REVENUE_CHART_MONTHS = 6;

    public const ACTIVE_BOOKING_STATUSES = ['confirmed', 'ongoing', 'completed'];

    public const SERVICE_IMAGE_DIRECTORY = 'services';

    /**
     * Upgrade a traveler account into a service provider profile.
     *
     * @param  array{business_name: string, service_type: string, destination_id: int, bio?: ?string, phone?: ?string, address?: ?string}  $data
     */
    public function registerProvider(User $user, array $data): Provider
    {
        if ($user->provider()->exists()) {
            throw new DomainException(__('This account is already registered as a service provider.'));
        }

        if ($user->role === 'admin') {
            throw new DomainException(__('Administrator accounts cannot be converted into provider accounts.'));
        }

        return DB::transaction(function () use ($user, $data) {
            $provider = Provider::create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'service_type' => $data['service_type'],
                'destination_id' => $data['destination_id'],
                'bio' => $data['bio'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'rating' => 5.0,
                'is_verified' => false,
            ]);

            $user->update(['role' => 'provider']);

            return $provider;
        });
    }

    /**
     * Create a new catalog service for the provider.
     *
     * @param  array{name: string, price: float|string, tags?: string|array|null, specs?: array|null}  $data
     */
    public function createService(Provider $provider, array $data, ?UploadedFile $image = null): Service
    {
        return $provider->services()->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'tags' => $this->normalizeTags($data['tags'] ?? []),
            'specs' => $this->normalizeSpecs($data['specs'] ?? []),
            'image' => $image ? $image->store(self::SERVICE_IMAGE_DIRECTORY, 'public') : null,
        ]);
    }

    /**
     * Update an existing catalog service, optionally replacing or removing its image.
     *
     * @param  array{name: string, price: float|string, tags?: string|array|null, specs?: array|null}  $data
     */
    public function updateService(Service $service, array $data, ?UploadedFile $image = null, bool $removeImage = false): Service
    {
        $attributes = [
            'name' => $data['name'],
            'price' => $data['price'],
            'tags' => $this->normalizeTags($data['tags'] ?? []),
            'specs' => $this->normalizeSpecs($data['specs'] ?? []),
        ];

        if ($image || $removeImage) {
            $this->deleteImageFile($service->image);
            $attributes['image'] = $image ? $image->store(self::SERVICE_IMAGE_DIRECTORY, 'public') : null;
        }

        $service->update($attributes);

        return $service->refresh();
    }

    /**
     * Delete a catalog service together with its stored image.
     */
    public function deleteService(Service $service): void
    {
        $this->deleteImageFile($service->image);
        $service->delete();
    }

    /**
     * Normalize the dynamic specs editor payload into a flat key => scalar JSON object.
     *
     * Accepts either an associative array (['seats' => 7]) or a list of
     * ['key' => 'seats', 'value' => '7'] pairs submitted by the form.
     */
    public function normalizeSpecs(array $specs): array
    {
        $normalized = [];

        foreach ($specs as $key => $value) {
            if (is_array($value)) {
                $key = $value['key'] ?? null;
                $value = $value['value'] ?? null;
            }

            $key = Str::snake(trim((string) $key));

            if ($key === '' || $value === null || $value === '' || ! is_scalar($value)) {
                continue;
            }

            $normalized[Str::limit($key, 50, '')] = $this->castSpecValue($value);

            if (count($normalized) >= self::MAX_SPEC_ENTRIES) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * Normalize tags from a comma-separated string or array into a unique lowercase list.
     */
    public function normalizeTags(string|array|null $tags): array
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        return collect($tags ?? [])
            ->map(fn ($tag) => Str::lower(trim((string) $tag)))
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Block a calendar date so the provider is excluded from request matching on that day.
     */
    public function blockDate(Provider $provider, string $date, ?string $reason = null): ProviderSchedule
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($day->lt(Carbon::today())) {
            throw new DomainException(__('You cannot block a date in the past.'));
        }

        $existing = $provider->schedules()->whereDate('blocked_date', $day->toDateString())->first();

        if ($existing) {
            $existing->update(['reason' => $reason]);

            return $existing;
        }

        return $provider->schedules()->create([
            'blocked_date' => $day->toDateString(),
            'reason' => $reason,
        ]);
    }

    /**
     * Re-open a previously blocked date.
     */
    public function unblockDate(Provider $provider, string $date): bool
    {
        $day = Carbon::parse($date)->toDateString();

        return $provider->schedules()->whereDate('blocked_date', $day)->delete() > 0;
    }

    /**
     * Get blocked dates for the month containing the given date, keyed by Y-m-d.
     *
     * @return array<string, ?string>
     */
    public function getBlockedDatesForMonth(Provider $provider, Carbon $month): array
    {
        return $provider->schedules()
            ->whereBetween('blocked_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('blocked_date')
            ->get()
            ->mapWithKeys(fn (ProviderSchedule $schedule) => [
                $schedule->blocked_date->toDateString() => $schedule->reason,
            ])
            ->all();
    }

    /**
     * Get booked trip dates (trip ranges of active bookings) for the given month, keyed by Y-m-d.
     *
     * @return array<string, int>
     */
    public function getBookedDatesForMonth(Provider $provider, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return $this->collectBookedDates($provider, $start, $end)
            ->countBy()
            ->all();
    }

    /**
     * Build all dashboard KPIs, chart data and work queues for a provider.
     *
     * @return array{
     *     today_bookings: int,
     *     monthly_revenue: float,
     *     total_revenue: float,
     *     occupancy_rate: float,
     *     rating: float,
     *     total_services: int,
     *     status_breakdown: array<string, int>,
     *     revenue_chart: array{labels: array<int, string>, values: array<int, float>},
     *     upcoming_bookings: Collection,
     *     open_requests: Collection
     * }
     */
    public function getProviderStats(Provider $provider): array
    {
        $today = Carbon::today();

        $activeBookings = $provider->bookings()->whereIn('status', self::ACTIVE_BOOKING_STATUSES);

        return [
            'today_bookings' => $provider->bookings()->whereDate('created_at', $today->toDateString())->count(),
            'monthly_revenue' => (float) (clone $activeBookings)
                ->whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->sum('price'),
            'total_revenue' => (float) (clone $activeBookings)->sum('price'),
            'occupancy_rate' => $this->calculateOccupancyRate($provider),
            'rating' => (float) $provider->rating,
            'total_services' => $provider->services()->count(),
            'status_breakdown' => $this->getStatusBreakdown($provider),
            'revenue_chart' => $this->getRevenueChart($provider),
            'upcoming_bookings' => $this->getUpcomingBookings($provider),
            'open_requests' => $this->getOpenRequests($provider),
        ];
    }

    /**
     * Percentage of the next 30 days that are unavailable (booked trip days or manually blocked days).
     */
    public function calculateOccupancyRate(Provider $provider): float
    {
        $start = Carbon::today();
        $end = $start->copy()->addDays(self::OCCUPANCY_WINDOW_DAYS - 1);

        $blocked = $provider->schedules()
            ->whereBetween('blocked_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn (ProviderSchedule $schedule) => $schedule->blocked_date->toDateString());

        $unavailable = $this->collectBookedDates($provider, $start, $end)
            ->merge($blocked)
            ->unique()
            ->count();

        return round($unavailable / self::OCCUPANCY_WINDOW_DAYS * 100, 1);
    }

    /**
     * Revenue of active bookings grouped by month for the last 6 months (oldest first).
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function getRevenueChart(Provider $provider): array
    {
        $firstMonth = Carbon::today()->startOfMonth()->subMonths(self::REVENUE_CHART_MONTHS - 1);

        $totals = $provider->bookings()
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
            ->where('created_at', '>=', $firstMonth)
            ->get(['price', 'created_at'])
            ->groupBy(fn (Booking $booking) => $booking->created_at->format('Y-m'))
            ->map(fn (Collection $group) => (float) $group->sum('price'));

        $labels = [];
        $values = [];

        for ($i = 0; $i < self::REVENUE_CHART_MONTHS; $i++) {
            $month = $firstMonth->copy()->addMonths($i);
            $labels[] = $month->format('M Y');
            $values[] = round($totals->get($month->format('Y-m'), 0.0), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Count bookings per status.
     *
     * @return array<string, int>
     */
    public function getStatusBreakdown(Provider $provider): array
    {
        $counts = $provider->bookings()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(['confirmed', 'ongoing', 'completed', 'cancelled'])
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    /**
     * Confirmed / ongoing bookings whose trip has not ended yet, soonest first.
     */
    public function getUpcomingBookings(Provider $provider, int $limit = 5): Collection
    {
        return $provider->bookings()
            ->with(['trip.owner'])
            ->whereIn('status', ['confirmed', 'ongoing'])
            ->whereHas('trip', fn ($query) => $query->whereDate('end_date', '>=', Carbon::today()->toDateString()))
            ->get()
            ->sortBy(fn (Booking $booking) => $booking->trip?->start_date)
            ->take($limit)
            ->values();
    }

    /**
     * Open travel requests at the provider's destination that are still accepting proposals.
     */
    public function getOpenRequests(Provider $provider, int $limit = 5): Collection
    {
        $destinationName = $provider->destination?->name;

        if (! $destinationName) {
            return collect();
        }

        return TravelRequest::query()
            ->with('trip')
            ->withCount('proposals')
            ->whereIn('status', ['open', 'bidding'])
            ->whereHas('trip', fn ($query) => $query->where('destination', 'LIKE', '%'.$destinationName.'%'))
            ->whereDoesntHave('proposals', fn ($query) => $query->where('provider_id', $provider->id))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Collect every Y-m-d (within the window) covered by trips of the provider's active bookings.
     * A date appears once per booking so callers can count or de-duplicate as needed.
     */
    protected function collectBookedDates(Provider $provider, Carbon $windowStart, Carbon $windowEnd): Collection
    {
        $bookings = $provider->bookings()
            ->with('trip:id,start_date,end_date')
            ->whereIn('status', ['confirmed', 'ongoing'])
            ->whereHas('trip', fn ($query) => $query
                ->whereDate('start_date', '<=', $windowEnd->toDateString())
                ->whereDate('end_date', '>=', $windowStart->toDateString()))
            ->get();

        $dates = collect();

        foreach ($bookings as $booking) {
            $from = $booking->trip->start_date->max($windowStart);
            $to = $booking->trip->end_date->min($windowEnd);

            foreach (CarbonPeriod::create($from, $to) as $day) {
                $dates->push($day->toDateString());
            }
        }

        return $dates;
    }

    protected function castSpecValue(string|int|float|bool $value): string|int|float|bool
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            $lower = Str::lower($trimmed);

            if (in_array($lower, ['true', 'yes'], true)) {
                return true;
            }

            if (in_array($lower, ['false', 'no'], true)) {
                return false;
            }

            if (is_numeric($trimmed) && ! preg_match('/^0\d/', $trimmed)) {
                return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
            }

            return Str::limit($trimmed, 255, '');
        }

        return $value;
    }

    protected function deleteImageFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
