{{-- resources\views\livewire\admin\register-guest-modal-component.blade.php --}}
<div>
    {{-- Register Guest Modal --}}
    <div class="modal fade" wire:ignore.self id="registerGuestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="registerGuest">
                    <div class="modal-header">
                        <h5 class="modal-title">Register Guest</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- General Errors --}}
                        @error('general') 
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ $message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @enderror

                        {{-- Search Existing Guest --}}
                        <div class="card mb-3 bg-light">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">Search Existing Guest (Optional)</h6>
                            </div>
                            <div class="card-body">
                                <label for="guestSearch" class="form-label">Search by Name or License Plate</label>
                                <input type="text" class="form-control" id="guestSearch" placeholder="e.g., John Doe or ABC 1234" 
                                       wire:model.live.debounce-300ms="guestSearch"
                                       style="position: relative; z-index: 2000;">
                            </div>
                        </div>

                        @if(!empty($searchResults) && $guestSearch)
                        <div class="card mb-3" style="position: relative; z-index: 2000; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <div class="list-group list-group-flush">
                                @foreach($searchResults as $registration)
                                <button type="button" class="list-group-item list-group-item-action text-start py-2" 
                                        wire:click="populateGuestData({{ $registration['id'] }})">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>{{ $registration['user']['firstname'] ?? 'N/A' }} {{ $registration['user']['lastname'] ?? 'N/A' }}</strong>
                                            <small class="d-block text-muted">
                                                {{ ucfirst($registration['vehicle_type']) }} - {{ $registration['license_plate'] }}
                                            </small>
                                            <small class="d-block text-muted">{{ $registration['reason'] }}</small>
                                        </div>
                                    </div>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Personal Information Section --}}
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Personal Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('firstname') is-invalid @enderror" 
                                               placeholder="First Name" wire:model.live="firstname"
                                               @if($isReturningGuest) readonly style="background-color: #e9ecef;" @endif>
                                        @error('firstname') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="middlename" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" 
                                               placeholder="Middle Name (Optional)" wire:model.live="middlename"
                                               @if($isReturningGuest) readonly style="background-color: #e9ecef;" @endif>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('lastname') is-invalid @enderror" 
                                               placeholder="Last Name" wire:model.live="lastname"
                                               @if($isReturningGuest) readonly style="background-color: #e9ecef;" @endif>
                                        @error('lastname') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="contactNumber" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('contactNumber') is-invalid @enderror" 
                                           placeholder="e.g., +63 9XX XXX XXXX" wire:model.live="contactNumber"
                                           @if($isReturningGuest) readonly style="background-color: #e9ecef;" @endif>
                                    @error('contactNumber') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" 
                                           placeholder="Street, Barangay, City" wire:model.live="address"
                                           @if($isReturningGuest) readonly style="background-color: #e9ecef;" @endif>
                                </div>
                            </div>
                        </div>

                        {{-- Vehicle Information Section --}}
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Vehicle Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="vehicleType" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                                        <select class="form-select @error('vehicleType') is-invalid @enderror" wire:model.live="vehicleType">
                                            <option value="">-- Select Type --</option>
                                            <option value="motorcycle">Motorcycle</option>
                                            <option value="car">Car</option>
                                        </select>
                                        @error('vehicleType') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="licensePlate" class="form-label">License Plate <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('licensePlate') is-invalid @enderror" 
                                               placeholder="e.g., ABC 1234" wire:model.live="licensePlate">
                                        @error('licensePlate') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Guest Details Section --}}
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Guest Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason for Visit <span class="text-danger">*</span></label>
                                    <select class="form-select @error('reason') is-invalid @enderror" wire:model.live="reason">
                                        <option value="">-- Select a Reason --</option>
                                        @foreach($reasons as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('reason') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="office" class="form-label">Office</label>
                                    <input type="text" class="form-control @error('office') is-invalid @enderror" 
                                           id="office" placeholder="e.g., Main Office, Sales Dept" wire:model.live="office">
                                    @error('office') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3" wire:key="guest-tags-{{ count($guestTags) }}">
                                    <label for="selectedTag" class="form-label">Guest Tag <span class="text-danger">*</span></label>
                                    <select class="form-select @error('selectedTag') is-invalid @enderror" wire:model.live="selectedTag">
                                        <option value="">-- Select a Tag --</option>
                                        @foreach($guestTags as $tag)
                                            <option value="{{ $tag['id'] }}">
                                                {{ $tag['name'] }} ({{ $tag['rfid_tag'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('selectedTag') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body" style="border-top: 1px solid #dee2e6;">
                        {{-- General Errors (Bottom) --}}
                        @error('general') 
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ $message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning" wire:click="resetForm">
                            <i class="bi bi-arrow-clockwise me-1"></i>Clear Inputs
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="registerGuest">
                            <span wire:loading.remove wire:target="registerGuest">Register Guest</span>
                            <span wire:loading wire:target="registerGuest">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
<script>
    const modalEl = document.getElementById('registerGuestModal');
    const modal = new bootstrap.Modal(modalEl);
    
    modalEl.addEventListener('shown.bs.modal', function() {
        $wire.loadGuestTags();
    });
    
    modalEl.addEventListener('hidden.bs.modal', function() {
        // Reset form
        $wire.firstname = '';
        $wire.middlename = '';
        $wire.lastname = '';
        $wire.contactNumber = '';
        $wire.address = '';
        $wire.licensePlate = '';
        $wire.vehicleType = '';
        $wire.reason = '';
        $wire.selectedTag = '';
    });

    // Listen for the reopenRegisterModal event
    Livewire.hook('reopenRegisterModal', function() {
        modal.show();
    });
</script>
@endscript