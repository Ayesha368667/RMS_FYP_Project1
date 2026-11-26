<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\FamilyContact;
use App\Models\FamilyContactLog;
use App\Models\User;
use App\Services\Sms\SmsService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EscalateAlerts extends Command
{
    protected $signature = 'alerts:escalate {--hours=24 : Alert kitne ghante open rahe to escalate ho}';

    protected $description = 'Escalate alerts that are still open after 24 hours: notify admin and send family SMS (consent only)';

    public function handle(SmsService $sms): int
    {
        $hours = (int) $this->option('hours');

        // Sirf wahi alerts jo abhi open hain, escalate nahi hue, aur purane ho chuke hain
        $alerts = Alert::with('patient.user')
            ->where('status', 'open')
            ->whereNull('escalated_at')
            ->where('triggered_at', '<=', now()->subHours($hours))
            ->get();

        $admins = User::where('role', 'admin')->where('is_active', true)->get();

        $escalated = 0;
        $smsSent   = 0;
        $smsFailed = 0;

        foreach ($alerts as $alert) {
            // 1) Escalate mark karo (dobara escalate nahi hoga)
            $alert->update(['escalated_at' => now()]);
            $escalated++;

            $patientName = $alert->patient?->user?->name ?? ('#' . $alert->patient_id);

            // 2) Admin ko notification
            if ($admins->isNotEmpty()) {
                try {
                    Notification::make()
                        ->title('Alert escalated')
                        ->body("Patient {$patientName}: alert {$hours} ghante se open hai.")
                        ->danger()
                        ->sendToDatabase($admins);
                } catch (\Throwable $e) {
                    Log::error('Escalation notification failed: ' . $e->getMessage());
                }
            }

            // 3) Family ko SMS: sirf active contacts jinki ijazat (consent) hai
            $contacts = FamilyContact::where('patient_id', $alert->patient_id)
                ->where('is_active', true)
                ->where('consent_status', 'granted')
                ->get();

            foreach ($contacts as $contact) {
                $message = $contact->prefered_language === 'ur'
                    ? "مریض {$patientName} کا الرٹ {$hours} گھنٹے سے زیادہ سے حل نہیں ہوا۔ براہ کرم ہسپتال سے رابطہ کریں۔"
                    : "The alert for patient {$patientName} has been open for more than {$hours} hours. Please contact the hospital.";

                $outcome       = 'sent';
                $failureReason = null;

                try {
                    $sms->send($contact->phone, $message);
                    $smsSent++;
                } catch (\Throwable $e) {
                    $outcome       = 'failed';
                    $failureReason = $e->getMessage();
                    $smsFailed++;
                }

                // 4) Har SMS ka record (kamyab ho ya na ho)
                FamilyContactLog::create([
                    'patient_id'        => $alert->patient_id,
                    'alert_id'          => $alert->id,
                    'family_contact_id' => $contact->id,
                    'contacted_by'      => null,
                    'contact_type'      => 'escalation',
                    'channel'           => 'sms',
                    'message'           => $message,
                    'contacted_at'      => now(),
                    'outcome'           => $outcome,
                    'failure_reason'    => $failureReason,
                ]);
            }
        }

        $this->info("{$escalated} alert(s) escalated, {$smsSent} family SMS sent, {$smsFailed} failed.");

        return self::SUCCESS;
    }
}