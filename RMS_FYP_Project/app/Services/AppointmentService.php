<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * All appointment rules live here, so the Doctor panel and the Admin panel
 * (Member A) can use the same code: no double booking, no past dates,
 * status flow requested -> approved/rejected, approved -> cancelled.
 */
class AppointmentService
{
    // doctor (or admin) approves a patient's request and sets the date
    public function approve(Appointment $appointment, array $data): Appointment
    {
        $this->assertCanManage($appointment);

        $start = $this->futureDate($data['appointment_date'] ?? null);
        $minutes = $this->minutes($data['duration_minutes'] ?? null);

        $appointment = DB::transaction(function () use ($appointment, $start, $minutes) {
            $this->lockDoctor($appointment->doctor_id);

            $fresh = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());

            if ($fresh->status !== 'requested') {
                throw new InvalidArgumentException('Only a requested appointment can be approved.');
            }

            $this->assertSlotFree($fresh->doctor_id, $start, $minutes, $fresh->getKey());

            $fresh->update([
                'status' => 'approved',
                'appointment_date' => $start,
                'duration_minutes' => $minutes,
                'approved_by' => auth()->id(),
                'rejection_reason' => null,
            ]);

            return $fresh;
        });

        $this->notifyPatient(
            $appointment,
            'Appointment approved',
            'Your appointment is confirmed for '.$start->format('d M Y, h:i A').'.'
        );

        return $appointment;
    }

    // reject a request, a reason is compulsory
    public function reject(Appointment $appointment, string $reason): Appointment
    {
        $this->assertCanManage($appointment);

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Please write a reason for rejecting.');
        }

        $appointment = DB::transaction(function () use ($appointment, $reason) {
            $fresh = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());

            if ($fresh->status !== 'requested') {
                throw new InvalidArgumentException('Only a requested appointment can be rejected.');
            }

            $fresh->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => auth()->id(),
            ]);

            return $fresh;
        });

        $this->notifyPatient($appointment, 'Appointment request rejected', 'Reason: '.$reason);

        return $appointment;
    }

    // doctor schedules an appointment directly for one of his own patients
    public function schedule(Patient $patient, array $data): Appointment
    {
        $doctorId = auth()->user()?->doctor?->id;

        if (! $doctorId) {
            throw new InvalidArgumentException('Only a doctor can schedule an appointment.');
        }

        $isAssigned = $patient->doctorAssignments()
            ->where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->exists();

        if (! $isAssigned) {
            throw new InvalidArgumentException('You are not assigned to this patient.');
        }

        $start = $this->futureDate($data['appointment_date'] ?? null);
        $minutes = $this->minutes($data['duration_minutes'] ?? null);

        $appointment = DB::transaction(function () use ($patient, $data, $doctorId, $start, $minutes) {
            $this->lockDoctor($doctorId);
            $this->assertSlotFree($doctorId, $start, $minutes);

            return Appointment::create([
                'patient_id' => $patient->getKey(),
                'doctor_id' => $doctorId,
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'appointment_type' => $data['appointment_type'],
                'reason' => $data['reason'] ?? null,
                'requested_at' => now(),
                'appointment_date' => $start,
                'duration_minutes' => $minutes,
                'status' => 'approved',
            ]);
        });

        $this->notifyPatient(
            $appointment,
            'New appointment scheduled',
            'Your doctor scheduled an appointment on '.$start->format('d M Y, h:i A').'.'
        );

        return $appointment;
    }

    // move an approved appointment to a new time (only before it starts)
    public function reschedule(Appointment $appointment, array $data): Appointment
    {
        $this->assertCanManage($appointment);

        $start = $this->futureDate($data['appointment_date'] ?? null);
        $minutes = $this->minutes($data['duration_minutes'] ?? null);

        $appointment = DB::transaction(function () use ($appointment, $start, $minutes) {
            $this->lockDoctor($appointment->doctor_id);

            $fresh = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());

            $this->assertStillUpcoming($fresh);
            $this->assertSlotFree($fresh->doctor_id, $start, $minutes, $fresh->getKey());

            $fresh->update([
                'appointment_date' => $start,
                'duration_minutes' => $minutes,
            ]);

            return $fresh;
        });

        $this->notifyPatient(
            $appointment,
            'Appointment rescheduled',
            'Your appointment is moved to '.$start->format('d M Y, h:i A').'.'
        );

        return $appointment;
    }

    // cancel an approved appointment (only before it starts)
    public function cancel(Appointment $appointment): Appointment
    {
        $this->assertCanManage($appointment);

        $appointment = DB::transaction(function () use ($appointment) {
            $fresh = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());

            $this->assertStillUpcoming($fresh);

            $fresh->update(['status' => 'cancelled']);

            return $fresh;
        });

        $this->notifyPatient(
            $appointment,
            'Appointment cancelled',
            'Your appointment on '.$appointment->appointment_date->format('d M Y, h:i A').' was cancelled.'
        );

        return $appointment;
    }

    // ---------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------

    private function assertCanManage(Appointment $appointment): void
    {
        $user = auth()->user();

        if ($user?->role === 'admin') {
            return;
        }

        if (! $user?->doctor || $user->doctor->id !== $appointment->doctor_id) {
            throw new InvalidArgumentException('This is not your appointment.');
        }
    }

    private function assertStillUpcoming(Appointment $appointment): void
    {
        if ($appointment->status !== 'approved') {
            throw new InvalidArgumentException('Only an approved appointment can be changed.');
        }

        if (! $appointment->appointment_date || $appointment->appointment_date->isPast()) {
            throw new InvalidArgumentException('This appointment time has already passed.');
        }
    }

    // two appointments of the same doctor must not overlap
    private function assertSlotFree(int $doctorId, Carbon $start, int $minutes, ?int $ignoreId = null): void
    {
        $end = $start->copy()->addMinutes($minutes);

        $clash = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'approved')
            ->whereNotNull('appointment_date')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('appointment_date', '<', $end->toDateTimeString())
            ->whereRaw('DATE_ADD(appointment_date, INTERVAL duration_minutes MINUTE) > ?', [$start->toDateTimeString()])
            ->first();

        if ($clash) {
            throw new InvalidArgumentException(
                'This doctor already has an appointment at '.$clash->appointment_date->format('d M Y, h:i A')
                .' ('.$clash->duration_minutes.' min). Please choose another time.'
            );
        }
    }

    // lock the doctor row so two people cannot book the same slot at the same moment
    private function lockDoctor(int $doctorId): void
    {
        Doctor::query()->lockForUpdate()->findOrFail($doctorId);
    }

    private function futureDate(mixed $value): Carbon
    {
        if (! $value) {
            throw new InvalidArgumentException('Please choose the appointment date and time.');
        }

        $date = Carbon::parse($value);

        if ($date->isPast()) {
            throw new InvalidArgumentException('The appointment time must be in the future.');
        }

        return $date;
    }

    private function minutes(mixed $value): int
    {
        $minutes = (int) ($value ?: 30);

        if ($minutes < 15 || $minutes > 180) {
            throw new InvalidArgumentException('Duration must be between 15 and 180 minutes.');
        }

        return $minutes;
    }

    private function notifyPatient(Appointment $appointment, string $title, string $message): void
    {
        try {
            $appointment->patient?->user?->notify(new SystemNotification($title, $message));
        } catch (Throwable $e) {
            // a notification problem must never undo the appointment
            report($e);
        }
    }
}