<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends ApiController
{
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('courierProfile'));
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return new UserResource($request->user()->load('courierProfile'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Chiqildi.']);
    }
}
