<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Throwable;

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
        if (config('cuztomisable.respondify.errors.log', false) &&
            !is_null($model = config('cuztomisable.respondify.errors.model', null))) {
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

}
