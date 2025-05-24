<?php

namespace VanDmade\Cuztomisable\Controllers;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\TableRequest;
use VanDmade\Cuztomisable\Requests\UserRequest;
use VanDmade\Cuztomisable\Models\Users;
use Auth;
use DB;
use Exception;

class UserController extends Controller
{

    public function get($id = null)
    {
        try {
            $user = is_null($id) ? Auth::user() : Users\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
            }
            return $this->success([
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->defaultPhone,
                    'address' => $user->defaultAddress,
                    'image' => !is_null($user->profile) ? $user->profile->output() : null,
                ],
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request)
    {
        try {
            $data = $request->validated();
            $query = null;
            $parameters = [];
            return $this->table($query, $data, $parameters);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(UserRequest $request, $id = null)
    {
        try {
            $data = $request->validated();
            return $this->debug($data);
            if (is_null($id)) {
                $user = new Users\User();
            } else {
                $user = Users\User::where('id', '=', $id)->first();
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
            $user->save();
            // TODO :: Add in the ability to upload an image
            return $this->success([
                'message' => __('cuztomisable/user.saved'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleLocked($id)
    {
        try {
            $user = Users\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
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

    public function toggleDelete($id)
    {
        try {
            $user = Users\User::where('id', '=', $id)->withTrashed()->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
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
            return $this->success([
                'message' => 'Token refreshed',
            ])->withCookie(Auth::user()->generateAuthCookie());
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}
