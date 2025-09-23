<div>
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-3 mb-3">
    {{-- 🔍 Search Box --}}
    <input type="text" class="form-control mb-3" placeholder="Search by name, ID, or action..."
        wire:model.live.debounce.300ms="search" style="max-width: 400px">
                <div class="d-flex align-items-center gap-1">
            <span>Show</span>
            <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                @foreach($perPageOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            <span>entries</span>
        </div>
    </div>

    {{-- 🎛 Filter Bar --}}
    <div class="d-flex flex-wrap justify-content-start gap-2 mb-3">

        {{-- Action Filter --}}
        <select class="form-select form-select-sm w-auto" wire:model.live="actionFilter">
            <option value="">All Actions</option>
            <option value="login">Login</option>
            <option value="logout">Logout</option>
            <option value="update">Update</option>
            <option value="create">Create</option>
            <option value="delete">Delete</option>
        </select>

        {{-- User Type Filter --}}
        <select class="form-select form-select-sm w-auto" wire:model.live="userType">
            <option value="">All Users</option>
            <option value="student">Students</option>
            <option value="employee">Employees</option>
            <option value="admin">Admins</option>
        </select>

        {{-- 📅 Date Range (kept together) --}}
        <div class="d-flex align-items-center flex-nowrap">
            <input type="date" class="form-control form-control-sm w-auto" wire:model.live="startDate"
                onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">

            <span class="mx-1">-</span>

            <input type="date" class="form-control form-control-sm w-auto" wire:model.live="endDate"
                onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
        </div>
        <div class="btn-group btn-group-sm" role="group" x-data="{ sortOrder: @entangle('sortOrder') }">
            <button type="button" class="btn" :class="sortOrder === 'desc' ? 'btn-primary' : 'btn-outline-primary'"
                wire:click="$set('sortOrder', 'desc')">
                Newest
            </button>
            <button type="button" class="btn" :class="sortOrder === 'asc' ? 'btn-primary' : 'btn-outline-primary'"
                wire:click="$set('sortOrder', 'asc')">
                Oldest
            </button>
        </div>
    </div>
    {{-- 📋 Activity Logs Table --}}
    <div class="table-responsive" wire:poll.2s>
        <table class="table table-striped custom-table">
            <thead wire:ignore>
                <tr>
                    <th>ID</th>
                    <th>User Identifier</th>
                    <th>Name</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Date/Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activityLogs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>

                        {{-- Show ID depending on actor type --}}
                        <td>{{ $log->actor_type === 'admin' ? $log->admin->username ?? '—' : $log->user->student_id ?? $log->user->employee_id ?? '—' }}
                        </td>

                        {{-- Show Name depending on actor type --}}
                        <td>{{ $log->actor_type === 'admin' ? $log->admin->lastname . ', ' . $log->admin->firstname : $log->user->lastname . ', ' . $log->user->firstname }}
                        </td>

                        <td>
                            @php
                                switch ($log->action) {
                                    case 'entry':
                                        $color = 'success';
                                        break;
                                    case 'exit':
                                        $color = 'danger';
                                        break;
                                    case 'create':
                                        $color = 'primary';
                                        break;
                                    case 'update':
                                        $color = 'warning';
                                        break;
                                    case 'login':
                                        $color = 'info';
                                        break;
                                    case 'logout':
                                        $color = 'secondary';
                                        break;
                                    default:
                                        $color = 'dark';
                                        break;
                                }
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td>{{ $log->details }}</td>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No activity logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- 📌 Pagination --}}
    <div wire:key="activity-logs-pagination">
        {{ $activityLogs->links() }}
    </div>
</div>