<?php

namespace App\Filament\Doctor\Resources\PostDischarge;

use App\Filament\Doctor\Resources\PostDischarge\Pages\ListPostDischarge;
use App\Filament\Doctor\Resources\PostDischarge\Pages\ViewPostDischarge;
use App\Filament\Doctor\Resources\PostDischarge\RelationManagers\PostDischargeNotesRelationManager;
use App\Filament\Doctor\Resources\PostDischarge\Tables\PostDischargeTable;
use App\Models\Admission;
use App\Models\Alert;
use App\Models\FollowupResponse;
use App\Support\DoctorDashboard;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PostDischargeResource extends Resource
{
    protected static ?string $model = Admission::class;

    protected static ?string $slug = 'post-discharge';

    protected static ?string $navigationLabel = 'Post-discharge';

    protected static ?string $modelLabel = 'post-discharge follow-up';

    protected static ?string $pluralModelLabel = 'Post-discharge follow-up';

    protected static string|UnitEnum|null $navigationGroup = 'Patient care';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    public static function table(Table $table): Table
    {
        return PostDischargeTable::configure($table);
    }

    // ---- follow-up status, worked out from the dates -----------------
    // returns [label, color]
    public static function followupState(Admission $admission): array
    {
        $start = $admission->followup_start_date;
        $end = $admission->followup_end_date;

        if (! $start) {
            return ['No follow-up', 'gray'];
        }

        if ($start->isFuture()) {
            return ['Not started', 'gray'];
        }

        if ($end && $end->lt(today())) {
            return ['Ended', 'gray'];
        }

        if ($end && $end->lte(today()->addDays(7))) {
            return ['Ending soon', 'warning'];
        }

        return ['Active', 'success'];
    }

    public static function daysLeft(Admission $admission): ?int
    {
        $end = $admission->followup_end_date;

        if (! $end || $end->lt(today())) {
            return null;
        }

        return (int) today()->diffInDays($end);
    }

    // [text, color] for "submitted / due (percent)"
    public static function compliance(Admission $admission): array
    {
        $due = (int) ($admission->due_count ?? 0);
        $done = (int) ($admission->submitted_count ?? 0);

        if ($due === 0) {
            return ['-', 'gray'];
        }

        $percent = (int) round($done / $due * 100);

        return [
            $done.' / '.$due.' ('.$percent.'%)',
            $percent >= 80 ? 'success' : ($percent >= 50 ? 'warning' : 'danger'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Follow-up')
                ->columns(3)
                ->components([
                    TextEntry::make('patient.patient_number')->label('Patient ID'),
                    TextEntry::make('patient.user.name')->label('Patient'),
                    TextEntry::make('admission_number')->label('Admission no.'),

                    TextEntry::make('discharge_date')->label('Discharged on')->dateTime('d M Y')->placeholder('-'),
                    TextEntry::make('followup_start_date')->label('Follow-up starts')->date('d M Y')->placeholder('-'),
                    TextEntry::make('followup_end_date')->label('Follow-up ends')->date('d M Y')->placeholder('-'),

                    TextEntry::make('followup_frequency')
                        ->label('Frequency')
                        ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                    TextEntry::make('status_label')
                        ->label('Follow-up status')
                        ->badge()
                        ->state(fn (Admission $record): string => self::followupState($record)[0])
                        ->color(fn (Admission $record): string => self::followupState($record)[1]),

                    TextEntry::make('days_left')
                        ->label('Days left')
                        ->state(fn (Admission $record): ?int => self::daysLeft($record))
                        ->placeholder('-'),
                ]),

            Section::make('How is the patient doing?')
                ->columns(3)
                ->components([
                    TextEntry::make('compliance')
                        ->label('Logs submitted')
                        ->badge()
                        ->state(fn (Admission $record): string => self::compliance($record)[0])
                        ->color(fn (Admission $record): string => self::compliance($record)[1]),

                    TextEntry::make('last_log_at')
                        ->label('Last log')
                        ->dateTime('d M Y, h:i A')
                        ->placeholder('Never'),

                    TextEntry::make('open_alerts_count')
                        ->label('Unresolved alerts')
                        ->badge()
                        ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'success'),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            PostDischargeNotesRelationManager::class,
        ];
    }

    // discharged admissions of HIS OWN active patients, with the follow-up numbers
    public static function getEloquentQuery(): Builder
    {
        $responses = fn () => FollowupResponse::query()
            ->whereColumn('followup_responses.admission_id', 'admissions.id');

        return parent::getEloquentQuery()
            ->select('admissions.*')
            ->addSelect([
                'submitted_count' => $responses()->selectRaw('count(*)')->where('status', 'submitted'),
                'due_count' => $responses()->selectRaw('count(*)')->whereDate('due_date', '<=', today()),
                'last_log_at' => $responses()->selectRaw('max(submitted_at)')->where('status', 'submitted'),
                'open_alerts_count' => Alert::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('alerts.patient_id', 'admissions.patient_id')
                    ->whereIn('status', ['open', 'under_action']),
            ])
            ->where('admissions.status', 'discharged')
            ->whereIn('admissions.patient_id', DoctorDashboard::myPatients()->select('patients.id'));
    }

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
            'index' => ListPostDischarge::route('/'),
            'view' => ViewPostDischarge::route('/{record}'),
        ];
    }
}