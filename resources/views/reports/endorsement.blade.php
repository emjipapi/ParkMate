<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Endorsement Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 20px;
        }
        h1, h2 {
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
            max-width: 400px;
            max-height: 300px;
            margin: 10px 0;
            display: block;
            border: 1px solid #ccc;
        }
        .violation-box {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <h1>Endorsement Report</h1>
    <div class="meta">
        <div><strong>{{ $reportType ?? 'For Endorsement' }}</strong></div>
        <div class="small">{{ $startDate }} — {{ $endDate }}</div>
    </div>

    <div class="section">
        <strong>Summary</strong>
        <ul>
            <li>Total reports: {{ $summary['total_reports'] ?? 0 }}</li>
            <li>Unique reporters: {{ $summary['unique_reporters'] ?? 0 }}</li>
            <li>Unique violators: {{ $summary['unique_violators'] ?? 0 }}</li>
        </ul>
    </div>

    @if($violations->count() > 0)
        @foreach($violations as $v)
            <div class="violation-box">
                <h2>Violation #{{ $v->id }}</h2>
                <p><span class="label">Date:</span> {{ $v->created_at ? $v->created_at->format('Y-m-d H:i') : 'N/A' }}</p>
                <p><span class="label">Reporter:</span>
                    {{ $v->reporter ? $v->reporter->firstname . ' ' . $v->reporter->lastname : 'N/A' }}
                    <span class="small">(#{{ $v->reporter_id ?? 'N/A' }})</span>
                </p>
                <p><span class="label">Violator:</span>
                    {{ $v->violator ? $v->violator->firstname . ' ' . $v->violator->lastname : 'Unknown' }}
                    <span class="small">({{ $v->license_plate ?? 'N/A' }})</span>
                </p>
                <p><span class="label">Area:</span> {{ $v->area ? $v->area->name : 'N/A' }}</p>
                <p><span class="label">Description:</span> {{ $v->description ?? 'N/A' }}</p>

                @php
                    $evidences = [];
                    if (!empty($v->evidence)) {
                        try {
                            $evidences = json_decode($v->evidence, true) ?? [];
                        } catch (\Exception $e) {
                            $evidences = [];
                        }
                    }
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
                No violations found for the specified date range.
            </p>
        </div>
    @endif

    <div style="margin-top: 30px; text-align: right; font-size: 10px; color: #666;">
        Generated on {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>