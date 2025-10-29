{{-- resources\views\livewire\admin\guest-info-modal.blade.php --}}
<div wire:ignore.self>
    <div class="modal fade" id="guestInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($guest) Guest Details: {{ $guest->firstname }} {{ $guest->lastname }} @else Guest Details @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Loading state --}}
                    @if($loading || ! $guest)
                    <div class="text-center py-5">
                        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                        <div class="mt-2 text-muted">Loading guest details…</div>
                    </div>
                    @else
                    <!-- Profile Picture -->
                    <div class="text-center mb-4">
                        @if($guest->profile_picture)
                        <img src="{{ route('profile.picture', $guest->profile_picture) }}" alt="Profile Picture"
                            class="rounded-circle"
                            style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #dee2e6;">
                        @else
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white"
                            style="width: 150px; height: 150px; font-size: 48px; font-weight: bold;">
                            {{ strtoupper(substr($guest->firstname ?? '', 0, 1) . substr($guest->lastname ?? '', 0, 1)) }}
                        </div>
                        @endif
                    </div>

                    <!-- Guest Basic Info -->
                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>ID:</strong></div>
                        <div class="col-8">{{ $guest->id ?? '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Firstname:</strong></div>
                        <div class="col-8">{{ $guest->firstname ?: '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Middlename:</strong></div>
                        <div class="col-8">{{ $guest->middlename ?: '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Lastname:</strong></div>
                        <div class="col-8">{{ $guest->lastname ?: '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Contact Number:</strong></div>
                        <div class="col-8">{{ $guest->contact_number ?? '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Address:</strong></div>
                        <div class="col-8">{{ $guest->address ?? '—' }}</div>
                    </div>

                    <!-- Vehicles Section -->
                    <hr>
                    <h6 class="mb-3">Vehicles</h6>
                    <div class="vehicle-rows">
                        @forelse($guest->vehicles as $vehicle)
                        <div class="card mb-3">
                            <div class="card-body p-3">
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>Type:</strong></div>
                                    <div class="col-md-8">{{ ucfirst($vehicle->type) }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>License Plate:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->license_plate ?? '—' }}</div>
                                </div>
                    <!-- RFID Tag Section -->
                    @if($guestPass)
                    <div class="row mb-1">
                        <div class="col-4"><strong>RFID Tag:</strong></div>
                        <div class="col-8">
                            <span class="badge bg-info text-dark me-2">{{ $guestPass->name }}</span>
                            <code>{{ $guestPass->rfid_tag }}</code>
                        </div>
                    </div>
                    @else
                    <div class="row mb-2">
                        <div class="col-4"><strong>RFID Tag:</strong></div>
                        <div class="col-8">—</div>
                    </div>
                    @endif
                                <div class="row mb-0">
                                    <div class="col-md-4"><strong>Registered:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->created_at?->format('F d, Y h:i A') }}</div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted">No vehicles registered for this guest.</p>
                        @endforelse
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Status:</strong></div>
                        <div class="col-8">
                            @if($guest->deleted_at)
                                <span class="badge bg-danger">Inactive</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Created:</strong></div>
                        <div class="col-8">{{ $guest->created_at?->format('F d, Y h:i A') }}</div>
                    </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
