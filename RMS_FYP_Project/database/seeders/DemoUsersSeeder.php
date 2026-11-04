<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::where('role', 'admin')->first();

        $doctors = [
            ['Dr. Ahmed Khan', 'doctor1@gmail.com', 'D-0001', 'MBBS, FCPS Psychiatry', 'Psychiatry', 'doctor'],
            ['Ms. Sara Malik', 'doctor2@gmail.com', 'D-0002', 'MSc Psychology', 'Addiction counselling', 'counsellor'],
        ];

        foreach ($doctors as [$name, $email, $staff, $qualification, $specialization, $type]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('Test12345'),
                'role' => 'doctor',
                'preffered_language' => 'en',
                'is_active' => true,
            ]);

            Doctor::updateOrCreate(['user_id' => $user->id], [
                'staff_number' => $staff,
                'qualification' => $qualification,
                'specialization' => $specialization,
                'professional_type' => $type,
                'joining_date' => '2026-01-01',
                'availability_status' => 'available',
            ]);
        }

        $patients = [
            ['Ali Raza', 'patient1@gmail.com', 'P-2026-0001'],
            ['Usman Tariq', 'patient2@gmail.com', 'P-2026-0002'],
            ['Bilal Hussain', 'patient3@gmail.com', 'P-2026-0003'],
        ];

        foreach ($patients as [$name, $email, $number]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('Test012345'),
                'role' => 'patient',
                'preffered_language' => 'ur',
                'is_active' => true,
            ]);

            Patient::updateOrCreate(['user_id' => $user->id], [
                'patient_number' => $number,
                'registration_date' => now()->toDateString(),
                'status' => 'registered',
                'created_by' => $admin->id,
            ]);
        }
    }
}