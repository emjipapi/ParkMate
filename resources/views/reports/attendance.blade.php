<h2 style="text-align: center;">Attendance Report</h2>
<p><strong>Report Type:</strong> {{ ucfirst($reportType) }}</p>
<p><strong>Date Range:</strong> {{ $startDate }} - {{ $endDate }}</p>

<h3>Summary</h3>
<ul>
  <li>Total Entries: {{ $summary['entries'] }}</li>
  <li>Total Exits: {{ $summary['exits'] }}</li>
  <li>Denied Attempts: {{ $summary['denied'] }}</li>
  <li>Unique Users: {{ $summary['unique_users'] }}</li>
</ul>

<table border="1" cellspacing="0" cellpadding="5" width="100%">
  <thead>
    <tr>
      <th>Timestamp</th>
      <th>User</th>
      <th>Area</th>
      <th>Action</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @foreach($logs as $log)
      <tr>
        <td>{{ $log->created_at }}</td>
        <td>{{ $log->user->name ?? $log->epc }}</td>
        <td>{{ $log->area->name ?? '-' }}</td>
        <td>{{ ucfirst($log->action) }}</td>
        <td>{{ ucfirst($log->status) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
