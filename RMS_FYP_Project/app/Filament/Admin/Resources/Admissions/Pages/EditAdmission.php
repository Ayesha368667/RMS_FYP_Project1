<?php

namespace App\Filament\Admin\Resources\Admissions\Pages;

use App\Filament\Admin\Resources\Admissions\AdmissionResource;
use Filament\Resources\Pages\EditRecord;

class EditAdmission extends EditRecord
{
    protected static string $resource = AdmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}