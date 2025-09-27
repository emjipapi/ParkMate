<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">

    <form wire:submit.prevent="submitReport" enctype="multipart/form-data" class="p-3 p-md-4 mx-auto" style="width: 85%; ">


        {{-- Description --}}
        <div class="mb-3 mb-md-4">
            <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
            <div class="d-flex flex-column gap-1 gap-md-2 mt-1 mt-md-2">

                <select wire:model.live="description" class="form-select" required>
                    <option value="">-- Select a violation --</option>
                    <option value="Motorcycle parked in car slot">Motorcycle parked in car slot</option>
                    <option value="Vehicle blocking entrance">Vehicle blocking entrance</option>
                    <option value="Reckless Driving">Reckless Driving</option>
                    <option value="Illegal Parking">Illegal Parking</option>
                    <option value="Obstruction of Traffic">Obstruction of Traffic</option>
                    <option value="Double Parking">Double Parking</option>
                    <option value="Parking in Reserved Area">Parking in Reserved Area</option>
                    <option value="Unauthorized Vehicle Entry">Unauthorized Vehicle Entry</option>
                    <option value="No Parking Sticker Displayed">No Parking Sticker Displayed</option>
                    <option value="Improper Parking (outside lines)">Improper Parking (outside lines)</option>
                    <option value="Vehicle Blocking Fire Exit">Vehicle Blocking Fire Exit</option>
                    <option value="Unregistered Vehicle on Campus">Unregistered Vehicle on Campus</option>
                    <option value="Other">Other</option>
                </select>

                @if($description === 'Other')
                <input type="text" wire:model="otherDescription" placeholder="Enter details"
                    class="form-control mt-1 mt-md-2" required />
                @endif
            </div>
            @error('description') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        {{-- Evidence --}}
        <div class="mb-3 mb-md-4">
            <label class="form-label fw-bold">Evidence <span class="text-danger">*</span></label>
            <input type="file" wire:model="evidence" class="form-control mt-1 mt-md-2" accept="image/*" required/>
            <div wire:loading wire:target="evidence" class="mt-2 text-muted">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                Uploading image…
            </div>

            @error('evidence') <span class="text-danger">{{ $message }}</span> @enderror
        </div>


        {{-- Area --}}
        <div class="mb-3 mb-md-4">
            <label class="form-label fw-bold">Area <span class="text-danger">*</span></label>
            <select wire:model="area_id" class="form-control mt-1 mt-md-2" required>
                <option value="">Select Area</option>
                @foreach($areas as $area)
                <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </select>
            @error('area_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        {{-- License Plate --}}
<div class="mb-3 mb-md-4">
    <label class="form-label fw-bold">License Plate <span class="text-danger">*</span></label>
    <input type="text" wire:model.live.debounce.50ms="license_plate" class="form-control mt-1 mt-md-2" placeholder="123ABC" required />

    {{-- Status text --}}
    <div class="text-xs mt-1" style="min-height:1.1em;">
        @if($violatorStatus === 'loading')
            <span class="text-primary">Searching...</span>
        @elseif($violatorStatus === 'found')
            <span class="text-success">✓ {{ $violatorName }}</span>
        @elseif($violatorStatus === 'not_found')
            <span class="text-danger">✗ User not found</span>
        @endif
    </div>
</div>

{{-- Submit --}}
<div class="mt-3 mt-md-4 d-flex gap-2">
    <button
        type="button"
        wire:click="submitReport('pending')"
        class="btn btn-warning px-3 px-md-4 py-2"
        wire:loading.attr="disabled"
        wire:target="license_plate,submitReport"
    >
        Submit as Pending
    </button>

    <button
        type="button"
        wire:click="submitReport('approved')"
        class="btn btn-success px-3 px-md-4 py-2"
        @disabled($violatorStatus !== 'found')
        wire:loading.attr="disabled"
        wire:target="license_plate,submitReport"
    >
        Submit as Approved
    </button>
</div>

        {{-- Success & Error Messages --}}
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
    </form>
</div>