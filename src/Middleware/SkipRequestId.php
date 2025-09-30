<?php

namespace BekAnd\TelescopeRequestTrack\Middleware;

use BekAnd\TelescopeRequestTrack\Helpers;
use Closure;
use Illuminate\Http\Request;

class SkipRequestId
{
    public function handle(Request $request, Closure $next)
    {
        Helpers::markSkip($request);

        return $next($request);
    }
}
