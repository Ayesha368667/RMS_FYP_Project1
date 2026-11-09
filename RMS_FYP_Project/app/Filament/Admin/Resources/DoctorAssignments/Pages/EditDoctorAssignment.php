<?php

namespace App\Filament\Admin\Resources\DoctorAssignments\Pages;

use App\Filament\Admin\Resources\DoctorAssignments\DoctorAssignmentResource;

use Filament\Resources\Pages\EditRecord;

class EditDoctorAssignment extends EditRecord
{
    protected static string $resource = DoctorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
        
        ];
    }
}
