<?php

namespace Vortechron\NightwatchTesting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NightwatchTestController extends Controller
{
    /**
     * Protected endpoint - requires authentication.
     */
    public function authenticated(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Nightwatch authenticated test endpoint',
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Public endpoint - no authentication required.
     */
    public function public(): JsonResponse
    {
        return response()->json([
            'message' => 'Nightwatch public test endpoint',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
