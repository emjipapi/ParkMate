<div class="flex-grow-1 d-flex justify-content-center align-items-center square-box">
    <form wire:submit.prevent="save">
        @csrf

        <div class="mb-3"><label>Username</label>
            <input type="text" wire:model="username" class="form-control" required>
        </div>

        <div class="row mb-3">
            <div class="col-md"><label>First Name</label>
                <input type="text" wire:model="firstname" class="form-control" required>
            </div>
            <div class="col-md"><label>Middle Name</label>
                <input type="text" wire:model="middlename" class="form-control">
            </div>
            <div class="col-md"><label>Last Name</label>
                <input type="text" wire:model="lastname" class="form-control" required>
            </div>
        </div>

        <div class="mb-3"><label>Password</label>
            <input type="password" wire:model="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Add Admin</button>

        @if (session()->has('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mt-3">
                @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif
    </form>
</div>
