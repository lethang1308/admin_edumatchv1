<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimeNotificationService
{
    /**
     * Persist the notification first, then publish it only after the active
     * database transaction commits. This keeps a rolled-back notification from
     * ever appearing in a user's realtime feed.
     */
    public function send(int $userId, string $type, string $title, ?string $content = null, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'data' => $data,
        ]);

        DB::afterCommit(fn () => $this->broadcast($notification));

        return $notification;
    }

    public function broadcast(Notification $notification): void
    {
        try {
            event(new NotificationCreated($notification));
        } catch (\Throwable $exception) {
            Log::warning('Không thể phát thông báo realtime qua Pusher.', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'exception' => $exception,
            ]);
        }
    }
}
