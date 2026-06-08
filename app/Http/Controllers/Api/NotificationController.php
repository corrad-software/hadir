<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(fn ($n) => $this->format($n));

        return $this->sendOk($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();
        return $this->sendOk(['count' => $count]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return $this->sendOk(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return $this->sendOk(['success' => true]);
    }

    private function format(object $n): array
    {
        $data = is_array($n->data) ? $n->data : json_decode($n->data, true) ?? [];
        return [
            'id'        => $n->id,
            'type'      => $data['type'] ?? 'general',
            'title'     => $data['title'] ?? 'Notification',
            'body'      => $data['body'] ?? '',
            'url'       => $data['url'] ?? null,
            'read'      => ! is_null($n->read_at),
            'createdAt' => $n->created_at?->toIso8601String(),
        ];
    }
}
