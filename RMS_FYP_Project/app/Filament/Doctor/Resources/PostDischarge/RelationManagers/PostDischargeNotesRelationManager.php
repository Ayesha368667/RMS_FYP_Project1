<?php

namespace App\Filament\Doctor\Resources\PostDischarge\RelationManagers;

use App\Models\Admission;
use App\Models\PostDischargeNote;
use App\Services\DoctorPostDischargeService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PostDischargeNotesRelationManager extends RelationManager
{
    // Admission::postDischargeNotes()
    protected static string $relationship = 'postDischargeNotes';

    protected static ?string $title = 'Notes and requests';

    // allow adding even though this is the View page
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('note')
                ->label('Post-discharge note')
                ->rows(4)
                ->minLength(5)
                ->maxLength(2000)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->modifyQueryUsing(fn ($query) => $query->with('doctor.user'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('note_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'extension_request' ? 'warning' : 'info')
                    ->formatStateUsing(fn (?string $state): string => $state === 'extension_request' ? 'Extension request' : 'Note'),

                TextColumn::make('note')
                    ->wrap(),

                TextColumn::make('requested_end_date')
                    ->label('Asked until')
                    ->date('d M Y')
                    ->placeholder('-'),

                // a request is done when the Admin has moved the end date to the asked date
                TextColumn::make('request_status')
                    ->label('Request')
                    ->badge()
                    ->state(function (PostDischargeNote $record): ?string {
                        if ($record->note_type !== 'extension_request') {
                            return null;
                        }

                        $end = $this->getOwnerRecord()->followup_end_date;

                        return ($end && $record->requested_end_date && $end->gte($record->requested_end_date))
                            ? 'Done by Admin'
                            : 'Waiting for Admin';
                    })
                    ->color(fn (?string $state): string => $state === 'Done by Admin' ? 'success' : 'warning')
                    ->placeholder('-'),

                TextColumn::make('doctor.user.name')
                    ->label('By')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add note')
                    ->modalHeading('Add post-discharge note')
                    ->using(function (array $data, CreateAction $action): Model {
                        try {
                            return app(DoctorPostDischargeService::class)->addNote(
                                $this->getOwnerRecord(),
                                $data['note']
                            );
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            $action->halt();
                        }
                    }),

                Action::make('requestExtension')
                    ->label('Request follow-up extension')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->visible(fn (): bool => (bool) $this->getOwnerRecord()->followup_end_date)
                    ->modalHeading('Ask the Admin to extend the follow-up')
                    ->modalDescription(fn (): string => 'Current end date: '
                        .$this->getOwnerRecord()->followup_end_date?->format('d M Y').'. You can ask for up to '
                        .DoctorPostDischargeService::MAX_EXTENSION_DAYS.' more days.')
                    ->modalSubmitActionLabel('Send request')
                    ->schema([
                        DatePicker::make('new_end_date')
                            ->label('New follow-up end date')
                            ->minDate(fn () => $this->getOwnerRecord()->followup_end_date?->copy()->addDay())
                            ->required(),

                        Textarea::make('reason')
                            ->label('Why is it needed?')
                            ->rows(3)
                            ->minLength(10)
                            ->maxLength(1000)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        try {
                            /** @var Admission $admission */
                            $admission = $this->getOwnerRecord();

                            app(DoctorPostDischargeService::class)->requestExtension(
                                $admission,
                                $data['new_end_date'],
                                $data['reason']
                            );

                            Notification::make()->title('Request sent to the Admin')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            // notes and requests are a record: no edit, no delete
            ->emptyStateHeading('No notes or requests yet');
    }
}