<?php

namespace App\Filament\Doctor\Resources\FamilyContacts\Pages;

use App\Filament\Doctor\Resources\FamilyContacts\FamilyContactResource;
use Filament\Resources\Pages\ListRecords;

class ListFamilyContacts extends ListRecords
{
    protected static string $resource = FamilyContactResource::class;
}