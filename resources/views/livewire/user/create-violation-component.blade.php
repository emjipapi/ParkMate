<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">

    <form wire:submit.prevent="submitReport" enctype="multipart/form-data" class="w-100 p-3 p-md-4">

{{-- Description --}}
<div class="mb-3 mb-md-4">
    <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
    <div class="d-flex flex-column gap-1 gap-md-2 mt-1 mt-md-2">
        
        <label class="d-block">
            <input type="radio" wire:model.live="description" value="Motorcycle parked in car slot" required>
            Motorcycle parked in car slot
        </label>

        <label class="d-block">
            <input type="radio" wire:model.live="description" value="Vehicle blocking entrance" required>
            Vehicle blocking entrance
        </label>

        <label class="d-block">
            <input type="radio" wire:model.live="description" value="Reckless Driving" required>
            Reckless Driving
        </label>

        <label class="d-block">
            <input type="radio" wire:model.live="description" value="Other" required>
            Other:
        </label>

        @if($description === 'Other')
            <input type="text" wire:model="otherDescription" 
                   placeholder="Enter details" 
                   class="form-control mt-1 mt-md-2"
                   required />
        @endif
    </div>
    @error('description') <span class="text-danger">{{ $message }}</span> @enderror
</div>

        {{-- Evidence --}}
        <div class="mb-3 mb-md-4">
            <label class="form-label fw-bold">Evidence (optional)</label>
            <input type="file" wire:model="evidence" class="form-control mt-1 mt-md-2" accept="image/*,video/*" />
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
            <label class="form-label fw-bold">License Plate (optional)</label>
            <input type="text" wire:model="license_plate" class="form-control mt-1 mt-md-2" placeholder="ABC-1234" />
        </div>


        {{-- Submit --}}
        <div class="mt-3 mt-md-4">
            <button type="submit" class="btn btn-primary px-3 px-md-4 py-2">
                Submit Report
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