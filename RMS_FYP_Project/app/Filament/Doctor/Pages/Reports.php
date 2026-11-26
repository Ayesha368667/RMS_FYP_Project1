<?php

namespace App\Filament\Doctor\Pages;

use App\Models\ActivityLog;
use App\Services\DoctorReportService;
use App\Support\DoctorDashboard;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Patient care';

    protected static ?int $navigationSort = 80;

    protected static ?string $title = 'Reports';

    protected string $view = 'filament.doctor.pages.reports';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->doctor, 403);

        $this->form->fill([
            'patient_id' => null,
            'from' => today()->subDays(29)->toDateString(),
            'until' => today()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Choose the period')
                    ->description('The compliance table below and both PDF reports use this period.')
                    ->columns(3)
                    ->components([
                        Select::make('patient_id')
                            ->label('Patient (for the progress summary)')
                            ->options(fn (): array => DoctorDashboard::myPatients()
                                ->with('user')
                                ->orderBy('patient_number')
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => $p->patient_number.' - '.($p->user?->name ?? '')])
                                ->all())
                            ->searchable(),

                        DatePicker::make('from')
                            ->label('From date')
                            ->maxDate(today())
                            ->live()
                            ->required(),

                        DatePicker::make('until')
                            ->label('To date')
                            ->maxDate(today())
                            ->live()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    // ---- period chosen on the page ------------------------------------
    private function from(): Carbon
    {
        return filled($this->data['from'] ?? null)
            ? Carbon::parse($this->data['from'])->startOfDay()
            : today()->subDays(29);
    }

    private function until(): Carbon
    {
        return filled($this->data['until'] ?? null)
            ? Carbon::parse($this->data['until'])->endOfDay()
            : today()->endOfDay();
    }

    #[Computed]
    public function rangeError(): ?string
    {
        return app(DoctorReportService::class)->rangeError($this->from(), $this->until());
    }

    // the table shown on the page
    #[Computed]
    public function complianceRows(): Collection
    {
        if ($this->rangeError) {
            return collect();
        }

        return app(DoctorReportService::class)->compliance($this->from(), $this->until());
    }

    // ---- downloads -----------------------------------------------------
    public function downloadProgress()
    {
        $patientId = $this->data['patient_id'] ?? null;

        if (! $patientId) {
            Notification::make()->title('Please choose a patient first.')->warning()->send();

            return null;
        }

        if ($error = $this->rangeError) {
            Notification::make()->title($error)->danger()->send();

            return null;
        }

        try {
            $service = app(DoctorReportService::class);
            $patient = $service->myPatient($patientId);
            $data = $service->progressSummary($patient, $this->from(), $this->until());
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return null;
        }

        $output = Pdf::loadView('pdf.progress-summary', $data)
            ->setPaper('a4', 'portrait')
            ->output();

        $this->audit('Progress summary PDF', $patient->id);

        return $this->download($output, 'progress-'.$patient->patient_number.'-'.$this->until()->format('Ymd').'.pdf');
    }

    public function downloadCompliance()
    {
        if ($error = $this->rangeError) {
            Notification::make()->title($error)->danger()->send();

            return null;
        }

        $output = Pdf::loadView('pdf.compliance', [
            'rows' => $this->complianceRows,
            'doctor' => auth()->user()?->doctor?->loadMissing('user'),
            'from' => $this->from(),
            'until' => $this->until(),
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->output();

        $this->audit('Compliance report PDF', null);

        return $this->download($output, 'compliance-'.$this->until()->format('Ymd').'.pdf');
    }

    private function download(string $pdfContent, string $fileName)
    {
        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, $fileName, ['Content-Type' => 'application/pdf']);
    }

    // exporting patient data is written in the activity log
    private function audit(string $what, ?int $patientId): void
    {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'exported',
                'subject_type' => $patientId ? 'Patient' : 'Report',
                'subject_id' => $patientId,
                'description' => $what.' ('.$this->from()->format('d M Y').' to '.$this->until()->format('d M Y').')',
                'ip_address' => request()->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}