<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h1,h3 { text-align: center; margin: 0; }
        .header { margin-bottom: 16px; }
        .summary { margin: 18px 0; }
        .summary ul { list-style: none; padding: 0; margin: 0; }
        .summary li { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; font-size: 11px; }
        th { background: #f5f5f5; text-align: center; }
        td { vertical-align: top; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #666; }
        .status-denied  { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <h3>{{ $reportType }} ({{ $startDate }} — {{ $endDate }})</h3>
    </div>

    <div class="summary">
        <ul>
            <li><strong>Total Entries:</strong> {{ $summary['entries'] }}</li>
            <li><strong>Total Exits:</strong> {{ $summary['exits'] }}</li>
            <li><strong>Denied Attempts:</strong> {{ $summary['denied'] }}</li>
            <li><strong>Unique Users:</strong> {{ $summary['unique_users'] }}</li>
        </ul>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:12%;">Timestamp</th>
                <th style="width:36%;">User (Name / ID / EPC)</th>
                <th style="width:20%;">Area</th>
                <th style="width:12%;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php
                    // Friendly area name: null => Main Gate
                    $areaName = $log->area->name ?? 'Main Gate';

                    // User name / id if actor_type is user and relation exists
                    $userName = null;
                    $userId = null;
                    if ($log->actor_type === 'user' && !empty($log->user)) {
                        $userName = trim(($log->user->lastname ?? '') . ', ' . ($log->user->firstname ?? ''));
                        $userId = $log->user->student_id ?? $log->user->employee_id ?? '-';
                    } else {
                        // e.g. system/admin actor
                        $userName = ucfirst($log->actor_type);
                        $userId = '-';
                    }

                    // Try to extract an EPC from details (if your logger includes it in details)
                    $epc = null;
                    if (!empty($log->details) && preg_match('/\bepc[:=]?\s*([A-Za-z0-9\-]+)/i', $log->details, $m)) {
                        $epc = $m[1];
                    }

                    // Action label (prettier)
                    $actionLabel = $log->action === 'denied_entry' ? 'Denied' : ucfirst($log->action);
                @endphp

                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>

                    <td>
                        {{ $userName }}<br>
                        <small>ID: {{ $userId }}</small>
                        @if($epc)
                            <br><small>EPC: {{ $epc }}</small>
                        @endif
                    </td>

                    <td>{{ $areaName }}</td>

                    <td style="text-align:center;">
                        {{ $actionLabel }}
                    </td>


                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">No activity logs found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
