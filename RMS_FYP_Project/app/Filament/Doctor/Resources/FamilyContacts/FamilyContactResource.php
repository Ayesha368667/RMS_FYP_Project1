<?php

namespace App\Filament\Doctor\Resources\FamilyContacts;

use App\Filament\Doctor\Resources\FamilyContacts\Pages\ListFamilyContacts;
use App\Filament\Doctor\Resources\FamilyContacts\Pages\ViewFamilyContact;
use App\Filament\Doctor\Resources\FamilyContacts\RelationManagers\ContactLogsRelationManager;
use App\Filament\Doctor\Resources\FamilyContacts\Tables\FamilyContactsTable;
use App\Models\FamilyContact;
use App\Support\DoctorDashboard;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FamilyContactResource extends Resource
{
    protected static ?string $model = FamilyContact::class;

    protected static ?string $navigationLabel = 'Family contacts';

    protected static ?string $modelLabel = 'family contact';

    protected static ?string $pluralModelLabel = 'Family contacts';

    protected static string|UnitEnum|null $navigationGroup = 'Patient care';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    public static function table(Table $table): Table
    {
        return FamilyContactsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Family contact')
                ->columns(3)
                ->components([
                    TextEntry::make('patient.patient_number')->label('Patient ID'),
                    TextEntry::make('patient.user.name')->label('Patient'),
                    TextEntry::make('relationship'),

                    TextEntry::make('contact_name')->label('Name'),
                    TextEntry::make('phone')->copyable(),
                    TextEntry::make('alternate_phone')->label('Alternate phone')->placeholder('-'),

                    TextEntry::make('consent_status')
                        ->label('SMS consent')
                        ->badge()
                        ->color(fn (?string $state): string => $state === 'granted' ? 'success' : 'warning')
                        ->formatStateUsing(fn (?string $state): string => $state === 'granted' ? 'Granted' : 'Not granted'),
                    TextEntry::make('consent_recorded_at')->label('Consent recorded')->dateTime('d M Y, h:i A')->placeholder('-'),
                    TextEntry::make('preffered_language')
                        ->label('Language')
                        ->formatStateUsing(fn (?string $state): string => $state === 'en' ? 'English' : 'Urdu'),

                    IconEntry::make('is_primary')->label('Primary')->boolean(),
                    IconEntry::make('is_emergency_contact')->label('Emergency contact')->boolean(),
                    IconEntry::make('is_active')->label('Active')->boolean(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ContactLogsRelationManager::class,
        ];
    }

    // a doctor sees only the family contacts of HIS OWN active patients
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('patient_id', DoctorDashboard::myPatients()->select('patients.id'));
    }

    // the Admin adds and edits family contacts; the doctor only views them and logs contacts
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilyContacts::route('/'),
            'view' => ViewFamilyContact::route('/{record}'),
        ];
    }
}