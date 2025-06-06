<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HealthCheckMiddleware
{
    /**
     * Handle an incoming request.
     * Ce middleware s'assure que les health checks peuvent toujours passer
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si c'est une route de health check
        if ($this->isHealthCheckRoute($request)) {
            // Bypasser certaines vérifications pour les health checks
            // Par exemple, ignorer les vérifications CSRF, etc.
            return $next($request);
        }

        return $next($request);
    }

    /**
     * Détermine si la requête est pour un health check
     */
    private function isHealthCheckRoute(Request $request): bool
    {
        $path = $request->path();
        
        return str_starts_with($path, 'health/') || 
               str_starts_with($path, 'api/health/');
    }
} 