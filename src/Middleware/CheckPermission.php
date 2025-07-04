<?php

namespace VanDmade\Cuztomisable\Middleware;

use Auth;
use Closure;

class CheckPermission
{

    public function handle($request, Closure $next, $permissions)
    {
        if (!Auth::check()) {
            abort(403, __('cuztomisable/global.unauthorized'));
        }
        $user = Auth::user();
        if (str_contains($permissions, '|')) {
            // OR: permission1|permission2
            $perms = explode('|', $permissions);
            if (!collect($perms)->some(fn($perm) => $user->hasPermission($perm))) {
                abort(403, __('cuztomisable/global.unauthorized'));
            }
        } elseif (str_contains($permissions, ',')) {
            // AND: permission1,permission2
            $perms = explode(',', $permissions);
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