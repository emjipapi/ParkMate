{{-- resources\views\livewire\admin\vehicles-table.blade.php --}}
<div>
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-3 mb-3">
        <input type="text" class="form-control" placeholder="Search vehicles..." wire:model.live.debounce.300ms="search"
            style="max-width: 400px" />

        <div class="d-flex align-items-center gap-1">
            <span>Show</span>
            <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                @foreach($perPageOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            <span>entries</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Serial Number</th>
                    <th>Owner</th>
                    <th>RFID Tag</th>
                    <th>Type</th>
                    <th>Body Type / Model</th>
                    <th>OR Number</th>
                    <th>CR Number</th>
                    <th>License Plate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                <tr>
                    <td>{{ $vehicle->id }}</td>
                    <td>{{ $vehicle->serial_number }}</td>
                    <td>
                        @if ($vehicle->user)
                        {{ $vehicle->user->firstname }} {{ $vehicle->user->lastname }}
                        <br>
                        <small class="text-muted">ID: {{ $vehicle->user->student_id ?? $vehicle->user->employee_id
                            }}</small>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>

                    <td>
                        @php
                        // $vehicle->rfid_tag is already casted to an array by Eloquent
                        $tags = $vehicle->rfid_tag;

                        // If it's not an array for some reason (e.g., legacy data), wrap it
                        if (!is_array($tags)) {
                        $tags = [$tags];
                        }
                        @endphp

                        {{ implode(', ', array_filter($tags)) }}

                    </td>
                    <td>{{ $vehicle->type }}</td>
                    <td>{{ $vehicle->body_type_model }}</td>
                    <td>{{ $vehicle->or_number }}</td>
                    <td>{{ $vehicle->cr_number }}</td>
                    <td>{{ $vehicle->license_plate }}</td>
                    <td>
                        <!-- Edit Icon -->

                        @canaccess("edit_user")
                        <a href="{{ route('users.edit', $vehicle->user_id) }}#vehicle-{{ $vehicle->id }}"
                            class="text-primary text-decoration-none">
                            <i class="bi bi-pencil-square text-secondary"></i>
                        </a>
                        @else
                        <a href="javascript:void(0)" class="text-muted text-decoration-none" data-bs-toggle="tooltip"
                            title="You don’t have permission to edit users.">
                            <i class="bi bi-pencil-square text-secondary"></i>
                        </a>
                        @endcanaccess


                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">No vehicles found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $vehicles->links() }}
</div>