<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\DeviceTokenRequest;
use Illuminate\Http\JsonResponse;

/** Push uchun qurilma tokeni (FCM). Ilova har kirishda yuboradi. */
class DeviceTokenController extends ApiController
{
    public function store(DeviceTokenRequest $request): JsonResponse
    {
        $request->user()->deviceTokens()->updateOrCreate(
            ['token' => $request->validated('token')],
            ['platform' => $request->validated('platform'), 'last_used_at' => now()],
        );

        return response()->json(['message' => 'Saqlandi.']);
    }

    public function destroy(DeviceTokenRequest $request): JsonResponse
    {
        $request->user()->deviceTokens()->where('token', $request->validated('token'))->delete();

        return response()->json(['message' => 'O\'chirildi.']);
    }
}
