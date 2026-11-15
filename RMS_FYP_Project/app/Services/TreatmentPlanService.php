<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\TreatmentPlan;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class TreatmentPlanService
{
    private const EDITABLE_FIELDS = [
        'plan_title',
        'admission_id',
        'goals',
        'planned_interventions',
        'start_date',
        'target_end_date',
    ];

    private const SNAPSHOT_FIELDS = [
        'plan_title',
        'admission_id',
        'goals',
        'planned_interventions',
        'start_date',
        'target_end_date',
        'status',
        'created_by_doctor_id',
        'approved_by',
        'approved_at',
    ];

    public function create(Patient $patient, array $data): TreatmentPlan
    {
        $doctorId = auth()->user()?->doctor?->id;

        if (! $doctorId) {
            throw new InvalidArgumentException('Only a doctor can create a treatment plan.');
        }

        $plan = DB::transaction(function () use ($patient, $data, $doctorId) {
            // lock the patient row so two plans cannot be created at the same time
            Patient::query()->lockForUpdate()->findOrFail($patient->getKey());

            $hasActivePlan = TreatmentPlan::query()
                ->where('patient_id', $patient->getKey())
                ->where('status', 'active')
                ->exists();

            if ($hasActivePlan) {
                throw new InvalidArgumentException('This patient already has an active treatment plan. Complete or cancel it first.');
            }

            $plan = TreatmentPlan::create([
                ...Arr::only($data, self::EDITABLE_FIELDS),
                'patient_id' => $patient->getKey(),
                'created_by_doctor_id' => $doctorId,
                'status' => 'active',
                'approved_by' => $doctorId,
                'approved_at' => now(),
            ]);

            // version 1
            $this->recordVersion($plan, 'Plan created');

            return $plan;
        });

        $this->notifyPatient(
            $plan,
            'A new treatment plan was created for you',
            'Your doctor '.(auth()->user()?->name ?? '').' created the plan: '.$plan->plan_title.'.'
        );

        return $plan;
    }

    public function update(TreatmentPlan $plan, array $data): TreatmentPlan
    {
        $reason = trim((string) ($data['change_reason'] ?? '')) ?: 'Plan updated';

        $plan = DB::transaction(function () use ($plan, $data, $reason) {
            $plan = TreatmentPlan::query()->lockForUpdate()->findOrFail($plan->getKey());

            if (! in_array($plan->status, ['draft', 'active'], true)) {
                throw new InvalidArgumentException('Only an active plan can be edited.');
            }

            // plans made before versions existed get a starting version first
            $this->ensureBaselineVersion($plan);

            $plan->update(Arr::only($data, self::EDITABLE_FIELDS));

            $this->recordVersion($plan->refresh(), $reason);

            return $plan;
        });

        $this->notifyPatient(
            $plan,
            'Your treatment plan was updated',
            'Your doctor updated the plan: '.$plan->plan_title.'.'
        );

        return $plan;
    }

    public function cancel(TreatmentPlan $plan, string $reason): TreatmentPlan
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new InvalidArgumentException('Please write a reason (at least 5 characters).');
        }

        $plan = DB::transaction(function () use ($plan, $reason) {
            $plan = TreatmentPlan::query()->lockForUpdate()->findOrFail($plan->getKey());

            if (! in_array($plan->status, ['draft', 'active'], true)) {
                throw new InvalidArgumentException('Only an active plan can be cancelled.');
            }

            $this->ensureBaselineVersion($plan);

            $plan->update(['status' => 'cancelled']);

            $this->recordVersion($plan->refresh(), 'Cancelled: '.$reason);

            return $plan;
        });

        $this->notifyPatient(
            $plan,
            'Your treatment plan was cancelled',
            'The plan "'.$plan->plan_title.'" was cancelled. Please contact your doctor for details.'
        );

        return $plan;
    }
    public function complete(TreatmentPlan $plan, ?string $note = null): TreatmentPlan
    {
        $note = trim((string) $note);

        $plan = DB::transaction(function () use ($plan, $note) {
            $plan = TreatmentPlan::query()->lockForUpdate()->findOrFail($plan->getKey());

            if ($plan->status !== 'active') {
                throw new InvalidArgumentException('Only an active plan can be marked as completed.');
            }

            $this->ensureBaselineVersion($plan);

            $plan->update(['status' => 'completed']);

            $this->recordVersion($plan->refresh(), $note !== '' ? 'Completed: '.$note : 'Marked as completed');

            return $plan;
        });

        $this->notifyPatient(
            $plan,
            'Your treatment plan was completed',
            'The plan "'.$plan->plan_title.'" was marked as completed.'
        );

        return $plan;
    }
    private function ensureBaselineVersion(TreatmentPlan $plan): void
    {
        if (! $plan->versions()->exists()) {
            $this->recordVersion($plan, 'Starting version (before the first change)');
        }
    }

    private function recordVersion(TreatmentPlan $plan, string $reason): void
    {
        $next = (int) $plan->versions()->max('version_number') + 1;

        $plan->versions()->create([
            'version_number' => $next,
            'plan_snapshot' => Arr::only($plan->attributesToArray(), self::SNAPSHOT_FIELDS),
            'change_reason' => $reason,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);
    }

    private function notifyPatient(TreatmentPlan $plan, string $title, string $body): void
    {
        try {
            $plan->loadMissing('patient.user');

            $user = $plan->patient?->user;

            if (! $user) {
                return;
            }

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-clipboard-document-list')
                ->sendToDatabase($user);
        } catch (Throwable $e) {
            // a notification problem must never stop the plan itself
            report($e);
        }
    }
}