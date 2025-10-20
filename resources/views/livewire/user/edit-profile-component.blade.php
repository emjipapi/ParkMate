{{-- resources\views\livewire\admin\user-form-create.blade.php --}}
<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">
    {{-- DEBUG: temporary diagnostics — remove when done --}}
    <div class="mb-3 p-2 border rounded bg-light w-100 text-monospace" style="font-size:0.85rem;">
        <strong>DEBUG STATE</strong>
@php
    $payload = [
        'id'     => $id ?? null,
        'student_id'     => $student_id ?? null,
        'employee_id'    => $employee_id ?? null,
        'firstname'      => $firstname ?? null,
        'lastname'       => $lastname ?? null,
        'address'        => $address ?? null,
        'contact_number' => $contact_number ?? null,
        'license_number' => $license_number ?? null,
        'expiration_date'=> $expiration_date ?? null,
        'vehicles'       => $vehicles ?? [],
        'rawUser'        => $rawUser ?? null,
        'rawVehicles'    => $rawVehicles ?? [],
    ];
@endphp

<pre style="white-space:pre-wrap;">{!! json_encode($payload, JSON_PRETTY_PRINT) !!}</pre>

    </div>
    <form wire:submit.prevent="save" enctype="multipart/form-data">
        @csrf

        <!-- User Info Fields -->
        <div class="row mb-3">
            <div class="col-md">
                @php
                    // show label based on which ID exists; default to Student ID label
                    $idLabel = 'Student ID';
                    if (!empty($employee_id) && empty($student_id)) {
                        $idLabel = 'Employee ID';
                    }
                @endphp
                <label>{{ $idLabel }}</label>
                <input type="text" value="{{ $student_id ?: $employee_id }}" class="form-control" disabled>
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
                <input type="text" wire:model="firstname" class="form-control" required disabled>
            </div>
            <div class="col-md">
                <label>Middle Name</label>
                <input type="text" wire:model="middlename" class="form-control" disabled>
            </div>
            <div class="col-md">
                <label>Last Name</label>
                <input type="text" wire:model="lastname" class="form-control" required disabled>
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
                    class="form-control" @disabled($isEmployee) disabled>
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" wire:key="dept-{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md">
                <label>Program</label>
                <select wire:model="program" wire:change="onProgramChanged($event.target.value)" class="form-control"
                    @disabled($isEmployee) disabled>
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
                <input type="text" wire:model="address" class="form-control" required disabled>
            </div>
            <div class="col-md">
                <label>Contact Number</label>
                <input type="text" wire:model="contact_number" class="form-control" required disabled>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md">
                <label>License Number</label>
                <input type="text" wire:model="license_number" class="form-control" required disabled>
            </div>
            <div class="col-md">
                <label>Expiration Date</label>
                <input type="date" wire:model="expiration_date"
                    class="form-control @error('expiration_date') is-invalid @enderror" onfocus="this.showPicker();"
                    onmousedown="event.preventDefault(); this.showPicker();" required disabled>

                @error('expiration_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>

        <div class="mb-3">
            <label>Profile Picture </label>
            <input type="file" wire:model="profile_picture" class="form-control">
            @if ($profile_picture && !$compressedProfilePicture)
                <div class="mt-2 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    Uploading image…
                </div>
            @endif
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
                @php
                    // Ensure we have an array of tags regardless of how it's stored
                    $tags = $vehicle['rfid_tags'] ?? [];
                    if (!is_array($tags)) {
                        $decoded = json_decode($tags, true);
                        $tags = is_array($decoded) ? $decoded : (array) $tags;
                    }
                    // reindex so keys are 0..n — prevents gaps if JSON had non-sequential keys
                    $tags = array_values($tags);
                @endphp
                {{-- Row 1: Serial | Type | RFID Tag 1 --}}
                <div class="row mb-2 align-items-end">
                    <div class="col-md">
                        <label>Serial Number <span class="text-muted" style="font-size:0.75rem;">(Number only)</span></label>
                        <input type="text" wire:model="vehicles.{{ $index }}.serial_number" class="form-control" placeholder="123" required disabled>
                    </div>

                    <div class="col-md">
                        <label>Type</label>
                        <select wire:model="vehicles.{{ $index }}.type" class="form-control" required disabled>
                            <option value="">Select Type</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="car">Car</option>
                        </select>
                    </div>

                    @if(isset($tags[0]) && trim((string)$tags[0]) !== '')
                        <div class="col-md">
                            <label>RFID Tag 1</label>
                            <div class="input-group">
                                <input type="text" value="{{ $tags[0] ?? '' }}" class="form-control" maxlength="10" disabled>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Row 2: License Plate | Body Type | RFID Tag 2 (dynamic) --}}
                <div class="row mb-2 align-items-end">
                    <div class="col-md">
                        <label>License Plate</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.license_plate" class="form-control" required disabled>
                    </div>

                    <div class="col-md">
                        <label>Body Type/Model</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.body_type_model" class="form-control" required disabled>
                    </div>

                    @if(isset($tags[1]) && trim((string)$tags[1]) !== '')
                        <div class="col-md">
                            <label>RFID Tag 2</label>
                            <div class="input-group">
                                <input type="text" value="{{ $tags[1] ?? '' }}" class="form-control" maxlength="10" disabled>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Row 3: OR Number | CR Number | RFID Tag 3 (dynamic) --}}
                <div class="row mb-3 align-items-end">
                    <div class="col-md">
                        <label>OR Number</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.or_number" class="form-control" required disabled>
                    </div>

                    <div class="col-md">
                        <label>CR Number</label>
                        <input type="text" wire:model="vehicles.{{ $index }}.cr_number" class="form-control" required disabled>
                    </div>

                    @if(isset($tags[2]) && trim((string)$tags[2]) !== '')
                        <div class="col-md">
                            <label>RFID Tag 3</label>
                            <div class="input-group">
                                <input type="text" value="{{ $tags[2] ?? '' }}" class="form-control" maxlength="10" disabled>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Remove Vehicle row button --}}
                {{-- vehicle actions removed for read-only profile --}}
            </div>
        </div>
    @endforeach
</div>




        <div class="d-flex flex-column">
            <button type="button" class="btn btn-secondary mb-5" disabled>
                Add Vehicle
            </button>

            <button type="submit" class="btn btn-primary" disabled>
                Save Profile
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