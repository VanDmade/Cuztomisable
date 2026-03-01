<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{

    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        if (!Auth::check()) {
            abort(403, __('cuztomisable/global.unauthorized'));
        }
        $user = Auth::user();
        $permissions = trim($permissions);
        if (str_contains($permissions, '|')) {
            // OR: permission1|permission2
            $perms = array_filter(array_map('trim', explode('|', $permissions)));
            if (!collect($perms)->some(fn($perm) => $user->hasPermission($perm))) {
                abort(403, __('cuztomisable/global.unauthorized'));
            }
        } elseif (str_contains($permissions, ',')) {
            // AND: permission1,permission2
            $perms = array_filter(array_map('trim', explode(',', $permissions)));
            if (!collect($perms)->every(fn($perm) => $user->hasPermission($perm))) {
                abort(403, __('cuztomisable/global.unauthorized'));
            }
        } else {
            // Single permission
            if (!$user->hasPermission($permissions)) {
                abort(403, __('cuztomisable/global.unauthorized'));
            }
        }
        return $next($request);
    }

}