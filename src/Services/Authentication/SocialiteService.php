<?php

namespace VanDmade\Cuztomisable\Services\Authentication;

use Exception;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use VanDmade\Cuztomisable\Services\SocialAccountService;

/**
 * Handles the redirect-to-provider and callback legs of social login.
 */
class SocialiteService
{

    public function __construct(
        protected readonly LoginService $loginService,
        protected readonly SocialAccountService $socialAccountService
    ) {
    }

    public function redirect(string $provider): mixed
    {
        $this->ensureEnabled($provider);
        return $this->driver($provider)->redirect();
    }

    public function callback(string $provider, bool $isMobile = false): array
    {
        $this->ensureEnabled($provider);
        $socialiteUser = $this->driver($provider)->user();
        return DB::transaction(function() use ($provider, $socialiteUser, $isMobile) {
            $account = $this->socialAccountService->findByProvider($provider, $socialiteUser->getId());
            if (isset($account->id)) {
                $user = $account->user;
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/authentication.socialite.errors.failed'), 404);
                }
                $this->socialAccountService->link($user, $provider, $socialiteUser);
            } else {
                $user = $this->socialAccountService->findOrCreateUser($provider, $socialiteUser);
            }
            // A locked account stays locked regardless of how someone tries to sign in
            if ($user->locked) {
                throw new Exception(__('cuztomisable/authentication.socialite.errors.locked'), 401);
            }
            return $this->loginService->establishSession($user, $isMobile);
        });
    }

    private function driver(string $provider): mixed
    {
        return Socialite::driver($provider)->stateless();
    }

    private function ensureEnabled(string $provider): void
    {
        if (
            !config('cuztomisable.social.enabled', false) ||
            !config("cuztomisable.social.providers.{$provider}.enabled", false)
        ) {
            throw new Exception(__('cuztomisable/authentication.socialite.errors.disabled'), 404);
        }
    }

}
