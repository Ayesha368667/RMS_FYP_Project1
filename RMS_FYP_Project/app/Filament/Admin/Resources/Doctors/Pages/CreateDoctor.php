<?php

namespace App\Filament\Admin\Resources\Doctors\Pages;

use App\Filament\Admin\Resources\Doctors\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Temporary password banao (8 letters + 2 numbers)
        $password = Str::random(8) . rand(10, 99);

        // 2. Staff number banao (D-0003, D-0004 ...)
        $number = Doctor::max('id') + 1;
        $staff = 'D-' . str_pad($number, 4, '0', STR_PAD_LEFT);

        // 3. User aur Doctor dono save karo (ek fail ho to dono cancel)
        $doctor = DB::transaction(function () use ($data, $password, $staff) {

            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'] ?? null;
            $user->password = Hash::make($password);
            $user->role = 'doctor';
            $user->preffered_language = $data['preffered_language'];
            $user->is_active = true;
            $user->must_change_password = true;
            $user->save();

            return Doctor::create([
                'user_id'             => $user->id,
                'staff_number'        => $staff,
                'qualification'       => $data['qualification'],
                'professional_type'   => $data['professional_type'],
                'specialization'      => $data['specialization'],
                'license_number'      => $data['license_number'] ?? null,
                'joining_date'        => $data['joining_date'],
                'availability_status' => $data['availability_status'],
            ]);
        });

        // 4. Password ek baar dikhao, admin copy karke doctor ko de
        Notification::make()
            ->title('Doctor created')
            ->body('Email: ' . $data['email'] . ' | Temporary password: ' . $password)
            ->success()
            ->persistent()
            ->send();

        return $doctor;
    }
    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}