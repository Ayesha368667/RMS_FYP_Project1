<?php

namespace App\Filament\Doctor\Resources\Alerts\Pages;

use App\Filament\Doctor\Resources\Alerts\AlertResource;
use Filament\Resources\Pages\ListRecords;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;
}