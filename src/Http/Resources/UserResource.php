<?php

namespace VanDmade\Cuztomisable\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin' => $this->admin,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->defaultPhone,
            'address' => $this->defaultAddress,
            'mfa' => $this->multi_factor_authentication ?? false,
            'image' => !is_null($this->profile) ? $this->profile->output() : null,
        ];
    }

}
