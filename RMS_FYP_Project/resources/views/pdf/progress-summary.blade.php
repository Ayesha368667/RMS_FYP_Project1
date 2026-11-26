<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Patient progress summary</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 17px; margin: 0; color: #0f766e; }
        h2 { font-size: 12px; margin: 16px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #0f766e; color: #0f766e; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #f0fdfa; }
        .plain td { border: none; padding: 2px 4px; }
        .box { border: 1px solid #d1d5db; padding: 6px 8px; margin-top: 4px; }
        .red { color: #b91c1c; font-weight: bold; }
        .footer { margin-top: 18px; padding-top: 6px; border-top: 1px solid #d1d5db; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>BloomWay - Patient progress summary</h1>
    <div class="muted">Smart Rehabilitation Management System</div>

    <h2>Patient</h2>
    <table class="plain">
        <tr>
            <td><b>Patient ID:</b> {{ $patient->patient_number }}</td>
            <td><b>Name:</b> {{ $patient->user?->name }}</td>
            <td><b>Age:</b> {{ $patient->date_of_birth ? $patient->date_of_birth->age.' yrs' : '-' }}</td>
        </tr>
        <tr>
            <td><b>Gender:</b> {{ ucfirst((string) $patient->gender) ?: '-' }}</td>
            <td><b>Admission:</b> {{ $admission?->admission_number ?? '-' }}</td>
            <td><b>Status:</b> {{ ucfirst((string) ($admission?->status ?? $patient->status)) }}</td>
        </tr>
        <tr>
            <td><b>Admitted:</b> {{ $admission?->admission_date?->format('d M Y') ?? '-' }}</td>
            <td><b>Discharged:</b> {{ $admission?->discharge_date?->format('d M Y') ?? '-' }}</td>
            <td><b>Follow-up:</b>
                @if ($admission?->followup_start_date)
                    {{ $admission->followup_start_date->format('d M Y') }} to {{ $admission->followup_end_date?->format('d M Y') ?? 'open' }}
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3"><b>Condition type:</b> {{ $patient->condition_type ? ucfirst(str_replace('_', ' ', $patient->condition_type)) : '-' }}</td>
        </tr>
    </table>
    <p><b>Report period:</b> {{ $from->format('d M Y') }} to {{ $until->format('d M Y') }}</p>

    <h2>Daily follow-up summary</h2>
    <table>
        <tr>
            <th>Logs due</th><th>Submitted</th><th>Missed</th><th>Compliance</th>
            <th>Critical logs</th><th>Relapses reported</th><th>Medicine taken</th>
        </tr>
        <tr>
            <td>{{ $stats['due'] }}</td>
            <td>{{ $stats['submitted'] }}</td>
            <td>{{ $stats['missed'] }}</td>
            <td>{{ $stats['percent'] === null ? '-' : $stats['percent'].'%' }}</td>
            <td class="{{ $stats['critical'] > 0 ? 'red' : '' }}">{{ $stats['critical'] }}</td>
            <td class="{{ $stats['relapses'] > 0 ? 'red' : '' }}">{{ $stats['relapses'] }}</td>
            <td>
                @if ($stats['medicine_percent'] === null)
                    -
                @else
                    {{ $stats['medicine_taken'] }} of {{ $stats['medicine_answered'] }} ({{ $stats['medicine_percent'] }}%)
                @endif
            </td>
        </tr>
    </table>

    <h2>Mood, pain and craving</h2>
    <table>
        <tr>
            <th></th><th>Average</th><th>Lowest</th><th>Highest</th><th>First half average</th><th>Second half average</th>
        </tr>
        @foreach ([['Mood (1-10)', 'mood'], ['Pain (0-10)', 'pain'], ['Craving (0-10)', 'craving']] as [$label, $key])
            <tr>
                <td><b>{{ $label }}</b></td>
                <td>{{ $stats[$key]['avg'] ?? '-' }}</td>
                <td>{{ $stats[$key]['min'] ?? '-' }}</td>
                <td>{{ $stats[$key]['max'] ?? '-' }}</td>
                <td>{{ $stats[$key]['first_half'] ?? '-' }}</td>
                <td>{{ $stats[$key]['second_half'] ?? '-' }}</td>
            </tr>
        @endforeach
    </table>
    <div class="muted">The first and second half averages are shown only when there are at least 4 submitted logs. These numbers are patient-reported and for the doctor's reference only.</div>

    <h2>Treatment</h2>
    @forelse ($plans as $plan)
        <div class="box">
            <b>{{ $plan->plan_title }}</b> ({{ ucfirst($plan->status) }}),
            {{ $plan->start_date?->format('d M Y') }} to {{ $plan->target_end_date?->format('d M Y') ?? 'open' }}<br>
            <b>Goals:</b> {{ $plan->goals }}
        </div>
    @empty
        <div class="muted">No treatment plan.</div>
    @endforelse

    <h2>Current medicines</h2>
    @if ($medicines->isEmpty())
        <div class="muted">No active medicines.</div>
    @else
        <table>
            <tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Started</th><th>Until</th></tr>
            @foreach ($medicines as $m)
                <tr>
                    <td>{{ $m->medicine?->name }}</td>
                    <td>{{ $m->dosage }}</td>
                    <td>{{ $m->frequency }}</td>
                    <td>{{ $m->start_date?->format('d M Y') }}</td>
                    <td>{{ $m->end_date?->format('d M Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Sessions and assessment</h2>
    <table class="plain">
        <tr>
            <td><b>Therapy sessions in this period:</b> {{ $sessionCount }} ({{ $sessionCompleted }} completed)</td>
            <td><b>Last completed session:</b> {{ $lastSessionAt ? \Carbon\Carbon::parse($lastSessionAt)->format('d M Y') : '-' }}</td>
        </tr>
        <tr>
            <td colspan="2">
                <b>Latest assessment:</b>
                @if ($assessment)
                    {{ $assessment->assessment_date?->format('d M Y') }} ({{ ucfirst(str_replace('_', ' ', $assessment->assessment_type)) }})
                    @if (! empty($assessment->diagnosis)) - <b>Diagnosis:</b> {{ $assessment->diagnosis }} @endif
                @else
                    -
                @endif
            </td>
        </tr>
    </table>

    <h2>Alerts in this period</h2>
    @if ($alerts->isEmpty())
        <div class="muted">No alerts.</div>
    @else
        <table>
            <tr><th>Date</th><th>Type</th><th>Severity</th><th>Status</th><th>Reason</th></tr>
            @foreach ($alerts as $alert)
                <tr>
                    <td>{{ $alert->triggered_at?->format('d M Y, h:i A') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}</td>
                    <td>{{ ucfirst($alert->severity) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $alert->status)) }}</td>
                    <td>{{ $alert->trigger_reason }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Daily logs</h2>
    @if ($logs->isEmpty())
        <div class="muted">No submitted logs in this period.</div>
    @else
        <table>
            <tr>
                <th>Date</th><th>Mood</th><th>Pain</th><th>Craving</th><th>Medicine</th><th>Relapse</th><th>Critical</th><th>Remarks</th>
            </tr>
            @foreach ($logs as $log)
                <tr>
                    <td>{{ $log->due_date?->format('d M Y') }}</td>
                    <td>{{ $log->mood_score ?? '-' }}</td>
                    <td>{{ $log->pain_level ?? '-' }}</td>
                    <td>{{ $log->craving_level ?? '-' }}</td>
                    <td>{{ $log->medicine_taken === null ? '-' : ($log->medicine_taken ? 'Yes' : 'No') }}</td>
                    <td class="{{ $log->relapse_reported ? 'red' : '' }}">{{ $log->relapse_reported === null ? '-' : ($log->relapse_reported ? 'Yes' : 'No') }}</td>
                    <td class="{{ $log->is_critical ? 'red' : '' }}">{{ $log->is_critical ? 'Yes' : 'No' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit((string) $log->remarks, 80) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="footer">
        Prepared by {{ $doctor?->user?->name ?? 'Doctor' }}@if ($doctor?->specialization), {{ $doctor->specialization }}@endif
        on {{ $generatedAt->format('d M Y, h:i A') }}.
        CONFIDENTIAL: this report contains private patient information. It is not a medical diagnosis made by the system.
    </div>
</body>
</html>