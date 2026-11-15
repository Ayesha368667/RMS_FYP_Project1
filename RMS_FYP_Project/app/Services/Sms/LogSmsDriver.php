<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsDriver implements SmsDriver
{
    public function send(string $to, string $message): void
    {
        // No real SMS is sent. The message is written to the log file.
        Log::info("[SMS] To {$to}: {$message}");
    }
}