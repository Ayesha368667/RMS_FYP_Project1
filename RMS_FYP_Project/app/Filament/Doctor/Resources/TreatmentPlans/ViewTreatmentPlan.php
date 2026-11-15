<?php

namespace App\Filament\Doctor\Resources\TreatmentPlans\Pages;

use App\Filament\Doctor\Resources\Patients\PatientResource;
use App\Filament\Doctor\Resources\TreatmentPlans\TreatmentPlanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewTreatmentPlan extends ViewRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    // this resource has no list page, so no breadcrumb back to it
    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to patient')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => PatientResource::getUrl('view', [
                    'record' => $this->getRecord()->patient_id,
                ])),
        ];
    }
}