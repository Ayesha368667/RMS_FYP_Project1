<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;

class AppointmentService
{
    /**
     * Appointment approve karo: date lagao, approver likho, reminder banao.
     */
    public function approve(Appointment $appointment, User $approver, Carbon $date): Appointment
    {
        if ($appointment->status !== 'requested') {
            throw new DomainException('Sirf requested appointment approve ho sakti hai.');
        }

        if ($date->isPast()) {
            throw new DomainException('Appointment ki date aane wale waqt ki honi chahiye.');
        }

        try {
            $appointment->update([
                'status'           => 'approved',
                'approved_by'      => $approver->id,
                'appointment_date' => $date,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new DomainException('Doctor is waqt pehle se busy hai. Doosra waqt chunein.');
        }

        $this->createReminder($appointment);

        return $appointment->fresh();
    }

    /**
     * In-app reminder: appointment se 24 ghante pehle (agar waqt kam ho to foran).
     */
    public function createReminder(Appointment $appointment): Reminder
    {
        $scheduled = $appointment->appointment_date->copy()->subDay();

        if ($scheduled->isPast()) {
            $scheduled = now();
        }

        return Reminder::create([
            'patient_id'      => $appointment->patient_id,
            'reminder_type'   => 'appointment_due',
            'remindable_type' => Appointment::class,
            'remindable_id'   => $appointment->id,
            'scheduled_at'    => $scheduled,
            'channel'         => Reminder::CHANNEL_IN_APP,
            'status'          => Reminder::STATUS_PENDING,
        ]);
    }
}