<?php

namespace App\Filament\Doctor\Resources\Patients\Pages;

use App\Filament\Doctor\Resources\Patients\PatientResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;
}