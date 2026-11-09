<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorAssignment;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class DoctorAssignmentService
{
    public function assign(array $data): DoctorAssignment
    {
        $this->ensureDoctorIsActive((int) $data['doctor_id']);

        $assignment = DB::transaction(function () use ($data) {
            $data = $this->withAssigner($data);

            // only one active primary doctor per patient:
            // close the old one (kept as history) before adding the new one
            if (($data['assignment_role'] ?? null) === 'primary' && ($data['status'] ?? 'active') === 'active') {
                DoctorAssignment::query()
                    ->where('patient_id', $data['patient_id'])
                    ->where('assignment_role', 'primary')
                    ->where('status', 'active')
                    ->update([
                        'status' => 'completed',
                        'end_date' => now()->toDateString(),
                    ]);
            }

            return DoctorAssignment::create($data);
        });

        $this->notify($assignment, false);

        return $assignment;
    }

    public function reassign(DoctorAssignment $old, int $newDoctorId, ?string $reason = null): DoctorAssignment
    {
        if ($old->status !== 'active') {
            throw new InvalidArgumentException('Only an active assignment can be reassigned.');
        }

        if ((int) $old->doctor_id === $newDoctorId) {
            throw new InvalidArgumentException('Please choose a different doctor.');
        }

        $this->ensureDoctorIsActive($newDoctorId);

        $new = DB::transaction(function () use ($old, $newDoctorId, $reason) {
            $old->update([
                'status' => 'completed',
                'end_date' => now()->toDateString(),
                'notes' => trim(($old->notes ? $old->notes.' | ' : '').'Reassigned on '.now()->toDateString().($reason ? ': '.$reason : '')),
            ]);

            return DoctorAssignment::create($this->withAssigner([
                'patient_id' => $old->patient_id,
                'doctor_id' => $newDoctorId,
                'assignment_role' => $old->assignment_role,
                'start_date' => now()->toDateString(),
                'status' => 'active',
                'notes' => $reason ? 'Reassigned: '.$reason : null,
            ]));
        });

        $this->notify($new, true, $old);

        return $new;
    }

    private function ensureDoctorIsActive(int $doctorId): void
    {
        if (! Schema::hasColumn('doctors', 'employment_status')) {
            return;
        }

        $isActive = Doctor::query()
            ->whereKey($doctorId)
            ->where('employment_status', 'active')
            ->exists();

        if (! $isActive) {
            throw new InvalidArgumentException('This doctor is not active and cannot be assigned patients.');
        }
    }

    private function withAssigner(array $data): array
    {
        $columns = Schema::getColumnListing((new DoctorAssignment)->getTable());

        if (in_array('assigned_by', $columns)) {
            $data['assigned_by'] = auth()->id();
        }

        if (in_array('assigned_at', $columns)) {
            $data['assigned_at'] = now();
        }

        return $data;
    }

    private function notify(DoctorAssignment $assignment, bool $isReassign, ?DoctorAssignment $old = null): void
    {
        try {
            $assignment->loadMissing(['patient.user', 'doctor.user']);

            $patientName = $assignment->patient?->user?->name ?? 'The patient';
            $patientNumber = $assignment->patient?->patient_number;
            $doctorName = $assignment->doctor?->user?->name ?? 'a doctor';
            $role = strtolower((string) $assignment->assignment_role);

            if ($assignment->doctor?->user) {
                Notification::make()
                    ->title($isReassign ? 'Patient reassigned to you' : 'New patient assigned to you')
                    ->body($patientName.($patientNumber ? ' ('.$patientNumber.')' : '').' is now your patient (role: '.$role.').')
                    ->icon('heroicon-o-user-plus')
                    ->success()
                    ->sendToDatabase($targetDoctor->user);
                    // ->sendToDatabase($assignment->doctor->user);
            }

            if ($assignment->patient?->user) {
                Notification::make()
                    ->title($isReassign ? 'Your doctor has changed' : 'A doctor has been assigned to you')
                    ->body($doctorName.' is now your '.$role.' doctor.')
                    ->icon('heroicon-o-user')
                    ->success()
                    ->sendToDatabase($assignment->patient->user);
            }

            if ($old) {
                $old->loadMissing('doctor.user');

                if ($old->doctor?->user) {
                    Notification::make()
                        ->title('Patient reassigned')
                        ->body($patientName.' is no longer assigned to you.')
                        ->icon('heroicon-o-user-minus')
                        ->warning()
                        ->sendToDatabase($old->doctor->user);
                }
            }
        } catch (Throwable $e) {
            // a notification problem must never stop the assignment itself
            report($e);
        }
    }
}