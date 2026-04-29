<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    private int $defaultTtl = 300;
    private string $prefix = 'hse';

    /**
     * Get cached data with tenant isolation
     */
    public function get(string $key, ?int $companyId = null): mixed
    {
        $tenantKey = $this->getTenantKey($key, $companyId);
        
        try {
            return Cache::get($tenantKey);
        } catch (\Exception $e) {
            Log::warning('Cache get failed', ['key' => $tenantKey, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Store data in cache with tenant isolation
     */
    public function set(string $key, mixed $value, ?int $ttl = null, ?int $companyId = null): bool
    {
        $tenantKey = $this->getTenantKey($key, $companyId);
        $ttl = $ttl ?? $this->getTtl($key);
        
        try {
            return Cache::put($tenantKey, $value, $ttl);
        } catch (\Exception $e) {
            Log::warning('Cache set failed', ['key' => $tenantKey, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Remember data in cache
     */
    public function remember(string $key, callable $callback, ?int $ttl = null, ?int $companyId = null): mixed
    {
        $tenantKey = $this->getTenantKey($key, $companyId);
        $ttl = $ttl ?? $this->getTtl($key);
        
        try {
            return Cache::remember($tenantKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed', ['key' => $tenantKey, 'error' => $e->getMessage()]);
            return $callback();
        }
    }

    /**
     * Remove data from cache
     */
    public function forget(string $key, ?int $companyId = null): bool
    {
        $tenantKey = $this->getTenantKey($key, $companyId);
        
        try {
            return Cache::forget($tenantKey);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed', ['key' => $tenantKey, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Flush all cache for a tenant
     */
    public function flushTenant(?int $companyId = null): bool
    {
        $tenantId = $companyId ?? auth()->user()?->company_id ?? 'global';
        $pattern = "{$this->prefix}:tenant:{$tenantId}:*";
        
        try {
            // For Redis, use scan and delete
            if (config('cache.default') === 'redis') {
                $this->flushRedisPattern($pattern);
                return true;
            }
            
            // For other drivers, clear all cache
            Cache::flush();
            return true;
        } catch (\Exception $e) {
            Log::error('Cache flush failed', ['tenant' => $tenantId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get cache tags for grouped invalidation
     */
    public function tags(array $tags): \Illuminate\Cache\TaggedCache|null
    {
        try {
            return Cache::tags($tags);
        } catch (\Exception $e) {
            Log::warning('Cache tags failed', ['tags' => $tags, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get cache statistics
     */
    public function stats(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $info = Redis::info('memory');
                return [
                    'driver' => 'redis',
                    'used_memory' => $info['used_memory'] ?? null,
                    'used_memory_human' => $info['used_memory_human'] ?? null,
                    'hit_rate' => $this->calculateHitRate(),
                ];
            }
            
            return ['driver' => config('cache.default')];
        } catch (\Exception $e) {
            Log::warning('Cache stats failed', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get tenant-specific cache key
     */
    private function getTenantKey(string $key, ?int $companyId = null): string
    {
        $tenantId = $companyId ?? auth()->user()?->company_id ?? 'global';
        return "{$this->prefix}:tenant:{$tenantId}:{$key}";
    }

    /**
     * Get TTL based on key pattern
     */
    private function getTtl(string $key): int
    {
        return match (true) {
            str_contains($key, 'dashboard') => config('cache.ttl_dashboard', 120),
            str_contains($key, 'list') => config('cache.ttl_list', 90),
            str_contains($key, 'detail') => config('cache.ttl_detail', 300),
            str_contains($key, 'stats') => 60,
            str_contains($key, 'user') => 1800,
            default => $this->defaultTtl,
        };
    }

    /**
     * Flush Redis keys by pattern
     */
    private function flushRedisPattern(string $pattern): void
    {
        $cursor = 0;
        $batchSize = 100;
        
        do {
            $result = Redis::scan($cursor, ['match' => $pattern, 'count' => $batchSize]);
            $cursor = $result[0];
            $keys = $result[1] ?? [];
            
            if (!empty($keys)) {
                Redis::del(...$keys);
            }
        } while ($cursor != 0);
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(): ?float
    {
        try {
            $info = Redis::info('stats');
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            
            if ($hits + $misses === 0) {
                return null;
            }
            
            return round($hits / ($hits + $misses) * 100, 2);
        } catch (\Exception $e) {
            return null;
        }
    }
}
