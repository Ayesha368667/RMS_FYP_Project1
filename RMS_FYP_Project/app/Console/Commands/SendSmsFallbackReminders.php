<?php

namespace App\Console\Commands;

use App\Models\FollowupResponse;
use App\Models\Reminder;
use App\Services\Sms\SmsService;
use Illuminate\Console\Command;

class SendSmsFallbackReminders extends Command
{
    protected $signature = 'reminders:send-sms-fallback {--after-hours= : Wait this many hours after the in-app reminder (default comes from config/sms.php)}';

    protected $description = 'Send an SMS for follow-up reminders that the patient has not acted on';

    public function handle(SmsService $sms): int
    {
        $hours = $this->option('after-hours') !== null
            ? (int) $this->option('after-hours')
            : (int) config('sms.fallback_after_hours');

        $cutoff = now()->subHours($hours);

        $reminders = Reminder::with(['patient.user', 'remindable'])
            ->where('channel', Reminder::CHANNEL_IN_APP)
            ->where('status', Reminder::STATUS_PENDING)
            ->where('reminder_type', 'followup_due')
            ->where('remindable_type', FollowupResponse::class)
            ->where('scheduled_at', '<=', $cutoff)
            ->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($reminders as $reminder) {
            $response = $reminder->remindable;

            // The follow-up is already filled in (or gone): no SMS needed
            if (! $response || $response->status !== 'pending' || $response->submitted_at) {
                $skipped++;
                continue;
            }

            // Only one SMS per follow-up
            $alreadyTried = Reminder::where('channel', Reminder::CHANNEL_SMS)
                ->where('remindable_type', $reminder->remindable_type)
                ->where('remindable_id', $reminder->remindable_id)
                ->exists();

            if ($alreadyTried) {
                $skipped++;
                continue;
            }

            $user   = $reminder->patient?->user;
            $locale = in_array($user?->preffered_language, ['en', 'ur'], true) ? $user->preffered_language : 'en';

            // Save the SMS as its own reminder row, then update its status
            $smsReminder = Reminder::create([
                'patient_id'      => $reminder->patient_id,
                'reminder_type'   => $reminder->reminder_type,
                'remindable_type' => $reminder->remindable_type,
                'remindable_id'   => $reminder->remindable_id,
                'scheduled_at'    => now(),
                'channel'         => Reminder::CHANNEL_SMS,
                'status'          => Reminder::STATUS_PENDING,
            ]);

            try {
                $message = __('followup.sms.followup_due', ['date' => $response->due_date->format('d/m/Y')], $locale);
                $sms->send($user?->phone, $message);

                $smsReminder->update(['status' => Reminder::STATUS_SENT, 'sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                $smsReminder->update([
                    'status'         => Reminder::STATUS_FAILED,
                    'failure_reason' => mb_substr($e->getMessage(), 0, 500),
                ]);
                $failed++;
            }
        }

        $this->info("SMS fallback: {$sent} sent, {$failed} failed, {$skipped} skipped.");

        return self::SUCCESS;
    }
}