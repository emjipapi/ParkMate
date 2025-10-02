<!doctype html>
<html>
<body>
  <h2>Parking Violation — 2nd Offense</h2>
  <p>Hi {{ $user->firstname }},</p>
  <p>Our records show this is your <strong>second</strong> approved parking violation (total: {{ $violationCount }}).</p>
  <p>Please be aware that further violations may incur penalties or restrictions.</p>

  <h4>Recent violations</h4>
  <ul>
    @foreach($recentViolations as $v)
      <li>{{ $v->license_plate }} — {{ $v->created_at }}</li>
    @endforeach
  </ul>

  <p>Regards,<br/>Parking Team</p>
</body>
</html>
