<?php

namespace Database\Seeders;

use App\Models\FollowupForm;
use App\Models\User;
use Illuminate\Database\Seeder;

class FollowupFormSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (! $admin) {
            $this->command->error('No user found. Create an admin first.');
            return;
        }

        $form = FollowupForm::firstOrNew(['form_name' => 'Default Follow-up Form']);

        if (! $form->exists) {
            $form->version_number = 1;
            $form->created_by     = $admin->id;
            $form->is_active      = ! FollowupForm::where('is_active', true)->exists();
        }

        $form->description = 'Standard check-in form for rehabilitation patients.';
        $form->questions   = FollowupForm::buildQuestions($form->extraQuestions());
        $form->save();
    }
}