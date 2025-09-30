<?php

namespace BekAnd\TelescopeRequestTrack\Middleware;

use BekAnd\TelescopeRequestTrack\Helpers;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachRequestId
{
    public function handle(Request $request, Closure $next)
    {
        if (! Helpers::isEnabled() || Helpers::isExceptedUri($request)) {
            return $next($request);
        }

        $requestId = Helpers::resolveRequestId($request);

        $response = $next($request);

        if (Helpers::isSkipped($request)) {
            return $response;
        }

        $appliedId = Helpers::augmentResponse($response, Helpers::keyName(), $requestId);

        Helpers::storeRequestId($request, $appliedId, true);

        $response->headers->set(Helpers::headerName(), $appliedId);

        return $response;
    }
}
