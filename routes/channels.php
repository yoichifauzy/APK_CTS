<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports.
|
*/

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) ($user->id ?? 0) === (int) $userId;
});

Broadcast::channel('superadmin', function ($user) {
    return ($user->role ?? null) === 'super_admin';
});

Broadcast::channel('category.{categoryId}', function ($user, $categoryId) {
    $role = $user->role ?? null;

    if ($role === 'super_admin') {
        return true;
    }

    if ($role === 'admin') {
        return (int) ($user->category_id ?? 0) === (int) $categoryId;
    }

    return false;
});

Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) {
    /** @var \App\Models\Ticket|null $ticket */
    $ticket = Ticket::query()->find($ticketId);
    if (!$ticket) {
        return false;
    }

    $role = $user->role ?? null;
    if ($role === 'super_admin') {
        return true;
    }

    if ($role === 'admin') {
        return (int) ($user->category_id ?? 0) !== 0
            && (int) $ticket->category_id === (int) $user->category_id;
    }

    if ($role === 'agent') {
        return (int) $ticket->agent_id === (int) ($user->id ?? 0);
    }

    // customer
    return (int) $ticket->customer_id === (int) ($user->id ?? 0);
});

Broadcast::channel('categories', function ($user) {
    return (int) ($user->id ?? 0) > 0;
});
