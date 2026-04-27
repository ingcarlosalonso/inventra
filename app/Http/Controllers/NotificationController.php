<?php

namespace App\Http\Controllers;

use App\Http\Resources\Notification\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_if($notification->notifiable_id !== $request->user()->id, 403);

        $notification->markAsRead();

        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['count' => 0]);
    }

    public function destroy(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_if($notification->notifiable_id !== $request->user()->id, 403);

        $notification->delete();

        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }
}
