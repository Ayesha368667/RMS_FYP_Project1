<?php

namespace App\Filament\Admin\Resources\FollowupForms\Pages;

use App\Filament\Admin\Resources\FollowupForms\FollowupFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFollowupForms extends ListRecords
{
    protected static string $resource = FollowupFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
