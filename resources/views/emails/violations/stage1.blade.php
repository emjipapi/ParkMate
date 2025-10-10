{{-- resources\views\emails\violations\stage1.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Violation Warning</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #ffc107;
            color: #212529;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }

        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }

        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .violation-list {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }

        .violation-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .violation-item:last-child {
            border-bottom: none;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>⚠️ Parking Violation Warning</h1>
    </div>

    <div class="content">
        <h2>Dear {{ $user->firstname }} {{ $user->lastname }},</h2>

        <div class="warning-box">
            <strong>Notice:</strong> This is an official warning regarding your <strong>first recorded parking violation</strong>.
        </div>

        <p>We have recorded <strong>{{ $violationCount }}</strong> approved parking violation on your account. While this is your first offense, please take this as a reminder to follow campus parking regulations to avoid further penalties.</p>

<h3>Recent Violation:</h3>
<div class="violation-list">
    @foreach($recentViolations as $v)
    <div class="violation-item">
        <strong>License Plate:</strong> {{ data_get($v, 'license_plate') }}<br>
        <strong>Date:</strong> {{ \Carbon\Carbon::parse(data_get($v, 'created_at'))->format('M j, Y g:i A') }}<br>


        @if(data_get($v, 'description'))
        <strong>Details:</strong> {{ data_get($v, 'description') }}
        @endif
    </div>
    @endforeach
</div>


        <h3>Please Remember:</h3>
        <ul>
            <li>Always park in your designated area.</li>
            <li>Ensure your vehicle has a valid parking permit displayed.</li>
            <li>Review the campus parking guidelines to prevent further violations.</li>
        </ul>

        <div class="warning-box">
            <strong>Note:</strong> Accumulating multiple violations may result in temporary suspension of your parking privileges.
        </div>

        <p>If you have questions or believe this violation was issued in error, please contact the administration office immediately.</p>

        <p>Thank you for your cooperation and understanding.</p>

        <p><strong>ParkMate Team</strong></p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        {{-- <p>{{ config('app.name') }} - ParkMate</p> --}}
    </div>
</body>

</html>
