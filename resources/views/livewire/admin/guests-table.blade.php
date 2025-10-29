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
                    <th>ID</th>
                    <th>Firstname</th>
                    <th>Middlename</th>
                    <th>Lastname</th>
                    <th>Contact Number</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($guests as $guest)
                @php
                    $isDeleted = $guest->deleted_at !== null;
                    $statusBadgeClass = $isDeleted ? 'badge bg-danger' : 'badge bg-success';
                    $statusText = $isDeleted ? 'Inactive' : 'Active';
                @endphp
                <tr>
                    <td>{{ $guest->id }}</td>
                    <td>{{ $guest->firstname }}</td>
                    <td>{{ $guest->middlename }}</td>
                    <td>{{ $guest->lastname }}</td>
                    <td>{{ $guest->contact_number ?? 'N/A' }}</td>
                    <td>{{ $guest->address ?? 'N/A' }}</td>
                    <td><span class="{{ $statusBadgeClass }}">{{ $statusText }}</span></td>
                    <td>
                        <!-- More Info Icon -->
                        <a href="#" class="text-info text-decoration-none"
                            wire:click.prevent="$dispatch('openGuestModal', { id: {{ $guest->id }} })">
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
