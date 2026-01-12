<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Ticket;
use App\Models\TicketComment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.sidebar', function ($view) {
            $openTicketsCount = 0;
            $unreadDiscussionCount = 0;

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if ($user) {
                // Open ticket badge: only for Admin & Customer
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

                // Unread discussion badge: Admin/Agent/Customer
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
            }

            $view->with('sidebarOpenTicketsCount', $openTicketsCount);
            $view->with('sidebarUnreadDiscussionCount', $unreadDiscussionCount);
        });
    }
}
