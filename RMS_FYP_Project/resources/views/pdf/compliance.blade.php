<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compliance report</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 17px; margin: 0; color: #0f766e; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; }
        th { background: #f0fdfa; }
        .red { color: #b91c1c; font-weight: bold; }
        .green { color: #15803d; font-weight: bold; }
        .orange { color: #b45309; font-weight: bold; }
        .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #d1d5db; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>BloomWay - Follow-up compliance report</h1>
    <div class="muted">Smart Rehabilitation Management System</div>

    <p>
        <b>Doctor:</b> {{ $doctor?->user?->name }}
        &nbsp; | &nbsp; <b>Period:</b> {{ $from->format('d M Y') }} to {{ $until->format('d M Y') }}
        &nbsp; | &nbsp; <b>Patients:</b> {{ $rows->count() }}
    </p>

    @if ($rows->isEmpty())
        <p class="muted">No patients found.</p>
    @else
        <table>
            <tr>
                <th>Patient ID</th><th>Patient</th><th>Due</th><th>Submitted</th><th>Missed</th>
                <th>Compliance</th><th>Critical</th><th>Not reviewed</th><th>Relapses</th><th>Last log</th><th>Open alerts</th>
            </tr>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['patient_number'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['due'] }}</td>
                    <td>{{ $row['submitted'] }}</td>
                    <td>{{ $row['missed'] }}</td>
                    <td class="{{ $row['percent'] === null ? '' : ($row['percent'] >= 80 ? 'green' : ($row['percent'] >= 50 ? 'orange' : 'red')) }}">
                        {{ $row['percent'] === null ? '-' : $row['percent'].'%' }}
                    </td>
                    <td class="{{ $row['critical'] > 0 ? 'red' : '' }}">{{ $row['critical'] }}</td>
                    <td class="{{ $row['unreviewed'] > 0 ? 'red' : '' }}">{{ $row['unreviewed'] }}</td>
                    <td class="{{ $row['relapses'] > 0 ? 'red' : '' }}">{{ $row['relapses'] }}</td>
                    <td>{{ $row['last_log_at']?->format('d M Y, h:i A') ?? 'Never' }}</td>
                    <td class="{{ $row['open_alerts'] > 0 ? 'red' : '' }}">{{ $row['open_alerts'] }}</td>
                </tr>
            @endforeach
        </table>
        <p class="muted">Compliance = submitted logs divided by logs due in the period. Green is 80% or more, orange 50% or more, red below 50%.</p>
    @endif

    <div class="footer">
        Generated on {{ $generatedAt->format('d M Y, h:i A') }}.
        CONFIDENTIAL: this report contains private patient information.
    </div>
</body>
</html>