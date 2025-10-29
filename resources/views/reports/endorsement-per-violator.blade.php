<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Third Violation Endorsement Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 20px;
        }

        h1,
        h2,
        h3 {
            text-align: center;
            margin: 10px 0;
        }

        .meta {
            text-align: center;
            margin-bottom: 20px;
        }

        .section {
            margin: 15px 0;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .small {
            font-size: 10px;
            color: #666;
        }

        .evidence-image {
            max-height: 250px;
            margin: 10px 0;
            display: block;
            border: 1px solid #ccc;
        }

        .violation-box {
            border: 2px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            page-break-inside: avoid;
            background-color: #f9f9f9;
        }

        .violation-number {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .violation-number.second {
            background-color: #ffc107;
            color: #333;
        }

        .violation-number.third {
            background-color: #dc3545;
        }

        .violator-header {
            background-color: #e9ecef;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .violator-header h1 {
            margin: 0 0 10px 0;
            text-align: left;
        }

        .summary-list {
            list-style-type: none;
            padding-left: 0;
        }

        .summary-list li {
            padding: 5px 0;
        }
    </style>
</head>

<body>
    <!-- Violator Header -->
    <div class="violator-header">
        <h1>{{ $violator ? $violator->firstname . ' ' . $violator->lastname : 'Unknown Violator' }}</h1>
        <p><span class="label">Student ID:</span> {{ $violator->student_id ?? 'N/A' }}</p>
        <p><span class="label">Employee ID:</span> {{ $violator->employee_id ?? 'N/A' }}</p>
        <p><span class="label">Report Period:</span> {{ $startDate }} — {{ $endDate }}</p>
    </div>

    <!-- Summary -->
    <div class="section">
        <h3>Summary</h3>
        <ul class="summary-list">
            <li><strong>Total Violations:</strong> {{ $summary['total_reports'] ?? 0 }}</li>
            <li><strong>Unique Reporters:</strong> {{ $summary['unique_reporters'] ?? 0 }}</li>
        </ul>
    </div>

    <!-- Violations -->
    @if($violations->count() > 0)
    @foreach($violations as $v)
    <div class="violation-box">
        <div class="violation-number @if($v->violation_number === 2) second @elseif($v->violation_number === 3) third @endif">
            {{ $v->violation_number }}{{ $v->violation_number === 1 ? 'st' : ($v->violation_number === 2 ? 'nd' : 'rd') }} Violation
        </div>

        <p><span class="label">Violation ID:</span> #{{ $v->id }}</p>
        <p><span class="label">Date:</span> {{ $v->created_at ? $v->created_at->format('Y-m-d H:i') : 'N/A' }}</p>
        <p><span class="label">Reporter:</span>
            {{ $v->reporter ? $v->reporter->firstname . ' ' . $v->reporter->lastname : 'N/A' }}
            <span class="small">(#{{ $v->reporter_id ?? 'N/A' }})</span>
        </p>
        <p><span class="label">Area:</span> {{ $v->area ? $v->area->name : 'N/A' }}</p>
        <p><span class="label">License Plate:</span> {{ $v->license_plate ?? 'N/A' }}</p>
        <p><span class="label">Description:</span> {{ $v->description ?? 'N/A' }}</p>

        @php
        $evidences = $v->evidence ?? [];
        @endphp

        @if(!empty($evidences))
        <div style="margin-top: 10px;">
            <strong>Evidence:</strong>
            <div style="margin: 5px 0;">
                @foreach($evidences as $label => $path)
                @php
                $fullPath = storage_path('app/public/' . $path);
                $imageExists = file_exists($fullPath);
                $base64Image = '';

                if ($imageExists) {
                    try {
                        $imageData = file_get_contents($fullPath);
                        $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                        $base64Image = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
                    } catch (\Exception $e) {
                        $imageExists = false;
                    }
                }
                @endphp

                <div style="margin: 8px 0;">
                    <strong>{{ ucfirst($label) }}:</strong><br>
                    @if($imageExists && !empty($base64Image))
                    <img src="{{ $base64Image }}" alt="Evidence {{ $label }}" class="evidence-image">
                    @else
                    <span class="small" style="color: #999;">Image not available: {{ $path }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @if(!$loop->last)
    <div style="page-break-after: always;"></div>
    @endif
    @endforeach
    @else
    <div class="section">
        <p style="text-align: center; color: #666; font-style: italic;">
            No violations found for this violator.
        </p>
    </div>
    @endif

    <div style="margin-top: 30px; text-align: right; font-size: 10px; color: #666;">
        Generated on {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
    </div>
</body>

</html>
