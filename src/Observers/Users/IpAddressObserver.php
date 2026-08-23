<?php

namespace VanDmade\Cuztomisable\Observers\Users;

use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Events\NewIpAddressLogin;
use VanDmade\Cuztomisable\Jobs\SendText;
use VanDmade\Cuztomisable\Models\Users\IpAddress;
use VanDmade\Cuztomisable\Models\Logs;
use VanDmade\Cuztomisable\Mail\Users\NewIpAddress as NewIpAddressMail;

class IpAddressObserver
{

    public function creating(IpAddress $ipAddress): void
    {
        if (empty($ipAddress->fingerprint)) {
            $ipAddress->fingerprint = self::makeFingerprint();
        }
    }

    public function created(IpAddress $ipAddress): void
    {
        $user = $ipAddress->user;
        if (!$user) {
            return;
        }
        // Package-owned hook for host apps - fires regardless of the notification gates below,
        // since those only decide whether the built-in email/SMS goes out for this one.
        NewIpAddressLogin::dispatch($user, $ipAddress);
        if (config('cuztomisable.notifications.new_ip_address.enabled', true) === false) {
            return;
        }
        $trustedRanges = config('cuztomisable.notifications.new_ip_address.trusted_ip_ranges', []);
        if (!empty($trustedRanges) && self::ipInRanges($ipAddress->ip_address, $trustedRanges)) {
            return;
        }
        if (config('cuztomisable.notifications.new_ip_address.skip_known_devices', false)) {
            $known = IpAddress::withTrashed()
                ->where('user_id', $ipAddress->user_id)
                ->where('fingerprint', $ipAddress->fingerprint)
                ->where('id', '!=', $ipAddress->id)
                ->exists();
            if ($known) {
                return;
            }
        }
        // Each channel is its own independent toggle - both can fire for the same alert
        $sendVia = config('cuztomisable.notifications.new_ip_address.send_via', ['email' => true, 'phone' => false]);
        if (!empty($sendVia['email']) && !$user->disable_emails && !empty($user->email)) {
            Mail::to($user->email)->send(new NewIpAddressMail($user, $ipAddress));
        }
        if (!empty($sendVia['phone'])) {
            $phone = $user->mobilePhone;
            if (isset($phone->id) && !$phone->disable_messages) {
                $message = __('cuztomisable/text.new_ip_address', [
                    'company' => env('APP_NAME'),
                ]);
                SendText::dispatch($phone->country_code, $phone->number, $message);
            }
        }
    }

    public function saved(IpAddress $ipAddress): void
    {
        if (!$ipAddress->user_id) {
            return;
        }
        // Logs that the user was logged into the app
        Logs\User::create([
            'user_id' => $ipAddress->user_id,
            'description' => 'user logged into the application with correct information',
            'parameters' => [
                'sent_to_mfa' => $ipAddress->requireMfa(),
                'user_ip_address' => $ipAddress->id,
            ],
        ]);
    }

    private static function makeFingerprint(): string
    {
        $userAgent = (string) request()->header('User-Agent', '');
        $platform = (string) request()->header('X-App-Platform', '');
        $raw = trim($userAgent.'|'.$platform);
        return $raw !== '' ? hash('sha256', $raw) : '';
    }

    private static function ipInRanges(?string $ip, array $ranges): bool
    {
        if (empty($ip)) {
            return false;
        }
        foreach ($ranges as $range) {
            if (!is_string($range) || $range === '') {
                continue;
            }
            if (self::ipInCidr($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }
        [$subnet, $mask] = explode('/', $cidr, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $mask = (int) $mask;
        if ($mask < 0 || $mask > 32) {
            return false;
        }
        $maskLong = -1 << (32 - $mask);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

}
