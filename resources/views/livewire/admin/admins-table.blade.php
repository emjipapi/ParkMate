{{-- resources\views\livewire\admin\admins-table.blade.php --}}
<div x-data="{
        check2: false,
        selectedIds: [],
        alertMessage: '',
        showAlert: false,

        init() {
            this.check2 = localStorage.getItem('adminTable_multiselect') === 'true';
            const stored = localStorage.getItem('adminTable_selectedIds');
            this.selectedIds = stored ? JSON.parse(stored) : [];
        },

        toggleMaster() {
            this.check2 = !this.check2;
            localStorage.setItem('adminTable_multiselect', this.check2);
            if (!this.check2) {
                this.selectedIds = [];
                localStorage.removeItem('adminTable_selectedIds');
            }
        },

        triggerDelete() {
            if (this.selectedIds.length === 0) {
                this.alertMessage = '⚠️ No admins selected to delete.';
                this.showAlert = true;
                setTimeout(() => this.showAlert = false, 3000);
                return;
            }
            $wire.deleteSelected(this.selectedIds);
            this.selectedIds = [];
            localStorage.removeItem('adminTable_selectedIds');
        }
    }">


    <!-- Alert -->
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
                <span class="me-3" x-text="`${selectedIds.length} admin(s) selected across all pages`"></span>
                <button type="button" class="btn btn-sm btn-outline-primary me-2"
                    @click="selectedIds = []; localStorage.removeItem('adminTable_selectedIds')">
                    Clear All
                </button>
            </div>
        </div>
    </template>
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-3 mb-3">
        <!-- Search -->
        <input type="text" class="form-control mb-3" placeholder="Search admins..."
            wire:model.live.debounce.300ms="search" style="max-width: 400px" />
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
    <!-- Toolbar -->
    <div class="d-flex justify-content-end mb-3 gap-3">
        <i :class="check2 ? 'bi bi-check2-all text-primary' : 'bi bi-check2-all'"
            style="transform: scale(1.2); cursor: pointer;" @click="toggleMaster()"
            title="Toggle multi-select mode"></i>

        <i class="bi bi-trash-fill" :class="selectedIds.length > 0 ? 'text-danger' : 'text-muted'"
            style="transform: scale(1.2); cursor: pointer;" @click="triggerDelete()" title="Delete selected"></i>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped custom-table" x-bind:class="{ 'table-hover': check2 }">
            <thead>
                <tr>
                    <th x-show="check2" style="width: 40px;"></th>
                    <th>Admin ID</th>
                    <th>Firstname</th>
                    <th>Middlename</th>
                    <th>Lastname</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admins as $admin)
                <tr x-bind:class="{ 'table-active': check2 && selectedIds.includes({{ $admin->id }}) }">
                    <td x-show="check2">
                        <input type="checkbox" class="form-check-input" value="{{ $admin->id }}"
                            :checked="selectedIds.includes({{ $admin->id }})" @change="
                                       if ($event.target.checked) {
                                           if (!selectedIds.includes({{ $admin->id }})) selectedIds.push({{ $admin->id }});
                                       } else {
                                           selectedIds = selectedIds.filter(id => id !== {{ $admin->id }});
                                       }
                                       localStorage.setItem('adminTable_selectedIds', JSON.stringify(selectedIds));
                                   ">
                    </td>
                    <td>{{ $admin->admin_id }}</td>
                    <td>{{ $admin->firstname }}</td>
                    <td>{{ $admin->middlename }}</td>
                    <td>{{ $admin->lastname }}</td>
                    <td>
                        <!-- Edit Icon -->
@canaccess("edit_admin")
    <a href="{{ route('admins.edit', $admin->admin_id) }}" 
       class="text-primary me-2 text-info text-decoration-none">
        <i class="bi bi-pencil-square text-secondary"></i>
    </a>
@else
    <a href="javascript:void(0)" 
       class="text-muted me-2 text-decoration-none" 
       data-bs-toggle="tooltip" 
       title="You don’t have permission to edit admins.">
        <i class="bi bi-pencil-square text-secondary"></i>
    </a>
@endcanaccess

                    </td>
                </tr>
                @empty
                <tr>
                    <td :colspan="check2 ? 7 : 6" class="text-center">No admins found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $admins->links() }}
</div>