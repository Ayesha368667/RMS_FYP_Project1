<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ClinicalTestDataSeeder extends Seeder
{
    /**
     * Creates 3 test patients (each with a users account) and active assignments:
     *  - Ali Raza, Usman Tariq  -> Dr. Ahmed Khan (doctor1@gmail.com)
     *  - Hamza Nawaz            -> Ms. Sara Malik (doctor2@gmail.com)
     * Used to test that each doctor only sees their own patients.
     */
    public function run(): void
    {
        $doctor1 = Doctor::whereHas('user', fn ($q) => $q->where('email', 'doctor1@gmail.com'))->first();
        $doctor2 = Doctor::whereHas('user', fn ($q) => $q->where('email', 'doctor2@gmail.com'))->first();

        if (! $doctor1 || ! $doctor2) {
            $this->command?->warn('Doctors not found. Run DemoUsersSeeder first.');

            return;
        }

        $adminId = User::where('role', 'admin')->value('id') ?? User::value('id');

        $patients = [
            ['P-0001', 'Ali Raza', 'patient1@gmail.com', 'Raza Ahmed', '1995-03-14', 'male', $doctor1->id],
            ['P-0002', 'Usman Tariq', 'patient2@gmail.com', 'Tariq Mehmood', '1998-07-22', 'male', $doctor1->id],
            ['P-0003', 'Hamza Nawaz', 'patient3@gmail.com', 'Nawaz Khan', '1993-11-05', 'male', $doctor2->id],
        ];

        foreach ($patients as [$number, $name, $email, $father, $dob, $gender, $doctorId]) {
            // 1. login account (the patient's name, email and phone live in users)
            $this->upsert('users', ['email' => $email], [
                'name' => $name,
                'phone' => '0300'.random_int(1000000, 9999999),
                'password' => Hash::make('Test12345'),
                'role' => 'patient',
                'preferred_language' => 'ur',
                'preffered_language' => 'ur', // spelling used in some migrations
                'is_active' => true,
            ]);

            // Pata lagayein ke user waqai bana ya nahi
            $userId = DB::table('users')->where('email', $email)->value('id');

            if (! $userId) {
                // Agar DB query se nahi mila, to Eloquent se try karein ya create karein
                $user = User::firstOrCreate(['email' => $email], [
                    'name' => $name,
                    'phone' => '0300'.random_int(1000000, 9999999),
                    'password' => Hash::make('Test12345'),
                    'role' => 'patient',
                    'is_active' => true,
                ]);
                $userId = $user->id;
            }

            // 2. patient profile
            $this->upsert('patients', ['patient_number' => $number], [
                'user_id' => (int) $userId, // Force cast to integer to ensure it's not null/empty
                'father_name' => $father,
                'date_of_birth' => $dob,
                'gender' => $gender,
                'address' => 'Gujranwala',
                'city' => 'Gujranwala',
                'registration_date' => now()->subDays(20)->toDateString(),
                'status' => 'admitted',
                'created_by' => $adminId,
            ]);

            $patientId = DB::table('patients')->where('patient_number', $number)->value('id');

            // 3. active assignment
            $this->upsert('doctor_assignments', [
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
            ], [
                'assigned_by' => $adminId,
                'assignment_role' => 'primary',
                'assigned_at' => now()->subDays(15),
                'start_date' => now()->subDays(15)->toDateString(),
                'status' => 'active',
            ]);
        }
    }

    /**
     * Insert or update a row, using only the columns that really exist in the table
     */
    private function upsert(string $table, array $match, array $values): void
    {
        $columns = array_flip(Schema::getColumnListing($table));

        $values = array_intersect_key($values, $columns);
        $match = array_intersect_key($match, $columns);

        // Agar user_id un-intentionally filter ho raha ho to usay force include karein
        if ($table === 'patients' && !isset($values['user_id']) && isset($match['user_id'])) {
            // keep it
        }

        if (isset($columns['created_at'])) {
            $values['created_at'] = now();
        }

        if (isset($columns['updated_at'])) {
            $values['updated_at'] = now();
        }

        DB::table($table)->updateOrInsert($match, $values);
    }
}
