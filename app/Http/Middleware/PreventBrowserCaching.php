<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBrowserCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // App pages contain authenticated content. Avoid reusing a prior
        // dashboard response after the session has been logged out.
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
