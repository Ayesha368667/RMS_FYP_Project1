<?php

namespace App\Filament\Admin\Concerns;

use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

trait ExportsReport
{
    // Har report page ye 4 cheezein batati hai
    abstract protected function reportTitle(): string;

    abstract protected function reportSummary(): string;

    abstract protected function reportHeadings(): array;

    abstract protected function reportRows(): array;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () {
                    $export = new ReportExport($this->reportHeadings(), $this->reportRows());

                    $content = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

                    return response()->streamDownload(function () use ($content) {
                        echo $content;
                    }, $this->reportFileName() . '.xlsx');
                }),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $pdf = Pdf::loadView('pdf.report', [
                        'title' => $this->reportTitle(),
                        'summary' => $this->reportSummary(),
                        'headings' => $this->reportHeadings(),
                        'rows' => $this->reportRows(),
                        'generatedAt' => now()->format('d M Y, h:i A'),
                    ])->setPaper('a4', 'landscape');

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $this->reportFileName() . '.pdf');
                }),
        ];
    }

    protected function reportFileName(): string
    {
        return str_replace(' ', '-', strtolower($this->reportTitle())) . '-' . now()->format('Y-m-d');
    }
}