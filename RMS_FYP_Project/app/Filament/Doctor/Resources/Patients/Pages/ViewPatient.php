<?php

namespace App\Filament\Doctor\Resources\Patients\Pages;

use App\Filament\Doctor\Resources\Patients\PatientResource;
use App\Filament\Doctor\Resources\Patients\Widgets\PatientProgressChart;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    // the progress chart is shown at the top of the patient page
    protected function getHeaderWidgets(): array
    {
        return [
            PatientProgressChart::class,
        ];
    }
}