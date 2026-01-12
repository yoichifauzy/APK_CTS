<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCommentsChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $ticketId,
        public readonly ?string $action = null,
        public readonly ?int $userId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('ticket.' . $this->ticketId)];
    }

    public function broadcastAs(): string
    {
        return 'ticket.comments.changed';
    }
}
