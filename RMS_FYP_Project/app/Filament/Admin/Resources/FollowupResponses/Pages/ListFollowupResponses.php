<?php

namespace App\Filament\Admin\Resources\FollowupResponses\Pages;

use App\Filament\Admin\Resources\FollowupResponses\FollowupResponseResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListFollowupResponses extends ListRecords
{
    protected static string $resource = FollowupResponseResource::class;

    // List khulte hi "dekh liya" likh do
    public function mount(): void
    {
        parent::mount();

        Cache::forever('followups_last_seen_' . auth()->id(), now()->toDateTimeString());
    }
}