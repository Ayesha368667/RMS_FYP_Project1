<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;
//notification class 
class SystemNotification extends Notification
{
    protected $title; //heading of notification
    protected $message; //actual msg 
    protected $url; //optional url

    //form bharna construct ky through
    public function __construct($title, $message, $url = null)
    {
        $this->title = $title; //Reassignment Inform
        $this->message = $message; //Content and detail of patient
        $this->url = $url; //to doctor like '/doctor'/ page pr bhejna
    }

    // Sirf in-app (database) notification
    public function via($notifiable): array
    {
        return ['database'];
    }

    // Ye Filament ka bell format samajhta hai
    public function toDatabase($notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title($this->title)
            ->body($this->message);

        // Link ho to "View" ka button lagao
        if ($this->url) {
            $notification->actions([
                Action::make('view')
                    ->label('View') //view ka button lga rhy hen ky 
                    //notification nay pr view ka button b sath aye 
                    //or us pr click kr ky user us notification pr chla jye and then mark it as read.
                    ->url($this->url)
                    ->markAsRead(),
            ]);
        }
        //saves this format in database
        return $notification->getDatabaseMessage();
    }
}