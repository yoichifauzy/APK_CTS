<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UsersChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ?string $role = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $userId = null,
        public readonly ?string $action = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('superadmin')];

        // Jika user punya category_id, broadcast juga ke category channel agar Admin terima notifikasi
        if ($this->categoryId) {
            $channels[] = new PrivateChannel('category.' . $this->categoryId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'users.changed';
    }
}
