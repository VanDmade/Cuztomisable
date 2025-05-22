<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;

class TokenFromCookie
{

    public function handle(Request $request, Closure $next)
    {
        if ($request->hasCookie('api_token') && !$request->bearerToken()) {
            $token = $request->cookie('api_token');
            $request->headers->set('Authorization', 'Bearer '.$token);
        }
        return $next($request);
    }

}
