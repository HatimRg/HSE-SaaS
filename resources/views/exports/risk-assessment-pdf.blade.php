<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Risk Assessment Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; color: #1e5f9e; }
        h2 { font-size: 14px; color: #333; margin-top: 20px; }
        h3 { font-size: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1e5f9e; color: white; }
        .meta { margin-bottom: 20px; background: #f5f5f5; padding: 15px; border-radius: 5px; }
        .meta-item { margin: 5px 0; }
        .risk-level { font-weight: bold; padding: 3px 8px; border-radius: 3px; }
        .risk-extreme { background: #7f1d1d; color: white; }
        .risk-high { background: #dc2626; color: white; }
        .risk-medium { background: #ca8a04; color: white; }
        .risk-low { background: #16a34a; color: white; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
        .section { margin-top: 20px; }
        .item { margin-bottom: 15px; padding: 10px; border: 1px solid #eee; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Risk Assessment Report</h1>
    
    <div class="meta">
        <div class="meta-item"><strong>Title:</strong> {{ $assessment->title }}</div>
        <div class="meta-item"><strong>Project:</strong> {{ $assessment->project?->name ?? 'N/A' }}</div>
        <div class="meta-item"><strong>Category:</strong> {{ ucfirst($assessment->category) }}</div>
        <div class="meta-item"><strong>Status:</strong> {{ ucfirst($assessment->status) }}</div>
        <div class="meta-item">
            <strong>Risk Level:</strong> 
            <span class="risk-level risk-{{ $assessment->risk_level }}">{{ ucfirst($assessment->risk_level) }}</span>
        </div>
        <div class="meta-item"><strong>Assessor:</strong> {{ $assessment->assessor?->name ?? 'N/A' }}</div>
        <div class="meta-item"><strong>Assessment Date:</strong> {{ $assessment->assessment_date?->format('Y-m-d') }}</div>
        <div class="meta-item"><strong>Next Review:</strong> {{ $assessment->next_review_date?->format('Y-m-d') ?? 'N/A' }}</div>
    </div>

    @if($assessment->description)
        <div class="section">
            <h2>Description</h2>
            <p>{{ $assessment->description }}</p>
        </div>
    @endif

    <div class="section">
        <h2>Risk Items ({{ count($assessment->items) }})</h2>
        
        @forelse($assessment->items as $item)
            <div class="item">
                <h3>{{ $item->hazard_description }}</h3>
                <table>
                    <tr>
                        <th width="25%">Hazard</th>
                        <td>{{ $item->hazard?->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Potential Consequence</th>
                        <td>{{ $item->potential_consequence ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Risk Before Controls</th>
                        <td>
                            Likelihood: {{ $item->likelihood_before }}/5 | 
                            Severity: {{ $item->severity_before }}/5 | 
                            Score: {{ $item->risk_score_before }}
                            <span class="risk-level risk-{{ $item->risk_level_before }}">{{ ucfirst($item->risk_level_before) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Control Measures</th>
                        <td>{{ $item->control_measures ?? 'None specified' }}</td>
                    </tr>
                    <tr>
                        <th>Control Type</th>
                        <td>{{ ucfirst(str_replace('_', ' ', $item->control_type ?? 'N/A')) }}</td>
                    </tr>
                    <tr>
                        <th>Risk After Controls</th>
                        <td>
                            Likelihood: {{ $item->likelihood_after }}/5 | 
                            Severity: {{ $item->severity_after }}/5 | 
                            Score: {{ $item->risk_score_after }}
                            <span class="risk-level risk-{{ $item->risk_level_after }}">{{ ucfirst($item->risk_level_after) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Responsible Person</th>
                        <td>{{ $item->responsiblePerson?->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Target Date</th>
                        <td>{{ $item->target_date?->format('Y-m-d') ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        @empty
            <p>No risk items found.</p>
        @endforelse
    </div>

    <div class="footer">
        Generated by HSE SaaS Platform on {{ $generatedAt->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
