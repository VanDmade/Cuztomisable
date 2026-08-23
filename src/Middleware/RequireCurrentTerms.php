<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use VanDmade\Cuztomisable\Services\TermsService;

class RequireCurrentTerms
{

    public function __construct(
        protected readonly TermsService $termsService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $this->termsService->needsToAccept(Auth::user())) {
            abort(403, __('cuztomisable/terms.errors.must_accept'));
        }
        return $next($request);
    }

}
