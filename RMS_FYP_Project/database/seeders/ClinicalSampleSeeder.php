<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\DoctorAssignment;
use App\Models\TreatmentPlan;
use App\Models\Session;
use Carbon\Carbon;

class ClinicalSampleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Check karein ke koi active assignment database mein hai ya nahi
        $activeAssignments = DoctorAssignment::where('status', 'active')->get();

        if ($activeAssignments->isEmpty()) {
            $this->command->info('No active doctor assignments found. Please create an assignment first.');
            return;
        }

        
        foreach ($activeAssignments as $assignment) {
            
            // Duplicate se bachne ke liye pehle check kar rahe hain
            $plan = TreatmentPlan::firstOrCreate(
                ['doctor_assignment_id' => $assignment->id],
                [
                    'title' => 'Initial Rehabilitation Plan',
                    'description' => 'Standard clinical observation and treatment plan.',
                    'start_date' => Carbon::now(),
                    'status' => 'active'
                ]
            );

            // 3. Treatment plan ke andar 3 sessions banayein
            for ($i = 1; $i <= 3; $i++) {
                Session::firstOrCreate(
                    [
                        'treatment_plan_id' => $plan->id,
                        'session_number' => $i
                    ],
                    [
                        'notes' => "Automated session note for session number $i.",
                        'session_date' => Carbon::now()->addDays($i * 2),
                        'status' => 'completed'
                    ]
                );
            }
        }

        $this->command->info('Clinical sample data seeded successfully!');
    }
}


