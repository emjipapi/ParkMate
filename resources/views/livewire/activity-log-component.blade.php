<div>
    <input type="text" class="form-control mb-3" placeholder="Search..." 
        wire:model.live.debounce.300ms="search" style="width: 300px">

    <table class="table table-striped custom-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student/Employee ID</th>
                <th>RFID Tag</th>
                <th>Name</th>
                <th>Status</th>
                <th>Scanned At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($activityLogs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->user->student_id ?? $log->user->employee_id }}</td>
                    <td>{{ $log->rfid_tag }}</td>
                    <td>{{ $log->user->lastname }}, {{ $log->user->firstname }}</td>
                    <td>{{ $log->status }}</td>
                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No activity logs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $activityLogs->links() }}
</div>
