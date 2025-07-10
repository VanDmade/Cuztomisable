<?php

namespace VanDmade\Cuztomisable\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use VanDmade\Cuztomisable\Helpers\Respondify;
use VanDmade\Cuztomisable\Services\SmsService;
use Illuminate\Support\Facades\Mail;

class Controller extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;
    
    private SmsService $sms;

    public function __construct()
    {
        $this->sms = new SmsService();
    }

    public function success($parameters)
    {
        return Respondify::success($parameters);
    }

    public function error($error, $parameters = [])
    {
        return Respondify::error($error, $parameters);
    }

    public function debug($parameters)
    {
        return Respondify::debug($parameters);
    }

    public function email($template, $email)
    {
        return Mail::to($email)->send($template);
    }

    public function text($message, $countryCode, $phone = null)
    {
        $countryCode = str_replace('+', '', $countryCode);  
        if (is_null($phone)) {
            $phone = explode(' ', $countryCode);
            // Allows the phone number to be sent in as a string with the country code and separates it appropriately
            if (!empty($phone[1])) {
                $countryCode = $phone[0];
                $phone = $phone[1];
            } else {
                throw new Exception(__('cuztomisable/global.server_broken'), 500);
            }
        }
        // Sends the text message
        return $this->sms->send($countryCode, $phone, $message);
    }

}
