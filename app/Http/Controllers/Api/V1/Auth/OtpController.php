<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Auth\SendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;

/** Mijoz ilovasi: telefon → SMS kod → token */
class OtpController extends ApiController
{
    public function send(SendOtpRequest $request, OtpService $otp): JsonResponse
    {
        $ttl = $otp->send($request->validated('phone'));

        return response()->json(['message' => 'Kod yuborildi.', 'expires_in' => $ttl]);
    }

    public function verify(VerifyOtpRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->loginClientWithOtp(
            $request->validated('phone'),
            $request->validated('code'),
            $request->validated('name'),
            $request->validated('device_name'),
        );

        return response()->json([
            'token' => $result['token'],
            'is_new' => $result['is_new'],
            'user' => new UserResource($result['user']),
        ]);
    }
}
