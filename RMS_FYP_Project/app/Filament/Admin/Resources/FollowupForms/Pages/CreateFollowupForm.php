<?php

namespace App\Filament\Admin\Resources\FollowupForms\Pages;

use App\Filament\Admin\Resources\FollowupForms\FollowupFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFollowupForm extends CreateRecord
{
    protected static string $resource = FollowupFormResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['created_by']     = auth()->id();
    $data['version_number'] = 1;
    $data['is_active']      = false;
    $data['questions']      = \App\Models\FollowupForm::buildQuestions($data['extra_questions'] ?? []);
    unset($data['extra_questions']);

    return $data;
}
}
