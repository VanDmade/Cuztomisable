<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequireCsrfUnlessMobile extends BaseVerifier
{

    public function handle($request, Closure $next)
    {
        if ($request->header('X-App-Platform') === 'mobile') {
            return $next($request);
        }
        return parent::handle($request, $next);
    }

}