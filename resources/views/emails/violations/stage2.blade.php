{{-- resources\views\emails\violations\stage2.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2nd Parking Violation Notice</title>
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
            background-color: #fd7e14;
            color: #fff;
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
            background-color: #ffe8cc;
            border: 1px solid #fcbf49;
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
        <h1>🚫 2nd Parking Violation Notice</h1>
    </div>

    <div class="content">
        <h2>Dear {{ $user->firstname }} {{ $user->lastname }},</h2>

        <div class="warning-box">
            <strong>Important:</strong> Our records indicate that you have now committed your <strong>second parking violation</strong>. 
        </div>

        <p>We have documented <strong>{{ $violationCount }}</strong> approved parking violations associated with your account. 
        Due to repeated non-compliance, your <strong>parking privileges have been suspended for a period of six (6) months</strong>, effective immediately.</p>

        <p>During this suspension period, you are not permitted to enter the campus and park within any campus-designated parking area. 
        Any attempt to do so may result in further disciplinary actions.</p>

        <h3>Recent Violations:</h3>
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

        <div class="warning-box">
            <strong>Note:</strong> Failure to comply with this suspension or committing further violations after the suspension period 
            may result in <strong>permanent revocation of your parking privileges</strong> and additional administrative sanctions.
        </div>

        <p>If you believe this notice was issued in error or wish to appeal the decision, please contact the administration office within 7 days of receiving this email.</p>

        <p>We strongly encourage you to review and adhere to all campus parking regulations moving forward.</p>

        <p>Thank you for your attention and cooperation.</p>

        <p><strong>ParkMate Team</strong></p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>

</html>
