<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PatientAppointmentController extends Controller
{
    private const TYPES = ['consultation', 'counselling', 'follow_up'];

    private function currentPatient(): ?Patient
    {
        return Patient::where('user_id', auth()->id())->first();
    }

    public function index()
    {
        $patient = $this->currentPatient();

        $appointments = Appointment::with('doctor.user')
            ->where('patient_id', $patient?->id ?? 0)
            ->latest('requested_at')
            ->paginate(10);

        $hasDoctor = (bool) $patient?->activeAssignments()->exists();

        return view('patient.appointments', [
            'appointments' => $appointments,
            'types'        => self::TYPES,
            'hasDoctor'    => $hasDoctor,
        ]);
    }

    public function store(Request $request)
    {
        $patient = $this->currentPatient();

        if (! $patient) {
            return redirect()->route('patient.appointments');
        }

        // Patient ka active doctor
        $assignment = $patient->activeAssignments()->with('doctor.user')->first();

        if (! $assignment) {
            return redirect()->route('patient.appointments')
                ->with('error', __('appointments.no_doctor'));
        }

        $data = $request->validate([
            'appointment_type' => ['required', Rule::in(self::TYPES)],
            'reason'           => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment = Appointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $assignment->doctor_id,
            'requested_by'     => auth()->id(),
            'appointment_type' => $data['appointment_type'],
            'reason'           => $data['reason'] ?? null,
            'requested_at'     => now(),
            'status'           => 'requested',
        ]);

        // Doctor ko notification (fail ho to bhi request save rehti hai)
        try {
            $doctorUser = $assignment->doctor?->user;

            if ($doctorUser) {
                Notification::make()
                    ->title('New appointment request')
                    ->body('Patient: ' . (auth()->user()->name ?? '#' . $patient->id))
                    ->info()
                    ->sendToDatabase($doctorUser);
            }
        } catch (\Throwable $e) {
            Log::error('Appointment notification failed: ' . $e->getMessage());
        }

        return redirect()->route('patient.appointments')
            ->with('success', __('appointments.saved'));
    }
}