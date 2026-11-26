<?php

namespace App\Http\Controllers;

use App\Services\ShutdownCheckService;
use Illuminate\Http\JsonResponse;

class ShutdownCheckController extends Controller
{
    public function __invoke(ShutdownCheckService $shutdownCheck): JsonResponse
    {
        return response()->json($shutdownCheck->decision());
    }
}
