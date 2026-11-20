<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\Client\AddressRequest;
use App\Http\Resources\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddressController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressResource::collection($request->user()->addresses()->latest()->get());
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $address = $request->user()->addresses()->create($request->validated());

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function update(AddressRequest $request, int $address): AddressResource
    {
        $model = $request->user()->addresses()->findOrFail($address);
        $model->update($request->validated());

        return new AddressResource($model);
    }

    public function destroy(Request $request, int $address): JsonResponse
    {
        $request->user()->addresses()->findOrFail($address)->delete();

        return response()->json(['message' => 'O\'chirildi.']);
    }
}
