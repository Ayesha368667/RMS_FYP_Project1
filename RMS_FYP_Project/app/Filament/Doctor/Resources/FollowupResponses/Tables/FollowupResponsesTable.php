<?php

namespace App\Filament\Doctor\Resources\FollowupResponses\Tables;

use App\Models\FollowupResponse;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FollowupResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // critical and not reviewed first, then the newest
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['patient.user', 'reviewer.user', 'form'])
                ->orderByRaw("(status = 'submitted' AND is_critical = 1 AND reviewed_at IS NULL) DESC")
                ->orderByDesc('due_date')
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('due_date')
                    ->label('Log date')
                    ->date('d M Y'),

                TextColumn::make('patient.patient_number')
                    ->label('Patient ID')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('is_critical')
                    ->label('Level')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'CRITICAL' : 'Normal')
                    ->color(fn ($state): string => $state ? 'danger' : 'gray'),

                TextColumn::make('mood_score')->label('Mood')->placeholder('-'),
                TextColumn::make('pain_level')->label('Pain')->placeholder('-'),
                TextColumn::make('craving_level')->label('Craving')->placeholder('-'),

                IconColumn::make('medicine_taken')
                    ->label('Medicine')
                    ->boolean(),

                IconColumn::make('relapse_reported')
                    ->label('Relapse')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'submitted' => 'success',
                        'missed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('reviewed_at')
                    ->label('Review')
                    ->badge()
                    ->state(fn (FollowupResponse $record): ?string => $record->status !== 'submitted'
                        ? null
                        : ($record->reviewed_at ? 'Reviewed' : 'Not reviewed'))
                    ->color(fn (?string $state): string => $state === 'Reviewed' ? 'success' : 'warning')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'missed' => 'Missed',
                        'pending' => 'Pending',
                    ])
                    ->default('submitted'),

                Filter::make('critical')
                    ->label('Critical only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_critical', true)),

                Filter::make('not_reviewed')
                    ->label('Not reviewed only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'submitted')
                        ->whereNull('reviewed_at')),

                Filter::make('log_date')
                    ->schema([
                        DatePicker::make('from')->label('From date'),
                        DatePicker::make('until')->label('To date'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->modalHeading('Daily log'),

                Action::make('review')
                    ->label('Mark reviewed')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (FollowupResponse $record): bool => $record->status === 'submitted'
                        && $record->reviewed_at === null)
                    ->modalHeading('Mark this log as reviewed')
                    ->modalSubmitActionLabel('Save review')
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Review notes')
                            ->rows(4)
                            ->minLength(5)
                            ->maxLength(2000)
                            ->required(),
                    ])
                    ->action(function (FollowupResponse $record, array $data): void {
                        $doctorId = auth()->user()?->doctor?->id;

                        if (! $doctorId) {
                            Notification::make()->title('Only a doctor can review a log.')->danger()->send();

                            return;
                        }

                        // whereNull makes sure a log is never reviewed twice
                        $updated = FollowupResponse::query()
                            ->whereKey($record->getKey())
                            ->whereNull('reviewed_at')
                            ->update([
                                'reviewed_by' => $doctorId,
                                'reviewed_at' => now(),
                                'review_notes' => trim($data['review_notes']),
                            ]);

                        if ($updated === 0) {
                            Notification::make()->title('This log was already reviewed.')->warning()->send();

                            return;
                        }

                        Notification::make()->title('Log marked as reviewed')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No daily logs found')
            ->emptyStateDescription('Logs appear here after your patients submit their daily follow-up form.');
    }
}