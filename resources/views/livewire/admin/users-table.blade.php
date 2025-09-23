<div x-data="{
        check2: false,
        selectedIds: [],
        alertMessage: '',
        showAlert: false,

        init() {
            this.check2 = localStorage.getItem('userTable_multiselect') === 'true';
            const stored = localStorage.getItem('userTable_selectedIds');
            this.selectedIds = stored ? JSON.parse(stored) : [];
            console.log('[INIT] check2:', this.check2, 'selectedIds:', this.selectedIds);
        },

        toggleMaster() {
            this.check2 = !this.check2;
            localStorage.setItem('userTable_multiselect', this.check2);
            if (!this.check2) {
                this.selectedIds = [];
                localStorage.removeItem('userTable_selectedIds');
            }
            console.log('[TOGGLE MASTER] check2:', this.check2);
        },

        triggerDelete() {
    if (this.selectedIds.length === 0) {
        this.alertMessage = '⚠️ No items selected to delete.';
        this.showAlert = true;
        setTimeout(() => this.showAlert = false, 3000);
        return;
    }

    // Call Livewire backend
    $wire.deleteSelected(this.selectedIds);

    // Clear selection
    this.selectedIds = [];
    localStorage.removeItem('userTable_selectedIds');
}

    }">

    <!-- Alert at the top -->
    <template x-if="showAlert">
        <div class="alert alert-warning text-center position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
            style="z-index: 2000;">
            <span x-text="alertMessage"></span>
        </div>
    </template>

    <!-- Clear selection button -->
    <template x-if="selectedIds.length > 0">
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
            <div class="alert alert-primary d-flex align-items-center shadow">
                <span class="me-3" x-text="`${selectedIds.length} user(s) selected across all pages`"></span>
                <button type="button" class="btn btn-sm btn-outline-primary me-2" @click="
                            console.log('[CLEAR ALL] Before:', selectedIds);
                            selectedIds = []; 
                            localStorage.removeItem('userTable_selectedIds');
                            console.log('[CLEAR ALL] After:', selectedIds);
                        ">
                    Clear All
                </button>
            </div>
        </div>
    </template>

    <input type="text" class="form-control mb-3" placeholder="Search users..." wire:model.live.debounce.300ms="search"
        style="max-width: 400px" />

    <div
        class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
        <div class="d-flex gap-2 flex-wrap">
    <select class="form-select form-select-sm w-auto"
            wire:model.live="filterDepartment"
            wire:change="onDepartmentChanged($event.target.value)">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
            <option value="{{ $dept }}" wire:key="dept-{{ \Illuminate\Support\Str::slug($dept) }}">
                {{ $dept }}
            </option>
        @endforeach
    </select>

    <select class="form-select form-select-sm w-auto"
            wire:model.live="filterProgram"
            wire:change="onProgramChanged($event.target.value)">
        <option value="">All Programs</option>
        @foreach($programs as $prog)
            <option value="{{ $prog }}" wire:key="prog-{{ \Illuminate\Support\Str::slug($prog) }}">
                {{ $prog }}
            </option>
        @endforeach
    </select>
        </div>

        <!-- Toolbar -->
        <div class="d-flex gap-3 justify-content-sm-end">
            <i :class="check2 ? 'bi bi-check2-all text-primary' : 'bi bi-check2-all'"
                style="transform: scale(1.2); cursor: pointer;" @click="toggleMaster()"
                title="Toggle multi-select mode">
            </i>

            <i class="bi bi-trash-fill" :class="selectedIds.length > 0 ? 'text-danger' : 'text-muted'"
                style="transform: scale(1.2); cursor: pointer;" @click="triggerDelete()" title="Delete selected">
            </i>
        </div>
    </div>


    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped custom-table" x-bind:class="{ 'table-hover': check2 }">
            <thead>
                <tr>
                    <th x-show="check2" style="width: 40px;"></th>
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
                    <tr x-bind:class="{ 'table-active': check2 && selectedIds.includes({{ $user->id }}) }">
                        <td x-show="check2">
                            <input type="checkbox" class="form-check-input" value="{{ $user->id }}"
                                :checked="selectedIds.includes({{ $user->id }})" @change="
                                                   if ($event.target.checked) {
                                                       if (!selectedIds.includes({{ $user->id }})) {
                                                           selectedIds.push({{ $user->id }});
                                                           console.log('[CHECKED] Added ID {{ $user->id }}, selectedIds:', selectedIds);
                                                       }
                                                   } else {
                                                       selectedIds = selectedIds.filter(id => id !== {{ $user->id }});
                                                       console.log('[UNCHECKED] Removed ID {{ $user->id }}, selectedIds:', selectedIds);
                                                   }
                                                   localStorage.setItem('userTable_selectedIds', JSON.stringify(selectedIds));
                                               ">
                        </td>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->student_id ?? $user->employee_id }}</td>
                        <td>{{ $user->firstname }}</td>
                        <td>{{ $user->middlename }}</td>
                        <td>{{ $user->lastname }}</td>
                        <td>{{ $user->program }}</td>
                        <td>{{ $user->department }}</td>
                        <td>
                            <!-- Edit Icon -->
                            <a href="{{ route('users.edit', $user->id) }}"
                                class="text-primary me-2 text-info text-decoration-none">
                                <i class="bi bi-pencil-square text-secondary"></i>
                            </a>


                            <!-- More Info Icon -->
                            <a href="#" class="text-info text-decoration-none" data-bs-toggle="modal"
                                data-bs-target="#userInfoModal{{ $user->id }}">
                                <i class="bi bi-info-circle"></i>
                            </a>

                            <!-- Modal -->
<div class="modal fade" id="userInfoModal{{ $user->id }}" tabindex="-1"
    aria-labelledby="userInfoLabel{{ $user->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userInfoLabel{{ $user->id }}">
                    User Details: {{ $user->firstname }} {{ $user->lastname }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- User Details -->
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

                <!-- Vehicles Section -->
                <hr>
                <h6 class="mb-3">Vehicles</h6>
                <div class="vehicle-rows">
                    @forelse($user->vehicles as $vehicle)
                        <div class="card mb-3">
                            <div class="card-body p-3">
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
                                    <div class="col-md-4"><strong>RFID:</strong></div>
                                    <div class="col-md-8">{{ $vehicle->rfid_tag }}</div>
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
            </div>
        </div>
    </div>
</div>


                        </td>

                    </tr>
                @empty
                    <tr>
                        <td :colspan="check2 ? 9 : 8" class="text-center">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>

<script>
    document.addEventListener('livewire:updated', function () {
        Alpine.nextTick(() => {
            const component = Alpine.$data(document.querySelector('[x-data]'));
            if (component) {
                component.check2 = localStorage.getItem('userTable_multiselect') === 'true';
                const stored = localStorage.getItem('userTable_selectedIds');
                component.selectedIds = stored ? JSON.parse(stored) : [];
                console.log('[LIVEWIRE UPDATED] check2:', component.check2, 'selectedIds:', component.selectedIds);
            }
        });
    });

    document.addEventListener('livewire:navigated', function () {
        Alpine.nextTick(() => {
            const component = Alpine.$data(document.querySelector('[x-data]'));
            if (component) {
                component.check2 = localStorage.getItem('userTable_multiselect') === 'true';
                const stored = localStorage.getItem('userTable_selectedIds');
                component.selectedIds = stored ? JSON.parse(stored) : [];
                console.log('[PAGE NAVIGATED] check2:', component.check2, 'selectedIds:', component.selectedIds);
            }
        });
    });

    window.addEventListener('beforeunload', () => {
        localStorage.removeItem('userTable_selectedIds');
        localStorage.removeItem('userTable_multiselect');
    });

</script>