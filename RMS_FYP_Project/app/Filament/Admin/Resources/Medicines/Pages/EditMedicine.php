<?php

namespace App\Filament\Admin\Resources\Medicines\Pages;

use App\Filament\Admin\Resources\Medicines\MedicineResource;
use Filament\Resources\Pages\EditRecord;

class EditMedicine extends EditRecord
{
    protected static string $resource = MedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action removed intentionally to protect patient medical history records
        ];
    }
}
