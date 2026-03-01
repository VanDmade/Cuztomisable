<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Personal\RefreshToken;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Requests\Users\RefreshRequest;
use VanDmade\Cuztomisable\Requests\Users\UserRequest;
use VanDmade\Cuztomisable\Services\ImageService;
use VanDmade\Cuztomisable\Services\SmsService;

class UserController extends Controller
{

    public function __construct(
        protected readonly ImageService $imageService,
        SmsService $sms
    ) {
        parent::__construct($sms);
    }

    public function get($id = null): JsonResponse
    {
        try {
            $user = is_null($id) || !Auth::user()->admin ? Auth::user() : config('auth.providers.users.model')::where('id', '=', $id)->first();
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

    public function table(TablelifyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $query = config('auth.providers.users.model')::select('users.id', 'users.name', 'users.email',
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
                ->where(function ($query) {
                    $query->whereNotNull('users.id');
                });
            /*$data['columns'] = [
                'name_with_email' => 'users.name',
                'last_used_at' => 'ip.last_used_at',
            ];*/
            $parameters = [
                'allowed_columns' => [
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.username',
                    'ip.last_used_at',
                    'users.email_verified_at',
                    'users.admin',
                    'users.locked',
                ],
                'search_columns' => ['users.name', 'users.email', 'users.username', 'p.number'],
                'allowed_filters' => ['users.admin', 'users.locked'],
                'default_columns' => ['users.id' => 'desc'],
            ];
            return Tablelify::run($query, array_merge($data, $parameters));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(UserRequest $request, $id = null): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $data = $request->validated();
                $this->rateLimit(
                    'cuztomisable:users:save:'.implode(':', [$request->ip(), $this->actorId(), (string) ($id ?? 'self')]),
                    'cuztomisable/user.errors.not_found'
                );
                $user = !Auth::user()->admin || is_null($id) ?
                    Auth::user() : config('auth.providers.users.model')::where('id', '=', $id)->first();
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
                }
                $user->name = $data['name'] ?? null;
                $user->username = $data['username'] ?? null;
                $user->email = $data['email'];
                $user->timezone = $data['timezone'] ?? 'America/New_York';
                if (config('cuztomisable.login.multi_factor_authentication.allowed', true) && !empty($data['mfa'])) {
                    $user->multi_factor_authentication = $data['mfa'] == '1' ? true : false;
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
                    $image = $this->imageService->upload($request->file('image'));
                    $user->image_id = $image->id;
                    $user->save();
                } elseif (!empty($data['clear_image']) && $data['clear_image'] == '1') {
                    if (!is_null($user->image_id)) {
                        $this->imageService->delete($user->image_id, true);
                    }
                    $user->image_id = null;
                    $user->save();
                }
                // Refreshes the user model to make suer everything is updated
                $user->refresh();
                return $this->success([
                    'message' => __('cuztomisable/user.saved'),
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
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleLocked($id = null): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:users:toggle_locked:'.implode(':', [$this->actorId(), (string) ($id ?? 'self')]),
                    'cuztomisable/user.errors.not_found'
                );
                $user = !Auth::user()->admin ? Auth::user() : config('auth.providers.users.model')::where('id', '=', $id)->first();
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
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id = null): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:users:toggle_delete:'.implode(':', [$this->actorId(), (string) ($id ?? 'self')]),
                    'cuztomisable/user.errors.not_found'
                );
                $user = !Auth::user()->admin ? Auth::user() : config('auth.providers.users.model')::where('id', '=', $id)->withTrashed()->first();
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
                }
                if ($user->id == Auth::user()->id) {
                    throw new Exception(__('cuztomisable/user.errors.delete_my_account'), 404);
                }
                $deleted = $user->trashed();
                if ($deleted) {
                    $user->restore();
                } else {
                    $user->delete();
                }
                return $this->success([
                    'message' => __('cuztomisable/user.'.($deleted ? 'undo' : 'deleted')),
                    'deleted' => $deleted,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleMfa($id = null): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:users:toggle_mfa:'.implode(':', [$this->actorId(), (string) ($id ?? 'self')]),
                    'cuztomisable/user.errors.not_found'
                );
                $user = !Auth::user()->admin || empty($id) ?
                    Auth::user() : config('auth.providers.users.model')::where('id', '=', $id)->first();
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
                }
                $user->multi_factor_authentication = !$user->multi_factor_authentication;
                $user->save();
                $type = $user->multi_factor_authentication ? 'enabled' : 'disabled';
                return $this->success([
                    'message' => __('cuztomisable/user.mfa.'.$type),
                    'enabled' => $user->multi_factor_authentication ? true : false,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function list(): JsonResponse
    {
        try {
            $list = [];
            foreach (config('auth.providers.users.model')::all() as $i => $user) {
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

    public function refresh(): JsonResponse
    {
        try {
            if (!Auth::check()) {
                throw new Exception('Unauthenticated', 401);
            }
            return DB::transaction(function () {
                $this->rateLimit(
                    'cuztomisable:users:refresh:'.implode(':', [request()->ip(), $this->actorId()]),
                    'Unauthenticated',
                    401
                );
                $user = Auth::user();
                $cookie = $user->generateAuthCookie();
                $latestToken = $user->tokens()->latest('id')->first();
                return $this->success([
                    'message' => 'Token refreshed',
                    'token_expires_at' => optional($latestToken->expires_at)->toIso8601String(),
                ])->withCookie($cookie);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function refreshToken(RefreshRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            return DB::transaction(function () use ($data, $request) {
                $this->rateLimit(
                    'cuztomisable:users:refresh_token:'.implode(':', [$request->ip()]),
                    'cuztomisable/user.refresh.errors.not_found'
                );
                $newToken = null;
                // Find refresh token in DB
                $candidates = RefreshToken::where('expires_at', '>=', now())
                    ->where('revoked', false)
                    ->get();
                $token = $candidates->first(function ($item) use ($data) {
                    return Hash::check($data['token'], $item->token);
                });
                if (!$token) {
                    throw new Exception(__('cuztomisable/user.refresh.errors.not_found'), 401);
                }
                // Makes sure the user exists and is still active in the system
                if (is_null($token->user)) {
                    throw new Exception(__('cuztomisable/user.refresh.errors.user_not_found'), 404);
                }
                // Checks to see if the token was revoked
                if ($token->revoked) {
                    throw new Exception(__('cuztomisable/user.refresh.errors.revoked'), 403);
                }
                // Determines if they want tokens to refresh or just the expiration date
                if (config('cuztomisable.mobile.refresh.reset_token', false)) {
                    $newToken = Str::random(64);
                    $token->token = Hash::make($newToken);
                }
                $token->used_at = now();
                $token->expires_at = now()->addDays(config('cuztomisable.mobile.refresh.expires_in', 30));
                $token->save();
                // Issue a new access token
                $accessToken = $token->user->createToken('mobile')->plainTextToken;
                return $this->success([
                    'access_token' => $accessToken,
                    'refresh_token' => $newToken ?? null,
                    'expires_in' => config('cuztomisable.login.session_length', 900),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function verification($token, $type): JsonResponse|RedirectResponse
    {
        try {
            return DB::transaction(function () use ($token, $type) {
                $this->rateLimit(
                    'cuztomisable:users:verification:'.implode(':', [request()->ip(), $token, $type]),
                    'cuztomisable/user.errors.not_found'
                );
                $hasError = true;
                $user = config('auth.providers.users.model')::where('token', $token)->first();
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
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function unsubscribe($token, $type): JsonResponse|RedirectResponse
    {
        try {
            return DB::transaction(function () use ($token, $type) {
                $this->rateLimit(
                    'cuztomisable:users:unsubscribe:'.implode(':', [request()->ip(), $token, $type]),
                    'cuztomisable/user.errors.not_found'
                );
                $hasError = true;
                $user = config('auth.providers.users.model')::where('token', $token)->first();
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
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}
