<div>
    <input
        type="text"
        class="form-control mb-3"
        placeholder="Search users..."
        wire:model.live.debounce.300ms="search"
        style="width: 300px"
    />

<div class="d-flex justify-content gap-2 mb-3">
    <select class="form-select form-select-sm w-auto" wire:model.live="filterDepartment">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
        @endforeach
    </select>

    <select class="form-select form-select-sm w-auto" wire:model.live="filterProgram">
        <option value="">All Programs</option>
        @foreach($programs as $prog)
            <option value="{{ $prog }}">{{ $prog }}</option>
        @endforeach
    </select>
</div>

    <table class="table table-striped custom-table">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Student/Employee ID</th>
                <th>Firstname</th>
                <th>Middlename</th>
                <th>Lastname</th>
                <th>Program</th>
                <th>Department</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>
                    {{ $user->student_id ?? $user->employee_id }}
                </td>
                    <td>{{ $user->firstname }}</td>
                    <td>{{ $user->middlename }}</td>
                    <td>{{ $user->lastname }}</td>
                    <td>{{ $user->program }}</td>
                    <td>{{ $user->department }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary">Edit</button>
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
        <div class="mb-2 small text-muted">
        Showing {{ $users->count() }} of {{ $users->total() }} users
    </div>

    {{ $users->links() }}
</div>
