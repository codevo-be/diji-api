<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertAuthCookieToHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookies->get('Authorization');
        $tenantId = $request->cookies->get('X-tenant');
        $request->headers->set('Authorization', $token);
        $request->headers->set('X-tenant', $tenantId);

        return $next($request);
    }
}
