<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Alert;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\FollowupResponse;
use App\Models\Patient;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $patients = Patient::count();
        $doctors = Doctor::count();

        $appointmentRequests = Appointment::where('status', 'requested')->count();

        $openAlerts = Alert::where('status', 'open')->count();

        // Jin follow-ups ka due date guzar gaya aur submit nahi hue
        $missedFollowups = FollowupResponse::whereNull('submitted_at')
            ->whereDate('due_date', '<', today())
            ->count();

        // Jin ka due date aaj ya aage hai aur abhi submit nahi hue
        $pendingFollowups = FollowupResponse::whereNull('submitted_at')
            ->whereDate('due_date', '>=', today())
            ->count();

        return [
            Stat::make('Total patients', $patients)
                ->description('Registered patients')
                ->color('primary'),

            Stat::make('Total doctors', $doctors)
                ->description('Registered doctors')
                ->color('primary'),

            Stat::make('Appointment requests', $appointmentRequests)
                ->description('Waiting for approval')
                ->color($appointmentRequests > 0 ? 'warning' : 'success'),

            Stat::make('Open alerts', $openAlerts)
                ->description('Need attention')
                ->color($openAlerts > 0 ? 'danger' : 'success'),

            Stat::make('Missed follow-ups', $missedFollowups)
                ->description('Due date passed')
                ->color($missedFollowups > 0 ? 'danger' : 'success'),

            Stat::make('Pending follow-ups', $pendingFollowups)
                ->description('Due today or later')
                ->color('info'),
        ];
    }
}