<?php

namespace App\Support;

use App\Models\FollowupResponse;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared queries for the Doctor dashboard widgets.
 * Everything is limited to the logged-in doctor's own ACTIVE patients.
 */
class DoctorDashboard
{
    // a patient in the follow-up window with no submitted log for this many days is "not responding"
    public const INACTIVE_DAYS = 2;

    public static function doctorId(): ?int
    {
        return auth()->user()?->doctor?->id;
    }

    // patients with an active assignment to this doctor
    public static function myPatients(): Builder
    {
        $query = Patient::query();
        $doctorId = self::doctorId();

        if (! $doctorId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('activeAssignments', fn (Builder $a) => $a->where('doctor_id', $doctorId));
    }

    // critical daily logs that the doctor has not reviewed yet
    public static function criticalLogsCount(): int
    {
        return FollowupResponse::query()
            ->where('status', 'submitted')
            ->where('is_critical', true)
            ->whereNull('reviewed_at')
            ->whereIn('patient_id', self::myPatients()->select('patients.id'))
            ->count();
    }

    // patients that have at least one unreviewed critical log
    public static function applyCritical(Builder $patients): Builder
    {
        return $patients->whereHas('followupResponses', fn (Builder $r) => $r
            ->where('status', 'submitted')
            ->where('is_critical', true)
            ->whereNull('reviewed_at'));
    }

    // discharged patients inside the follow-up window who sent no log in the last few days
    public static function applyNotResponding(Builder $patients): Builder
    {
        return $patients
            ->whereHas('admissions', fn (Builder $a) => $a
                ->where('status', 'discharged')
                ->whereDate('followup_start_date', '<=', today())
                ->where(fn (Builder $w) => $w
                    ->whereNull('followup_end_date')
                    ->orWhereDate('followup_end_date', '>=', today())))
            ->whereDoesntHave('followupResponses', fn (Builder $r) => $r
                ->where('status', 'submitted')
                ->whereDate('due_date', '>=', today()->subDays(self::INACTIVE_DAYS)));
    }

    // the list for the "needs attention" table
    public static function attentionQuery(): Builder
    {
        return self::myPatients()
            ->with('user')
            ->withCount(['followupResponses as critical_logs_count' => fn (Builder $r) => $r
                ->where('status', 'submitted')
                ->where('is_critical', true)
                ->whereNull('reviewed_at')])
            ->withMax(['followupResponses as last_log_at' => fn (Builder $r) => $r
                ->where('status', 'submitted')], 'submitted_at')
            ->where(fn (Builder $w) => $w
                ->where(fn (Builder $x) => self::applyCritical($x))
                ->orWhere(fn (Builder $x) => self::applyNotResponding($x)));
    }
}