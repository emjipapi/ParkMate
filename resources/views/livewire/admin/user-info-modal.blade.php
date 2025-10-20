<div wire:ignore.self>
    <div class="modal fade" id="userInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <!-- same as your original (no modal-lg) -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($user) User Details: {{ $user->firstname }} {{ $user->lastname }} @else User Details @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Loading state (keeps the same modal size while loading) --}}
                    @if($loading || ! $user)
                    <div class="text-center py-5">
                        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                        <div class="mt-2 text-muted">Loading user details…</div>
                    </div>
                    @else
                    <!-- Profile Picture -->
                    <div class="text-center mb-4">
                        @if($user->profile_picture)
                        <img src="{{ route('profile.picture', $user->profile_picture) }}" alt="Profile Picture"
                            class="rounded-circle"
                            style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #dee2e6;">
                        @else
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white"
                            style="width: 150px; height: 150px; font-size: 48px; font-weight: bold;">
                            {{ strtoupper(substr($user->firstname ?? '', 0, 1) . substr($user->lastname ?? '', 0, 1)) }}
                        </div>
                        @endif
                    </div>





                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>ID:</strong></div>
                        <div class="col-8">{{ $user->id ?? '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>User ID:</strong></div>
                        <div class="col-8">{{ $user->employee_id ?: $user->student_id ?: '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>User Type:</strong></div>
                        <div class="col-8">
                            @if(!empty($user->student_id) && $user->student_id !== '0')
                            <span class="badge bg-primary">Student</span>
                            @elseif(!empty($user->employee_id) && $user->employee_id !== '0')
                            <span class="badge bg-success">Employee</span>
                            @else
                            <span class="badge bg-secondary">Unknown</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Firstname:</strong></div>
                        <div class="col-8">{{ $user->firstname ?: '—' }}</div>
                    </div>



                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Middlename:</strong></div>
                        <div class="col-8">{{ $user->middlename ?: '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Lastname:</strong></div>
                        <div class="col-8">{{ $user->lastname ?: '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Program:</strong></div>
                        <div class="col-8">{{ $user->program ?: '—' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-4 text-muted"><strong>Department:</strong></div>
                        <div class="col-8">{{ $user->department ?: '—' }}</div>
                    </div>




                    <!-- The rest of your details (keeps original layout) -->
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Year & Section:</strong></div>
                        <div class="col-md-8">{{ $user->year_section }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Address:</strong></div>
                        <div class="col-md-8">{{ $user->address }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Contact Number:</strong></div>
                        <div class="col-md-8">{{ $user->contact_number }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>License Number:</strong></div>
                        <div class="col-md-8">{{ $user->license_number }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Expiration Date:</strong></div>
                        <div class="col-md-8">{{ $user->expiration_date }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Created At:</strong></div>
                        <div class="col-md-8">{{ $user->created_at?->format('F d, Y h:i A') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Updated At:</strong></div>
                        <div class="col-md-8">{{ $user->updated_at?->format('F d, Y h:i A') }}</div>
                    </div>

                    <!-- Vehicles Section (matches your old markup) -->
                    <hr>
                    <h6 class="mb-3">Vehicles</h6>
                    <div class="vehicle-rows">
                        @forelse($user->vehicles as $vehicle)
                        <div class="card mb-3">
                            <div class="card-body p-3">
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>Serial Number:</strong></div>
                                    <div class="col-md-8">{{ ucfirst($vehicle->serial_number) }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>Type:</strong></div>
                                    <div class="col-md-8">{{ ucfirst($vehicle->type) }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>Model:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->body_type_model }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>Plate:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->license_plate }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>RFID Tag(s):</strong></div>
                                    <div class="col-md-8">
                                        @php
                                        // Decode JSON if necessary
                                        $tags = is_array($vehicle->rfid_tag) ? $vehicle->rfid_tag :
                                        json_decode($vehicle->rfid_tag, true);
                                        @endphp

                                        @if(!empty($tags) && is_array($tags))
                                        @foreach($tags as $tag)
                                        <span class="badge bg-info text-dark me-1">{{ $tag }}</span>
                                        @endforeach
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>OR No.:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->or_number }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>CR No.:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->cr_number }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-md-4"><strong>Created At:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->created_at?->format('F d, Y h:i A') }}</div>
                                </div>
                                <div class="row mb-0">
                                    <div class="col-md-4"><strong>Updated At:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->updated_at?->format('F d, Y h:i A') }}</div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted">No vehicles linked to this user.</p>
                        @endforelse
                    </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                    {{-- only show Edit when user is loaded --}}
                    @if($user)
                    @canaccess("edit_user")
                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary" target="_blank">
                        Edit
                    </a>
                    @else
                    <a href="javascript:void(0)" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true"
                        data-bs-toggle="tooltip" title="You don’t have permission to edit users.">
                        Edit
                    </a>
                    @endcanaccess
                    @else
                    <a href="javascript:void(0)" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true"
                        data-bs-toggle="tooltip" title="No user found.">
                        Edit
                    </a>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>