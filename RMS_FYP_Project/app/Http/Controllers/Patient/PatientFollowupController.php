<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\FollowupForm;

class PatientFollowupController extends Controller
{
    public function show()
    {
        // Only one form can be active
        $form = FollowupForm::where('is_active', true)->first();

        $locale = app()->getLocale();

        // Pick the question text in the patient's language
        $questions = collect($form?->questions ?? [])->map(function ($q) use ($locale) {
            $q['label'] = $q['label_'.$locale] ?? $q['label_en'] ?? $q['label'] ?? $q['key'];

            return $q;
        });

        return view('patient.followup', compact('form', 'questions'));
    }
}