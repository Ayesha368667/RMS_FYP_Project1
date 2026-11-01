<?php

namespace Database\Seeders;

use App\Models\FollowupForm;
use Illuminate\Database\Seeder;

class FollowupFormSeeder extends Seeder
{
    public function run(): void
    {
        FollowupForm::updateOrCreate(
            ['title' => 'Default Follow-up Form', 'is_default' => true],
            [
                'description' => 'Standard weekly check-in for rehabilitation patients.',
                'is_active' => true,
                'questions' => [
                    ['key' => 'mood', 'label' => 'How is your mood today?', 'type' => 'select',
                     'options' => ['Very good', 'Good', 'Neutral', 'Low', 'Very low'], 'required' => true],
                    ['key' => 'sleep', 'label' => 'How well did you sleep this week?', 'type' => 'select',
                     'options' => ['Well', 'Average', 'Poorly'], 'required' => true],
                    ['key' => 'cravings', 'label' => 'Rate your cravings (1-10)', 'type' => 'number',
                     'min' => 1, 'max' => 10, 'required' => true],
                    ['key' => 'relapse', 'label' => 'Have you relapsed since the last check-in?', 'type' => 'boolean', 'required' => true],
                    ['key' => 'medication', 'label' => 'Are you taking your medication as prescribed?', 'type' => 'boolean', 'required' => true],
                    ['key' => 'support', 'label' => 'Do you feel supported by family and friends?', 'type' => 'boolean', 'required' => false],
                    ['key' => 'notes', 'label' => 'Anything else you would like to share?', 'type' => 'textarea', 'required' => false],
                ],
            ]
        );
    }
}