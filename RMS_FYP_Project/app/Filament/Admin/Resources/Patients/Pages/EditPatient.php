<?php

namespace App\Filament\Admin\Resources\Patients\Pages;

use App\Filament\Admin\Resources\Patients\PatientResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;
    //ismy delete ka button hota he wo nhi chahye
    protected function getHeaderActions(): array
    {
        return [];
    }
    //Save ke baad Patients ki list par jao
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    //Form khulte waqt user ki cheezein bhi bharo
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record->user;

        $data['name'] = $user->name;
        $data['email'] = $user->email;
        $data['phone'] = $user->phone;
        $data['preffered_language'] = $user->preffered_language;

        return $data;
    }

    //Save dabane par pehle user update, phir patient update
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // User ki cheezein
        $user = $record->user;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?: null;
        $user->preffered_language = $data['preffered_language'];
        $user->save();

        // Patient ki cheezein
        $record->father_name = $data['father_name'] ?: null;
        $record->date_of_birth = $data['date_of_birth'] ?: null;
        $record->gender = $data['gender'] ?: null;
        $record->address = $data['address'] ?: null;
        $record->city = $data['city'] ?: null;
        $record->photo_path = $data['photo_path'] ?: null;
        $record->registration_date = $data['registration_date'];
        $record->save();

        return $record;
    }

}
