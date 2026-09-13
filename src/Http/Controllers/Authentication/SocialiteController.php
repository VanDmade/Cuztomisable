<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Services\Authentication\SocialiteService;

/**
 * Sends the user to the provider to log in, then handles them coming back.
 */
class SocialiteController extends CuztomisableController
{

    public function __construct(
        protected readonly SocialiteService $socialiteService
    ) {
    }

    public function redirect(string $provider): RedirectResponse
    {
        try {
            return $this->socialiteService->redirect($provider);
        } catch (Throwable $error) {
            return redirect(url('/message?m='.urlencode($error->getMessage())));
        }
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        try {
            $isMobile = $request->header('X-App-Platform') === 'mobile';
            $result = $this->socialiteService->callback($provider, $isMobile);
            if ($result['requires_mfa']) {
                return redirect(url('/mfa/'.$result['mfa_token']));
            }
            $redirect = redirect(url(config('cuztomisable.app.home', '/portal')));
            return isset($result['cookie']) ? $redirect->withCookie($result['cookie']) : $redirect;
        } catch (Throwable $error) {
            return redirect(url('/message?m='.urlencode($error->getMessage())));
        }
    }

}
