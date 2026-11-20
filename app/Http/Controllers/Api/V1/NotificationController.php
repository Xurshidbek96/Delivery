<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()->notifications()->paginate($this->perPage($request));

        return NotificationResource::collection($notifications)
            ->additional(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['message' => 'O\'qildi.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Hammasi o\'qildi.']);
    }
}
