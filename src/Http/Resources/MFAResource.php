<?php

namespace VanDmade\Cuztomisable\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MFAResource extends JsonResource
{

    public static $wrap = null;

    public function __construct(
        Model $user,
        protected readonly bool $remember,
        protected readonly ?string $mfaToken
    ) {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        return [
            'message' => __('cuztomisable/authentication.login.mfa_logged_in'),
            'multi_factor_authentication' => true,
            'remember' => $this->remember,
            'user' => UserResource::forUser($this->resource),
            'permissions' => $this->resource->permissionSlugs(),
            'change_password' => $this->resource->change_password,
            'token' => $this->mfaToken,
        ];
    }

}
