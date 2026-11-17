<?php

namespace App\Filament\Doctor\Resources\FollowupResponses;

use App\Filament\Doctor\Resources\FollowupResponses\Pages\ListFollowupResponses;
use App\Filament\Doctor\Resources\FollowupResponses\Tables\FollowupResponsesTable;
use App\Models\FollowupResponse;
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

class FollowupResponseResource extends Resource
{
    protected static ?string $model = FollowupResponse::class;

    protected static ?string $navigationLabel = 'Daily logs';

    protected static ?string $modelLabel = 'daily log';

    protected static ?string $pluralModelLabel = 'Daily logs';

    // same group name as PatientResource
    protected static string|UnitEnum|null $navigationGroup = 'Patient care';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function table(Table $table): Table
    {
        return FollowupResponsesTable::configure($table);
    }

    // details window (opens with the "View" button)
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Patient')
                ->columns(3)
                ->components([
                    TextEntry::make('patient.patient_number')->label('Patient ID'),
                    TextEntry::make('patient.user.name')->label('Name'),
                    TextEntry::make('due_date')->label('Log date')->date('d M Y'),
                    TextEntry::make('submitted_at')->label('Submitted at')->dateTime('d M Y, h:i A')->placeholder('-'),
                    TextEntry::make('language')->formatStateUsing(fn (?string $state): string => $state === 'ur' ? 'Urdu' : 'English'),
                    IconEntry::make('is_critical')->label('Critical')->boolean()
                        ->trueIcon('heroicon-s-exclamation-triangle')
                        ->trueColor('danger'),
                ]),

            Section::make('Answers')
                ->columns(3)
                ->components([
                    TextEntry::make('mood_score')->label('Mood (1-10)')->placeholder('-'),
                    TextEntry::make('pain_level')->label('Pain (0-10)')->placeholder('-'),
                    TextEntry::make('craving_level')->label('Craving (0-10)')->placeholder('-'),
                    IconEntry::make('medicine_taken')->label('Took medicine')->boolean(),
                    IconEntry::make('relapse_reported')->label('Relapse reported')->boolean()
                        ->trueColor('danger')
                        ->falseColor('success'),
                    TextEntry::make('remarks')->placeholder('-')->columnSpanFull(),

                    // extra questions that the admin added to the form
                    TextEntry::make('response_data')
                        ->label('Extra answers')
                        ->columnSpanFull()
                        ->state(fn (FollowupResponse $record): array => self::extraAnswers($record))
                        ->listWithLineBreaks()
                        ->placeholder('-'),
                ]),

            Section::make('Review')
                ->columns(3)
                ->components([
                    TextEntry::make('reviewer.user.name')->label('Reviewed by')->placeholder('Not reviewed yet'),
                    TextEntry::make('reviewed_at')->label('Reviewed at')->dateTime('d M Y, h:i A')->placeholder('-'),
                    TextEntry::make('review_notes')->label('Notes')->placeholder('-')->columnSpanFull(),
                ]),
        ]);
    }

    // "Question: answer" lines for the extra (non-core) questions
    private static function extraAnswers(FollowupResponse $record): array
    {
        $labels = collect($record->form?->questions ?? [])
            ->mapWithKeys(fn ($q) => [($q['key'] ?? '') => ($q['label_en'] ?? $q['key'] ?? '')]);

        return collect($record->response_data ?? [])
            ->map(function ($value, $key) use ($labels) {
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                }

                return ($labels[$key] ?? $key).': '.(is_scalar($value) ? $value : json_encode($value));
            })
            ->values()
            ->all();
    }

    // a doctor sees only the logs of HIS OWN active patients
    public static function getEloquentQuery(): Builder
    {
        $doctorId = auth()->user()?->doctor?->id;

        $query = parent::getEloquentQuery();

        if (! $doctorId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('patient.activeAssignments', fn (Builder $q) => $q->where('doctor_id', $doctorId));
    }

    // red number in the menu = critical logs not reviewed yet
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status', 'submitted')
            ->where('is_critical', true)
            ->whereNull('reviewed_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    // the patient's answers are never created, edited or deleted by the doctor
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
            'index' => ListFollowupResponses::route('/'),
        ];
    }
}