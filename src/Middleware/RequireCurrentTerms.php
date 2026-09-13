<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use VanDmade\Cuztomisable\Services\TermsService;

/**
 * Blocks a request until the user accepts the current terms & conditions.
 */
class RequireCurrentTerms
{

    public function __construct(
        protected readonly TermsService $termsService
    ) {
    }

    // Must stay reachable even when terms are pending, or a user could never accept them (and
    // /me and /logout stay open so the frontend can still identify/sign out a blocked user).
    protected array $exempt = ['api/terms/*', 'api/me', 'api/logout', 'api/refresh', 'api/refresh/token'];

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('cuztomisable.terms.enabled', false) || $request->is($this->exempt)) {
            return $next($request);
        }
        if (Auth::check() && $this->termsService->needsToAccept(Auth::user())) {
            abort(403, __('cuztomisable/terms.errors.must_accept'));
        }
        return $next($request);
    }

}
