<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Reminder;

class PatientDashboardController extends Controller
{
    public function index()
    {
        $patient = Patient::where('user_id', auth()->id())->first();

        // In-app reminders that are still waiting for this patient
        $reminders = Reminder::where('patient_id', $patient?->id ?? 0)
            ->where('channel', 'in_app')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->latest('scheduled_at')
            ->take(5)
            ->get();

        // Remember when the patient first saw each reminder
        Reminder::whereIn('id', $reminders->pluck('id'))
            ->whereNull('sent_at')
            ->update(['sent_at' => now()]);

        return view('patient.dashboard', compact('reminders'));
    }
}