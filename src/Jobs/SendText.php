<?php

namespace VanDmade\Cuztomisable\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use VanDmade\Cuztomisable\Events\TextSent;
use VanDmade\Cuztomisable\Sms\SmsProviderInterface;

class SendText implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected readonly ?int $createdBy;

    public function __construct(
        protected readonly string $countryCode,
        protected readonly string $number,
        protected readonly string $message,
        ?int $createdBy = null
    ) {
        $this->createdBy = $createdBy ?? Auth::id();
    }

    public function handle(SmsProviderInterface $smsProvider): void
    {
        $sent = $smsProvider->send($this->countryCode, $this->number, $this->message);
        if ($sent) {
            $cleanedPhone = '+'.trim($this->countryCode, '+').cleanPhone($this->number);
            TextSent::dispatch(
                $this->countryCode,
                $this->number,
                $this->message,
                $cleanedPhone,
                (bool) config('app.debug', false),
                $this->createdBy
            );
        }
    }

}
