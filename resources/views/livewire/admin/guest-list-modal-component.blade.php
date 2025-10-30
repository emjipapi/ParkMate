{{-- resources\views\livewire\admin\guest-list-modal-component.blade.php --}}
<div>
    <div class="modal fade" id="guestListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Guest List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Flash messages --}}
                    @if(session('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @canaccess("manage_guest")
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Vehicle</th>
                                <th scope="col">Plate</th>
                                <th scope="col">RFID Tag</th>
                                <th scope="col">Time In</th>
                                <th scope="col">Current Location</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($guests as $guest)
                                <tr>
                                    {{-- Access the user's name through the relationship --}}
                                    <td>{{ $guest->user->firstname ?? 'N/A' }} {{ $guest->user->lastname ?? '' }}</td>
                                    
                                    {{-- Vehicle type from registration --}}
                                    <td>{{ ucfirst($guest->vehicle_type ?? 'N/A') }}</td>

                                    {{-- License plate from registration --}}
                                    <td>{{ $guest->license_plate ?? 'N/A' }}</td>

                                    {{-- RFID tag from guest pass --}}
                                    <td>{{ $guest->guestPass?->rfid_tag ?? 'N/A' }}</td>

                                    {{-- The 'updated_at' timestamp reflects when the status changed to 'in_use' --}}
                                    <td>{{ $guest->updated_at->format('h:i A') }}</td>
                                    
                                    <td>{{ $this->getLocationSummary($guest->id, $guest->user->id) }}</td>

                                    <td>
                                        {{-- <button class="btn btn-sm btn-info">Edit</button> --}}
                                        <button type="button" 
                                                wire:click="clearGuestInfo({{ $guest->id }})"
                                                wire:confirm="Are you sure you want to clear this guest's information?"
                                                class="btn btn-sm btn-danger"
                                                wire:loading.attr="disabled" 
                                                wire:target="clearGuestInfo">
                                            Clear Info
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- Updated colspan to match the new number of columns --}}
                                    <td colspan="7" class="text-center">No active guests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @endcanaccess
                </div>
                <div class="modal-footer">
                    @canaccess("manage_guest_tag")
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#guestTagsModal">
                        Manage Guest Tags
                    </button>
                    @endcanaccess
                    @canaccess("manage_guest")
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registerGuestModal">
                        <i class="bi bi-person-plus me-1"></i> Register Guest
                    </button>    
                    @endcanaccess           
                </div>
            </div>
        </div>
    </div>
</div>