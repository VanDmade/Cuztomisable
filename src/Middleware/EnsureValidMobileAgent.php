<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidMobileAgent
{

    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = (string) $request->header('User-Agent', '');
        // Optional: also check X-App-Platform for layered verification
        $isMobile = $request->header('X-App-Platform') === 'mobile';
        $enabled = (bool) config('cuztomisable.login.mobile_agent.enabled', true);
        $apps = (array) config('cuztomisable.login.mobile_agent.apps', []);
        $platforms = (array) config('cuztomisable.login.mobile_agent.platforms', ['Android', 'iOS', 'Other']);
        $logInvalid = (bool) config('cuztomisable.login.mobile_agent.log_invalid', false);
        if ($isMobile && $enabled) {
            $platformPattern = implode('|', array_map('preg_quote', $platforms));
            foreach ($apps as $app) {
                $appName = is_array($app) ? ($app['name'] ?? '') : (string) $app;
                if ($appName === '') {
                    continue;
                }
                $appPattern = preg_quote($appName, '/');
                if (preg_match('/'.$appPattern.'\/v([0-9]+\.[0-9]+(?:\.[0-9]+)?) \(('.$platformPattern.')\)/', $userAgent, $matches)) {
                    $version = $matches[1] ?? null;
                    $minVersion = is_array($app) ? ($app['min_version'] ?? null) : null;
                    if ($minVersion && $version && version_compare($version, $minVersion, '<')) {
                        return response()->json([
                            'message' => __('cuztomisable/global.upgrade'),
                            'upgrade_required' => true,
                            'min_version' => $minVersion,
                        ], 426);
                    }
                    return $next($request);
                }
            }
            if (!empty($apps)) {
                if ($logInvalid) {
                    Log::warning('Invalid mobile user agent', [
                        'user_agent' => $userAgent,
                        'apps' => $apps,
                    ]);
                }
                return response()->json([
                    'message' => __('cuztomisable/global.invalid_user_agent'),
                    'upgrade_required' => false,
                ], 403);
            }
        }
        return $next($request);
    }

}