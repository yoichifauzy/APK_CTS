<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketsChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?int $ticketId = null,
        public readonly ?string $action = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->categoryId) {
            $channels[] = new PrivateChannel('category.' . $this->categoryId);
        }

        $channels[] = new PrivateChannel('superadmin');

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'tickets.changed';
    }
}
