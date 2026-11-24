<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\PostDischargeNote;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\DoctorDashboard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Doctor side of the post-discharge follow-up:
 *  - write a note about a discharged patient
 *  - ask the Admin to extend the follow-up window (the Admin changes the date)
 */
class DoctorPostDischargeService
{
    // how far beyond the current end date a doctor may ask for
    public const MAX_EXTENSION_DAYS = 90;

    public function addNote(Admission $admission, string $note): PostDischargeNote
    {
        $doctor = $this->doctorFor($admission);

        $note = trim($note);

        if (mb_strlen($note) < 5) {
            throw new InvalidArgumentException('Please write at least 5 characters in the note.');
        }

        return PostDischargeNote::create([
            'patient_id' => $admission->patient_id,
            'admission_id' => $admission->getKey(),
            'doctor_id' => $doctor->id,
            'note_type' => 'note',
            'note' => $note,
        ]);
    }

    public function requestExtension(Admission $admission, mixed $newEndDate, string $reason): PostDischargeNote
    {
        $doctor = $this->doctorFor($admission);

        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw new InvalidArgumentException('Please explain the reason in at least 10 characters.');
        }

        if (! $newEndDate) {
            throw new InvalidArgumentException('Please choose the new follow-up end date.');
        }

        $newEnd = Carbon::parse($newEndDate)->startOfDay();

        $note = DB::transaction(function () use ($admission, $doctor, $newEnd, $reason) {
            // lock the admission so two requests cannot be sent together
            $fresh = Admission::query()->lockForUpdate()->findOrFail($admission->getKey());

            if (! $fresh->followup_start_date || ! $fresh->followup_end_date) {
                throw new InvalidArgumentException('This admission has no follow-up period to extend.');
            }

            $currentEnd = $fresh->followup_end_date->copy()->startOfDay();

            if ($newEnd->lte($currentEnd)) {
                throw new InvalidArgumentException(
                    'The new end date must be after the current end date ('.$currentEnd->format('d M Y').').'
                );
            }

            if ($newEnd->gt($currentEnd->copy()->addDays(self::MAX_EXTENSION_DAYS))) {
                throw new InvalidArgumentException(
                    'You can ask for at most '.self::MAX_EXTENSION_DAYS.' more days at a time.'
                );
            }

            // a request is "pending" until the Admin moves the end date to the requested date
            $hasPending = PostDischargeNote::query()
                ->where('admission_id', $fresh->id)
                ->where('note_type', 'extension_request')
                ->whereDate('requested_end_date', '>', $currentEnd->toDateString())
                ->exists();

            if ($hasPending) {
                throw new InvalidArgumentException('A request for this patient is already waiting for the Admin.');
            }

            return PostDischargeNote::create([
                'patient_id' => $fresh->patient_id,
                'admission_id' => $fresh->id,
                'doctor_id' => $doctor->id,
                'note_type' => 'extension_request',
                'note' => $reason,
                'requested_end_date' => $newEnd->toDateString(),
            ]);
        });

        $this->notifyAdmins($admission, $newEnd, $reason);

        return $note;
    }

    private function doctorFor(Admission $admission)
    {
        $doctor = auth()->user()?->doctor;

        if (! $doctor) {
            throw new InvalidArgumentException('Only a doctor can do this.');
        }

        $isMyPatient = DoctorDashboard::myPatients()
            ->whereKey($admission->patient_id)
            ->exists();

        if (! $isMyPatient) {
            throw new InvalidArgumentException('This patient is not assigned to you.');
        }

        if ($admission->status !== 'discharged') {
            throw new InvalidArgumentException('Only a discharged patient has a post-discharge follow-up.');
        }

        return $doctor;
    }

    private function notifyAdmins(Admission $admission, Carbon $newEnd, string $reason): void
    {
        try {
            $admission->loadMissing('patient.user');

            $patientLabel = ($admission->patient?->patient_number ?? '').' '.($admission->patient?->user?->name ?? '');
            $doctorName = auth()->user()?->name ?? 'A doctor';

            $admins = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new SystemNotification(
                    'Follow-up extension requested',
                    $doctorName.' asks to extend the follow-up of '.trim($patientLabel)
                        .' until '.$newEnd->format('d M Y').'. Reason: '.$reason
                ));
            }
        } catch (Throwable $e) {
            // a notification problem must never undo the request
            report($e);
        }
    }
}