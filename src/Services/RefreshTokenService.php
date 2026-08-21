<?php

namespace VanDmade\Cuztomisable\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use VanDmade\Cuztomisable\Models\Personal\RefreshToken;

/**
 * Orchestration for mobile refresh tokens - shared between LoginService/MfaService (issuing one
 * on a successful mobile login) and UserService (finding/renewing one on /refresh/token), rather
 * than each touching the RefreshToken model independently.
 */
class RefreshTokenService
{

    public function issue(Model $user): string
    {
        $plainToken = Str::random(64);
        RefreshToken::create([
            'user_id' => $user->id,
            'token' => Hash::make($plainToken),
            'expires_at' => now()->addDays(config('cuztomisable.mobile.refresh.expires_in', 30)),
        ]);
        return $plainToken;
    }

    public function findValid(string $plainToken): ?RefreshToken
    {
        return RefreshToken::where('expires_at', '>=', now())
            ->where('revoked', false)
            ->get()
            ->first(fn($item) => Hash::check($plainToken, $item->token));
    }

    public function renew(RefreshToken $token): ?string
    {
        $newToken = null;
        // Determines if they want tokens to refresh or just the expiration date
        if (config('cuztomisable.mobile.refresh.reset_token', false)) {
            $newToken = Str::random(64);
            $token->token = Hash::make($newToken);
        }
        $token->used_at = now();
        $token->expires_at = now()->addDays(config('cuztomisable.mobile.refresh.expires_in', 30));
        $token->save();
        return $newToken;
    }

}
