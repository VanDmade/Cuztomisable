<?php

namespace VanDmade\Cuztomisable\Services;

use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use VanDmade\Cuztomisable\Models\Logs;
use Exception;
use Illuminate\Support\Facades\Log;

class SmsService
{

    protected ?SnsClient $client = null;
    protected bool $debug;
    protected bool $verifyTls;

    public function __construct()
    {
        $this->debug = (bool) config('app.debug', false);
        $this->verifyTls = config('app.env') !== 'local';
    }

    protected function setup(): void
    {
        if ($this->client) {
            return;
        }
        $this->client = new SnsClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID', null),
                'secret' => env('AWS_SECRET_ACCESS_KEY', null),
            ],
            'http' => [
                // Can be removed whenever not on local development
                'verify' => $this->verifyTls,
            ],
        ]);
    }

    public function send(string $countryCode, string $number, string $message): bool
    {
        $parameters = [];
        $phone = null;
        $loggedMessage = $this->sanitizeMessage($message);
        try {
            // Generates the phone number with the country code and phone number
            $phone = '+'.trim($countryCode, '+').cleanPhone($number);
            // Disables the texts from sending to prevent costs from occurring
            if (!$this->debug) {
                $this->setup();
                $this->client->publish([
                    'Message' => $message,
                    'PhoneNumber' => $phone,
                    'MessageAttributes' => [
                        'AWS.SNS.SMS.SMSType' => [
                            'DataType' => 'String',
                            'StringValue' => 'Transactional',
                        ],
                    ],
                ]);
            } else {
                Log::debug($phone.': '.$message);
            }
            // Logs the text message for verification and traceability
            Logs\Text::create([
                'country_code' => $countryCode,
                'number' => $number,
                'message' => $loggedMessage,
                'parameters' => [
                    'cleaned_phone' => $phone,
                    'log' => $this->debug,
                    'redacted' => $loggedMessage !== $message,
                ]
            ]);
            return true;
        } catch (AwsException $error) {
            $parameters = [
                'aws_message' => $error->getAwsErrorMessage(),
                'aws_code' => $error->getAwsErrorCode(),
                'request_id' => $error->getRequestId(),
            ];
        } catch (Exception $error) {
            // Error caught not associated with AWS
        }
        // Appends the phone's information to the log to allow for ease of traceback
        $parameters['phone'] = [
            'country_code' => $countryCode,
            'number' => $number,
            'message' => $loggedMessage,
            'cleaned_phone' => $phone ?? null,
            'redacted' => $loggedMessage !== $message,
        ];
        Logs\Error::create([
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile() ?? null,
            'line' => $error->getLine() ?? null,
            'parameters' => $parameters ?? [],
        ]);
        return false;
    }

    protected function sanitizeMessage(string $message): string
    {
        $redact = (bool) config('cuztomisable.notifications.texts.redact_message', false);
        if (!$redact) {
            return $message;
        }
        $replacement = (string) config('cuztomisable.notifications.texts.redact_replacement', '********');
        $patterns = config('cuztomisable.notifications.texts.redact_patterns', []);
        if (empty($patterns)) {
            return $replacement;
        }
        $redacted = preg_replace($patterns, $replacement, $message);
        return $redacted === null ? $replacement : $redacted;
    }

}