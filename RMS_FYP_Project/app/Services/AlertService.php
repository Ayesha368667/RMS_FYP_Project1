<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\FollowupResponse;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AlertService
{
    public const TYPE_MISSED   = 'missed_followups';
    public const TYPE_CRITICAL = 'critical_entry';
    public const MISSED_LIMIT  = 3;

    /**
     * Patient ke pichle 3 follow-up din agar teeno missed hon to HIGH alert.
     * Agar patient ka alert pehle se unresolved hai to naya nahi banta.
     */
    public function checkMissedFollowups(int $patientId, ?Carbon $today = null): ?Alert
    {
        $today = $today ?? today();

        $patient = Patient::find($patientId);
        if (! $patient) {
            return null;
        }

        // Ek patient ka sirf ek unresolved missed-alert
        $hasOpen = Alert::where('patient_id', $patientId)
            ->where('alert_type', self::TYPE_MISSED)
            ->where('status', '!=', 'resolved')
            ->exists();
        if ($hasOpen) {
            return null;
        }

        $latest = FollowupResponse::where('patient_id', $patientId)
            ->whereDate('due_date', '<', $today)
            ->orderByDesc('due_date')
            ->first();
        if (! $latest) {
            return null;
        }

        $query = FollowupResponse::where('admission_id', $latest->admission_id)
            ->whereDate('due_date', '<', $today);

        // Pichle alert ke baad wale din hi gine jayein (resolve ke baad dobara foran trigger na ho)
        $lastAlert = Alert::where('patient_id', $patientId)
            ->where('alert_type', self::TYPE_MISSED)
            ->latest('triggered_at')
            ->first();
        if ($lastAlert) {
            $query->whereDate('due_date', '>=', $lastAlert->triggered_at->toDateString());
        }

        $rows = $query->orderByDesc('due_date')->limit(self::MISSED_LIMIT)->get();

        if ($rows->count() < self::MISSED_LIMIT || $rows->contains(fn ($r) => $r->status !== 'missed')) {
            return null;
        }

        $alert = Alert::create([
            'patient_id'     => $patientId,
            'admission_id'   => $latest->admission_id,
            'alert_type'     => self::TYPE_MISSED,
            'severity'       => 'high',
            'trigger_reason' => self::MISSED_LIMIT . ' follow-ups missed in a row',
            'missed_count'   => self::MISSED_LIMIT,
            'triggered_at'   => now(),
            'status'         => 'open',
        ]);

        $this->notifyStaff($alert, 'High alert: missed follow-ups');

        return $alert;
    }

    /**
     * Critical response aaye to CRITICAL alert (ek response ka ek hi alert).
     */
    public function raiseCriticalAlert(FollowupResponse $response): ?Alert
    {
        if (! $response->is_critical) {
            return null;
        }

        $exists = Alert::where('followup_response_id', $response->id)
            ->where('alert_type', self::TYPE_CRITICAL)
            ->exists();
        if ($exists) {
            return null;
        }

        $alert = Alert::create([
            'patient_id'           => $response->patient_id,
            'admission_id'         => $response->admission_id,
            'alert_type'           => self::TYPE_CRITICAL,
            'followup_response_id' => $response->id,
            'severity'             => 'critical',
            'trigger_reason'       => 'Critical follow-up response submitted',
            'triggered_at'         => now(),
            'status'               => 'open',
        ]);

        $this->notifyStaff($alert, 'Critical alert: critical follow-up entry');

        return $alert;
    }

    /** Patient ke active doctor(s) aur saare active admins ko notification. */
    protected function notifyStaff(Alert $alert, string $title): void
    {
        try {
            $patient = $alert->patient()->with('user')->first();

            $doctorUsers = $patient->activeAssignments()
                ->with('doctor.user')->get()
                ->pluck('doctor.user')->filter();

            $admins = User::where('role', 'admin')->where('is_active', true)->get();

            $recipients = $doctorUsers->merge($admins)->unique('id');
            if ($recipients->isEmpty()) {
                return;
            }

            Notification::make()
                ->title($title)
                ->body('Patient: ' . ($patient->user->name ?? '#' . $patient->id) . ' - ' . $alert->trigger_reason)
                ->danger()
                ->sendToDatabase($recipients);
        } catch (\Throwable $e) {
            // Notification fail ho to bhi alert save rehna chahiye
            Log::error('Alert notification failed: ' . $e->getMessage());
        }
    }
}