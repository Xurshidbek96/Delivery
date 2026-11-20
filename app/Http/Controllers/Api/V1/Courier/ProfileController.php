<?php

namespace App\Http\Controllers\Api\V1\Courier;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Courier\UpdateLocationRequest;
use App\Http\Requests\Api\Courier\UpdateOnlineRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends ApiController
{
    /** Ishga chiqdim / dam olyapman */
    public function online(UpdateOnlineRequest $request): UserResource
    {
        $request->user()->courierProfile()->update(['is_online' => $request->boolean('is_online')]);

        return new UserResource($request->user()->load('courierProfile'));
    }

    /** Ilova fon rejimida har 30–60 soniyada yuboradi (eng yaqin kuryerni topish uchun) */
    public function location(UpdateLocationRequest $request): JsonResponse
    {
        $request->user()->courierProfile()->update([
            'last_lat' => $request->validated('lat'),
            'last_lng' => $request->validated('lng'),
            'last_location_at' => now(),
        ]);

        return response()->json(['message' => 'Saqlandi.']);
    }
}
