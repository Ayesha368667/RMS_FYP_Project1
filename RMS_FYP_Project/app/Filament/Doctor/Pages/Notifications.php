<?php

namespace App\Filament\Doctor\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Notifications extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.doctor.pages.notifications';

    // Ek notification padhi hui karo
    public function markOneAsRead($id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }
    }

    // Saari padhi hui karo
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    // Open: padhi hui karo aur link par jao
    public function openNotification($id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();

        if (! $notification) {
            return;
        }

        $notification->markAsRead();

        $url = data_get($notification->data, 'actions.0.url');

        if ($url) {
            return $this->redirect($url);
        }
    }
}