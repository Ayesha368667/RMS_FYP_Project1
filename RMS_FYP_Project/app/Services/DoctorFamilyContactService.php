<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\FamilyContact;
use App\Models\FamilyContactLog;
use App\Support\DoctorDashboard;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * A doctor writes a MANUAL contact log: "I called / visited the family of my patient".
 * Logs are a record: they are never edited or deleted.
 */
class DoctorFamilyContactService
{
    public const CHANNELS = [
        'call' => 'Phone call',
        'sms' => 'SMS (sent by me)',
        'whatsapp' => 'WhatsApp',
        'visit' => 'Visit',
        'other' => 'Other',
    ];

    public const OUTCOMES = [
        'reached' => 'Reached',
        'no_answer' => 'No answer',
        'wrong_number' => 'Wrong number',
        'failed' => 'Failed',
    ];

    public function logContact(FamilyContact $contact, array $data): FamilyContactLog
    {
        $user = auth()->user();

        if (! $user?->doctor) {
            throw new InvalidArgumentException('Only a doctor can add a contact log.');
        }

        $isMyPatient = DoctorDashboard::myPatients()
            ->whereKey($contact->patient_id)
            ->exists();

        if (! $isMyPatient) {
            throw new InvalidArgumentException('This contact belongs to a patient who is not assigned to you.');
        }

        if (! $contact->is_active) {
            throw new InvalidArgumentException('This family contact is not active.');
        }

        $channel = $data['channel'] ?? null;
        $outcome = $data['outcome'] ?? null;

        if (! array_key_exists((string) $channel, self::CHANNELS)) {
            throw new InvalidArgumentException('Please choose how you contacted the family.');
        }

        if (! array_key_exists((string) $outcome, self::OUTCOMES)) {
            throw new InvalidArgumentException('Please choose the outcome of the contact.');
        }

        $contactedAt = Carbon::parse($data['contacted_at'] ?? now());

        if ($contactedAt->gt(now()->addMinutes(5))) {
            throw new InvalidArgumentException('The contact time cannot be in the future.');
        }

        $failureReason = trim((string) ($data['failure_reason'] ?? ''));

        if ($outcome === 'failed' && $failureReason === '') {
            throw new InvalidArgumentException('Please write why the contact failed.');
        }

        // the alert (if chosen) must belong to the same patient and still be unresolved
        $alertId = $data['alert_id'] ?? null;

        if ($alertId) {
            $alertOk = Alert::query()
                ->whereKey($alertId)
                ->where('patient_id', $contact->patient_id)
                ->whereIn('status', ['open', 'under_action'])
                ->exists();

            if (! $alertOk) {
                throw new InvalidArgumentException('Please choose an unresolved alert of the same patient.');
            }
        }

        return FamilyContactLog::create([
            'patient_id' => $contact->patient_id,
            'alert_id' => $alertId ?: null,
            'family_contact_id' => $contact->getKey(),
            'contacted_by' => $user->id,
            'contact_type' => 'manual',
            'channel' => $channel,
            'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
            'contacted_at' => $contactedAt,
            'outcome' => $outcome,
            'failure_reason' => $outcome === 'failed' ? $failureReason : null,
            'remarks' => filled($data['remarks'] ?? null) ? trim($data['remarks']) : null,
        ]);
    }
}