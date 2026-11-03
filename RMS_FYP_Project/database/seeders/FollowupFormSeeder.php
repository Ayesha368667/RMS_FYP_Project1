<?php

namespace Database\Seeders;

use App\Models\FollowupForm;
use App\Models\User;
use Illuminate\Database\Seeder;

class FollowupFormSeeder extends Seeder
{
    public function run(): void
    {
        // created_by required hai, is liye pehle ek admin user chahiye
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (! $admin) {
            $this->command->error('Koi user nahi mila. Pehle admin user banayein.');
            return;
        }

        FollowupForm::updateOrCreate(
            ['form_name' => 'Default Follow-up Form', 'version_number' => 1],
            [
                'description' => 'Standard check-in form for rehabilitation patients.',
                'is_active'   => true,
                'created_by'  => $admin->id,
                'questions'   => [
                    ['key' => 'mood_score', 'label' => 'Aaj aap ka mood kaisa hai? (1-10)', 'type' => 'number', 'min' => 1, 'max' => 10, 'required' => true],
                    ['key' => 'pain_level', 'label' => 'Dard ki shiddat (0-10)', 'type' => 'number', 'min' => 0, 'max' => 10, 'required' => true],
                    ['key' => 'craving_level', 'label' => 'Talab (craving) ki shiddat (0-10)', 'type' => 'number', 'min' => 0, 'max' => 10, 'required' => true],
                    ['key' => 'medicine_taken', 'label' => 'Kya aap ne dawai waqt par li?', 'type' => 'boolean', 'required' => true],
                    ['key' => 'relapse_reported', 'label' => 'Kya pichle check-in ke baad relapse hua?', 'type' => 'boolean', 'required' => true],
                    ['key' => 'remarks', 'label' => 'Koi aur baat jo aap batana chahein', 'type' => 'textarea', 'required' => false],
                ],
            ]
        );
    }
}