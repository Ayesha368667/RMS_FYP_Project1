<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\FollowupForm;
use App\Models\FollowupResponse;
use App\Models\Patient;
use App\Models\Reminder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class PatientFollowupController extends Controller
{
    // Critical rules. Change these numbers if your team decides differently.
    private const CRITICAL_MOOD_MAX    = 2;  // mood 2 or lower
    private const CRITICAL_PAIN_MIN    = 8;  // pain 8 or higher
    private const CRITICAL_CRAVING_MIN = 8;  // craving 8 or higher

    private function currentPatient(): ?Patient
    {
        return Patient::where('user_id', auth()->id())->first();
    }

       // After-care: the admission whose follow-up window includes today
    private function activeAdmission(?Patient $patient): ?Admission
    {
        return $patient?->admissions()
            ->where('status', 'discharged')
            ->whereNotNull('followup_start_date')
            ->whereDate('followup_start_date', '<=', today())
            ->whereDate('followup_end_date', '>=', today())
            ->latest('followup_start_date')
            ->first();
    }

    private function activeForm(): ?FollowupForm
    {
        return FollowupForm::where('is_active', true)->first();
    }

    public function show()
    {
        $admission = $this->activeAdmission($this->currentPatient());
        $form      = $this->activeForm();
        $locale    = app()->getLocale();

        // Did this admission already send today's follow-up?
        $already = $admission
            ? FollowupResponse::where('admission_id', $admission->id)
                ->whereDate('due_date', today())
                ->whereNotNull('submitted_at')
                ->exists()
            : false;

        // Pick the question text in the patient's language
        $questions = collect($form?->questions ?? [])->map(function ($q) use ($locale) {
            $q['label'] = $q['label_'.$locale] ?? $q['label_en'] ?? $q['label'] ?? $q['key'];

            return $q;
        });

        return view('patient.followup', compact('form', 'questions', 'admission', 'already'));
    }

    public function store(Request $request)
    {
        $patient   = $this->currentPatient();
        $admission = $this->activeAdmission($patient);
        $form      = $this->activeForm();

        if (! $patient || ! $admission || ! $form) {
            return redirect()->route('patient.followups');
        }

        // The patient must answer the form that is active right now
        if ((int) $request->input('followup_form_id') !== $form->id) {
            return redirect()->route('patient.followups')->with('error', __('followup.form_changed'));
        }

        // 1) Build the validation rules from the form's own questions
        $locale = app()->getLocale();
        $rules  = [];
        $names  = [];

        foreach ($form->questions as $q) {
            $rule = [($q['required'] ?? false) ? 'required' : 'nullable'];

            if ($q['type'] === 'number') {
                array_push($rule, 'integer', 'min:'.($q['min'] ?? 0), 'max:'.($q['max'] ?? 10));
            } elseif ($q['type'] === 'boolean') {
                $rule[] = 'boolean';
            } else {
                array_push($rule, 'string', 'max:2000');
            }

            $rules['answers.'.$q['key']] = $rule;
            $names['answers.'.$q['key']] = $q['label_'.$locale] ?? $q['label_en'] ?? $q['key'];
        }

        $answers = $request->validate($rules, [], $names)['answers'] ?? [];

        // 2) Split the answers: core columns and extra answers
        $coreKeys = array_column(FollowupForm::CORE_QUESTIONS, 'key');
        $core     = Arr::only($answers, $coreKeys);
        $extra    = Arr::except($answers, $coreKeys);

        // 3) Only one submission per due day
        $response = FollowupResponse::firstOrNew([
            'admission_id' => $admission->id,
            'due_date'     => today()->toDateString(),
        ]);

        if ($response->submitted_at) {
            return redirect()->route('patient.followups');
        }

        $response->fill([
            'patient_id'       => $patient->id,
            'followup_form_id' => $form->id,
            'submitted_at'     => now(),
            'mood_score'       => $core['mood_score'] ?? null,
            'pain_level'       => $core['pain_level'] ?? null,
            'craving_level'    => $core['craving_level'] ?? null,
            'medicine_taken'   => isset($core['medicine_taken']) ? (bool) $core['medicine_taken'] : null,
            'relapse_reported' => isset($core['relapse_reported']) ? (bool) $core['relapse_reported'] : null,
            'remarks'          => $core['remarks'] ?? null,
            'response_data'    => $extra ?: null,
            'is_critical'      => $this->isCritical($core),
            'language'         => $locale,
            'status'           => 'submitted',
        ]);

        try {
            $response->save();
        } catch (UniqueConstraintViolationException $e) {
            // Two clicks at the same time: the database allows only one row per day
            return redirect()->route('patient.followups');
        }
                // The follow-up is filled in, so its reminder is done
        Reminder::where('remindable_type', FollowupResponse::class)
            ->where('remindable_id', $response->id)
            ->where('status', 'pending')
            ->update(['status' => 'done']);

        $next = Route::has('patient.followups.history') ? 'patient.followups.history' : 'patient.followups';

        return redirect()->route($next)->with('success', __('followup.saved'));
    }

    private function isCritical(array $core): bool
    {
        return ((int) ($core['relapse_reported'] ?? 0)) === 1
            || (isset($core['mood_score'])    && (int) $core['mood_score']    <= self::CRITICAL_MOOD_MAX)
            || (isset($core['pain_level'])    && (int) $core['pain_level']    >= self::CRITICAL_PAIN_MIN)
            || (isset($core['craving_level']) && (int) $core['craving_level'] >= self::CRITICAL_CRAVING_MIN);
    }
        public function history()
    {
        $patient = $this->currentPatient();
        $locale  = app()->getLocale();

        $responses = FollowupResponse::with('form')
            ->where('patient_id', $patient?->id ?? 0)
            ->whereNotNull('submitted_at')
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->paginate(10);

        $responses->getCollection()->each(
            fn ($r) => $r->setAttribute('answer_rows', $this->answersFor($r, $locale))
        );

        return view('patient.history', compact('responses'));
    }

    // Label + answer for each question, in the patient's language
    private function answersFor(FollowupResponse $r, string $locale): array
    {
        $values = [
            'mood_score'       => $r->mood_score,
            'pain_level'       => $r->pain_level,
            'craving_level'    => $r->craving_level,
            'medicine_taken'   => $r->medicine_taken,
            'relapse_reported' => $r->relapse_reported,
            'remarks'          => $r->remarks,
        ] + ($r->response_data ?? []);

        $rows = [];

        foreach ($r->form?->questions ?? [] as $q) {
            $value = $values[$q['key']] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if (($q['type'] ?? '') === 'boolean') {
                $value = ((int) $value) === 1 ? __('followup.yes') : __('followup.no');
            }

            $rows[] = [
                'label' => $q['label_'.$locale] ?? $q['label_en'] ?? $q['key'],
                'value' => $value,
            ];
        }

        return $rows;
    }
}