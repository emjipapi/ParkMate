{{-- resources\views\livewire\admin\activity-log-entry-exit-component.blade.php --}}
<div>
    <!-- Generate Report Button -->
    <button type="button" class="btn-add-slot btn btn-primary mb-3" data-bs-toggle="modal"
        data-bs-target="#reportModal">
        Generate Report
    </button>
    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true"
        wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-custom-width">
            <div class="modal-content" x-data="{ type: @entangle('reportType') }">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Generate Attendance Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="generateReport">
                        <div class="mb-3">
                            <label for="reportType" class="form-label">Report Type</label>
                            <select id="reportType" class="form-select" wire:model="reportType" required>
                                <option value="day">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="range">Custom Range</option>
                            </select>
                        </div>

                        <div class="row g-2 mt-2" x-show="type === 'range'" x-cloak>
                            <div class="col-md-6">
                                <label for="reportStartDate" class="form-label">Start Date</label>
                                <input type="date" id="reportStartDate" class="form-control"
                                    wire:model="reportStartDate" onfocus="this.showPicker();"
                                    onmousedown="event.preventDefault(); this.showPicker();">
                            </div>
                            <div class="col-md-6">
                                <label for="reportEndDate" class="form-label">End Date</label>
                                <input type="date" id="reportEndDate" class="form-control" wire:model="reportEndDate"
                                    onfocus="this.showPicker();"
                                    onmousedown="event.preventDefault(); this.showPicker();">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            @php
                            // disable when using custom range but dates are missing
                                $disabled = ($reportType === 'range' && (empty($reportStartDate) || empty($reportEndDate)));
                            @endphp

                            <button type="submit" class="btn btn-success" @if($disabled) disabled
                                title="Please select start and end date for custom range" @endif
                                wire:loading.attr="disabled" wire:target="generateReport">
                                <span wire:loading.remove wire:target="generateReport">
                                    Generate
                                </span>
                                <span wire:loading wire:target="generateReport">
                                    Generating...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
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
    <div class="row g-3 mb-3">
        {{-- Action Filter --}}
        <div class="col-md-2">
            <label for="actionFilter" class="form-label fw-bold">Action</label>
            <select id="actionFilter" class="form-select form-select-sm" wire:model.live="actionFilter">
                <option value="">All Actions</option>
                <option value="entry">Entry</option>
                <option value="exit">Exit</option>
                <option value="denied_entry">Denied Entry</option>
            </select>
        </div>

        {{-- User Type Filter --}}
        <div class="col-md-2">
            <label for="userType" class="form-label fw-bold">User Type</label>
            <select id="userType" class="form-select form-select-sm" wire:model.live="userType">
                <option value="">All Users</option>
                <option value="student">Students</option>
                <option value="employee">Employees</option>
            </select>
        </div>

        {{-- Area Filter --}}
        <div class="col-md-2">
            <label for="areaFilter" class="form-label fw-bold">Area</label>
            <select id="areaFilter" class="form-select form-select-sm" wire:model.live="areaFilter">
                <option value="">All Areas</option>
                @foreach($parkingAreas as $area)
                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Date Range --}}
        <div class="col-md-3">
            <label for="startDate" class="form-label fw-bold">Date Range</label>
            <div class="input-group input-group-sm">
                <input type="date" id="startDate" class="form-control" wire:model.live="startDate"
                    onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
                <span class="input-group-text">-</span>
                <input type="date" class="form-control" wire:model.live="endDate" onfocus="this.showPicker();"
                    onmousedown="event.preventDefault(); this.showPicker();">
            </div>
        </div>

        {{-- Sort Order Buttons --}}
        <div class="col-md-3 d-flex align-items-end">
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
    </div>

    {{-- Counters --}}
    <div class="row mb-3">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Entries</h5>
                    <p class="card-text">{{ $entryCount }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Exits</h5>
                    <p class="card-text">{{ $exitCount }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Denied</h5>
                    <p class="card-text">{{ $deniedCount }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Unknown Tags Scanned</h5>
                    <p class="card-text">{{ $unknownTagsCount }}</p>
                </div>
            </div>
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

 @php
                    // Safe ID display
                    $idDisplay = $log->actor_type === 'admin'
                    ? (optional($log->admin)->username ?? '—')
                    : (optional($log->user)->student_id ?? optional($log->user)->employee_id ?? '—');

                    // Safe Name display (lastname, firstname) — fall back to id-like value if name missing
                    if ($log->actor_type === 'admin') {
                    $lastname = optional($log->admin)->lastname;
                    $firstname = optional($log->admin)->firstname;
                    } else {
                    $lastname = optional($log->user)->lastname;
                    $firstname = optional($log->user)->firstname;
                    }

                    $nameDisplay = trim(($lastname ? $lastname . ', ' : '') . ($firstname ?? ''));

                    if ($nameDisplay === '') {
                    // fallback if no name available
                    $nameDisplay = $log->actor_type === 'admin'
                    ? (optional($log->admin)->username ?? '—')
                    : (optional($log->user)->student_id ?? optional($log->user)->employee_id ?? '—');
                    }
                    @endphp

                    {{-- Show ID depending on actor type --}}
                    <td>{{ $idDisplay }}</td>

                    {{-- Show Name depending on actor type --}}
                    <td>{{ $nameDisplay }}</td>

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