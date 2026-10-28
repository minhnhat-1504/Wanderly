<?php

namespace App\Services\Admin;

use App\Jobs\SendBroadcastNotificationJob;
use App\Models\Broadcast;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AdminBroadcastService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Get paginated broadcast history with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $audience = $filters['target_audience'] ?? null;
        $search = isset($filters['search']) ? trim($filters['search']) : null;

        $query = Broadcast::query()->with('admin')->withCount('notifications');

        if (! empty($audience) && in_array($audience, ['all', 'travelers', 'providers'])) {
            $query->where('target_audience', $audience);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Get aggregate statistics on broadcasts and engagement.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $totalBroadcasts = Broadcast::count();
        $totalDelivered = Notification::whereNotNull('broadcast_id')->count();
        $totalRead = Notification::whereNotNull('broadcast_id')->whereNotNull('read_at')->count();
        $readRate = $totalDelivered > 0 ? round(($totalRead / $totalDelivered) * 100, 1) : 0.0;

        return [
            'total_broadcasts' => $totalBroadcasts,
            'total_delivered' => $totalDelivered,
            'total_read' => $totalRead,
            'read_rate' => $readRate,
            'audience_all' => Broadcast::where('target_audience', 'all')->count(),
            'audience_travelers' => Broadcast::where('target_audience', 'travelers')->count(),
            'audience_providers' => Broadcast::where('target_audience', 'providers')->count(),
            'travelers_count' => User::where('role', 'traveler')->whereNull('deleted_at')->count(),
            'providers_count' => User::where('role', 'provider')->whereNull('deleted_at')->count(),
            'all_users_count' => User::whereNull('deleted_at')->count(),
        ];
    }

    /**
     * Get detailed broadcast record with delivery samples.
     *
     * @return array{broadcast: Broadcast, sampleRecipients: Collection<int, Notification>, readCount: int}
     */
    public function show(int $id): array
    {
        $broadcast = Broadcast::with('admin')->withCount('notifications')->findOrFail($id);
        $readCount = $broadcast->read_count;
        $sampleRecipients = $broadcast->notifications()->with('user')->latest()->limit(25)->get();

        return [
            'broadcast' => $broadcast,
            'sampleRecipients' => $sampleRecipients,
            'readCount' => $readCount,
        ];
    }

    /**
     * Dispatch broadcast to target audience using 500-chunked queued jobs.
     *
     * @param  array{title: string, message: string, target_audience: string, action_url?: ?string}  $data
     */
    public function send(User $admin, array $data): Broadcast
    {
        $targetAudience = $data['target_audience'];
        $title = $data['title'];
        $message = $data['message'];
        $actionUrl = ! empty($data['action_url']) ? $data['action_url'] : null;

        $userQuery = User::query()->whereNull('deleted_at');

        if ($targetAudience === 'travelers') {
            $userQuery->where('role', 'traveler');
        } elseif ($targetAudience === 'providers') {
            $userQuery->where('role', 'provider');
        }

        $userIds = $userQuery->pluck('id')->all();

        return DB::transaction(function () use ($admin, $title, $message, $targetAudience, $actionUrl, $userIds) {
            $broadcast = Broadcast::create([
                'admin_id' => $admin->id,
                'title' => $title,
                'message' => $message,
                'target_audience' => $targetAudience,
                'recipients_count' => count($userIds),
                'action_url' => $actionUrl,
                'sent_at' => now(),
            ]);

            // Chunk user IDs into 500-sized batches to prevent memory overflow and dispatch jobs
            $chunks = array_chunk($userIds, 500);
            foreach ($chunks as $chunk) {
                SendBroadcastNotificationJob::dispatch($broadcast, $chunk);
            }

            // Record immutable audit log
            $this->auditLogService->record(
                admin: $admin,
                action: 'broadcast.create',
                subject: $broadcast,
                oldValues: [],
                newValues: [
                    'title' => $title,
                    'target_audience' => $targetAudience,
                    'recipients_count' => count($userIds),
                ],
                metadata: [
                    'target_audience' => $targetAudience,
                    'action_url' => $actionUrl,
                ]
            );

            return $broadcast;
        });
    }
}
