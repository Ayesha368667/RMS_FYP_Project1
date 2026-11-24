<?php

namespace App\Filament\Doctor\Resources\FamilyContacts\Tables;

use App\Filament\Doctor\Resources\FamilyContacts\FamilyContactResource;
use App\Models\FamilyContact;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FamilyContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['patient.user'])
                ->withMax('logs', 'contacted_at')
                ->orderBy('patient_id')
                ->orderByDesc('is_primary'))
            ->columns([
                TextColumn::make('patient.patient_number')
                    ->label('Patient ID')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('contact_name')
                    ->label('Family member')
                    ->searchable(),

                TextColumn::make('relationship'),

                TextColumn::make('phone')
                    ->copyable(),

                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),

                IconColumn::make('is_emergency_contact')
                    ->label('Emergency')
                    ->boolean(),

                TextColumn::make('consent_status')
                    ->label('SMS consent')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'granted' ? 'success' : 'warning')
                    ->formatStateUsing(fn (?string $state): string => $state === 'granted' ? 'Granted' : 'Not granted'),

                TextColumn::make('logs_max_contacted_at')
                    ->label('Last contacted')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Active contacts only')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                SelectFilter::make('consent_status')
                    ->label('SMS consent')
                    ->options([
                        'granted' => 'Granted',
                        'not_granted' => 'Not granted',
                    ]),
            ])
            ->recordUrl(fn (FamilyContact $record): string => FamilyContactResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Open'),
            ])
            ->emptyStateHeading('No family contacts')
            ->emptyStateDescription('The Admin adds family contacts when a patient is registered.');
    }
}