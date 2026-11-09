<?php

namespace App\Filament\Admin\Resources\DoctorAssignments\Pages;

use App\Filament\Admin\Resources\DoctorAssignments\DoctorAssignmentResource;
use App\Services\DoctorAssignmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDoctorAssignment extends CreateRecord
{
    protected static string $resource = DoctorAssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(DoctorAssignmentService::class)->assign($data);
    }
}