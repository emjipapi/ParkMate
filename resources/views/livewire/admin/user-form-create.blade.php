{{-- resources\views\livewire\admin\user-form-create.blade.php --}}
<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">
    <form wire:submit.prevent="save" enctype="multipart/form-data">
        @csrf

        <!-- User Info Fields -->
        <div class="row mb-3">
            <!-- Student ID -->
            <div class="col-md d-flex align-items-center gap-2">
                <div>
                    <input type="checkbox" wire:model="useStudentId" wire:change="$set('useEmployeeId', false)"
                        id="chkStudentId">
                </div>
                <div class="flex-grow-1">
                    <label for="chkStudentId">Student ID</label>
                    <input type="text" wire:model="student_id" class="form-control" @disabled(!$useStudentId)>
                </div>
            </div>

            <!-- Employee ID -->
            <div class="col-md d-flex align-items-center gap-2">
                <div>
                    <input type="checkbox" wire:model="useEmployeeId" wire:change="$set('useStudentId', false)"
                        id="chkEmployeeId">
                </div>
                <div class="flex-grow-1">
                    <label for="chkEmployeeId">Employee ID</label>
                    <input type="text" wire:model="employee_id" class="form-control" @disabled(!$useEmployeeId)>
                </div>
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

        @php
            // determine "employee" either from userType (if present) or the checkbox flag useEmployeeId
            $isEmployee = (($userType ?? '') === 'employee') || (isset($useEmployeeId) && $useEmployeeId);
        @endphp

        <div class="row mb-3 position-relative {{ $isEmployee ? 'opacity-50 pointer-events-none' : '' }}"
            aria-disabled="{{ $isEmployee ? 'true' : 'false' }}">
            <div class="col-md">
                <label>Department</label>
                <select wire:model="department" wire:change="onDepartmentChanged($event.target.value)"
                    class="form-control" @disabled($isEmployee)>
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" wire:key="dept-{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md">
                <label>Program</label>
                <select wire:model="program" wire:change="onProgramChanged($event.target.value)" class="form-control"
                    @disabled($isEmployee)>
                    <option value="">Select Program</option>
                    @foreach($this->filteredPrograms as $prog)
                        <option value="{{ $prog }}" wire:key="prog-{{ \Illuminate\Support\Str::slug($prog) }}">{{ $prog }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md">
                <label>Year &amp; Section</label>
                <input type="text" wire:model="year_section" class="form-control" maxlength="2" placeholder="1A"
                    required @disabled($isEmployee)>
            </div>
        </div>



        <div class="row mb-3">
            <div class="col-md">
                <label>Address</label>
                <input type="text" wire:model="address" class="form-control" required>
            </div>
            <div class="col-md">
                <label>Contact Number</label>
                <input type="text" wire:model="contact_number" class="form-control" maxlength="11" placeholder="09XXXXXXXXX" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md">
                <label>License Number</label>
                <input type="text" wire:model="license_number" class="form-control" required>
            </div>
            <div class="col-md">
                <label>Expiration Date</label>
                <input type="date" wire:model="expiration_date"
                    class="form-control @error('expiration_date') is-invalid @enderror" onfocus="this.showPicker();"
                    onmousedown="event.preventDefault(); this.showPicker();" required>

                @error('expiration_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>

        <div class="mb-3">
            <label>Profile Picture <small class="text-muted">(optional)</small></label>
            <input type="file" wire:model="profile_picture" class="form-control">
            <div wire:loading wire:target="profile_picture" class="mt-2 text-muted">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                Uploading image…
            </div>
            @if ($compressedProfilePicture)
                <small class="d-block mt-2">
                    Preview:
                    <img src="{{ Storage::url($compressedProfilePicture) }}" width="80"
                        style="border-radius: 50%; object-fit: cover;">
                </small>
            @endif
        </div>


        <!-- Vehicles Section -->
        <h4>Vehicles</h4>

<div class="vehicle-rows">
    @foreach($vehicles as $index => $vehicle)
        <div class="card mb-4" wire:key="vehicle-{{ $vehicle['uid'] }}">
            <div class="card-body">
                {{-- Row 1: Serial | Type | RFID Tag 1 --}}
                <div class="row mb-2 align-items-end">
                    <div class="col-md">
                        <label>Serial Number <span class="text-muted" style="font-size:0.75rem;">(Number only)</span></label>
                        <input type="text" wire:model="vehicles.{{ $index }}.serial_number" class="form-control" placeholder="123" maxlength="4" required>
                    </div>

                    <div class="col-md">
                        <label>Type</label>
                        <select wire:model="vehicles.{{ $index }}.type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="car">Car</option>
                        </select>
                    </div>

                    <div class="col-md">
                        <label>RFID Tag 1</label>
                        <div class="input-group">
                            <input type="text" wire:model="vehicles.{{ $index }}.rfid_tags.0" class="form-control" maxlength="10">
                        </div>
                    </div>
                </div>

                {{-- Row 2: License Plate | Body Type | RFID Tag 2 (dynamic) --}}
                <div class="row mb-2 align-items-end">
                    <div class="col-md">
                        <label>License Plate</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.license_plate" class="form-control" required>
                    </div>

                    <div class="col-md">
                        <label>Body Type/Model</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.body_type_model" class="form-control" required>
                    </div>

                    <div class="col-md">
                        <label>RFID Tag 2</label>

                        @if(isset($vehicles[$index]['rfid_tags'][1]))
                            <div class="input-group">
                                <input type="text"
                                    wire:model="vehicles.{{ $index }}.rfid_tags.1"
                                    class="form-control"
                                    maxlength="10">
                                <button type="button" wire:click="removeRfidTag({{ $index }}, 1)" class="btn btn-outline-danger">
                                    &times;
                                </button>
                            </div>
                        @else
                            <button type="button" wire:click="addRfidTag({{ $index }})" class="btn btn-sm btn-outline-primary">
                                + Add Tag
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Row 3: OR Number | CR Number | RFID Tag 3 (dynamic) --}}
                <div class="row mb-3 align-items-end">
                    <div class="col-md">
                        <label>OR Number</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.or_number" class="form-control" required>
                    </div>

                    <div class="col-md">
                        <label>CR Number</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.cr_number" class="form-control" required>
                    </div>

                    <div class="col-md">
                        <label>RFID Tag 3</label>

                        @if(isset($vehicles[$index]['rfid_tags'][2]))
                            <div class="input-group">
                                <input type="text"
                                    wire:model="vehicles.{{ $index }}.rfid_tags.2"
                                    class="form-control"
                                    maxlength="10">
                                <button type="button" wire:click="removeRfidTag({{ $index }}, 2)" class="btn btn-outline-danger">
                                    &times;
                                </button>
                            </div>
                        @else
                            {{-- If tag 2 exists but tag 3 doesn't, show Add Tag button for tag 3 --}}
                            @if(isset($vehicles[$index]['rfid_tags'][1]))
                                <button type="button" wire:click="addRfidTag({{ $index }})" class="btn btn-sm btn-outline-primary">
                                    + Add Tag
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Remove Vehicle row button --}}
                <div class="row mb-3">
                    <div class="col">
                        @if(count($vehicles) > 1)
                            <button type="button" wire:click="removeVehicleRow({{ $index }})" class="btn btn-danger w-100">
                                Remove Vehicle
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary w-100" disabled>Remove Vehicle</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>




        <div class="d-flex flex-column">
            @if(count($vehicles) >= 3)
                <button type="button" class="btn btn-secondary mb-5" disabled title="Maximum 3 vehicles per user">
                    Add Vehicle
                </button>
            @else
                <button type="button" wire:click="addVehicleRow" class="btn btn-secondary mb-5">
                    Add Vehicle
                </button>
            @endif

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