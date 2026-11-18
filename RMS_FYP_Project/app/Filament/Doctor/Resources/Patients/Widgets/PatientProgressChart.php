<?php

namespace App\Filament\Doctor\Resources\Patients\Widgets;

use App\Models\FollowupResponse;
use App\Models\Patient;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Line chart on the patient page: mood, pain and craving over time,
 * taken from the daily follow-up forms the patient submitted.
 */
class PatientProgressChart extends ChartWidget
{
    // Filament gives the open patient to this widget
    public ?Model $record = null;

    // default date range = last 30 days
    public ?string $filter = '30';

    protected int|string|array $columnSpan = 'full';

    private ?Collection $logsCache = null;

    public function getHeading(): ?string
    {
        return 'Progress chart';
    }

    public function getDescription(): ?string
    {
        $logs = $this->logs();

        if ($logs->isEmpty()) {
            return 'No submitted daily logs in this period.';
        }

        return sprintf(
            '%d logs. Average mood %s, pain %s, craving %s.',
            $logs->count(),
            $this->average($logs, 'mood_score'),
            $this->average($logs, 'pain_level'),
            $this->average($logs, 'craving_level'),
        );
    }

    // date range choices
    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '14' => 'Last 14 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            'all' => 'All time',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $logs = $this->logs();

        return [
            'labels' => $logs->map(fn ($log) => Carbon::parse($log->due_date)->format('d M'))->all(),
            'datasets' => [
                [
                    'label' => 'Mood (1-10)',
                    'data' => $logs->pluck('mood_score')->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => '#16a34a',
                    'tension' => 0.3,
                    'spanGaps' => true,
                ],
                [
                    'label' => 'Pain (0-10)',
                    'data' => $logs->pluck('pain_level')->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                    'tension' => 0.3,
                    'spanGaps' => true,
                ],
                [
                    'label' => 'Craving (0-10)',
                    'data' => $logs->pluck('craving_level')->all(),
                    'borderColor' => '#dc2626',
                    'backgroundColor' => '#dc2626',
                    'tension' => 0.3,
                    'spanGaps' => true,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => 10,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
            ],
        ];
    }

    // submitted logs of this patient in the chosen date range
    private function logs(): Collection
    {
        if ($this->logsCache !== null) {
            return $this->logsCache;
        }

        if (! $this->record) {
            return $this->logsCache = collect();
        }

        // safety: only the doctor who has this patient may see the chart
        $doctorId = auth()->user()?->doctor?->id;

        $isMyPatient = Patient::query()
            ->whereKey($this->record->getKey())
            ->whereHas('activeAssignments', fn ($q) => $q->where('doctor_id', $doctorId))
            ->exists();

        if (! $isMyPatient) {
            return $this->logsCache = collect();
        }

        $query = FollowupResponse::query()
            ->where('patient_id', $this->record->getKey())
            ->where('status', 'submitted');

        if ($this->filter !== 'all') {
            $days = max(1, (int) $this->filter);

            $query->whereDate('due_date', '>=', today()->subDays($days - 1));
        }

        return $this->logsCache = $query
            ->orderBy('due_date')
            ->get(['id', 'due_date', 'mood_score', 'pain_level', 'craving_level']);
    }

    private function average(Collection $logs, string $column): string
    {
        $values = $logs->pluck($column)->filter(fn ($v) => $v !== null);

        return $values->isEmpty() ? '-' : (string) round($values->avg(), 1);
    }
}