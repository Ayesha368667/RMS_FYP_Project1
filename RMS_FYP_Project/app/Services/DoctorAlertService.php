<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertAction;
use App\Support\DoctorDashboard;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Doctor side of alerts: add an action note to an alert of HIS OWN patient.
 * The first note moves the alert from "open" to "under_action".
 * Resolving an alert is NOT here: only the Admin resolves alerts (FR12).
 */
class DoctorAlertService
{
    public function addNote(Alert $alert, string $notes): AlertAction
    {
        $user = auth()->user();

        if (! $user?->doctor) {
            throw new InvalidArgumentException('Only a doctor can add an action note.');
        }

        $notes = trim($notes);

        if (mb_strlen($notes) < 5) {
            throw new InvalidArgumentException('Please write at least 5 characters in the note.');
        }

        return DB::transaction(function () use ($alert, $user, $notes) {
            $fresh = Alert::query()->lockForUpdate()->findOrFail($alert->getKey());

            $isMyPatient = DoctorDashboard::myPatients()
                ->whereKey($fresh->patient_id)
                ->exists();

            if (! $isMyPatient) {
                throw new InvalidArgumentException('This alert is not for your patient.');
            }

            if ($fresh->status === 'resolved') {
                throw new InvalidArgumentException('This alert is already resolved.');
            }

            $action = AlertAction::create([
                'alert_id' => $fresh->id,
                'user_id' => $user->id,
                'action_type' => 'doctor_note',
                'notes' => $notes,
            ]);

            $changes = [];

            if ($fresh->status === 'open') {
                $changes['status'] = 'under_action';
            }

            if (! $fresh->assigned_to) {
                $changes['assigned_to'] = $user->id;
            }

            if ($changes) {
                $fresh->update($changes);
            }

            return $action;
        });
    }
}