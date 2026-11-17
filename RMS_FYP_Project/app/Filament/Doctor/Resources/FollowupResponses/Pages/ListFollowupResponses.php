<?php

namespace App\Filament\Doctor\Resources\FollowupResponses\Pages;

use App\Filament\Doctor\Resources\FollowupResponses\FollowupResponseResource;
use Filament\Resources\Pages\ListRecords;

class ListFollowupResponses extends ListRecords
{
    protected static string $resource = FollowupResponseResource::class;

    protected ?string $subheading = 'Critical logs that are not reviewed yet are shown first.';
}