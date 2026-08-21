<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generic rate limiter, driven entirely from the route definition - no per-action registration
 * needed. First argument is the action name (used as both the cache key prefix and the
 * cuztomisable.rate_limits.<name>.attempts/decay_seconds config lookup); the rest describe what
 * to fold into the key:
 *
 *   actor - The authenticated user's id
 *   ip - The request's IP address
 *   params=name - Route/URL parameter (e.g. {id})
 *   request=name - Request input field (e.g. body/query "username")
 *   message=key - Translation key to use on a 429 instead of the generic default
 *
 * Usage: ->middleware('throttler:ip_addresses.toggle_delete,actor,params=id')
 *        ->middleware('throttler:passwords.forgot,ip,request=username')
 *        ->middleware('throttler:passwords.forgot,ip,request=username,message=cuztomisable/authentication.passwords.errors.already_sent')
 *
 * Returns cuztomisable/global.rate_limited on a hit unless a message=key flag overrides it -
 * the action-specific "not found"-style messages the old per-controller rateLimit() calls
 * reused were never really an honest description of "you're being rate limited" anyway, but a
 * few actions may still want their own wording.
 */
class Throttler
{

    public function handle(
        Request $request,
        Closure $next,
        string $action,
        string ...$flags
    ): Response {
        $defaultMessage = __('cuztomisable/global.rate_limited');
        $keyParts = [];
        foreach ($flags as $flag) {
            if ($flag === 'actor') {
                $keyParts[] = (string) (Auth::id() ?? 0);
            } elseif ($flag === 'ip') {
                $keyParts[] = (string) $request->ip();
            } elseif (str_starts_with($flag, 'params=')) {
                $value = $request->route(substr($flag, 7));
                // Route-model-bound parameters (e.g. {registration}) resolve to the model
                // itself, not a raw id - use the key rather than fatal on a string cast.
                $keyParts[] = (string) (is_object($value) ? $value->getKey() : ($value ?? ''));
            } elseif (str_starts_with($flag, 'request=')) {
                $keyParts[] = (string) ($request->input(substr($flag, 8)) ?? '');
            } elseif (str_starts_with($flag, 'message=')) {
                // The value here is the translation key itself (e.g. cuztomisable/x.y.z), not a
                // request field to look up - overrides the default message on a 429.
                $message = __(substr($flag, 8));
            }
        }
        $key = 'cuztomisable:'.$action.':'.implode(':', $keyParts);
        $defaultMaxAttempts = (int) config('cuztomisable.rate_limits.default.attempts', 5);
        $defaultDecaySeconds = (int) config('cuztomisable.rate_limits.default.decay_seconds', 60);
        $maxAttempts = (int) config("cuztomisable.rate_limits.$action.attempts", $defaultMaxAttempts);
        $decaySeconds = (int) config("cuztomisable.rate_limits.$action.decay_seconds", $defaultDecaySeconds);
        // DEtermines if the user has exceeded the number of attempts for this specific action
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => $message ?? $defaultMessage,
            ], 429);
        }
        RateLimiter::hit($key, $decaySeconds);
        return $next($request);
    }

}
