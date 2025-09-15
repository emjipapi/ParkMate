<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">
    <form wire:submit.prevent="save" enctype="multipart/form-data">
        @csrf

        <!-- User Info Fields -->
<div class="row mb-3">
    <div class="col-md">
        <label>Student ID</label>
        <input type="text" wire:model="student_id" class="form-control">
    </div>
    <div class="col-md">
        <label>Employee ID</label>
        <input type="text" wire:model="employee_id" class="form-control">
    </div>
    <div class="col-md">
        <label>Serial Number <span class="text-muted" style="font-size: 0.75rem;">(Number only)</span></label>
        <input type="text" wire:model="serial_number" class="form-control"  placeholder="123">
    </div>
</div>

<div class="mb-3">
    <label>Email</label>
    <input type="email" wire:model="email" class="form-control" required>
</div>
<div class="mb-3">
    <label>Password</label>
    <input type="password" wire:model="password" class="form-control" required>
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

<div class="row mb-3">
<div class="col-md">
    <label>Department</label>
    <select wire:model.live="department" class="form-control">
        <option value="">Select Department</option>
        @foreach($departments as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
        @endforeach
    </select>
</div>

<div class="col-md">
    <label>Program</label>
    <select wire:model.live="program" class="form-control">
        <option value="">Select Program</option>
        @foreach($programs as $prog)
            <option value="{{ $prog }}">{{ $prog }}</option>
        @endforeach
    </select>
</div>


    <div class="col-md">
        <label>Year & Section</label>
        <input type="text" wire:model="year_section" class="form-control" maxlength="2" placeholder="1A" required>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md">
        <label>Address</label>
        <input type="text" wire:model="address" class="form-control" required>
    </div>
    <div class="col-md">
        <label>Contact Number</label>
        <input type="text" wire:model="contact_number" class="form-control" required>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md">
        <label>License Number</label>
        <input type="text" wire:model="license_number" class="form-control" required>
    </div>
<div class="col-md">
    <label>Expiration Date</label>
    <input type="date"
           wire:model="expiration_date"
           class="form-control @error('expiration_date') is-invalid @enderror"
           onfocus="this.showPicker();"
           onmousedown="event.preventDefault(); this.showPicker();">

    @error('expiration_date')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

</div>

<div class="mb-3">
    <label>Profile Picture <small class="text-muted">(optional)</small></label>
    <input type="file" wire:model="profile_picture" class="form-control">
</div>


        <!-- Vehicles Section -->
        <h4>Vehicles</h4>

<div class="vehicle-rows">
    @foreach($vehicles as $index => $vehicle)
    <div class="card mb-4">
    <div class="card-body">
        <div class="row mb-2 align-items-end">
            <div class="col-md">
                <label>Type</label>
                <select wire:model="vehicles.{{ $index }}.type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="car">Car</option>     
                </select>
            </div>
<div class="col-md">
    <label>RFID Tag</label>
    <div class="input-group">
        <input type="text" wire:model="vehicles.{{ $index }}.rfid_tag" class="form-control" required>
        <button type="button" 
                wire:click="scanRfid({{ $index }})" 
                class="btn btn-primary"
                wire:loading.attr="disabled"
                wire:target="scanRfid">
            <span wire:loading.remove wire:target="scanRfid">Scan</span>
            <span wire:loading wire:target="scanRfid">
                <i class="spinner-border spinner-border-sm me-1"></i>
            </span>
        </button>
    </div>
</div>

            <div class="col-md">
                <label>License Plate</label>
                <input type="text" wire:model="vehicles.{{ $index }}.license_plate" class="form-control" required>
            </div>
        </div>

        <!-- New row for additional fields -->
        <div class="row mb-3">
            <div class="col-md">
                <label>Body Type/Model</label>
                <input type="text" wire:model="vehicles.{{ $index }}.body_type_model" class="form-control" required>
            </div>
            <div class="col-md">
                <label>OR Number</label>
                <input type="text" wire:model="vehicles.{{ $index }}.or_number" class="form-control" required>
            </div>
            <div class="col-md">
                <label>CR Number</label>
                <input type="text" wire:model="vehicles.{{ $index }}.cr_number" class="form-control" required>
            </div>
        </div>

        <!-- Remove button full width -->
        <div class="row mb-3">
            <div class="col">
                @if(count($vehicles) > 1)
                    <button type="button" wire:click="removeVehicleRow({{ $index }})" class="btn btn-danger w-100">
                        Remove
                    </button>
                @else
                    <button type="button" class="btn btn-secondary w-100" disabled>
                        Remove
                    </button>
                @endif
            </div>
        </div>
    </div>
    </div>
    @endforeach
</div>



        <div class="d-flex flex-column">
            <button type="button" wire:click="addVehicleRow" class="btn btn-secondary mb-5">
                Add Vehicle
            </button>

            <button type="submit" class="btn btn-primary">
                Add User
            </button>
        </div>



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