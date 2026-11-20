<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    /** `limit` 1..100 oralig'ida (standart 20) */
    protected function perPage(Request $request): int
    {
        return max(1, min(100, $request->integer('limit', 20)));
    }
}
