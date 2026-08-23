<?php

namespace VanDmade\Cuztomisable\Sms;

use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use Exception;
use Illuminate\Support\Facades\Log;
use VanDmade\Cuztomisable\Services\Logs\ErrorLogService;

class AwsSnsSmsProvider implements SmsProviderInterface
{

    protected ?SnsClient $client = null;
    protected bool $debug;
    protected bool $verifyTls;

    public function __construct(
        protected readonly ErrorLogService $errorLogService
    ) {
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
            'message' => $message,
            'cleaned_phone' => $phone ?? null,
        ];
        $this->errorLogService->log($error, 'SMS send failed', $parameters ?? []);
        return false;
    }

}
