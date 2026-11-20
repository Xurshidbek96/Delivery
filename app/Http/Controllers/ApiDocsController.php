<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

/** Swagger UI va OpenAPI spetsifikatsiyasi (docs/openapi.yaml — yagona manba). */
class ApiDocsController extends Controller
{
    public function ui(): View
    {
        return view('docs.swagger');
    }

    public function spec(): Response
    {
        return response(file_get_contents(base_path('docs/openapi.yaml')), 200, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
