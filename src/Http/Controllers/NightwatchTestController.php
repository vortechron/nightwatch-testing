<?php

namespace Vortechron\NightwatchTesting\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Vortechron\NightwatchTesting\Jobs\NightwatchAuthenticatedJob;
use Vortechron\NightwatchTesting\Services\BulkGenerator;

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

    /**
     * Outgoing request endpoint - makes an external HTTP request.
     */
    public function outgoing(): JsonResponse
    {
        $response = Http::get('https://httpbin.org/status/200');

        return response()->json([
            'message' => 'Nightwatch outgoing request test',
            'outgoing_status' => $response->status(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Dispatch authenticated job - dispatches job from authenticated HTTP context.
     */
    public function dispatchAuthenticatedJob(Request $request): JsonResponse
    {
        NightwatchAuthenticatedJob::dispatch(
            'Nightwatch authenticated job dispatched at ' . now()->toDateTimeString()
        );

        return response()->json([
            'message' => 'Nightwatch authenticated job dispatched',
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Authenticated exception endpoint - triggers exception with user context.
     */
    public function authenticatedException(Request $request): never
    {
        throw new Exception(
            'Nightwatch authenticated exception test for user ' . ($request->user()?->id ?? 'unknown')
        );
    }

    /**
     * Generate bulk entries via API.
     */
    public function bulk(string $type, int $count): JsonResponse
    {
        $maxCount = config('nightwatch-testing.bulk.max_count', 1000);
        $count = min((int) $count, $maxCount);

        $generator = new BulkGenerator();
        $generatedCount = $generator->generate($type, $count);

        return response()->json([
            'message' => 'Bulk entries generated successfully',
            'type' => $type,
            'requested_count' => (int) $count,
            'generated_count' => $generatedCount,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
