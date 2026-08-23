<?php

namespace VanDmade\Cuztomisable\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{

    public static $wrap = null;

    public function __construct(
        Model $user,
        protected readonly array $result,
        protected readonly bool $remember
    ) {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        $response = [
            'message' => __('cuztomisable/authentication.login.logged_in'),
            'multi_factor_authentication' => false,
            'remember' => $this->remember,
            'user' => UserResource::forUser($this->resource),
            'permissions' => $this->resource->permissionSlugs(),
            'change_password' => $this->resource->change_password,
        ];
        if (isset($this->result['access_token'])) {
            $response['access_token'] = $this->result['access_token'];
            $response['refresh_token'] = $this->result['refresh_token'];
            $response['expires_in'] = config('cuztomisable.login.session_length', 900);
        }
        return $response;
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        if (isset($this->result['cookie'])) {
            $response->withCookie($this->result['cookie']);
        }
    }

}
