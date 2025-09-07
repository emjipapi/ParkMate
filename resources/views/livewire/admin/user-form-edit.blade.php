<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">
    <form wire:submit.prevent="update" enctype="multipart/form-data"
      wire:loading.attr="disabled" wire:target="update">
        @csrf
        <!-- User Info Fields -->
        <div class="row mb-3">
            <div class="col-md">
                <label>Student ID</label>
                <input type="text" wire:model="student_id" class="form-control"
                       wire:loading.attr="disabled" wire:target="update">
            </div>
            <div class="col-md">
                <label>Employee ID</label>
                <input type="text" wire:model="employee_id" class="form-control"
                       wire:loading.attr="disabled" wire:target="update">
            </div>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" wire:model="email" class="form-control" required
                   wire:loading.attr="disabled" wire:target="update">
        </div>

        <div class="mb-3">
            <label>Password <small class="text-muted">(leave blank to keep current)</small></label>
            <input type="password" wire:model="password" class="form-control"
                   wire:loading.attr="disabled" wire:target="update">
        </div>

        <div class="row mb-3">
            <div class="col-md">
                <label>First Name</label>
                <input type="text" wire:model="firstname" class="form-control" required
                       wire:loading.attr="disabled" wire:target="update">
            </div>
            <div class="col-md">
                <label>Middle Name</label>
                <input type="text" wire:model="middlename" class="form-control"
                       wire:loading.attr="disabled" wire:target="update">
            </div>
            <div class="col-md">
                <label>Last Name</label>
                <input type="text" wire:model="lastname" class="form-control" required
                       wire:loading.attr="disabled" wire:target="update">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md">
                <label>Department</label>
                <select wire:model="department" class="form-control" required
                        wire:loading.attr="disabled" wire:target="update">
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md">
                <label>Program</label>
                <select wire:model="program" class="form-control" required
                        wire:loading.attr="disabled" wire:target="update">
                    <option value="">Select Program</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog }}">{{ $prog }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label>Profile Picture <small class="text-muted">(optional)</small></label>
            <input type="file" wire:model="profile_picture" class="form-control"
                   wire:loading.attr="disabled" wire:target="update">
            @if($currentProfilePicture)
                <small class="d-block mt-2">
                    Current: <img src="{{ route('profile.picture', $currentProfilePicture) }}" width="60">
                </small>
            @endif
        </div>

        <!-- Vehicles Section -->
        <h4>Vehicles</h4>
        <div class="vehicle-rows">
            @foreach($vehicles as $index => $vehicle)
                <div class="row mb-2 align-items-end">
                    <div class="col-md">
                        <label>Type</label>
                        <select wire:model="vehicles.{{ $index }}.type" class="form-control" required
                                wire:loading.attr="disabled" wire:target="update">
                            <option value="">Select Type</option>
                            <option value="car">Car</option>
                            <option value="motorcycle">Motorcycle</option>
                        </select>
                    </div>
                    <div class="col-md">
                        <label>RFID Tag</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.rfid_tag" class="form-control" required
                               wire:loading.attr="disabled" wire:target="update">
                    </div>
                    <div class="col-md">
                        <label>License Plate</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.license_plate" class="form-control"
                               wire:loading.attr="disabled" wire:target="update">
                    </div>
                    <div class="col-auto mt-3">
                        @if(count($vehicles) > 1)
                            <button type="button" wire:click="removeVehicleRow({{ $index }})" class="btn btn-danger"
                                    wire:loading.attr="disabled" wire:target="update">
                                Remove
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary" disabled>Remove</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-column">
            <button type="button" wire:click="addVehicleRow" class="btn btn-secondary mb-5"
                    wire:loading.attr="disabled" wire:target="update">
                Add Vehicle
            </button>

            <button type="submit" class="btn btn-primary"
                    wire:loading.attr="disabled" wire:target="update">
                <span wire:loading.remove wire:target="update">Update User</span>
                <span wire:loading wire:target="update">Updating...</span>
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
