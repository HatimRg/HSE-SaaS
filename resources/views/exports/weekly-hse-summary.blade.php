<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weekly HSE Summary</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; color: #1e5f9e; }
        h2 { font-size: 14px; color: #333; margin-top: 20px; border-bottom: 2px solid #1e5f9e; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #1e5f9e; color: white; }
        .meta { margin-bottom: 20px; background: #f5f5f5; padding: 15px; border-radius: 5px; }
        .meta-item { margin: 5px 0; }
        .summary-cards { display: flex; gap: 15px; margin: 15px 0; }
        .card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px; padding: 12px; text-align: center; }
        .card-value { font-size: 24px; font-weight: bold; color: #1e5f9e; }
        .card-label { font-size: 10px; color: #666; margin-top: 4px; }
        .severity-critical { color: #dc2626; font-weight: bold; }
        .severity-high { color: #ea580c; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <h1>Weekly HSE Summary</h1>

    <div class="meta">
        <div class="meta-item"><strong>Project:</strong> {{ $project->name ?? 'N/A' }}</div>
        <div class="meta-item"><strong>Week:</strong> {{ $week_start }} to {{ $week_end }}</div>
    </div>

    <div class="summary-cards">
        <div class="card">
            <div class="card-value">{{ count($hse_events) }}</div>
            <div class="card-label">HSE Events</div>
        </div>
        <div class="card">
            <div class="card-value">{{ count($kpi_values) }}</div>
            <div class="card-label">KPI Values</div>
        </div>
        <div class="card">
            <div class="card-value">{{ count($work_permits) }}</div>
            <div class="card-label">Work Permits</div>
        </div>
        <div class="card">
            <div class="card-value">{{ count($inspections) }}</div>
            <div class="card-label">Inspections</div>
        </div>
        <div class="card">
            <div class="card-value">{{ count($training_sessions) }}</div>
            <div class="card-label">Training Sessions</div>
        </div>
    </div>

    <h2>HSE Events</h2>
    @if(count($hse_events) > 0)
    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Title</th>
                <th>Occurred At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hse_events as $event)
            <tr>
                <td>{{ $event->reference }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $event->type)) }}</td>
                <td class="severity-{{ $event->severity }}">{{ ucfirst($event->severity) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $event->status)) }}</td>
                <td>{{ $event->title }}</td>
                <td>{{ $event->occurred_at?->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No HSE events this week.</p>
    @endif

    <h2>KPI Values</h2>
    @if(count($kpi_values) > 0)
    <table>
        <thead>
            <tr>
                <th>Definition</th>
                <th>Value</th>
                <th>Target</th>
                <th>Status</th>
                <th>Computed At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kpi_values as $kpi)
            <tr>
                <td>{{ $kpi->definition?->name ?? 'N/A' }}</td>
                <td>{{ $kpi->value }}</td>
                <td>{{ $kpi->target_value }}</td>
                <td>{{ ucfirst($kpi->status) }}</td>
                <td>{{ $kpi->computed_at?->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No KPI values computed this week.</p>
    @endif

    <h2>Work Permits</h2>
    @if(count($work_permits) > 0)
    <table>
        <thead>
            <tr><th>Type</th><th>Status</th><th>Expiry</th></tr>
        </thead>
        <tbody>
            @foreach($work_permits as $permit)
            <tr>
                <td>{{ $permit->permit_type ?? 'N/A' }}</td>
                <td>{{ ucfirst($permit->status) }}</td>
                <td>{{ $permit->expiry_date?->format('Y-m-d') ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No work permits this week.</p>
    @endif

    <h2>Inspections</h2>
    @if(count($inspections) > 0)
    <table>
        <thead>
            <tr><th>Title</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
            @foreach($inspections as $inspection)
            <tr>
                <td>{{ $inspection->title ?? 'N/A' }}</td>
                <td>{{ ucfirst($inspection->status) }}</td>
                <td>{{ $inspection->inspection_date?->format('Y-m-d') ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No inspections this week.</p>
    @endif

    <h2>Training Sessions</h2>
    @if(count($training_sessions) > 0)
    <table>
        <thead>
            <tr><th>Title</th><th>Type</th><th>Date</th><th>Duration</th></tr>
        </thead>
        <tbody>
            @foreach($training_sessions as $session)
            <tr>
                <td>{{ $session->title }}</td>
                <td>{{ ucfirst($session->training_type ?? 'N/A') }}</td>
                <td>{{ $session->scheduled_date?->format('Y-m-d') ?? 'N/A' }}</td>
                <td>{{ $session->duration_hours ?? 'N/A' }}h</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No training sessions this week.</p>
    @endif

    <div class="footer">
        Generated by HSE SaaS Platform on {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
