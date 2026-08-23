<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Models\Address;

class AddressService
{

    public function setDefault(Model $user, array $data): Address
    {
        $address = $user->defaultAddress ?? new Address([
            'user_id' => $user->id,
            'default' => true,
        ]);
        $address->address = $data['address'];
        $address->address_two = $data['address_two'] ?? null;
        $address->address_three = $data['address_three'] ?? null;
        $address->state_or_province = $data['state_or_province'];
        $address->city = $data['city'];
        $address->country = $data['country'];
        $address->zip_or_postal_code = $data['zip_or_postal_code'];
        $address->save();
        return $address;
    }

    public function find($id): Address
    {
        $address = $this->query()
            ->where('id', '=', $id)
            ->withTrashed()
            ->first();
        if (!isset($address->id)) {
            throw new Exception(__('cuztomisable/user.address.errors.not_found'), 404);
        }
        return $address;
    }

    public function table(array $data, $id = null): JsonResponse
    {
        $user = is_null($id) || !Auth::user()->admin ?
            Auth::user() :
            config('auth.providers.users.model')::where('id', '=', $id)->first();
        $query = (Auth::user()->admin && is_null($id) ?
            Address::query() :
            $user->addresses())
            ->select('addresses.id', 'addresses.address', 'addresses.address_two', 'addresses.address_three',
                'addresses.city', 'addresses.state_or_province', 'addresses.zip_or_postal_code',
                'addresses.country', 'addresses.shipping', 'addresses.billing', 'addresses.default')
            ->where(function($query) {
                $query->whereNotNull('addresses.id');
            });
        if (Auth::user()->admin && !empty($data['filters'])) {
            foreach ($data['filters'] as $filter) {
                if (($filter['key'] ?? null) === 'user_id' && !empty($filter['value'])) {
                    $query->where('addresses.user_id', '=', $filter['value']);
                }
            }
        }
        $parameters = [
            'allowed_columns' => [
                'addresses.id',
                'addresses.city',
                'addresses.state_or_province',
                'addresses.country',
                'addresses.default',
            ],
            'search_columns' => ['addresses.address', 'addresses.city', 'addresses.zip_or_postal_code'],
            'default_columns' => ['addresses.default' => 'desc', 'addresses.id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function save(array $data, $id = null): Address
    {
        return DB::transaction(function() use ($data, $id) {
            $isNew = is_null($id);
            $address = $isNew ? new Address(['user_id' => Auth::id()]) : $this->query()->where('id', '=', $id)->first();
            if (!$isNew && !isset($address->id)) {
                throw new Exception(__('cuztomisable/user.address.errors.not_found'), 404);
            }
            $address->address = $data['address'];
            $address->address_two = $data['address_two'] ?? null;
            $address->address_three = $data['address_three'] ?? null;
            $address->state_or_province = $data['state_or_province'];
            $address->city = $data['city'];
            $address->country = $data['country'];
            $address->zip_or_postal_code = $data['zip_or_postal_code'];
            $address->shipping = !empty($data['shipping']) && $data['shipping'] == '1';
            $address->billing = !empty($data['billing']) && $data['billing'] == '1';
            if ($isNew) {
                // The first address a user adds becomes their default automatically
                $address->default = is_null($address->user->defaultAddress);
            }
            $address->save();
            return $address;
        });
    }

    public function makeDefault($id): Address
    {
        return DB::transaction(function() use ($id) {
            $address = $this->query()->where('id', '=', $id)->first();
            if (!isset($address->id)) {
                throw new Exception(__('cuztomisable/user.address.errors.not_found'), 404);
            }
            $address->user->addresses()
                ->where('id', '!=', $address->id)
                ->update(['default' => false]);
            $address->default = true;
            $address->save();
            return $address;
        });
    }

    public function toggleDelete($id): bool
    {
        return DB::transaction(function() use ($id) {
            $address = $this->query()->where('id', '=', $id)->withTrashed()->first();
            if (!isset($address->id)) {
                throw new Exception(__('cuztomisable/user.address.errors.not_found'), 404);
            }
            $deleted = $address->trashed();
            if ($deleted) {
                $address->deleted_by = null;
                $address->restore();
            } else {
                $address->delete();
            }
            return $deleted;
        });
    }

    private function query(): Builder|HasMany
    {
        return Auth::user()->admin ?
            Address::query() :
            Auth::user()->addresses();
    }

}
