<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class PatientCareController extends Controller
{
    private function patientId(): int
    {
        return (int) Patient::where('user_id', auth()->id())->value('id');
    }

    public function treatmentPlan()
    {
        $patientId = $this->patientId();

        // Draft plans are not shown to the patient
        $plans = DB::table('treatment_plans')
            ->join('doctors', 'doctors.id', '=', 'treatment_plans.created_by_doctor_id')
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->where('treatment_plans.patient_id', $patientId)
            ->where('treatment_plans.status', '!=', 'draft')
            ->orderByRaw("treatment_plans.status = 'active' desc")
            ->orderByDesc('treatment_plans.start_date')
            ->get([
                'treatment_plans.id', 'treatment_plans.plan_title', 'treatment_plans.goals',
                'treatment_plans.planned_interventions', 'treatment_plans.start_date',
                'treatment_plans.target_end_date', 'treatment_plans.status',
                'users.name as doctor_name',
            ]);

        // Only these columns are read. The confidential notes column is never selected.
        $sessions = DB::table('therapy_sessions')
            ->where('patient_id', $patientId)
            ->whereIn('treatment_plan_id', $plans->pluck('id'))
            ->orderByDesc('session_date')
            ->get([
                'id', 'treatment_plan_id', 'session_date', 'session_type', 'session_status',
                'progress_summary', 'next_steps', 'next_session_date',
            ])
            ->groupBy('treatment_plan_id');

        return view('patient.treatment-plan', compact('plans', 'sessions'));
    }

    public function medicines()
    {
        $medicines = DB::table('patient_medicines')
            ->join('medicines', 'medicines.id', '=', 'patient_medicines.medicine_id')
            ->where('patient_medicines.patient_id', $this->patientId())
            ->orderByRaw("patient_medicines.status = 'active' desc")
            ->orderByDesc('patient_medicines.start_date')
            ->get([
                'medicines.name', 'medicines.generic_name', 'medicines.form',
                'patient_medicines.dosage', 'patient_medicines.frequency',
                'patient_medicines.instructions', 'patient_medicines.start_date',
                'patient_medicines.end_date', 'patient_medicines.status',
            ]);

        return view('patient.medicines', compact('medicines'));
    }
}