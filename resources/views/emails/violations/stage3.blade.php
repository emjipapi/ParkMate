{{-- resources\views\emails\violations\stage3.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3rd Parking Violation — Permanent Suspension</title>
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
            background-color: #dc3545;
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
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
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
        <h1>🚫 Final Notice: Permanent Parking Suspension</h1>
    </div>

    <div class="content">
        <h2>Dear {{ $user->firstname }} {{ $user->lastname }},</h2>

        <div class="warning-box">
            <strong>Final Notice:</strong> Our records indicate that you have now committed your <strong>third parking violation</strong>. 
        </div>

        <p>As a result of repeated non-compliance with campus parking policies, your <strong>parking privileges have been permanently revoked</strong>. 
        This means that effective immediately, you are <strong>no longer allowed to park or enter campus parking zones indefinitely</strong>.</p>

        <p>This decision is final and non-appealable unless explicitly reviewed and reversed by the administration under exceptional circumstances.</p>

        <h3>Violation Summary:</h3>
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
            <strong>Important:</strong> Unauthorized parking after this permanent suspension may lead to 
            <strong>disciplinary actions, towing at the owner’s expense, and possible security restrictions</strong>.
        </div>

        <p>Please ensure that you no longer attempt to access parking facilities or use any registered vehicle under your name within the campus grounds.</p>

        <p>We regret that it has come to this point, but these measures are necessary to maintain order and fairness within the campus community.</p>

        <p><strong>ParkMate Team</strong></p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>

</html>
