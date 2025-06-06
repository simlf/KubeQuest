<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HealthCheckMiddleware
{
    /**
     * Handle an incoming request.
     * This middleware ensures that health checks can always pass through
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if this is a health check route
        if ($this->isHealthCheckRoute($request)) {
            // Bypass certain checks for health checks
            // For example, ignore CSRF verification, etc.
            return $next($request);
        }

        return $next($request);
    }

    /**
     * Determine if the request is for a health check
     */
    private function isHealthCheckRoute(Request $request): bool
    {
        $path = $request->path();
        
        return str_starts_with($path, 'health/') || 
               str_starts_with($path, 'api/health/');
    }
} 