<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;
use VanDmade\Cuztomisable\Http\Resources\UserResource;

/**
 * Base controller every Cuztomisable controller extends - success()/error()/debug() response
 * shaping lives here directly (the former Helpers/Respondify.php's job), since nothing outside
 * a controller ever calls it. Outbound mail/SMS is now each service's own responsibility
 * (injecting Mail/SmsProviderInterface directly) rather than a controller convenience method,
 * so this base class no longer carries an SmsService dependency at all.
 */
class CuztomisableController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function success(mixed $parameters): JsonResponse
    {
        // Makes sure the information sent in is an array, if it isn't it forces the data type
        $parameters = !is_array($parameters) ? [$parameters] : $parameters;
        return response()->json($parameters, 200);
    }

    public function error(Throwable $error, array $parameters = []): JsonResponse
    {
        $debug = env('APP_DEBUG', false);
        $debugCode = uniqid();
        // Sets the message in a variable to remove server, SQL, and code errors that will make no sense to the user
        $message = isset($parameters['message']) ? $parameters['message'] : $error->getMessage();
        $responseCode = isset($parameters['code']) ? $parameters['code'] : $error->getCode();
        if ($responseCode == 0) {
            $responseCode = 500;
        }
        if (!$debug) {
            // Lists out the cleaned error messages to prevent a user from recieving a message that doesn't make sense
            if (strpos($message, 'SQLERROR') !== false) {
                // Write default message for an SQL error
                $message = __('cuztomisable/global.sql_error');
                $responseCode = config('cuztomisable.respondify.errors.sql_error_code', 500);
            }
        }
        // Logs the errors of the site
        if (config('cuztomisable.respondify.errors.log', false) && !is_null($model = config('cuztomisable.respondify.errors.model', null))) {
            $model = new $model();
            $model->user_id = Auth::check() ? Auth::user()->id : null;
            $args = [];
            foreach (['message', 'line', 'file', 'code'] as $i => $key) {
                $value = null;
                switch ($key) {
                    case 'message': $value = $error->getMessage(); break;
                    case 'line': $value = $error->getLine(); break;
                    case 'file': $value = $error->getFile(); break;
                    case 'code': $value = $error->getCode(); break;
                }
                if (!is_null($column = config('cuztomisable.respondify.errors.columns.'.$key))) {
                    $model->$column = $value;
                } else {
                    $args[$key] = $value;
                }
            }
            if (!is_null(config('cuztomisable.respondify.errors.columns.parameters', null))) {
                $args['response'] = [
                    'message' => $message != $error->getMessage() ? $message : null,
                    'code' => $responseCode != $error->getCode() ? $responseCode : null,
                ];
                $model->parameters = $args;
            }
            $model->save();
        }
        if (is_numeric($responseCode) && $responseCode >= 500) {
            $responseCode = 500;
        }
        return response()->json(
            array_merge([
                'success' => false,
                'debug_code' => $debugCode,
                'message' => $message,
            ],
            // Appends debugging information that should only be displayed to certain users
            $debug ? [
                'line' => $error->getLine() ?? null,
                'file' => $error->getFile() ?? null,
            ] : []
        ), is_numeric($responseCode) ? $responseCode : 500);
    }

    public function debug(mixed $parameters): JsonResponse
    {
        // Makes sure the information sent in is an array, if it isn't it forces the data type
        $parameters = !is_array($parameters) ? [$parameters] : $parameters;
        return response()->json($parameters, config('cuztomisable.respondify.debugging_response_code', 500));
    }

    protected function actorId(): string
    {
        return (string) (Auth::id() ?? 0);
    }

    /**
     * Shared "authenticated session established" response envelope for both a completed
     * login and a completed MFA save - the two produce the same shape (user, permissions,
     * change_password, then either mobile tokens or a cookie), so this collapses what used
     * to be two independently hand-built, drifting versions of the same response.
     */
    protected function authResponse(
        Model $user,
        array $result,
        bool $remember,
        bool $requiresMfa = false,
        ?string $mfaToken = null
    ): JsonResponse {
        $response = [
            'message' => __('cuztomisable/authentication.login.'.($requiresMfa ? 'mfa_' : '').'logged_in'),
            'multi_factor_authentication' => $requiresMfa,
            'remember' => $remember,
            'user' => new UserResource($user),
            'permissions' => $user->permissionSlugs(),
            'change_password' => $user->change_password,
        ];
        if ($mfaToken !== null) {
            $response['token'] = $mfaToken;
        }
        if (isset($result['access_token'])) {
            $response['access_token'] = $result['access_token'];
            $response['refresh_token'] = $result['refresh_token'];
            $response['expires_in'] = config('cuztomisable.login.session_length', 900);
            return $this->success($response);
        }
        $response = $this->success($response);
        return isset($result['cookie']) ? $response->withCookie($result['cookie']) : $response;
    }

    protected function rateLimit(
        string $key,
        string $messageKey,
        int $status = 429,
        ?int $maxAttempts = null,
        ?int $decaySeconds = null,
        ?string $configKey = null
    ): void {
        if ($configKey !== null) {
            $maxAttempts = $maxAttempts ?? (int) config("cuztomisable.rate_limits.$configKey.attempts", 5);
            $decaySeconds = $decaySeconds ?? (int) config("cuztomisable.rate_limits.$configKey.decay_seconds", 60);
        }
        $maxAttempts = $maxAttempts ?? (int) config('cuztomisable.rate_limits.default.attempts', 5);
        $decaySeconds = $decaySeconds ?? (int) config('cuztomisable.rate_limits.default.decay_seconds', 60);
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new Exception(__($messageKey), $status);
        }
        RateLimiter::hit($key, $decaySeconds);
    }

}
