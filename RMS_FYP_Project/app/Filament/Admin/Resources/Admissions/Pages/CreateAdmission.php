<?php

namespace App\Filament\Admin\Resources\Admissions\Pages;

use App\Filament\Admin\Resources\Admissions\AdmissionResource;
use App\Services\AdmissionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateAdmission extends CreateRecord
{
    protected static string $resource = AdmissionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AdmissionService::class)->admit($data);
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}