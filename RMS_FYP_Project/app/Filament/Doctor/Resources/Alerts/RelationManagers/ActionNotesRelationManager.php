<?php

namespace App\Filament\Doctor\Resources\Alerts\RelationManagers;

use App\Filament\Doctor\Resources\Alerts\AlertResource;
use App\Services\DoctorAlertService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ActionNotesRelationManager extends RelationManager
{
    // Alert::actions() = the action log of the alert
    protected static string $relationship = 'actions';

    protected static ?string $title = 'Action notes';

    // allow adding a note even though this is the View page
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('notes')
                ->label('What did you do? (call, visit, advice...)')
                ->rows(4)
                ->minLength(5)
                ->maxLength(2000)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('notes')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('By'),

                TextColumn::make('action_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('notes')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add action note')
                    ->modalHeading('Add action note')
                    // no new notes once the admin has resolved the alert
                    ->visible(fn (): bool => $this->getOwnerRecord()->status !== 'resolved')
                    ->using(function (array $data, CreateAction $action): Model {
                        try {
                            return app(DoctorAlertService::class)->addNote(
                                $this->getOwnerRecord(),
                                $data['notes']
                            );
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            $action->halt();
                        }
                    })
                    // reload the page so the status badge (open -> under action) updates
                    ->after(fn () => $this->redirect(AlertResource::getUrl('view', ['record' => $this->getOwnerRecord()]))),
            ])
            // notes are a record: no edit, no delete
            ->emptyStateHeading('No action notes yet');
    }
}