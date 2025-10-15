{{-- resources\views\livewire\admin\guest-list-modal-component.blade.php --}}
<div>
    <div wire:ignore.self class="modal fade" id="guestListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Guest List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Plate Number</th>
                                <th scope="col">Time In</th>
                                <th scope="col">Current Location</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($guests as $guest)
                                <tr>
                                    <td>{{ $guest['name'] }}</td>
                                    <td>{{ $guest['plate_number'] }}</td>
                                    <td>{{ $guest['time_in'] }}</td>
                                    <td>{{ $guest['location'] }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info">Edit</button>
                                        <button class="btn btn-sm btn-danger">Clear Info</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No guests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registerTagModal">
                            Register a Tag
                        </button>
                    <button type="button" class="btn btn-primary">Create Guest</button>             
                </div>
            </div>
        </div>
    </div>
</div>
