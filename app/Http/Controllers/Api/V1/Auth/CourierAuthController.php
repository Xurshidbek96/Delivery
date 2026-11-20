<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Auth\CourierLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

/** Kuryer ilovasi: admin bergan telefon + parol */
class CourierAuthController extends ApiController
{
    public function login(CourierLoginRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->loginCourier(
            $request->validated('phone'),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        return response()->json([
            'token' => $result['token'],
            'user' => new UserResource($result['user']->load('courierProfile')),
        ]);
    }
}
