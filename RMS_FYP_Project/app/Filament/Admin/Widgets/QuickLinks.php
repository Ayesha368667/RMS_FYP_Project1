<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Alerts\AlertResource;
use App\Filament\Admin\Resources\Appointments\AppointmentResource;
use App\Filament\Admin\Resources\FamilyContactLogs\FamilyContactLogResource;
use App\Filament\Admin\Resources\FollowupResponses\FollowupResponseResource;
use App\Filament\Admin\Resources\Patients\PatientResource;
use Filament\Widgets\Widget;

class QuickLinks extends Widget
{
    protected string $view = 'filament.admin.widgets.quick-links';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getLinks(): array
    {
        return [
            [
                'label' => 'Add patient',
                'url' => PatientResource::getUrl('create'),
                'icon' => 'heroicon-o-user-plus',
                'color' => 'primary',
            ],
            [
                'label' => 'Patients',
                'url' => PatientResource::getUrl('index'),
                'icon' => 'heroicon-o-users',
                'color' => 'gray',
            ],
            [
                'label' => 'Appointments',
                'url' => AppointmentResource::getUrl('index'),
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'gray',
            ],
            [
                'label' => 'Alerts',
                'url' => AlertResource::getUrl('index'),
                'icon' => 'heroicon-o-bell-alert',
                'color' => 'danger',
            ],
            [
                'label' => 'Follow-up Monitoring',
                'url' => FollowupResponseResource::getUrl('index'),
                'icon' => 'heroicon-o-clipboard-document-check',
                'color' => 'gray',
            ],
            [
                'label' => 'Family Contact Logs',
                'url' => FamilyContactLogResource::getUrl('index'),
                'icon' => 'heroicon-o-phone',
                'color' => 'gray',
            ],
        ];
    }
}
