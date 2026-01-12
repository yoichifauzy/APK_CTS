<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LiveUpdateController extends Controller
{
    public function summary(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $categoryName = null;
        if (in_array($user->role, ['admin', 'agent'], true)) {
            try {
                $categoryName = optional($user->loadMissing('category')->category)->name;
            } catch (\Throwable $e) {
                $categoryName = null;
            }
        }

        // Sidebar counts (same semantics as AppServiceProvider)
        $openTicketsCount = 0;
        $showOpenBadge = in_array($user->role, ['admin', 'customer'], true);

        if ($user->role === 'admin') {
            if ($user->category_id) {
                $openTicketsCount = Ticket::query()
                    ->where('category_id', $user->category_id)
                    ->where('status', 'open')
                    ->count();
            }
        } elseif ($user->role === 'customer') {
            $openTicketsCount = Ticket::query()
                ->where('customer_id', $user->id)
                ->where('status', 'open')
                ->count();
        }

        $unreadDiscussionCount = 0;
        if (in_array($user->role, ['admin', 'agent', 'customer'], true)) {
            $unreadQuery = TicketComment::query()
                ->join('tickets', 'tickets.id', '=', 'ticket_comments.ticket_id')
                ->leftJoin('ticket_discussion_reads as r', function ($join) use ($user) {
                    $join->on('r.ticket_id', '=', 'ticket_comments.ticket_id')
                        ->where('r.user_id', '=', (int) $user->id);
                })
                ->where('ticket_comments.user_id', '!=', (int) $user->id)
                ->whereRaw("ticket_comments.created_at > COALESCE(r.last_read_at, '1970-01-01 00:00:00')");

            if ($user->role === 'admin') {
                if ($user->category_id) {
                    $unreadQuery->where('tickets.category_id', $user->category_id);
                } else {
                    $unreadQuery->whereRaw('1 = 0');
                }
            } elseif ($user->role === 'agent') {
                $unreadQuery->where('tickets.agent_id', $user->id);
            } elseif ($user->role === 'customer') {
                $unreadQuery->where('tickets.customer_id', $user->id)
                    ->where('ticket_comments.is_internal', false);
            }

            $unreadDiscussionCount = (int) $unreadQuery->count();
        }

        // "Version" hash to detect meaningful changes (used for auto-refresh)
        $versionParts = [
            'users' => (string) (DB::table('users')->max('updated_at') ?? ''),
            'categories' => (string) (DB::table('categories')->max('updated_at') ?? ''),
            'tickets' => (string) (DB::table('tickets')->max('updated_at') ?? ''),
            'ticket_comments' => (string) (DB::table('ticket_comments')->max('updated_at') ?? ''),
            'discussion_reads' => (string) (DB::table('ticket_discussion_reads')->max('last_read_at') ?? ''),
        ];
        $version = sha1(json_encode($versionParts));

        // Agent notification payload
        $latestAssignedTicketId = null;
        $latestAssignedTicketUpdatedAt = null;
        if ($user->role === 'agent') {
            $latest = Ticket::query()
                ->where('agent_id', $user->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->orderByDesc('updated_at')
                ->first(['id', 'title', 'updated_at']);

            if ($latest) {
                $latestAssignedTicketId = (int) $latest->id;
                $latestAssignedTicketUpdatedAt = optional($latest->updated_at)->toISOString();
            }
        }

        return response()->json([
            'serverTime' => now()->toISOString(),
            'role' => $user->role,
            'userId' => (int) $user->id,
            'categoryName' => $categoryName,
            'showOpenBadge' => $showOpenBadge,
            'openTicketsCount' => (int) $openTicketsCount,
            'unreadDiscussionCount' => (int) $unreadDiscussionCount,
            'version' => $version,
            'latestAssignedTicketId' => $latestAssignedTicketId,
            'latestAssignedTicketUpdatedAt' => $latestAssignedTicketUpdatedAt,
        ]);
    }
}
