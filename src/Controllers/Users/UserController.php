<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Requests\Users\UserRequest;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Image;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Users as UserModels;
use Auth;
use DB;
use Exception;
use Hash;
use Storage;

class UserController extends Controller
{

    public function get($id = null)
    {
        try {
            $user = is_null($id) || !Auth::user()->admin ? Auth::user() : UserModels\User::where('id', '=', $id)->first();
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
                'change_password' => $user->change_password,
                'permissions' => $user->permissionSlugs(),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TablelifyRequest $request)
    {
        try {
            $data = $request->validated();
            $query = UserModels\User::select('users.id', 'users.name', 'users.email',
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
            /*$data['columns'] = [
                'name_with_email' => 'users.name',
                'last_used_at' => 'ip.last_used_at',
            ];*/
            return Tablelify::run($query, $data);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(UserRequest $request, $id = null)
    {
        try {
            $data = $request->validated();
            $user = !Auth::user()->admin || is_null($id) ?
                Auth::user() : UserModels\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            $user->name = $data['name'] ?? null;
            $user->username = $data['username'] ?? null;
            $user->email = $data['email'];
            if (config('cuztomisable.login.multi_factor_authentication.allowed', true)) {
                $user->multi_factor_authentication = isset($data['mfa']) && $data['mfa'] == '1' ? true : false;
            }
            $user->save();
            // Determines if the phone is set up and entered
            if (!empty($data['phone'])) {
                $phone = $user->defaultPhone;
                if (!isset($phone->id)) {
                    $phone = new Phone();
                    $phone->user_id = $user->id;
                    $phone->default = true;
                }
                $phone->number = $data['phone'];
                $phone->country_code = $data['country_code'] ?? 1;
                $phone->save();
            }
            // Makes sure the address is entered or if it needs to be ignored
            if (config('cuztomisable.account.address') !== false && !empty($data['address'])) {
                $address = $user->defaultAddress;
                if (!isset($address->id)) {
                    $address = new Address();
                    $address->user_id = $user->id;
                    $address->default = true;
                }
                $address->address = $data['address'];
                $address->address_two = $data['address_two'] ?? null;
                $address->address_three = $data['address_three'] ?? null;
                $address->state_or_province = $data['state_or_province'];
                $address->city = $data['city'];
                $address->country = $data['country'];
                $address->zip_or_postal_code = $data['zip_or_postal_code'];
                $address->save();
            }
            // Checks to see if the image exists and is valid prior to uploading it to the bucket
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file = $request->file('image');
                // Generates a path for the image to be uploaded into
                $path = 'uploads/'.Auth::user()->id.'/'.(Auth::user()->token ?? 'token').'/';
                $path = $file->store($path, $disk = 's3');
                if (!Storage::disk('s3')->exists($path)) {
                    throw new Exception('Image not stored in S3: '.$path);
                }
                // Makes it so that the uploaded image is visible and can be used within the system
                Storage::disk($disk)->setVisibility($path, 'public');
                [$width, $height] = getimagesize($file->getRealPath());
                $image = Image::create([
                    'name' => $file->getClientOriginalName(),
                    'extension' => $file->getClientOriginalExtension(),
                    'path' => $path,
                    'disk' => $disk,
                    'parameters' => json_encode([
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'width' => $width ?? 0,
                        'height' => $height ?? 0,
                    ]),
                    'original' => true,
                ]);
                $user->image_id = $image->id;
                $user->save();
            }
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
            $user = !Auth::user()->admin ? Auth::user() : UserModels\User::where('id', '=', $id)->first();
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
            $user = !Auth::user()->admin ? Auth::user() : UserModels\User::where('id', '=', $id)->withTrashed()->first();
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
            $user = !Auth::user()->admin ? Auth::user() : UserModels\User::where('id', '=', $id)->first();
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
            foreach (UserModels\User::all() as $i => $user) {
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

    public function verification($token, $type)
    {
        try {
            $hasError = true;
            $user = UserModels\User::where('token', $token)->first();
            if (isset($user->id)) {
                if ($type === 'email') {
                    $email = request()->query('email');
                    if ($email && strcasecmp(trim($user->email), trim($email)) === 0) {
                        if (is_null($user->email_verified_at)) {
                            $user->email_verified_at = now();
                            $user->save();
                        }
                        $hasError = false;
                    }
                } elseif ($type === 'phone') {
                    $phone = str_replace(' ', '', request()->query('phone', ''));
                    foreach ($user->phones as $phoneRecord) {
                        $full = $phoneRecord->country_code.$phoneRecord->number;
                        if ($phone === $full) {
                            if (is_null($phoneRecord->verified_at)) {
                                $phoneRecord->verified_at = now();
                                $phoneRecord->save();
                            }
                            $hasError = false;
                            break;
                        }
                    }
                }
            }
            $messageKey = $hasError ? 'errors.invalid_verification' : 'verification';
            return redirect(url('/message?m='.__('cuztomisable/user.'.$messageKey, ['type' => $type])));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function unsubscribe($token, $type)
    {
        try {
            $hasError = true;
            $user = UserModels\User::where('token', $token)->first();
            if (isset($user->id)) {
                if ($type === 'email') {
                    $email = request()->query('email');
                    if ($email && strcasecmp(trim($user->email), trim($email)) === 0) {
                        $user->disable_emails = true;
                        $user->save();
                        $hasError = false;
                    }
                } elseif ($type === 'phone') {
                    $phone = str_replace(' ', '', request()->query('phone', ''));
                    foreach ($user->phones as $phoneRecord) {
                        $full = $phoneRecord->country_code.$phoneRecord->number;
                        if ($phone === $full) {
                            $phoneRecord->disable_messages = true;
                            $phoneRecord->save();
                            $hasError = false;
                            break;
                        }
                    }
                }
            }
            $messageKey = $hasError ? 'errors.invalid_unsubscribe' : 'unsubscribe';
            return redirect(url('/message?m='.__('cuztomisable/user.'.$messageKey, ['type' => $type])));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}
