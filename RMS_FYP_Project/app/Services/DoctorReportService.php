<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Alert;
use App\Models\ClinicalAssessment;
use App\Models\FollowupResponse;
use App\Models\Patient;
use App\Models\PatientMedicine;
use App\Models\TherapySession;
use App\Models\TreatmentPlan;
use App\Support\DoctorDashboard;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Builds the numbers for the doctor's reports.
 * Only the doctor's OWN active patients are ever included.
 * Confidential therapy session notes are NOT part of any report.
 */
class DoctorReportService
{
    public const MAX_DAYS = 366;

    public function rangeError(Carbon $from, Carbon $until): ?string
    {
        if ($from->gt($until)) {
            return 'The start date must be before the end date.';
        }

        if ($from->diffInDays($until) > self::MAX_DAYS) {
            return 'Please choose a period of at most '.self::MAX_DAYS.' days.';
        }

        return null;
    }

    // a patient of this doctor, or an error
    public function myPatient(mixed $patientId): Patient
    {
        $patient = DoctorDashboard::myPatients()
            ->with('user')
            ->whereKey($patientId)
            ->first();

        if (! $patient) {
            throw new InvalidArgumentException('This patient is not assigned to you.');
        }

        return $patient;
    }

    // ------------------------------------------------------------------
    // 1. progress summary of ONE patient
    // ------------------------------------------------------------------
    public function progressSummary(Patient $patient, Carbon $from, Carbon $until): array
    {
        // days that are still in the future cannot be "due"
        $dueUntil = $until->copy()->min(today()->endOfDay());

        $rows = FollowupResponse::query()
            ->where('patient_id', $patient->id)
            ->whereDate('due_date', '>=', $from->toDateString())
            ->whereDate('due_date', '<=', $dueUntil->toDateString())
            ->orderBy('due_date')
            ->get();

        $logs = $rows->where('status', 'submitted')->values();

        $due = $rows->count();
        $submitted = $logs->count();

        $answeredMedicine = $logs->filter(fn ($l) => $l->medicine_taken !== null);
        $medicineTaken = $answeredMedicine->where('medicine_taken', true)->count();

        $stats = [
            'due' => $due,
            'submitted' => $submitted,
            'missed' => $rows->where('status', 'missed')->count(),
            'percent' => $due > 0 ? (int) round($submitted / $due * 100) : null,
            'critical' => $logs->where('is_critical', true)->count(),
            'relapses' => $logs->where('relapse_reported', true)->count(),
            'medicine_answered' => $answeredMedicine->count(),
            'medicine_taken' => $medicineTaken,
            'medicine_percent' => $answeredMedicine->count() > 0
                ? (int) round($medicineTaken / $answeredMedicine->count() * 100)
                : null,
            'mood' => $this->averages($logs, 'mood_score'),
            'pain' => $this->averages($logs, 'pain_level'),
            'craving' => $this->averages($logs, 'craving_level'),
        ];

        $sessions = TherapySession::query()
            ->where('patient_id', $patient->id)
            ->whereBetween('session_date', [$from, $until]);

        return [
            'patient' => $patient,
            'doctor' => auth()->user()?->doctor?->loadMissing('user'),
            'from' => $from,
            'until' => $until,
            'generatedAt' => now(),
            'admission' => Admission::query()->where('patient_id', $patient->id)->latest('admission_date')->first(),
            'stats' => $stats,
            'logs' => $logs,
            'plans' => TreatmentPlan::query()
                ->where('patient_id', $patient->id)
                ->orderByRaw("status = 'active' DESC")
                ->orderByDesc('start_date')
                ->limit(3)
                ->get(),
            'medicines' => PatientMedicine::query()
                ->with('medicine')
                ->where('patient_id', $patient->id)
                ->where('status', 'active')
                ->get(),
            'sessionCount' => (clone $sessions)->count(),
            'sessionCompleted' => (clone $sessions)->where('session_status', 'completed')->count(),
            'lastSessionAt' => TherapySession::query()
                ->where('patient_id', $patient->id)
                ->where('session_status', 'completed')
                ->max('session_date'),
            'assessment' => ClinicalAssessment::query()
                ->where('patient_id', $patient->id)
                ->orderByDesc('assessment_date')
                ->first(),
            'alerts' => Alert::query()
                ->where('patient_id', $patient->id)
                ->whereBetween('triggered_at', [$from, $until])
                ->orderBy('triggered_at')
                ->get(),
        ];
    }

    // average, and (when there are 4+ logs) the average of the first and second half
    private function averages(Collection $logs, string $column): array
    {
        $values = fn (Collection $c) => $c->pluck($column)->filter(fn ($v) => $v !== null);

        $all = $values($logs);

        $result = [
            'avg' => $all->isEmpty() ? null : round($all->avg(), 1),
            'min' => $all->isEmpty() ? null : $all->min(),
            'max' => $all->isEmpty() ? null : $all->max(),
            'first_half' => null,
            'second_half' => null,
        ];

        if ($logs->count() >= 4) {
            $half = intdiv($logs->count(), 2);
            $first = $values($logs->take($half));
            $second = $values($logs->slice($half));

            $result['first_half'] = $first->isEmpty() ? null : round($first->avg(), 1);
            $result['second_half'] = $second->isEmpty() ? null : round($second->avg(), 1);
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // 2. compliance of ALL the doctor's patients
    // ------------------------------------------------------------------
    public function compliance(Carbon $from, Carbon $until): Collection
    {
        $dueUntil = $until->copy()->min(today()->endOfDay());

        $patients = DoctorDashboard::myPatients()
            ->with('user')
            ->orderBy('patient_number')
            ->get();

        if ($patients->isEmpty()) {
            return collect();
        }

        $ids = $patients->pluck('id');

        $stats = FollowupResponse::query()
            ->whereIn('patient_id', $ids)
            ->whereDate('due_date', '>=', $from->toDateString())
            ->whereDate('due_date', '<=', $dueUntil->toDateString())
            ->selectRaw("patient_id,
                count(*) as due_count,
                sum(status = 'submitted') as submitted_count,
                sum(status = 'missed') as missed_count,
                sum(status = 'submitted' and is_critical = 1) as critical_count,
                sum(status = 'submitted' and is_critical = 1 and reviewed_at is null) as unreviewed_count,
                sum(status = 'submitted' and relapse_reported = 1) as relapse_count,
                max(submitted_at) as last_log_at")
            ->groupBy('patient_id')
            ->get()
            ->keyBy('patient_id');

        $openAlerts = Alert::query()
            ->whereIn('patient_id', $ids)
            ->whereIn('status', ['open', 'under_action'])
            ->selectRaw('patient_id, count(*) as total')
            ->groupBy('patient_id')
            ->pluck('total', 'patient_id');

        return $patients->map(function (Patient $patient) use ($stats, $openAlerts) {
            $row = $stats->get($patient->id);

            $due = (int) ($row->due_count ?? 0);
            $submitted = (int) ($row->submitted_count ?? 0);

            return [
                'patient_number' => $patient->patient_number,
                'name' => $patient->user?->name ?? '-',
                'due' => $due,
                'submitted' => $submitted,
                'missed' => (int) ($row->missed_count ?? 0),
                'percent' => $due > 0 ? (int) round($submitted / $due * 100) : null,
                'critical' => (int) ($row->critical_count ?? 0),
                'unreviewed' => (int) ($row->unreviewed_count ?? 0),
                'relapses' => (int) ($row->relapse_count ?? 0),
                'last_log_at' => $row?->last_log_at ? Carbon::parse($row->last_log_at) : null,
                'open_alerts' => (int) ($openAlerts[$patient->id] ?? 0),
            ];
        })->values();
    }
}