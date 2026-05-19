<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class RequireCsrfUnlessMobile extends BaseVerifier
{

    public function handle($request, Closure $next)
    {
        if ($request->header('X-App-Platform') === 'mobile') {
            $apiKey = config('cuztomisable.app.mobile_agent.api_key');
            if (!empty($apiKey)) {
                $headerName = config('cuztomisable.app.mobile_agent.api_key_header', 'X-App-Key');
                $provided = $request->header($headerName);
                if (!hash_equals((string) $apiKey, (string) $provided)) {
                    abort(403, __('cuztomisable/global.unauthorized'));
                }
            }
            return $next($request);
        }
        return parent::handle($request, $next);
    }

}