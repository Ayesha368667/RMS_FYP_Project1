<?php

namespace App\Filament\Admin\Pages;

use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\FollowupResponse;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FollowupCompliance extends Page implements HasTable
{
    use InteractsWithTable;

   protected string $view = 'filament.admin.pages.followup-compliance';

    protected static ?string $title = 'Follow-up Compliance';

    protected static ?string $navigationLabel = 'Follow-up Compliance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(FollowupResponse::query()->with('patient.user'))
            ->heading('Follow-up compliance')
            // Filters lagne ke baad ka summary
            ->description(function () {
                $query = $this->getFilteredTableQuery();

                $responded = (clone $query)->whereNotNull('submitted_at')->count();

                $missed = (clone $query)->whereNull('submitted_at')
                    ->whereDate('due_date', '<', today())
                    ->count();

                $pending = (clone $query)->whereNull('submitted_at')
                    ->whereDate('due_date', '>=', today())
                    ->count();

                // Pending ko ginti mein nahi liya, kyunki unka waqt abhi guzra nahi
                $dueTotal = $responded + $missed;

                $compliance = 'N/A';

                if ($dueTotal > 0) {
                    $compliance = round(($responded / $dueTotal) * 100) . '%';
                }

                return 'Responded: ' . $responded
                    . ' | Missed: ' . $missed
                    . ' | Pending: ' . $pending
                    . ' | Compliance: ' . $compliance
                    . ' (responded / (responded + missed))';
            })
            ->columns([
                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('patient.condition_type')
                    ->label('Condition')
                    ->placeholder('Not set')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return null;
                        }

                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Not submitted'),

                TextColumn::make('followup_state')
                    ->label('Status')
                    ->badge()
                    ->state(function (FollowupResponse $record) {
                        if ($record->submitted_at !== null) {
                            return 'Responded';
                        }

                        if ($record->due_date->lt(today())) {
                            return 'Missed';
                        }

                        return 'Pending';
                    })
                    ->color(function (string $state) {
                        return match ($state) {
                            'Responded' => 'success',
                            'Missed' => 'danger',
                            default => 'warning',
                        };
                    }),

                IconColumn::make('is_critical')
                    ->label('Critical')
                    ->boolean(),
            ])
            ->defaultSort('due_date', 'desc')
            ->deferFilters(false)
            ->filters([

                Filter::make('due_range')
                    ->schema([
                        DatePicker::make('from')->label('Due from'),
                        DatePicker::make('until')->label('Due until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('due_date', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('due_date', '<=', $data['until']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if (! empty($data['from'])) {
                            $indicators[] = 'Due from ' . $data['from'];
                        }

                        if (! empty($data['until'])) {
                            $indicators[] = 'Due until ' . $data['until'];
                        }

                        return $indicators;
                    }),

                // Jis doctor ke assigned patients hain
                SelectFilter::make('doctor')
                    ->label('Doctor')
                    ->options(function () {
                        return Doctor::with('user')->get()->pluck('user.name', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $patientIds = DoctorAssignment::where('doctor_id', $data['value'])->pluck('patient_id');

                        return $query->whereIn('patient_id', $patientIds);
                    }),

                SelectFilter::make('followup_state')
                    ->label('Status')
                    ->options([
                        'responded' => 'Responded',
                        'missed' => 'Missed',
                        'pending' => 'Pending',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        if ($value === 'responded') {
                            return $query->whereNotNull('submitted_at');
                        }

                        if ($value === 'missed') {
                            return $query->whereNull('submitted_at')
                                ->whereDate('due_date', '<', today());
                        }

                        if ($value === 'pending') {
                            return $query->whereNull('submitted_at')
                                ->whereDate('due_date', '>=', today());
                        }

                        return $query;
                    }),

                SelectFilter::make('condition')
                    ->label('Condition type')
                    ->options([
                        'drug_addiction' => 'Drug addiction',
                        'mental_health' => 'Mental health',
                        'both' => 'Both',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('patient', function ($patientQuery) use ($data) {
                            $patientQuery->where('condition_type', $data['value']);
                        });
                    }),
            ])
            ->emptyStateHeading('No follow-ups found');
    }
}