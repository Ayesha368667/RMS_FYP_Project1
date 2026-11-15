<?php

namespace App\Filament\Admin\Resources\FollowupForms\Pages;

use App\Filament\Admin\Resources\FollowupForms\FollowupFormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFollowupForm extends EditRecord
{
    protected static string $resource = FollowupFormResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    protected function mutateFormDataBeforeFill(array $data): array
{
    $data['extra_questions'] = $this->record->extraQuestions();

    return $data;
}

protected function mutateFormDataBeforeSave(array $data): array
{
    $questions = \App\Models\FollowupForm::buildQuestions($data['extra_questions'] ?? []);

    // Version goes up only when the questions really changed
    if ($questions != $this->record->questions) {
        $data['version_number'] = $this->record->version_number + 1;
    }

    $data['questions'] = $questions;
    unset($data['extra_questions']);

    return $data;
}
}
