<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">

    <form wire:submit.prevent="save" enctype="multipart/form-data">
        @csrf

        <div class="row mb-3">
            <div class="col">
                <label>Student ID</label>
                <input type="text" wire:model="student_id" class="form-control">
            </div>
            <div class="col">
                <label>Employee ID</label>
                <input type="text" wire:model="employee_id" class="form-control">
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

        <div class="mb-3">
            <label>RFID Tag</label>
            <input type="text" wire:model="rfid_tag" class="form-control" required>
        </div>

        <div class="row mb-3">
            <div class="col">
                <label>First Name</label>
                <input type="text" wire:model="firstname" class="form-control" required>
            </div>
            <div class="col">
                <label>Middle Name</label>
                <input type="text" wire:model="middlename" class="form-control">
            </div>
            <div class="col">
                <label>Last Name</label>
                <input type="text" wire:model="lastname" class="form-control" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col">
                <label>Department</label>
                <select wire:model="department" class="form-control" required>
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            

            <div class="col">
                <label>Program</label>
                <select wire:model="program" class="form-control" required>
                    <option value="">Select Program</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog }}">{{ $prog }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label>License Number <small class="text-muted">(optional)</small></label>
            <input type="text" wire:model="license_number" class="form-control">
        </div>

        <div class="mb-3">
            <label>Profile Picture <small class="text-muted">(optional)</small></label>
            <input type="file" wire:model="profile_picture" class="form-control">
        </div>

        <button type="submit" class="btn-add-slot">Add User</button>

        {{-- Success message --}}
        @if (session()->has('success'))
            <div x-data="{ show: true }" 
                 x-init="setTimeout(() => show = false, 3000)" 
                 x-show="show"
                 class="alert alert-success mt-3 p-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="alert alert-danger mt-3 p-3 rounded">
                
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                
            </div>
        @endif

    </form>

</div>
