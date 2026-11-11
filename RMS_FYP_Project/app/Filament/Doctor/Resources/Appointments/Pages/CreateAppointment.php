<?php

namespace App\Filament\Doctor\Resources\Appointments\Pages;

use App\Filament\Doctor\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set requested_by to current logged-in user's ID
        $data['requested_by'] = auth()->id();

        // Automatically set requested_at to current timestamp
        $data['requested_at'] = now();

        return $data;
    }
}