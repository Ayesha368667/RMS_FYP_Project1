<?php

namespace App\Filament\Admin\Pages;

use App\Models\Admission;
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
use App\Filament\Admin\Concerns\ExportsReport;
use UnitEnum;

class AdmissionsReport extends Page implements HasTable
{
    use InteractsWithTable;
    use ExportsReport;

    protected string $view = 'filament.admin.pages.admissions-report';

    protected static ?string $title = 'Admissions & Discharges';

    protected static ?string $navigationLabel = 'Admissions & Discharges';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(Admission::query()->with('patient.user'))
            ->heading('Admissions & Discharges')
            // Filters lagne ke baad ka summary
            ->description(function () {
                return $this->reportSummary();
            })
            ->columns([
                TextColumn::make('admission_number')
                    ->label('Admission no.')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('admission_date')
                    ->label('Admitted on')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('admission_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return ucfirst((string) $state);
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'admitted' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function ($state) {
                        return ucfirst((string) $state);
                    }),

                TextColumn::make('discharge_date')
                    ->label('Discharged on')
                    ->dateTime('d M Y')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('discharge_type')
                    ->label('Discharge type')
                    ->placeholder('-')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return null;
                        }

                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                // Discharge nahi hua to aaj tak ke din
                TextColumn::make('stay_days')
                    ->label('Stay (days)')
                    ->state(function (Admission $record) {
                        $end = $record->discharge_date ?? now();

                        return (int) $record->admission_date->diffInDays($end);
                    }),
            ])
            ->defaultSort('admission_date', 'desc')
            ->deferFilters(false)
            ->filters([

                Filter::make('admission_range')
                    ->schema([
                        DatePicker::make('from')->label('Admitted from'),
                        DatePicker::make('until')->label('Admitted until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('admission_date', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('admission_date', '<=', $data['until']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if (! empty($data['from'])) {
                            $indicators[] = 'Admitted from ' . $data['from'];
                        }

                        if (! empty($data['until'])) {
                            $indicators[] = 'Admitted until ' . $data['until'];
                        }

                        return $indicators;
                    }),

                Filter::make('discharge_range')
                    ->schema([
                        DatePicker::make('from')->label('Discharged from'),
                        DatePicker::make('until')->label('Discharged until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('discharge_date', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('discharge_date', '<=', $data['until']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if (! empty($data['from'])) {
                            $indicators[] = 'Discharged from ' . $data['from'];
                        }

                        if (! empty($data['until'])) {
                            $indicators[] = 'Discharged until ' . $data['until'];
                        }

                        return $indicators;
                    }),

                SelectFilter::make('status')
                    ->options([
                        'admitted' => 'Admitted',
                        'discharged' => 'Discharged',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('admission_type')
                    ->label('Type')
                    ->options([
                        'new' => 'New admission',
                        'readmission' => 'Readmission',
                        'transfer' => 'Transfer',
                    ]),

                // Jis patient ka ye doctor assign hai ya tha
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
            ])
            ->emptyStateHeading('No admissions found');
    }
      // ===== Export ke liye methods (Excel aur PDF dono yahi use karte hain) =====
    //inko week 5 ky kam me smjhna jb bhi smjho gy.
    protected function reportTitle(): string
    {
        return 'Admissions and Discharges Report';
    }

    protected function reportSummary(): string
    {
        $query = $this->getFilteredTableQuery();

        $total = (clone $query)->count();
        $admitted = (clone $query)->where('status', 'admitted')->count();
        $discharged = (clone $query)->where('status', 'discharged')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();

        return 'Total: ' . $total
            . ' | Currently admitted: ' . $admitted
            . ' | Discharged: ' . $discharged
            . ' | Cancelled: ' . $cancelled;
    }

    protected function reportHeadings(): array
    {
        return ['Admission no.', 'Patient', 'Admitted on', 'Type', 'Status', 'Discharged on', 'Discharge type', 'Stay (days)'];
    }

    protected function reportRows(): array
    {
        $admissions = $this->getFilteredTableQuery()->orderBy('admission_date', 'desc')->get();
        $rows = [];

        foreach ($admissions as $admission) {
            $end = $admission->discharge_date ?? now();

            $dischargeType = '-';
            if ($admission->discharge_type) {
                $dischargeType = ucfirst(str_replace('_', ' ', $admission->discharge_type));
            }

            $dischargeDate = '-';
            if ($admission->discharge_date) {
                $dischargeDate = $admission->discharge_date->format('d M Y');
            }

            $rows[] = [
                $admission->admission_number,
                $admission->patient->user->name ?? '-',
                $admission->admission_date->format('d M Y'),
                ucfirst($admission->admission_type),
                ucfirst($admission->status),
                $dischargeDate,
                $dischargeType,
                (int) $admission->admission_date->diffInDays($end),
            ];
        }

        return $rows;
    }
}
