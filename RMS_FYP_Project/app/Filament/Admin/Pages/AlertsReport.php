<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\ExportsReport;
use App\Models\Alert;
use App\Models\Doctor;
use App\Models\DoctorAssignment;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AlertsReport extends Page implements HasTable
{
    use InteractsWithTable;
    use ExportsReport;

    protected string $view = 'filament.admin.pages.alerts-report';

    protected static ?string $title = 'Alerts Report';

    protected static ?string $navigationLabel = 'Alerts Report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Alert::query()->with('patient.user'))
            ->heading('Alerts report')
            // Filters lagne ke baad ka summary
            ->description(function () {
                return $this->reportSummary();
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

                TextColumn::make('alert_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('severity')
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'critical', 'high' => 'danger',
                            'medium' => 'warning',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    })
                    ->color(function (string $state) {
                        return match ($state) {
                            'open' => 'danger',
                            'under_action' => 'warning',
                            'resolved' => 'success',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('trigger_reason')
                    ->label('Reason')
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('triggered_at')
                    ->label('Triggered At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('resolved_at')
                    ->label('Resolved At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),
            ])
            ->defaultSort('triggered_at', 'desc')
            ->deferFilters(false)
            ->filters([

                Filter::make('triggered_range')
                    ->schema([
                        DatePicker::make('from')->label('Triggered from'),
                        DatePicker::make('until')->label('Triggered until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('triggered_at', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('triggered_at', '<=', $data['until']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if (! empty($data['from'])) {
                            $indicators[] = 'Triggered from ' . $data['from'];
                        }

                        if (! empty($data['until'])) {
                            $indicators[] = 'Triggered until ' . $data['until'];
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

                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'under_action' => 'Under action',
                        'resolved' => 'Resolved',
                    ]),

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
            ->emptyStateHeading('No alerts found');
    }

    // ===== Export ke liye methods (Excel aur PDF dono yahi use karte hain) =====

    protected function reportTitle(): string
    {
        return 'Alerts Report';
    }

    protected function reportSummary(): string
    {
        $query = $this->getFilteredTableQuery();

        $total = (clone $query)->count();
        $open = (clone $query)->where('status', 'open')->count();
        $underAction = (clone $query)->where('status', 'under_action')->count();
        $resolved = (clone $query)->where('status', 'resolved')->count();
        $serious = (clone $query)->whereIn('severity', ['critical', 'high'])->count();

        return 'Total: ' . $total
            . ' | Open: ' . $open
            . ' | Under action: ' . $underAction
            . ' | Resolved: ' . $resolved
            . ' | Critical/High: ' . $serious;
    }

    protected function reportHeadings(): array
    {
        return ['Patient', 'Condition', 'Type', 'Severity', 'Status', 'Reason', 'Triggered at', 'Resolved at'];
    }

    protected function reportRows(): array
    {
        $alerts = $this->getFilteredTableQuery()->orderBy('triggered_at', 'desc')->get();
        $rows = [];

        foreach ($alerts as $alert) {
            $condition = 'Not set';
            if ($alert->patient && $alert->patient->condition_type) {
                $condition = ucfirst(str_replace('_', ' ', $alert->patient->condition_type));
            }

            $resolvedAt = '-';
            if ($alert->resolved_at) {
                $resolvedAt = $alert->resolved_at->format('d M Y, h:i A');
            }

            $rows[] = [
                $alert->patient->user->name ?? '-',
                $condition,
                ucfirst(str_replace('_', ' ', $alert->alert_type)),
                ucfirst($alert->severity),
                ucfirst(str_replace('_', ' ', $alert->status)),
                $alert->trigger_reason,
                $alert->triggered_at->format('d M Y, h:i A'),
                $resolvedAt,
            ];
        }

        return $rows;
    }
}