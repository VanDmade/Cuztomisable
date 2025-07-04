<?php

namespace VanDmade\Cuztomisable\Services;

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use VanDmade\Cuztomisable\Models\Logs;
use Exception;
use Log;

class SmsService
{

    protected ?SNSClient $client = null;
    protected bool $debug;

    public function __construct()
    {
        $this->debug = env('APP_DEBUG', false);
    }

    protected function setup()
    {
        if ($this->client) return;
        $this->client = new SnsClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID', null),
                'secret' => env('AWS_SECRET_ACCESS_KEY', null),
            ],
            'http' => [
                // TODO :: Remove before going to production
                'verify' => env('APP_ENV') == 'local' ? false : true,
            ],
        ]);
    }

    public function send(string $countryCode, string $number, string $message): bool
    {
        try {
            $parameters = [];
            // Generates the phone number with the country code and phone number
            $phone = '+'.trim($countryCode, '+').cleanPhone($number);
            // Disables the texts from sending to prevent costs from occurring
            if (!$this->debug) {
                self::setup();
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
                'message' => $message,
                'parameters' => [
                    'cleaned_phone' => $phone,
                    'log' => $this->debug,
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
            'message' => $message,
            'cleaned_phone' => $phone ?? null,
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

}