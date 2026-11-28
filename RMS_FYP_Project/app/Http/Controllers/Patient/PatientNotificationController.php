<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;

class PatientNotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('patient.notifications', compact('notifications'));
    }
}