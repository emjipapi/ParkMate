<div>
    {{-- Filter Bar --}}
    <div class="d-flex align-items-center gap-2 mb-3">

        {{-- Search Box --}}
        <input type="text"
            class="form-control"
            placeholder="Search by name, ID, or RFID..."
            wire:model.live.debounce.300ms="search"
            style="width: 250px"
        >

        {{-- Status Filter --}}
        <select class="form-select w-auto" wire:model.live="statusFilter">
            <option value="">All Status</option>
            <option value="IN">IN</option>
            <option value="OUT">OUT</option>
            <option value="DENIED">DENIED</option>
        </select>

        {{-- User Type Filter --}}
        <select class="form-select w-auto" wire:model.live="userType">
            <option value="">All Users</option>
            <option value="student">Students</option>
            <option value="employee">Employees</option>
        </select>

        {{-- Date Range --}}
        <input type="date" class="form-control w-auto" wire:model.live="startDate">
        <input type="date" class="form-control w-auto" wire:model.live="endDate">
    </div>

    {{-- Activity Logs Table --}}
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

    {{-- Pagination --}}
    <div class="mb-2 small text-muted">
        Showing {{ $activityLogs->count() }} of {{ $activityLogs->total() }} logs
    </div>

    {{ $activityLogs->links('pagination::bootstrap-5') }}
</div>
