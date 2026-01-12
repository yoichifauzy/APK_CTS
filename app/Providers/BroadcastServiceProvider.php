<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Authenticated private channels (session-based)
        Broadcast::routes([
            'middleware' => ['web', 'auth'],
        ]);

        require base_path('routes/channels.php');
    }
}
