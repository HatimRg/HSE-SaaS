<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class BaseController extends Controller
{
    protected CacheService $cache;
    protected int $defaultPerPage = 20;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Return a success JSON response.
     */
    protected function successResponse(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated response.
     */
    protected function paginatedResponse($query, Request $request, ?string $cacheKey = null): JsonResponse
    {
        $perPage = min($request->get('per_page', $this->defaultPerPage), 100);
        $page = $request->get('page', 1);
        
        // Build cache key if provided
        if ($cacheKey) {
            $fullCacheKey = "{$cacheKey}:page:{$page}:perPage:{$perPage}";
            $ttl = $this->getCacheTtl($cacheKey);
            
            $paginated = $this->cache->remember($fullCacheKey, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            }, $ttl);
        } else {
            $paginated = $query->paginate($perPage);
        }

        return $this->successResponse([
            'items' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * Get cache TTL based on key type.
     */
    protected function getCacheTtl(string $key): int
    {
        return match (true) {
            str_contains($key, 'dashboard') => 120,
            str_contains($key, 'list') => 90,
            str_contains($key, 'detail') => 300,
            default => 60,
        };
    }

    /**
     * Clear related cache.
     */
    protected function clearCache(string $pattern): void
    {
        try {
            $this->cache->flushTenant();
        } catch (\Exception $e) {
            Log::warning('Cache clear failed', ['pattern' => $pattern, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Log activity for audit.
     */
    protected function logActivity(string $action, $subject = null, array $properties = []): void
    {
        $user = auth()->user();
        
        activity()
            ->performedOn($subject)
            ->causedBy($user)
            ->withProperties($properties)
            ->log($action);
    }
}
