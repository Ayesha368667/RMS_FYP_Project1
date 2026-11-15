<?php

namespace App\Services\Sms;

use RuntimeException;

class SmsService
{
    private SmsDriver $driver;

    public function __construct(?SmsDriver $driver = null)
    {
        $this->driver = $driver ?? match (config('sms.driver')) {
            'log'   => new LogSmsDriver(),
            default => throw new RuntimeException('Unknown SMS driver: '.config('sms.driver')),
        };
    }

    /**
     * Send an SMS. Throws a RuntimeException when it cannot be sent.
     */
    public function send(?string $to, string $message): void
    {
        $to = trim((string) $to);

        if ($to === '') {
            throw new RuntimeException('The patient has no phone number.');
        }

        $this->driver->send($to, $message);
    }
}