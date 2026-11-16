<?php

return [
    // Which driver sends the SMS: "log" writes it to the log file (no real SMS is sent)
    'driver' => env('SMS_DRIVER', 'log'),

    // How many hours after the in-app reminder we wait before sending an SMS
    'fallback_after_hours' => (int) env('SMS_FALLBACK_AFTER_HOURS', 12),
];