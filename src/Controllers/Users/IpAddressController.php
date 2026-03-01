<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use VanDmade\Cuztomisable\Models\Users as UserModels;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Requests\Users\IpAddressSaveRequest;

class IpAddressController extends Controller
{

    public function get($id): JsonResponse
    {
        try {
            $ip = $this->ipQuery()
                ->where('id', '=', $id)
                ->withTrashed()
                ->first();
            if (!isset($ip->id)) {
                throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
            }
            return $this->success([
                'ip' => $ip,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TablelifyRequest $request, $id = null): JsonResponse
    {
        try {
            $data = $request->validated();
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
                ->where(function ($query) {
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
            return Tablelify::run($query, array_merge($data, $parameters));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function forget($id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:ip_addresses:forget:'.implode(':', [$this->actorId(), (string) $id]),
                    'cuztomisable/user.ip_address.errors.not_found'
                );
                $ip = $this->ipQuery()
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
                return $this->success([
                    'message' => __('cuztomisable/user.ip_address.forgotten'),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:ip_addresses:toggle_delete:'.implode(':', [$this->actorId(), (string) $id]),
                    'cuztomisable/user.ip_address.errors.not_found'
                );
                $ip = $this->ipQuery()
                    ->where('id', '=', $id)
                    ->withTrashed()
                    ->first();
                if (!isset($ip->id)) {
                    throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
                }
                $deleted = $ip->trashed();
                if ($deleted) {
                    $ip->restore();
                } else {
                    $ip->delete();
                }
                return $this->success([
                    'message' => __('cuztomisable/user.ip_address.'.($deleted ? 'undo' : 'deleted')),
                    'deleted' => $deleted,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(IpAddressSaveRequest $request, $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $this->rateLimit(
                    'cuztomisable:ip_addresses:save:'.implode(':', [$this->actorId(), (string) $id]),
                    'cuztomisable/user.ip_address.errors.not_found'
                );
                $ip = $this->ipQuery()
                    ->where('id', '=', $id)
                    ->first();
                if (!isset($ip->id)) {
                    throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
                }
                $data = $request->validated();
                $ip->label = $data['label'] ?? null;
                $ip->save();
                return $this->success([
                    'message' => __('cuztomisable/user.ip_address.saved'),
                    'ip' => $ip,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    private function ipQuery(): Builder|HasMany
    {
        return Auth::user()->admin ?
            UserModels\IpAddress::query() :
            Auth::user()->ipAddresses();
    }

}
