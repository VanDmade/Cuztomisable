<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenFromCookie
{

    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('cuztomisable.login.cookie_name', 'api_token');
        if ($request->hasCookie($cookieName) && !$request->bearerToken()) {
            $token = $request->cookie($cookieName);
            $request->headers->set('Authorization', 'Bearer '.$token);
        }
        return $next($request);
    }

}
