<?php

namespace App\Filament\Doctor\Resources\FamilyContacts\RelationManagers;

use App\Models\Alert;
use App\Services\DoctorFamilyContactService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ContactLogsRelationManager extends RelationManager
{
    // FamilyContact::logs() = history of every contact with this person
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Contact history';

    // allow adding a log even though this is the View page
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $patientId = $this->getOwnerRecord()->patient_id;

        return $schema
            ->columns(2)
            ->components([
                Select::make('channel')
                    ->label('How did you contact them?')
                    ->options(DoctorFamilyContactService::CHANNELS)
                    ->default('call')
                    ->required(),

                DateTimePicker::make('contacted_at')
                    ->label('Contact date and time')
                    ->default(now())
                    ->maxDate(now())
                    ->seconds(false)
                    ->required(),

                Select::make('outcome')
                    ->options(DoctorFamilyContactService::OUTCOMES)
                    ->live()
                    ->required(),

                // only the unresolved alerts of this same patient
                Select::make('alert_id')
                    ->label('Related alert (optional)')
                    ->options(fn (): array => Alert::query()
                        ->where('patient_id', $patientId)
                        ->whereIn('status', ['open', 'under_action'])
                        ->orderByDesc('triggered_at')
                        ->get()
                        ->mapWithKeys(fn (Alert $a) => [
                            $a->id => ucfirst(str_replace('_', ' ', $a->alert_type)).' - '.$a->triggered_at->format('d M, h:i A'),
                        ])
                        ->all()),

                Textarea::make('failure_reason')
                    ->label('Why did it fail?')
                    ->rows(2)
                    ->visible(fn (Get $get): bool => $get('outcome') === 'failed')
                    ->required(fn (Get $get): bool => $get('outcome') === 'failed')
                    ->columnSpanFull(),

                Textarea::make('message')
                    ->label('What was said or sent (optional)')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('remarks')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contacted_at')
            ->modifyQueryUsing(fn ($query) => $query->with(['contactedBy', 'alert']))
            ->columns([
                TextColumn::make('contacted_at')
                    ->label('When')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('contact_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('channel')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('outcome')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'reached', 'delivered', 'sent' => 'success',
                        'no_answer', 'pending' => 'warning',
                        'failed', 'wrong_number' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('contactedBy.name')
                    ->label('By')
                    ->placeholder('System'),

                TextColumn::make('alert.alert_type')
                    ->label('Alert')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state)))
                    ->toggleable(),

                TextColumn::make('message')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('remarks')
                    ->wrap()
                    ->placeholder('-'),
            ])
            ->defaultSort('contacted_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add contact log')
                    ->modalHeading('Add contact log')
                    ->modalDescription(fn (): ?string => $this->getOwnerRecord()->consent_status !== 'granted'
                        ? 'SMS consent is NOT granted for this person. Do not share patient details.'
                        : null)
                    ->visible(fn (): bool => (bool) $this->getOwnerRecord()->is_active)
                    ->using(function (array $data, CreateAction $action): Model {
                        try {
                            return app(DoctorFamilyContactService::class)->logContact(
                                $this->getOwnerRecord(),
                                $data
                            );
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            $action->halt();
                        }
                    }),
            ])
            // a contact log is a record: no edit, no delete
            ->emptyStateHeading('No contact has been recorded yet');
    }
}