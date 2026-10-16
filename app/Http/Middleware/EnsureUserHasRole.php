<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(
            $request->user() && $request->user()->hasRole(...$roles),
            Response::HTTP_FORBIDDEN,
            'You are not allowed to access this dashboard.',
        );

        return $next($request);
    }
}
