<?php

namespace App\Console\Commands;

use App\Models\Admission;
use App\Models\FollowupForm;
use App\Models\FollowupResponse;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreatePendingFollowups extends Command
{
    protected $signature = 'followups:create-pending {--date= : Date to create rows for (YYYY-MM-DD). Default is today.}';

    protected $description = 'Create pending follow-up rows for discharged patients whose follow-up window includes the given date';

    // How many days between two follow-ups, for each frequency
    private const STEP_DAYS = [
        'daily'          => 1,
        'alternate_days' => 2,
        'weekly'         => 7,
    ];

    public function handle(): int
    {
        // 1) Which date are we creating rows for?
        try {
            $date = $this->option('date')
                ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay()
                : today();
        } catch (\Throwable $e) {
            $this->error('Wrong date. Please use the format YYYY-MM-DD.');

            return self::FAILURE;
        }

        // 2) We need the active form
        $form = FollowupForm::where('is_active', true)->first();

        if (! $form) {
            $this->warn('There is no active follow-up form. Nothing was created.');

            return self::SUCCESS;
        }

        // 3) Discharged patients whose follow-up window includes this date
        $admissions = Admission::with('patient.user')
            ->where('status', 'discharged')
            ->whereNotNull('followup_start_date')
            ->whereDate('followup_start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('followup_end_date')
                  ->orWhereDate('followup_end_date', '>=', $date);
            })
            ->get();

        $created = 0;
        $existing = 0;
        $notDue = 0;

        foreach ($admissions as $admission) {
            // 4) Is this date a due day for the patient's frequency?
            $step = self::STEP_DAYS[$admission->followup_frequency] ?? 1;
            $daysSinceStart = (int) $admission->followup_start_date->diffInDays($date);

            if ($daysSinceStart % $step !== 0) {
                $notDue++;
                continue;
            }

            // 5) Create the pending row (only one row per admission per day)
            $response = FollowupResponse::firstOrCreate(
                [
                    'admission_id' => $admission->id,
                    'due_date'     => $date->toDateString(),
                ],
                [
                    'patient_id'       => $admission->patient_id,
                    'followup_form_id' => $form->id,
                    'status'           => 'pending',
                    'language'         => $admission->patient?->user?->preffered_language ?? 'en',
                    'is_critical'      => false,
                ]
            );

            $response->wasRecentlyCreated ? $created++ : $existing++;
        }

        $this->info("Date {$date->toDateString()}: {$created} created, {$existing} already existed, {$notDue} not due today.");

        return self::SUCCESS;
    }
}