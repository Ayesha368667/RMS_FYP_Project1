<?php

namespace App\Filament\Admin\Resources\DoctorAssignments\Pages;

use App\Filament\Admin\Resources\DoctorAssignments\DoctorAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDoctorAssignments extends ListRecords
{
    protected static string $resource = DoctorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
