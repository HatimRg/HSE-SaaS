<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    /**
     * List notifications.
     */
    public function index(Request $request)
    {
        $query = Notification::where('company_id', auth()->user()->company_id)
            ->where(function ($q) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', auth()->id());
            });

        // Filter by read status
        if ($request->has('unread')) {
            if ($request->boolean('unread')) {
                $query->whereNull('read_at');
            } else {
                $query->whereNotNull('read_at');
            }
        }

        // Filter by urgency
        if ($request->has('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->latest()->limit(50)->get();

        return $this->successResponse($notifications);
    }

    /**
     * Get unread count.
     */
    public function unreadCount()
    {
        $count = Notification::where('company_id', auth()->user()->company_id)
            ->where(function ($q) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', auth()->id());
            })
            ->whereNull('read_at')
            ->count();

        $urgentCount = Notification::where('company_id', auth()->user()->company_id)
            ->where(function ($q) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', auth()->id());
            })
            ->whereNull('read_at')
            ->whereIn('urgency', ['urgent', 'critical'])
            ->count();

        return $this->successResponse([
            'total' => $count,
            'urgent' => $urgentCount,
        ]);
    }

    /**
     * Mark as read.
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        
        // Verify user has access
        if ($notification->user_id && $notification->user_id !== auth()->id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $notification->markAsRead();

        return $this->successResponse(null, 'Notification marked as read');
    }

    /**
     * Mark all as read.
     */
    public function markAllAsRead()
    {
        Notification::where('company_id', auth()->user()->company_id)
            ->where(function ($q) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', auth()->id());
            })
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'All notifications marked as read');
    }

    /**
     * Delete notification.
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return $this->successResponse(null, 'Notification deleted');
    }
}
