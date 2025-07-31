<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use VanDmade\Cuztomisable\Models\Users as UserModels;
use Auth;
use DB;
use Exception;

class IpAddressController extends Controller
{

    public function get($id)
    {
        try {
            $ip = (!Auth::user()->admin ? UserModels\IpAddress() : Auth::user()->ipAddresses())
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

    public function table(TablelifyRequest $request, $id = null)
    {
        try {
            $data = $request->validated();
            $user = is_null($id) || !Auth::user()->admin ? Auth::user() : config('auth.providers.users.model')::where('id', '=', $id)->first();
            $query = $user->ipAddresses()
                ->select('user_ip_addresses.id', 'user_ip_addresses.ip_address',
                    'user_ip_addresses.last_used_at', 'user_ip_addresses.remember_until',
                DB::raw('IF(user_ip_addresses.remember_until > NOW(), true, false) as remember'))
                ->where(function ($query) use ($data) {
                    $query->where('user_ip_addresses.ip_address', 'LIKE', $data['search']);
                });
            $parameters = [
                'columns' => [
                    
                ],
            ];
            return Tablelify::run($query, $data, $parameters);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function forget($id)
    {
        try {
            $ip = (!Auth::user()->admin ? UserModels\IpAddress() : Auth::user()->ipAddresses())
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
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id)
    {
        try {
            $ip = (!Auth::user()->admin ? UserModels\IpAddress() : Auth::user()->ipAddresses())
                ->where('id', '=', $id)
                ->withTrashed()
                ->first();
            if (!isset($ip->id)) {
                throw new Exception(__('cuztomisable/user.ip_address.errors.not_found'), 404);
            }
            $deleted = $ip->trashed();
            // Resets OR sets the deleted at parameters for soft deletion
            $ip->deleted_by = $deleted ? null : Auth::user()->id;
            $ip->deleted_at = $deleted ? null : date('Y-m-d H:i:s');
            $ip->save();
            return $this->success([
                'message' => __('cuztomisable/user.ip_address.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}
