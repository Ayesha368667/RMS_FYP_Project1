<?php

namespace App\Filament\Doctor\Widgets;

use App\Models\FollowupResponse;
use App\Models\TreatmentPlan;
use App\Support\DoctorDashboard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DoctorStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $myPatientIds = fn () => DoctorDashboard::myPatients()->select('patients.id');

        $assigned = DoctorDashboard::myPatients()->count();

        $activePlans = TreatmentPlan::query()
            ->where('status', 'active')
            ->whereIn('patient_id', $myPatientIds())
            ->count();

        $submittedToday = FollowupResponse::query()
            ->where('status', 'submitted')
            ->whereDate('submitted_at', today())
            ->whereIn('patient_id', $myPatientIds())
            ->count();

        $stillPending = FollowupResponse::query()
            ->where('status', 'pending')
            ->whereDate('due_date', today())
            ->whereIn('patient_id', $myPatientIds())
            ->count();

        $critical = DoctorDashboard::criticalLogsCount();

        $notResponding = DoctorDashboard::applyNotResponding(DoctorDashboard::myPatients())->count();

        return [
            Stat::make('Assigned patients', $assigned)
                ->description('Active assignments')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Active treatment plans', $activePlans)
                ->description('Plans in progress')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make("Today's submissions", $submittedToday)
                ->description($stillPending.' still pending today')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),

            Stat::make('Critical logs to review', $critical)
                ->description($critical > 0 ? 'Open Daily logs now' : 'Nothing waiting')
                ->descriptionIcon($critical > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($critical > 0 ? 'danger' : 'success'),

            Stat::make('Not responding', $notResponding)
                ->description('No log for '.DoctorDashboard::INACTIVE_DAYS.'+ days')
                ->descriptionIcon($notResponding > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($notResponding > 0 ? 'warning' : 'success'),
        ];
    }
}