{{-- resources\views\livewire\admin\guest-tags-modal-component.blade.php --}}
<div>
    {{-- Guest Tags List Modal --}}
    <div wire:ignore.self class="modal fade" id="guestTagsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Guest Tags</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-end mb-3">
                        {{-- This button now opens the register tag modal --}}
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerTagModal">
                            <i class="bi bi-plus-circle me-1"></i> Register New Tag
                        </button>
                    </div>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Tag Name</th>
                                <th scope="col">RFID Tag</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($guestTags as $tag)
                                <tr>
                                    <td>{{ $tag->name }}</td>
                                    <td>{{ $tag->rfid_tag }}</td>
                                    <td>
                                        @if($tag->status == 'available')
                                            <span class="badge bg-success">Available</span>
                                        @else
                                            <span class="badge bg-warning">In Use</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" wire:click="editTag({{ $tag->id }})" 
        class="btn btn-sm btn-info" 
        data-bs-toggle="modal" data-bs-target="#registerTagModal">
    Edit
</button>
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No guest tags registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
