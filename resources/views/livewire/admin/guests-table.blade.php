{{-- resources\views\livewire\admin\guests-table.blade.php --}}
<div>
    <livewire:admin.guest-info-modal />
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-3 mb-3">
        <input type="text" class="form-control" placeholder="Search guests..." wire:model.live.debounce.300ms="search"
            style="max-width: 400px" />

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

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
        <div class="row g-3 mb-3 align-items-end">
            {{-- Guest Status Filter --}}
            <div class="col-12 col-md-3 col-lg-3">
                <label for="guestStatus" class="form-label fw-bold">Status</label>
                <select id="guestStatus" class="form-select form-select-sm" 
                    style="min-width: 200px;"
                    wire:model.live="guestStatus">
                    <option value="">All Guests</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped custom-table">
            <thead>
                <tr>
                    <th>Guest Name</th>
                    <th>Contact Number</th>
                    <th>Vehicle</th>
                    <th>Plate</th>
                    <th>Visit Reason</th>
                    <th>RFID Tag</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($guests as $registration)
                @php
                    // A registration is active if it's NOT soft-deleted AND guest pass is in_use
                    $isActive = !$registration->trashed() && $registration->guestPass && $registration->guestPass->status === 'in_use';
                    $statusBadgeClass = $isActive ? 'badge bg-success' : 'badge bg-secondary';
                    $statusText = $isActive ? 'Active' : 'Inactive';
                @endphp
                <tr>
                    <td>{{ $registration->user->firstname ?? 'N/A' }} {{ $registration->user->lastname ?? '' }}</td>
                    <td>{{ $registration->user->contact_number ?? 'N/A' }}</td>
                    <td>{{ ucfirst($registration->vehicle_type ?? 'N/A') }}</td>
                    <td>{{ $registration->license_plate ?? 'N/A' }}</td>
                    <td>{{ $registration->reason ?? 'N/A' }}</td>
                    <td>{{ $registration->guestPass->rfid_tag ?? 'N/A' }}</td>
                    <td><span class="{{ $statusBadgeClass }}">{{ $statusText }}</span></td>
                    <td>
                        <!-- More Info Icon -->
                        <a href="#" class="text-info text-decoration-none"
                            wire:click.prevent="$dispatch('openGuestModal', { id: {{ $registration->id }} })">
                            <i class="bi bi-info-circle"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">No guests found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $guests->links() }}
</div>
