<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Reminder;
use App\Services\Sms\SmsService;
use Illuminate\Console\Command;

class SendAppointmentSmsReminders extends Command
{
    protected $signature = 'reminders:send-appointment-sms {--after-hours= : In-app reminder ke itne ghante baad SMS (default config/sms.php se)}';

    protected $description = 'Send an SMS for approved appointment reminders after the in-app reminder';

    public function handle(SmsService $sms): int
    {
        $hours = $this->option('after-hours') !== null
            ? (int) $this->option('after-hours')
            : (int) config('sms.fallback_after_hours');

        $cutoff = now()->subHours($hours);

        $reminders = Reminder::with(['patient.user', 'remindable'])
            ->where('channel', Reminder::CHANNEL_IN_APP)
            ->where('status', Reminder::STATUS_PENDING)
            ->where('reminder_type', 'appointment_due')
            ->where('remindable_type', Appointment::class)
            ->where('scheduled_at', '<=', $cutoff)
            ->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($reminders as $reminder) {
            $appointment = $reminder->remindable;

            // Appointment cancel/reject ho gayi ya guzar chuki: reminder khatam
            if (! $appointment || $appointment->status !== 'approved' || ! $appointment->appointment_date || $appointment->appointment_date->isPast()) {
                $reminder->update(['status' => Reminder::STATUS_EXPIRED]);
                $skipped++;
                continue;
            }

            // Ek appointment ki sirf ek SMS
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
            $when   = $appointment->appointment_date->format('d/m/Y h:i A');

            // SMS ko apni reminder row mein save karo
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
                $message = $locale === 'ur'
                    ? "یاد دہانی: آپ کی اپائنٹمنٹ {$when} کو ہے۔ براہ کرم وقت پر پہنچیں۔"
                    : "Reminder: you have an appointment on {$when}. Please arrive on time.";

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

        $this->info("Appointment SMS: {$sent} sent, {$failed} failed, {$skipped} skipped.");

        return self::SUCCESS;
    }
}