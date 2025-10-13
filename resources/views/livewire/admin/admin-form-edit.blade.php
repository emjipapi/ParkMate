{{-- resources\views\livewire\admin\admin-form-edit.blade.php --}}
<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box position-relative">


    <form wire:submit.prevent="update">
        @csrf

        <div class="mb-3">
            <label>Username</label>
            <input type="text" wire:model="username" class="form-control" required>
        </div>

        <div class="row mb-3">
            <div class="col-md">
                <label>First Name</label>
                <input type="text" wire:model="firstname" class="form-control" required>
            </div>
            <div class="col-md">
                <label>Middle Name</label>
                <input type="text" wire:model="middlename" class="form-control">
            </div>
            <div class="col-md">
                <label>Last Name</label>
                <input type="text" wire:model="lastname" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <label>Password <small class="text-muted">(leave blank to keep current)</small></label>
            <input type="password" wire:model="password" class="form-control">
        </div>
        <hr class="my-4">
        <h5 class="mb-3">Admin Permissions</h5>
        <div class="permissions-wrapper position-relative">

    {{-- overlay that blocks only permissions when editing super admin --}}
@if ($isSuperAdmin)
<div class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center text-center"
     style="background: rgba(255,255,255,0.6); z-index: 10; cursor: not-allowed; font-weight: 600;">
    The Super Admin permissions cannot be edited.
</div>
@endif

        {{-- Dashboard --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input group-main" type="checkbox" value="dashboard" data-group="dashboard"
                    wire:model="permissions" wire:change="toggleGroup('dashboard')">
                <label class="form-check-label fw-bold">Dashboard
                    <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                        title="{{ $allPermissions['dashboard'] ?? '' }}" tabindex="0" role="button"></i></label>
            </div>
            <div class="ms-4 mt-2">
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="analytics_dashboard"
                        data-group="dashboard" wire:model="permissions" wire:change="syncParent('dashboard')">
                    <label class="form-check-label">Analytics Dashboard
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['analytics_dashboard'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="live_attendance"
                        data-group="dashboard" wire:model="permissions" wire:change="syncParent('dashboard')">
                    <label class="form-check-label">Live Attendance<i class="bi bi-info-circle ms-1 text-secondary"
                            wire:ignore data-bs-toggle="tooltip" title="{{ $allPermissions['live_attendance'] ?? '' }}"
                            tabindex="0" role="button"></i></label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="view_map" data-group="dashboard"
                        wire:model="permissions" wire:change="syncParent('dashboard')">
                    <label class="form-check-label">View Map
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['view_map'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
            </div>
        </div>

        {{-- Parking Slots --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input group-main" type="checkbox" value="parking_slots"
                    data-group="parking_slots" wire:model="permissions" wire:change="toggleGroup('parking_slots')">
                <label class="form-check-label fw-bold">Parking Slots
                    <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                        title="{{ $allPermissions['parking_slots'] ?? '' }}" tabindex="0" role="button"></i>
                </label>
            </div>
            <div class="ms-4 mt-2">
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="manage_map"
                        data-group="parking_slots" wire:model="permissions" wire:change="syncParent('parking_slots')">
                    <label class="form-check-label">Manage Map
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['manage_map'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="add_parking_area"
                        data-group="parking_slots" wire:model="permissions" wire:change="syncParent('parking_slots')">
                    <label class="form-check-label">Add Parking Area
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['add_parking_area'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="edit_parking_area"
                        data-group="parking_slots" wire:model="permissions" wire:change="syncParent('parking_slots')">
                    <label class="form-check-label">Edit Parking Area
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['edit_parking_area'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
            </div>
        </div>

        {{-- Violation Tracking --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input group-main" type="checkbox" value="violation_tracking"
                    data-group="violation_tracking" wire:model="permissions"
                    wire:change="toggleGroup('violation_tracking')">
                <label class="form-check-label fw-bold">Violation Tracking
                    <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                        title="{{ $allPermissions['violation_tracking'] ?? '' }}" tabindex="0" role="button"></i>
                </label>
            </div>
            <div class="ms-4 mt-2">
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="pending_reports"
                        data-group="violation_tracking" wire:model="permissions"
                        wire:change="syncParent('violation_tracking')">
                    <label class="form-check-label">Pending Reports Tab
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['pending_reports'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="approved_reports"
                        data-group="violation_tracking" wire:model="permissions"
                        wire:change="syncParent('violation_tracking')">
                    <label class="form-check-label">Approved Reports Tab
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['approved_reports'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="for_endorsement"
                        data-group="violation_tracking" wire:model="permissions"
                        wire:change="syncParent('violation_tracking')">
                    <label class="form-check-label">For Endorsement Tab
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['for_endorsement'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="create_report"
                        data-group="violation_tracking" wire:model="permissions"
                        wire:change="syncParent('violation_tracking')">
                    <label class="form-check-label">Create Violation Report
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['create_report'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="submit_approved_report"
                        data-group="violation_tracking" wire:model="permissions"
                        wire:change="syncParent('violation_tracking')">
                    <label class="form-check-label">Submit Approved Report
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['submit_approved_report'] ?? '' }}" tabindex="0"
                            role="button"></i>
                    </label>
                </div>
            </div>
        </div>

        {{-- Users --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input group-main" type="checkbox" value="users" data-group="users"
                    wire:model="permissions" wire:change="toggleGroup('users')">
                <label class="form-check-label fw-bold">Users
                    <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                        title="{{ $allPermissions['users'] ?? '' }}" tabindex="0" role="button"></i>
                </label>
            </div>
            <div class="ms-4 mt-2">
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="users_table" data-group="users"
                        wire:model="permissions" wire:change="syncParent('users')">
                    <label class="form-check-label">Users Table
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['users_table'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="vehicles_table"
                        data-group="users" wire:model="permissions" wire:change="syncParent('users')">
                    <label class="form-check-label">Vehicles Table
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['vehicles_table'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="admins_table" data-group="users"
                        wire:model="permissions" wire:change="syncParent('users')">
                    <label class="form-check-label">Admins Table
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['admins_table'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="create_user" data-group="users"
                        wire:model="permissions" wire:change="syncParent('users')">
                    <label class="form-check-label">Create Users
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['create_user'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="edit_user" data-group="users"
                        wire:model="permissions" wire:change="syncParent('users')">
                    <label class="form-check-label">Edit Users
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['edit_user'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="create_admin" data-group="users"
                        wire:model="permissions" wire:change="syncParent('users')">
                    <label class="form-check-label">Create Admins
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['create_admin'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="edit_admin" data-group="users"
                        wire:model="permissions" wire:change="syncParent('users')">
                    <label class="form-check-label">Edit Admins
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['edit_admin'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
            </div>
        </div>

        {{-- Sticker Generator --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input group-main" type="checkbox" value="sticker_generator"
                    data-group="sticker_generator" wire:model="permissions"
                    wire:change="toggleGroup('sticker_generator')">
                <label class="form-check-label fw-bold">Sticker Generator
                    <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                        title="{{ $allPermissions['sticker_generator'] ?? '' }}" tabindex="0" role="button"></i>
                </label>
            </div>
            <div class="ms-4 mt-2">
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="generate_sticker"
                        data-group="sticker_generator" wire:model="permissions"
                        wire:change="syncParent('sticker_generator')">
                    <label class="form-check-label">Generate Stickers
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['generate_sticker'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="manage_sticker"
                        data-group="sticker_generator" wire:model="permissions"
                        wire:change="syncParent('sticker_generator')">
                    <label class="form-check-label">Manage Sticker Templates
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['manage_sticker'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
            </div>
        </div>

        {{-- Activity Log --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input group-main" type="checkbox" value="activity_log"
                    data-group="activity_log" wire:model="permissions" wire:change="toggleGroup('activity_log')">
                <label class="form-check-label fw-bold">Activity Log
                    <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                        title="{{ $allPermissions['activity_log'] ?? '' }}" tabindex="0" role="button"></i>
                </label>
            </div>
            <div class="ms-4 mt-2">
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="system_logs"
                        data-group="activity_log" wire:model="permissions" wire:change="syncParent('activity_log')">
                    <label class="form-check-label">System Logs Table
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['system_logs'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="entry_exit_logs"
                        data-group="activity_log" wire:model="permissions" wire:change="syncParent('activity_log')">
                    <label class="form-check-label">Entry and Exit Logs Table
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['entry_exit_logs'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input group-child" type="checkbox" value="unknown_tags"
                        data-group="activity_log" wire:model="permissions" wire:change="syncParent('activity_log')">
                    <label class="form-check-label">Unknown Tags Table
                        <i class="bi bi-info-circle ms-1 text-secondary" wire:ignore data-bs-toggle="tooltip"
                            title="{{ $allPermissions['unknown_tags'] ?? '' }}" tabindex="0" role="button"></i>
                    </label>
                </div>
            </div>
        </div>
</div>
        <hr class="my-4">

@if (empty($permissions))
<div class="alert alert-danger mt-3">Please select at least one permission.</div>
@endif
        @if (session()->has('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger mt-3">
            @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif
        <button type="submit" class="btn btn-primary" @disabled(empty($permissions))>
            <span wire:loading.remove wire:target="update">Update Admin</span>
            <span wire:loading wire:target="update">Updating...</span>
        </button>
    </form>
</div>
@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
    function initTooltips() {
        // find all tooltip triggers
        const els = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        els.forEach(el => {
            // dispose existing instance (avoid duplicates)
            const inst = bootstrap.Tooltip.getInstance(el);
            if (inst) inst.dispose();
            // create new instance
            new bootstrap.Tooltip(el);
        });
    }

    // initial run
    initTooltips();

    // re-run after every Livewire update so tooltips stay attached
    Livewire.hook('message.processed', (message, component) => {
        initTooltips();
    });
});
</script>
@endpush