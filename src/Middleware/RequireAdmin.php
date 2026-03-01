<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAdmin
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->admin) {
            abort(403, __('cuztomisable/global.unauthorized'));
        }
        return $next($request);
    }

}
