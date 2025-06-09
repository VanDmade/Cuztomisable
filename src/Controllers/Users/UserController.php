<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Requests\Users\UserRequest;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use VanDmade\Cuztomisable\Models\Users;
use Auth;
use DB;
use Exception;
use Hash;

class UserController extends Controller
{

    public function get($id = null)
    {
        try {
            $user = is_null($id) || !Auth::user()->admin ? Auth::user() : Users\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            return $this->success([
                'user' => [
                    'id' => $user->id,
                    'admin' => $user->admin,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->defaultPhone,
                    'address' => $user->defaultAddress,
                    'mfa' => $user->multi_factor_authentication ?? false,
                    'image' => !is_null($user->profile) ? $user->profile->output() : null,
                ],
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TablelifyRequest $request)
    {
        try {
            $data = $request->validated();
            $query = Users\User::select('users.id', 'users.name', 'users.email',
                'users.username', 'ip.last_used_at', 'p.number as phone', 'p.country_code',
                'p.verified_at as phone_verified_at', 'users.email_verified_at',
                'users.admin', 'users.locked', 'users.multi_factor_authentication as mfa')
                ->leftJoin('phones as p', function($join) {
                    $join->on('p.user_id', '=', 'users.id')
                        ->where('p.default', '=', true);
                })
                ->leftJoin('user_ip_addresses as ip', function($join) {
                    $join->on('ip.user_id', '=', 'users.id')
                        // Grabs the latest login attempt for this user
                        ->whereRaw('ip.id=(SELECT temp.id FROM user_ip_addresses as temp WHERE temp.user_id=ip.user_id ORDER BY temp.last_used_at DESC LIMIT 1)');
                })
                ->where(function ($query) use ($data) {
                    $query->where('users.name', 'LIKE', $data['search'])
                        ->orWhere('users.email', 'LIKE', $data['search'])
                        ->orWhere('users.username', 'LIKE', $data['search'])
                        ->orWhere('p.number', 'LIKE', $data['search']);
                });
            $parameters = [];
            return Tablelify::run($query, $data, $parameters);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(UserRequest $request, $id = null)
    {
        try {
            $data = $request->validated();
            if (is_null($id)) {
                $user = new Users\User();
            } else {
                $user = !Auth::user()->admin ? Auth::user() : Users\User::where('id', '=', $id)->first();
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
                }
            }
            $user->first_name = $data['first_name'];
            $user->middle_name = $data['middle_name'] ?? null;
            $user->last_name = $data['last_name'];
            $user->suffix = $data['suffix'] ?? null;
            $user->title = $data['title'] ?? null;
            $user->username = $data['username'] ?? null;
            $user->email = $data['email'];
            $user->gender = $data['gender'] ?? null;
            $user->timezone = $data['timezone'] ?? null;
            if (config('cuztomisable.login.multi_factor_authentication.allowed', true)) {
                $user->multi_factor_authentication = isset($data['mfa']) && $data['mfa'] == '1' ? true : false;
            }
            $user->save();
            // TODO :: Add in the ability to upload an image
            return $this->success([
                'message' => __('cuztomisable/user.saved'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleLocked($id = null)
    {
        try {
            $user = !Auth::user()->admin ? Auth::user() : Users\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            $locked = $user->locked;
            $user->locked = !$locked;
            $user->save();
            return $this->success([
                'message' => __('cuztomisable/user.'.($locked ? 'unlocked' : 'locked')),
                'locked' => $locked,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id = null)
    {
        try {
            $user = !Auth::user()->admin ? Auth::user() : Users\User::where('id', '=', $id)->withTrashed()->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            if ($user->id == Auth::user()->id) {
                throw new Exception(__('cuztomisable/user.errors.delete_my_account'), 404);
            }
            $deleted = $user->trashed();
            // Resets OR sets the deleted at parameters for soft deletion
            $user->deleted_by = $deleted ? null : Auth::user()->id;
            $user->deleted_at = $deleted ? null : date('Y-m-d H:i:s');
            $user->save();
            return $this->success([
                'message' => __('cuztomisable/user.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleMfa($id = null)
    {
        try {
            $user = !Auth::user()->admin ? Auth::user() : Users\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            $user->multi_factor_authentication = !$user->multi_factor_authentication;
            $user->save();
            $type = $user->multi_factor_authentication ? 'enabled' : 'disabled';
            return $this->success([
                'message' => __('cuztomisable/user.mfa.'.$type),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function list()
    {
        try {
            $list = [];
            foreach (Users\User::all() as $i => $user) {
                $list[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'subtitle' => $user->email,
                ];
            }
            return $this->success([
                'list' => $list,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function refresh()
    {
        try {
            if (!Auth::check()) {
                throw new Exception('Unauthenticated', 401);
            }
            $cookie = Auth::user()->generateAuthCookie();
            return $this->success([
                'message' => 'Token refreshed',
                'token_expires_at' => Auth::user()->currentAccessToken()->expires_at->toIso8601String(),
            ])->withCookie($cookie);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}
