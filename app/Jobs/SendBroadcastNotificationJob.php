<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendBroadcastNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array<int, int>  $userIds
     */
    public function __construct(
        public Broadcast $broadcast,
        public array $userIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->userIds)) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($this->userIds as $userId) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'broadcast_id' => $this->broadcast->id,
                'user_id' => $userId,
                'type' => NotificationService::TYPE_BROADCAST,
                'title' => $this->broadcast->title,
                'message' => $this->broadcast->message,
                'action_url' => $this->broadcast->action_url,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in safe batches of 250 rows to optimize database packet size
        foreach (array_chunk($rows, 250) as $chunk) {
            Notification::insert($chunk);
        }
    }
}
