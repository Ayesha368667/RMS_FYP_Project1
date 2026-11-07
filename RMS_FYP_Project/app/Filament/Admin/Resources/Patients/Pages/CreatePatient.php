<?php

namespace App\Filament\Admin\Resources\Patients\Pages;

use App\Filament\Admin\Resources\Patients\PatientResource;
use App\Models\Patient;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    // Save ke baad Patients ki list par jao
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Save se pehle duplicate check
    protected function beforeCreate(): void
    {
        $data = $this->data;
        $duplicate = false;

        // 1. Wahi phone number pehle se kisi patient ka hai?
        if (! empty($data['phone'])) {
            if (User::where('role', 'patient')->where('phone', $data['phone'])->exists()) {
                $duplicate = true;
            }
        }

        // 2. Wahi naam + father ka naam + date of birth pehle se hai?
        if (! empty($data['father_name']) && ! empty($data['date_of_birth'])) {
            $found = Patient::where('father_name', $data['father_name'])
                ->whereDate('date_of_birth', $data['date_of_birth'])
                ->whereHas('user', function ($query) use ($data) {
                    $query->where('name', $data['name']);
                })
                ->exists();

            if ($found) {
                $duplicate = true;
            }
        }

        // Duplicate mila to rok do
        if ($duplicate) {
            Notification::make()
                ->title('Possible duplicate patient')
                ->body('A patient with the same phone number, or the same name, father name and date of birth already exists.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Temporary password (8 letters + 2 numbers)
        $password = Str::random(8) . rand(10, 99);

        // 2. Patient ID: P-2026-0004, P-2026-0005 ...
        $number = Patient::withTrashed()->max('id') + 1;
        $patientNumber = 'P-' . date('Y') . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

        // 3. User aur Patient dono save karo (ek fail ho to dono cancel)
        $patient = DB::transaction(function () use ($data, $password, $patientNumber) {

            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'] ?? null;
            $user->password = Hash::make($password);
            $user->role = 'patient';
            $user->preffered_language = $data['preffered_language'];
            $user->is_active = true;
            $user->must_change_password = true;
            $user->save();

            return Patient::create([
                'user_id'           => $user->id,
                'patient_number'    => $patientNumber,
                'father_name'       => $data['father_name'] ?? null,
                'date_of_birth'     => $data['date_of_birth'] ?? null,
                'gender'            => $data['gender'] ?? null,
                'address'           => $data['address'] ?? null,
                'city'              => $data['city'] ?? null,
                'photo_path'        => $data['photo_path'] ?? null,
                'registration_date' => $data['registration_date'],
                'status'            => 'registered',
                'created_by'        => auth()->id(),
            ]);
        });

        // 4. Password ek baar dikhao, admin copy karke patient ko de
        Notification::make()
            ->title('Patient created: ' . $patientNumber)
            ->body('Email: ' . $data['email'] . ' | Temporary password: ' . $password)
            ->success()
            ->persistent()
            ->send();

        return $patient;
    }
}