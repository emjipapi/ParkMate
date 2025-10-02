<!doctype html>
<html>
<body>
  <h2>Parking Violation — 1st Offense</h2>
  <p>Hi {{ $user->firstname }},</p>
  <p>We recorded your <strong>first</strong> approved parking violation (total: {{ $violationCount }}).</p>
  <p>Please review the campus parking rules to avoid further penalties.</p>

  <h4>Recent violations</h4>
  <ul>
    @foreach($recentViolations as $v)
      <li>{{ $v->license_plate }} — {{ $v->created_at }}</li>
    @endforeach
  </ul>

  <p>Regards,<br/>Parking Team</p>
</body>
</html>
