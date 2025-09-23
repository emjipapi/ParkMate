<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Access Reminder</title>
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
            color: white;
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

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
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
        <h1>🚗 Parking Access Reminder - Week {{ $weekNumber }}</h1>
    </div>

    <div class="content">
        <h2>Dear {{ $user->firstname }} {{ $user->lastname }},</h2>

        <p>This is a reminder that your parking access remains restricted due to <strong>{{ $violationCount }} approved
                violations</strong> on your account.</p>

        <h3>What You Need to Do:</h3>
        <ol>
            <li><strong>Contact the Administration Office</strong> to discuss your violations</li>
            <li><strong>Address any outstanding issues</strong> related to your parking violations</li>
            <li><strong>Follow the appeals process</strong> if you believe any violations were issued in error</li>
        </ol>

        <div class="warning-box">
            <strong>Action Required:</strong> Please contact the administration office to resolve your violations.
        </div>

        <p>If you have any questions or need assistance, please contact the parking administration office.</p>

        <p>Thank you for your cooperation.</p>

        <p><strong>ParkMate</strong></p>

        <!-- Rest of your email content -->
    </div>
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>{{ config('app.name') }} - ParkMate</p>
    </div>
</body>

</html>