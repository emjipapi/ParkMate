<!doctype html>
<html>
<body>
  <h2>Parking Violation — 3rd Offense</h2>
  <p>Hi {{ $user->firstname }},</p>
  <p>This is your <strong>third</strong> approved parking violation (total: {{ $violationCount }}). Access restrictions or other sanctions may now apply.</p>

  <h4>Recent violations</h4>
  <ul>
    @foreach($recentViolations as $v)
      <li>{{ $v->license_plate }} — {{ $v->created_at }}</li>
    @endforeach
  </ul>

  <p>If you believe this is a mistake, contact the parking office.</p>

  <p>Regards,<br/>Parking Team</p>
</body>
</html>
