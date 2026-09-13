<?php

namespace VanDmade\Cuztomisable\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a user for API responses.
 */
class UserResource extends JsonResource
{

    public static function forUser($user): JsonResource
    {
        $class = config('cuztomisable.resources.user', static::class);
        return new $class($user);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin' => $this->admin,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'disable_emails' => $this->disable_emails ?? false,
            'phone' => $this->defaultPhone,
            'address' => $this->defaultAddress,
            'mfa' => $this->multi_factor_authentication ?? false,
            'timezone' => $this->timezone,
            'timezone_auto' => $this->timezone_auto ?? true,
            'image' => !is_null($this->profile) ? $this->profile->output() : null,
            'locked' => $this->locked ?? false,
            'created_at' => $this->created_at,
            'last_login_at' => $this->lastIpAddress?->last_used_at,
            'change_password_sent_at' => $this->change_password_sent_at,
        ];
    }

}
