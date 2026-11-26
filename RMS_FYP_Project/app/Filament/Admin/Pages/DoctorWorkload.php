<?php

namespace App\Filament\Admin\Pages;

use App\Models\Doctor;
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
use UnitEnum;

class DoctorWorkload extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.doctor-workload';

    protected static ?string $title = 'Doctor Workload';

    protected static ?string $navigationLabel = 'Doctor Workload';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    // Date range filter ki value (from ya until) nikalne ke liye
    private function rangeValue(string $key)
    {
        return $this->tableFilters['range'][$key] ?? null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Doctor::query()->with('user'))
            ->heading('Doctor workload')
            ->description(function () {
                $from = $this->rangeValue('from');
                $until = $this->rangeValue('until');

                if (! $from && ! $until) {
                    return 'Period: all time. Active patients hamesha abhi ka load dikhata hai.';
                }

                return 'Period: ' . ($from ?? 'start') . ' to ' . ($until ?? 'today')
                    . '. Active patients hamesha abhi ka load dikhata hai.';
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Doctor')
                    ->searchable(),

                TextColumn::make('specialization')
                    ->label('Specialization'),

                TextColumn::make('availability_status')
                    ->label('Availability')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return ucfirst(str_replace('_', ' ', (string) $state));
                    }),

                // Abhi ke active patients (date range ka asar nahi)
                TextColumn::make('active_patients')
                    ->label('Active patients')
                    ->state(function (Doctor $record) {
                        return $record->activeAssignments()->count();
                    }),

                // Approved ya completed appointments
                TextColumn::make('appointments_count')
                    ->label('Appointments')
                    ->state(function (Doctor $record) {
                        $query = $record->appointments()->whereIn('status', ['approved', 'completed']);

                        if ($this->rangeValue('from')) {
                            $query->whereDate('appointment_date', '>=', $this->rangeValue('from'));
                        }

                        if ($this->rangeValue('until')) {
                            $query->whereDate('appointment_date', '<=', $this->rangeValue('until'));
                        }

                        return $query->count();
                    }),

                // Doctor ne jitni follow-up responses review ki
                TextColumn::make('reviews_count')
                    ->label('Follow-ups reviewed')
                    ->state(function (Doctor $record) {
                        $query = $record->reviewedResponses();

                        if ($this->rangeValue('from')) {
                            $query->whereDate('reviewed_at', '>=', $this->rangeValue('from'));
                        }

                        if ($this->rangeValue('until')) {
                            $query->whereDate('reviewed_at', '<=', $this->rangeValue('until'));
                        }

                        return $query->count();
                    }),
            ])
            ->deferFilters(false)
            ->filters([

                // Sirf counts ki period tay karta hai, doctors ki list nahi badalta
                Filter::make('range')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if (! empty($data['from'])) {
                            $indicators[] = 'From ' . $data['from'];
                        }

                        if (! empty($data['until'])) {
                            $indicators[] = 'Until ' . $data['until'];
                        }

                        return $indicators;
                    }),

                SelectFilter::make('id')
                    ->label('Doctor')
                    ->options(function () {
                        return Doctor::with('user')->get()->pluck('user.name', 'id');
                    }),

                SelectFilter::make('specialization')
                    ->options(function () {
                        return Doctor::query()->distinct()->pluck('specialization', 'specialization')->toArray();
                    }),
            ])
            ->emptyStateHeading('No doctors found');
    }
}