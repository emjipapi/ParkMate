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
            margin: 0;
            padding: 20px;
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

        /* Cover page styling */
        .cover-page {
            page-break-after: always;
            margin: 0;
            padding: 0;
            height: 750px;
            position: relative;
            display: block;
        }

        .cover-card {
            border: 2px solid #333;
            padding: 40px;
            border-radius: 8px;
            background-color: #f5f5f5;
            width: 350px;
            text-align: center;
            margin: 0 auto;
            margin-top: 280px;
        }

        .cover-card h1 {
            font-size: 32px;
            margin: 0 0 30px 0;
            text-align: center;
        }

        .cover-info {
            text-align: left;
            font-size: 14px;
            line-height: 2;
        }

        .cover-info p {
            margin: 10px 0;
        }

        .cover-label {
            font-weight: bold;
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
    <!-- Cover Page -->
    <div style="width: 100%; height: 750px; display: -webkit-box; -webkit-box-align: center; -webkit-box-pack: center;">
        <div style="border: 2px solid #333; padding: 40px; background-color: #f5f5f5; width: 350px;">
            <h1 style="font-size: 32px; margin: 0 0 30px 0; text-align: center;">{{ $violator ? $violator->firstname . ' ' . $violator->lastname : 'Unknown Violator' }}</h1>
                    <div style="text-align: left; font-size: 14px; line-height: 2;">
                        @if($violator && $violator->student_id)
                        <p style="margin: 10px 0;"><span style="font-weight: bold;">Student ID:</span> {{ $violator->student_id }}</p>
                        @endif
                        @if($violator && $violator->employee_id)
                        <p style="margin: 10px 0;"><span style="font-weight: bold;">Employee ID:</span> {{ $violator->employee_id }}</p>
                        @endif
                        @if(!$violator || (!$violator->student_id && !$violator->employee_id))
                        <p style="margin: 10px 0;"><span style="font-weight: bold;">ID:</span> N/A</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="page-break-after: always;"></div>

    <!-- Violations - Each on separate page -->
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
