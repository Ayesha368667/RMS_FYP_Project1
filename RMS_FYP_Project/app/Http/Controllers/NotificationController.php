<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Ek notification padhi hui karo, phir uske link par jao
    public function read(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (! $notification) {
            return back();
        }

        $notification->markAsRead();

        $url = data_get($notification->data, 'actions.0.url');

        if ($url) {
            return redirect($url);
        }

        return back();
    }

    // Saari notifications padhi hui karo
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}