<?php

namespace App\Filament\Doctor\Resources\Patients\Pages;

use App\Filament\Doctor\Resources\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;
}
