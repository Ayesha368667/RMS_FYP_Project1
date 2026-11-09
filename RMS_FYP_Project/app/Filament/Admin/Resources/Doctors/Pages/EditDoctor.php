<?php

namespace App\Filament\Admin\Resources\Doctors\Pages;

use App\Filament\Admin\Resources\Doctors\DoctorResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDoctor extends EditRecord
{
    protected static string $resource = DoctorResource::class;

    // 1. Delete ka button nahi chahiye
    protected function getHeaderActions(): array
    {
        return [];
    }

    // 2. Save ke baad Doctors ki list par jao
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 3. Form khulte waqt user ki cheezein bhi bharo
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record->user;

        $data['name'] = $user->name;
        $data['email'] = $user->email;
        $data['phone'] = $user->phone;
        $data['preffered_language'] = $user->preffered_language;

        return $data;
    }

    // 4. Save dabane par pehle user update, phir doctor update
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // User ki cheezein
        $user = $record->user;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?: null;
        $user->preffered_language = $data['preffered_language'];
        $user->save();

        // Doctor ki cheezein
        $record->qualification = $data['qualification'];
        $record->professional_type = $data['professional_type'];
        $record->specialization = $data['specialization'];
        $record->license_number = $data['license_number'] ?: null;
        $record->joining_date = $data['joining_date'];
        $record->availability_status = $data['availability_status'];
        $record->save();

        return $record;
    }
}