<?php

namespace VanDmade\Cuztomisable\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use VanDmade\Cuztomisable\Models\Social\SocialAccount;

/**
 * Orchestration for linking/unlinking social provider accounts via Socialite.
 */
class SocialAccountService
{


    public function findOrCreateUser(string $provider, SocialiteUserContract $socialiteUser): Model
    {
        $userModel = config('auth.providers.users.model');
        $email = $socialiteUser->getEmail();
        $user = $email ? $userModel::findUserByType($email, 'email') : null;
        if (!isset($user->id)) {
            $user = $userModel::create([
                'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: 'New User',
                'email' => $email ?? (Str::uuid().'@'.$provider.'.cuztomisable.com'),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
                'locked' => false,
                // Disable emails if the provider did not share an email address.
                'disable_emails' => empty($email),
            ]);
        }
        $this->link($user, $provider, $socialiteUser);
        return $user;
    }

    public function findByProvider(string $provider, string $providerUserId): ?SocialAccount
    {
        return SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    public function link(Model $user, string $provider, SocialiteUserContract $socialiteUser): SocialAccount
    {
        return SocialAccount::updateOrCreate(
            [
                'provider' => $provider,
                'provider_user_id' => $socialiteUser->getId(),
            ],
            [
                'user_id' => $user->id,
                'token' => $socialiteUser->token ?? null,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
                'expires_at' => isset($socialiteUser->expiresIn) ?
                    now()->addSeconds((int) $socialiteUser->expiresIn) : null,
            ]
        );
    }

    public function unlink(Model $user, string $provider): bool
    {
        return (bool) SocialAccount::where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();
    }

}
