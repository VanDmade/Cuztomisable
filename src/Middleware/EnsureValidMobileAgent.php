<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidMobileAgent
{

    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent');
        // Optional: also check X-App-Platform for layered verification
        $isMobile = $request->header('X-App-Platform') === 'mobile';
        // Very simple check – make it stricter if needed
        if ($isMobile && !preg_match('/Cuztomisable\/v1\.0 \((Android|iOS|Other)\)/', $userAgent)) {
            return response()->json([
                'message' => 'Unauthorized – Invalid user agent.'
            ], 403);
        }
        return $next($request);
    }

}