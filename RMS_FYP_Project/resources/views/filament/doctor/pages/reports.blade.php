<x-filament-panels::page>
    {{ $this->form }}

    <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
        <x-filament::button wire:click="downloadProgress" icon="heroicon-o-document-arrow-down">
            Progress summary (PDF)
        </x-filament::button>

        <x-filament::button wire:click="downloadCompliance" color="gray" icon="heroicon-o-document-arrow-down">
            Compliance report (PDF)
        </x-filament::button>
    </div>

    <x-filament::section heading="Compliance of my patients"
        description="Daily follow-up forms in the chosen period: how many were due, submitted and missed.">

        @if ($this->rangeError)
            <p style="color: rgb(220 38 38);">{{ $this->rangeError }}</p>
        @elseif ($this->complianceRows->isEmpty())
            <p>No patients found.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid rgba(128,128,128,.35);">
                            <th style="padding:.5rem;">Patient ID</th>
                            <th style="padding:.5rem;">Patient</th>
                            <th style="padding:.5rem;">Due</th>
                            <th style="padding:.5rem;">Submitted</th>
                            <th style="padding:.5rem;">Missed</th>
                            <th style="padding:.5rem;">Compliance</th>
                            <th style="padding:.5rem;">Critical</th>
                            <th style="padding:.5rem;">Relapses</th>
                            <th style="padding:.5rem;">Last log</th>
                            <th style="padding:.5rem;">Open alerts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->complianceRows as $row)
                            <tr style="border-bottom:1px solid rgba(128,128,128,.2);">
                                <td style="padding:.5rem;">{{ $row['patient_number'] }}</td>
                                <td style="padding:.5rem;">{{ $row['name'] }}</td>
                                <td style="padding:.5rem;">{{ $row['due'] }}</td>
                                <td style="padding:.5rem;">{{ $row['submitted'] }}</td>
                                <td style="padding:.5rem;">{{ $row['missed'] }}</td>
                                <td style="padding:.5rem;">
                                    @if ($row['percent'] === null)
                                        -
                                    @else
                                        <x-filament::badge :color="$row['percent'] >= 80 ? 'success' : ($row['percent'] >= 50 ? 'warning' : 'danger')">
                                            {{ $row['percent'] }}%
                                        </x-filament::badge>
                                    @endif
                                </td>
                                <td style="padding:.5rem;">
                                    @if ($row['critical'] > 0)
                                        <x-filament::badge color="danger">
                                            {{ $row['critical'] }}@if ($row['unreviewed'] > 0) ({{ $row['unreviewed'] }} not reviewed)@endif
                                        </x-filament::badge>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td style="padding:.5rem;">{{ $row['relapses'] }}</td>
                                <td style="padding:.5rem;">{{ $row['last_log_at']?->format('d M Y, h:i A') ?? 'Never' }}</td>
                                <td style="padding:.5rem;">
                                    @if ($row['open_alerts'] > 0)
                                        <x-filament::badge color="danger">{{ $row['open_alerts'] }}</x-filament::badge>
                                    @else
                                        0
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>