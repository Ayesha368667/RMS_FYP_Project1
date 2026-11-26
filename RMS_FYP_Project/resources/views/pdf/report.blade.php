<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { color: #0f766e; font-size: 18px; margin-bottom: 2px; }
        .small { color: #555; font-size: 10px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #0f766e; color: #fff; text-align: left; padding: 6px; }
        td { border-bottom: 1px solid #ddd; padding: 5px; }
        tr:nth-child(even) td { background: #f3f9f8; }
    </style>
</head>
<body>
    <h1>BloomWay - {{ $title }}</h1>
    <div class="small">Generated: {{ $generatedAt }}</div>
    <div class="small"><b>{{ $summary }}</b></div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>