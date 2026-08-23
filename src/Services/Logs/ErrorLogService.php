<?php

namespace VanDmade\Cuztomisable\Services\Logs;

use Throwable;
use VanDmade\Cuztomisable\Events\ErrorOccurred;
use VanDmade\Cuztomisable\Models\Logs\Error;

class ErrorLogService
{

    public function log(Throwable $error, string $context, array $parameters = []): Error
    {
        $log = Error::create([
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'parameters' => array_merge(['context' => $context], $parameters),
        ]);
        // Package-owned hook for host apps
        ErrorOccurred::dispatch($log);
        return $log;
    }

}
