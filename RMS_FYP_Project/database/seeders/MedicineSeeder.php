<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\User;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $medicines = [
            ['Naltrexone', 'Addiction treatment', 'tablet'],
            ['Buprenorphine', 'Addiction treatment', 'tablet'],
            ['Acamprosate', 'Addiction treatment', 'tablet'],
            ['Disulfiram', 'Addiction treatment', 'tablet'],
            ['Clonidine', 'Withdrawal support', 'tablet'],
            ['Diazepam', 'Anxiety / withdrawal', 'tablet'],
            ['Lorazepam', 'Anxiety', 'tablet'],
            ['Sertraline', 'Antidepressant', 'tablet'],
            ['Escitalopram', 'Antidepressant', 'tablet'],
            ['Fluoxetine', 'Antidepressant', 'capsule'],
            ['Quetiapine', 'Antipsychotic', 'tablet'],
            ['Olanzapine', 'Antipsychotic', 'tablet'],
            ['Paracetamol', 'Pain relief', 'tablet'],
            ['Multivitamin', 'Supplement', 'tablet'],
        ];

        foreach ($medicines as [$name, $category, $form]) {
            Medicine::updateOrCreate(
                ['name' => $name],
                [
                    'generic_name' => $name,
                    'category' => $category,
                    'form' => $form,
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]
            );
        }
    }
}