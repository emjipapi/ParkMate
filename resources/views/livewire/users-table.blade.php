<div>
    <input type="text" wire:model.debounce.300ms="search" placeholder="Search users..." class="form-control mb-3">


    <table class="table table-striped custom-table">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Firstname</th>
                <th>Middlename</th>
                <th>Lastname</th>
                <th>Program</th>
                <th>Department</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
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
            @endforeach
        </tbody>
    </table>

    {{ $users->links() }}
</div>
