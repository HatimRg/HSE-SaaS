<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy
        $response->headers->set('Permissions-Policy', 
            'camera=(), microphone=(), geolocation=(self), payment=()');

        // HSTS (HTTP Strict Transport Security) in production
        if (app()->isProduction() && config('security.headers_hsts', true)) {
            $response->headers->set('Strict-Transport-Security', 
                'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy
        if (config('security.headers_csp', true)) {
            $csp = $this->generateCSP();
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Cache control for sensitive data
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Generate Content Security Policy
     */
    private function generateCSP(): string
    {
        $directives = [
            "default-src" => "'self'",
            "script-src" => "'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src" => "'self' 'unsafe-inline'",
            "img-src" => "'self' data: blob:",
            "font-src" => "'self'",
            "connect-src" => "'self'",
            "media-src" => "'self'",
            "object-src" => "'none'",
            "frame-ancestors" => "'none'",
            "base-uri" => "'self'",
            "form-action" => "'self'",
        ];

        // Add Vite dev server in development
        if (app()->isLocal()) {
            $directives['script-src'] .= " http://localhost:* ws://localhost:*";
            $directives['style-src'] .= " http://localhost:*";
            $directives['connect-src'] .= " http://localhost:* ws://localhost:*";
        }

        return collect($directives)
            ->map(fn($value, $key) => "{$key} {$value}")
            ->join('; ');
    }
}
