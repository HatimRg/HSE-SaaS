<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Configure encryption
        if (config('app.enable_e2e_encryption')) {
            $this->app->singleton(\App\Services\EncryptionService::class);
        }

        // Configure caching strategies
        $this->app->singleton(\App\Services\CacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in production
        Model::preventLazyLoading(!app()->isProduction());

        // Enable strict mode for models
        Model::shouldBeStrict(!app()->isProduction());

        // HTTPS forcing disabled - SSL not enabled in Apache
        // if (app()->isProduction()) {
        //     URL::forceScheme('https');
        // }

        // Database query logging for debugging
        if (config('app.debug')) {
            DB::listen(function ($query) {
                Log::debug('Query executed', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            });
        }

        // Configure default cache TTL
        Cache::macro('tenantRemember', function ($key, $ttl, $callback) {
            $tenantId = auth()->check() ? auth()->user()->company_id : 'global';
            return Cache::remember("tenant:{$tenantId}:{$key}", $ttl, $callback);
        });

        // Configure pagination defaults
        Model::unguard();
    }
}
