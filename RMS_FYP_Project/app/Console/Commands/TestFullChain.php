<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\AlertAction;
use App\Models\FamilyContact;
use App\Models\FamilyContactLog;
use App\Models\FollowupForm;
use App\Models\FollowupResponse;
use App\Models\Patient;
use App\Models\User;
use App\Services\AlertActionService;
use App\Services\AlertService;
use Illuminate\Console\Command;

class TestFullChain extends Command
{
    protected $signature = 'chain:test {--patient=1 : Kis patient par test chalana hai}';

    protected $description = 'Test the full chain: missed days, alert, escalation, family SMS, doctor action';

    public function handle(AlertService $alerts, AlertActionService $actions): int
    {
        if (app()->isProduction()) {
            $this->error('Yeh test production par nahi chal sakta.');

            return self::FAILURE;
        }

        $patient = Patient::find($this->option('patient'));
        if (! $patient) {
            $this->error('Patient nahi mila.');

            return self::FAILURE;
        }

        $admission = $patient->admissions()->latest('id')->first();
        $form      = FollowupForm::first();
        if (! $admission || ! $form) {
            $this->error('Is patient ki admission ya follow-up form maujood nahi.');

            return self::FAILURE;
        }

        // Step 0: is patient ke purane test alerts saaf karo, taake naya saaf test ho
        $this->info('Step 0: purane alerts saaf kar rahe hain...');
        $oldIds = Alert::where('patient_id', $patient->id)->pluck('id');
        FamilyContactLog::whereIn('alert_id', $oldIds)->delete();
        AlertAction::whereIn('alert_id', $oldIds)->delete();
        Alert::whereIn('id', $oldIds)->delete();

        // Family contact jisko ijazat (consent) hai
        $hasContact = FamilyContact::where('patient_id', $patient->id)
            ->where('is_active', true)
            ->where('consent_status', 'granted')
            ->exists();

        if (! $hasContact) {
            FamilyContact::create([
                'patient_id'         => $patient->id,
                'contact_name'       => 'Test Family',
                'relationship'       => 'brother',
                'phone'              => '03001234567',
                'consent_status'     => 'granted',
                'preferred_language' => 'ur',
            ]);
        }

        // Step 1: pichle 3 din missed
        $this->info('Step 1: pichle 3 din missed bana rahe hain...');
        foreach ([3, 2, 1] as $d) {
            FollowupResponse::updateOrCreate(
                ['admission_id' => $admission->id, 'due_date' => today()->subDays($d)->toDateString()],
                [
                    'patient_id'       => $patient->id,
                    'followup_form_id' => $form->id,
                    'status'           => 'missed',
                    'submitted_at'     => null,
                ]
            );
        }

        // Step 2: alert engine
        $this->info('Step 2: alert engine chala rahe hain...');
        $alert = $alerts->checkMissedFollowups($patient->id);

        if (! $alert) {
            $this->error('Alert nahi bana. Agar is patient ke pichle 3 din ke rows missed nahi hain to yeh hota hai.');

            return self::FAILURE;
        }

        // Step 3: alert ko 25 ghante purana karo aur escalation chalao
        $this->info('Step 3: alert ko 25 ghante purana karke escalation chala rahe hain...');
        $alert->update(['triggered_at' => now()->subHours(25)]);
        $this->call('alerts:escalate');
        $alert->refresh();

        $smsSent = FamilyContactLog::where('alert_id', $alert->id)->where('outcome', 'sent')->count();

        // Step 4: doctor ka action
        $this->info('Step 4: doctor action kar raha hai...');
        $assignment = $patient->activeAssignments()->with('doctor.user')->first();
        $doctorUser = $assignment?->doctor?->user ?? User::where('role', 'doctor')->first();

        $doctorOk = false;
        if ($doctorUser) {
            try {
                $actions->startAction($alert, $doctorUser, 'Chain test: doctor ne patient ko call ki');
                $actions->resolve($alert, $doctorUser, 'Chain test: patient theek hai');
                $doctorOk = true;
            } catch (\Throwable $e) {
                $this->error('Doctor action fail: ' . $e->getMessage());
            }
        }
        $alert->refresh();

        // Nateeja
        $checks = [
            ['Missed alert bana (HIGH)',          $alert->alert_type === 'missed_followups' && $alert->severity === 'high'],
            ['Alert escalate hua',                $alert->escalated_at !== null],
            ['Family SMS gayi aur log hui',       $smsSent > 0],
            ['Doctor ne action kiya',             $doctorOk && $alert->assigned_to === $doctorUser?->id],
            ['Alert resolved hua',                $alert->status === 'resolved'],
            ['Action history save hui (2 rows)',  $alert->actions()->count() === 2],
        ];

        $this->newLine();
        $this->table(['Check', 'Natija'], array_map(fn ($c) => [$c[0], $c[1] ? 'PASS' : 'FAIL'], $checks));

        $allPass = collect($checks)->every(fn ($c) => $c[1]);
        $allPass ? $this->info('Poori chain theek chali.') : $this->error('Kuch check fail hue.');

        return $allPass ? self::SUCCESS : self::FAILURE;
    }
}