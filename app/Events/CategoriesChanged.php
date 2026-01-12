<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoriesChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?string $action = null,
    ) {}

    public function broadcastOn(): array
    {
        // Global channel for any authenticated user
        return [new PrivateChannel('categories')];
    }

    public function broadcastAs(): string
    {
        return 'categories.changed';
    }
}
