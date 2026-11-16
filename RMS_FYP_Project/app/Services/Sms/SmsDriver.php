<?php

namespace App\Services\Sms;

interface SmsDriver
{
    /**
     * Send one SMS. Throw an exception if it could not be sent.
     */
    public function send(string $to, string $message): void;
}