<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Models\Users as UserModels;

/**
 * Orchestration for managing a user's known IP addresses.
 */
class IpAddressService
{

    public function get($id): UserModels\IpAddress
    {
        $ip = $this->query()
            ->where('id', '=', $id)
            ->withTrashed()
            ->first();
        if (!isset($ip->id)) {
            throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
        }
        return $ip;
    }

    public function table(array $data, $id = null): JsonResponse
    {
        $user = is_null($id) || !Auth::user()->admin ?
            Auth::user() :
            config('auth.providers.users.model')::where('id', '=', $id)->first();
        $query = (Auth::user()->admin && is_null($id) ?
            UserModels\IpAddress::query() :
            $user->ipAddresses())
            ->select('user_ip_addresses.id', 'user_ip_addresses.ip_address',
                'user_ip_addresses.label', 'user_ip_addresses.geo_label',
                'user_ip_addresses.latitude', 'user_ip_addresses.longitude',
                'user_ip_addresses.last_used_at', 'user_ip_addresses.remember_until',
            DB::raw('IF(user_ip_addresses.remember_until > NOW(), true, false) as remember'))
            ->where(function($query) {
                $query->whereNotNull('user_ip_addresses.id');
            });
        if (Auth::user()->admin && !empty($data['filters'])) {
            foreach ($data['filters'] as $filter) {
                if (($filter['key'] ?? null) === 'user_id' && !empty($filter['value'])) {
                    $query->where('user_ip_addresses.user_id', '=', $filter['value']);
                }
            }
        }
        $parameters = [
            'allowed_columns' => [
                'user_ip_addresses.id',
                'user_ip_addresses.ip_address',
                'user_ip_addresses.label',
                'user_ip_addresses.geo_label',
                'user_ip_addresses.latitude',
                'user_ip_addresses.longitude',
                'user_ip_addresses.last_used_at',
                'user_ip_addresses.remember_until',
            ],
            'search_columns' => ['user_ip_addresses.ip_address', 'user_ip_addresses.label', 'user_ip_addresses.geo_label'],
            'default_columns' => ['user_ip_addresses.last_used_at' => 'desc'],
        ];
        return TableService::run($query, array_merge($data, $parameters));
    }

    public function forget($id): void
    {
        DB::transaction(function() use ($id) {
            $ip = $this->query()
                ->where('id', '=', $id)
                ->withTrashed()
                ->first();
            if (!isset($ip->id)) {
                throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
            }
            // No reason to forget a non-remembered IP Address
            if (!$ip->remember) {
                throw new Exception(__('cuztomisable/user.ip_address.errors.not_remembered'), 202);
            }
            $ip->remember = false;
            $ip->remember_until = null;
            $ip->save();
        });
    }

    public function toggleDelete($id): bool
    {
        return DB::transaction(function() use ($id) {
            $ip = $this->query()
                ->where('id', '=', $id)
                ->withTrashed()
                ->first();
            if (!isset($ip->id)) {
                throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
            }
            $deleted = $ip->trashed();
            if ($deleted) {
                $ip->undo();
            } else {
                $ip->delete();
            }
            return $deleted;
        });
    }

    public function save(array $data, $id): UserModels\IpAddress
    {
        return DB::transaction(function() use ($data, $id) {
            $ip = $this->query()
                ->where('id', '=', $id)
                ->first();
            if (!isset($ip->id)) {
                throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
            }
            $ip->label = $data['label'] ?? null;
            $ip->save();
            return $ip;
        });
    }

    private function query(): Builder|HasMany
    {
        return Auth::user()->admin ?
            UserModels\IpAddress::query() :
            Auth::user()->ipAddresses();
    }

}
