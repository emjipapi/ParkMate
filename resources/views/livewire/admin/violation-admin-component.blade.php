<div class="container mt-4">

        {{-- Search Bar --}}
<div class="mb-3 position-relative" style="max-width: 300px;">
    <input type="text" 
           class="form-control" 
           placeholder="User_ID / License_Plate Finder..." 
           wire:model.live.debounce.300ms="searchTerm">

    {{-- Dynamic Dropdown --}}
    @if(!empty($searchResults))
        <ul class="list-group position-absolute" 
            style="max-height: 200px; overflow-y: auto; z-index: 50; width: 100%;">
            @foreach($searchResults as $result)
                <li class="list-group-item list-group-item-action" 
                    wire:click="selectResult({{ $result->id }})">
                    {{ $result->user_id }} — {{ $result->license_plate }}
                </li>
            @endforeach
        </ul>
    @endif
</div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs border-b mb-4 flex space-x-2">
        <li>
            <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black {{ $activeTab === 'pending' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                wire:click="setActiveTab('pending')">
                Pending Reports
            </a>
        </li>
        <li>
            <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black {{ $activeTab === 'approved' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                wire:click="setActiveTab('approved')">
                Approved Reports
            </a>
        </li>
        <li>
            <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black {{ $activeTab === 'resolved' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                wire:click="setActiveTab('resolved')">
                Resolved Reports
            </a>
        </li>
    </ul>


    {{-- Tab Content --}}
    <div>
        {{-- Pending Reports --}}
        @if ($activeTab === 'pending')
            <table class="table table-striped custom-table">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Reporter ID & Name</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Area</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">License Plate</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Violator</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Evidence</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($violations as $violation)
                        <tr class="hover:bg-gray-50" x-data="violationRow_{{ $violation->id }}()" x-init="init()">

                            {{-- Reporter ID & Name --}}
                            <td class="px-4 py-2 text-sm text-gray-800">
                                <div class="font-medium">#{{ $violation->reporter->id ?? 'N/A' }}</div>
                                <div class="text-gray-600">{{ $violation->reporter->firstname ?? '' }}
                                    {{ $violation->reporter->lastname ?? '' }}
                                </div>
                            </td>

                            {{-- Area --}}
                            <td class="px-4 py-2 text-sm text-gray-800">
                                {{ $violation->area->name ?? 'N/A' }}
                            </td>

                            {{-- License Plate Input --}}
                            <td class="px-2 py-2 text-sm">
                                <div class="d-flex flex-column">
                                    <input type="text" x-model="licensePlate" @blur="findViolatorByPlate()"
                                        @keydown.enter.prevent="findViolatorByPlate()" placeholder="Enter license plate"
                                        :disabled="'{{ $violation->status }}' === 'approved'"
                                        class="form-control form-control-sm" style="max-width: 150px;">

                                    <div class="text-xs mt-1">
                                        <span x-show="plateStatus === 'found'" class="text-success font-weight-medium">
                                            ✓ <span x-text="foundOwnerName"></span>
                                        </span>
                                        <span x-show="plateStatus === 'not_found'" class="text-danger">
                                            ✗ Plate not found
                                        </span>
                                        <span x-show="plateStatus === 'loading'" class="text-primary">
                                            Searching...
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Violator Input --}}
                            <td class="px-2 py-2 text-sm">
                                <div class="d-flex flex-column">
                                    <input type="text" x-model="violatorId" @blur="findPlateByViolator()"
                                        @keydown.enter.prevent="findPlateByViolator()" placeholder="Enter User ID"
                                        :disabled="'{{ $violation->status }}' === 'approved'"
                                        class="form-control form-control-sm" style="max-width: 150px;">

                                    <div class="text-xs mt-1">
                                        <span x-show="violatorStatus === 'found'" class="text-success font-weight-medium">
                                            ✓ <span x-text="foundViolatorName"></span>
                                        </span>
                                        <span x-show="violatorStatus === 'not_found'" class="text-danger">
                                            ✗ User not found
                                        </span>
                                        <span x-show="violatorStatus === 'loading'" class="text-primary">
                                            Searching...
                                        </span>
                                    </div>
                                </div>
                            </td>


                            {{-- Description --}}
                            <td class="px-4 py-2 text-sm text-gray-800">
                                <div class="max-w-xs">
                                    <div class="truncate" title="{{ $violation->description }}">
                                        {{ Str::limit($violation->description, 50) }}
                                    </div>
                                </div>
                            </td>

                            {{-- Evidence --}}
                            <td class="px-4 py-2 text-sm">
                                @if ($violation->evidence)
                                    <a href="{{ asset('storage/' . $violation->evidence) }}" target="_blank"
                                        class="text-blue-600 hover:text-blue-800 underline text-xs">
                                        View Evidence
                                    </a>
                                @else
                                    <span class="text-gray-500 text-xs">No evidence</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2 text-sm">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'resolved' => 'bg-blue-100 text-blue-800',
                                    ];
                                @endphp
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$violation->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($violation->status) }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-2 align-middle">
                                <div class="d-flex flex-column gap-1">
                                    @if ($violation->status === 'approved')
                                        {{-- Locked Approved --}}
                                        <span class="badge bg-success d-inline-block w-100 text-center py-2">
                                            ✓ Approved
                                        </span>
                                    @elseif ($violation->status === 'rejected')
                                        {{-- Rejected but can still be approved later --}}
                                        <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                                            class="btn btn-sm btn-success w-100">
                                            Approve
                                        </button>
                                        <span class="badge bg-danger d-inline-block w-100 text-center py-2">
                                            ✓ Rejected
                                        </span>
                                    @else
                                        {{-- Pending --}}
                                        <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                                            class="btn btn-sm btn-success w-100">
                                            Approve
                                        </button>
                                        <button wire:click="updateStatus({{ $violation->id }}, 'rejected')"
                                            class="btn btn-sm btn-danger w-100">
                                            Reject
                                        </button>
                                    @endif
                                </div>
                            </td>


                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Empty state --}}
            @if($violations->isEmpty())
                <div class="text-center py-8">
                    <div class="text-gray-500 text-lg mb-2">No pending violations found</div>
                    <div class="text-gray-400 text-sm">Violations will appear here once reported</div>
                </div>
            @endif

            <script>
                // Generate violation row functions for all violations
                @foreach($violations as $violation)
                    function violationRow_{{ $violation->id }}() {
                        return {
                            // Data properties
                            licensePlate: '{{ $violation->license_plate ?? '' }}',
                            violatorId: '{{ $violation->violator_id ?? '' }}',
                            originalLicensePlate: '{{ $violation->license_plate ?? '' }}',
                            originalViolatorId: '{{ $violation->violator_id ?? '' }}',

                            // Status tracking
                            plateStatus: null,
                            violatorStatus: null,

                            // Found data
                            foundOwnerName: '',
                            foundViolatorName: '',
                            foundPlates: [],

                            // Change tracking
                            hasChanges: false,
                            isAutoFilling: false,

                            init() {
                                @if($violation->violator_name && $violation->violator_name !== 'Unknown')
                                    this.foundOwnerName = '{{ $violation->violator_name }}';
                                    this.foundViolatorName = '{{ $violation->violator_name }}';
                                    this.plateStatus = 'found';
                                    this.violatorStatus = 'found';
                                @endif

                                this.$watch('licensePlate', () => {
                                    if (!this.isAutoFilling) {
                                        this.checkForChanges();
                                    }
                                });
                                this.$watch('violatorId', () => {
                                    if (!this.isAutoFilling) {
                                        this.checkForChanges();
                                    }
                                });
                            },

                            checkForChanges() {
                                this.hasChanges = (this.licensePlate !== this.originalLicensePlate) ||
                                    (this.violatorId !== this.originalViolatorId);
                            },

                            async findViolatorByPlate() {
                                if (!this.licensePlate || this.licensePlate.trim() === '' || this.isAutoFilling) {
                                    if (!this.licensePlate || this.licensePlate.trim() === '') {
                                        this.plateStatus = null;
                                        this.foundOwnerName = '';
                                    }
                                    return;
                                }

                                this.plateStatus = 'loading';

                                try {
                                    const result = await this.$wire.findViolatorByPlate(this.licensePlate.trim());

                                    if (result && result.user_id) {
                                        this.isAutoFilling = true;

                                        this.violatorId = result.user_id;
                                        this.foundOwnerName = result.owner_name || 'Unknown';
                                        this.foundViolatorName = result.owner_name || 'Unknown';
                                        this.plateStatus = 'found';
                                        this.violatorStatus = 'found';

                                        setTimeout(() => {
                                            this.isAutoFilling = false;
                                            this.checkForChanges();
                                        }, 100);
                                    } else {
                                        this.plateStatus = 'not_found';
                                        this.foundOwnerName = '';
                                    }
                                } catch (error) {
                                    console.error('Error finding violator:', error);
                                    this.plateStatus = 'not_found';
                                    this.foundOwnerName = '';
                                }
                            },

                            async findPlateByViolator() {
                                if (!this.violatorId || this.violatorId.trim() === '' || this.isAutoFilling) {
                                    if (!this.violatorId || this.violatorId.trim() === '') {
                                        this.violatorStatus = null;
                                        this.foundViolatorName = '';
                                    }
                                    return;
                                }

                                this.violatorStatus = 'loading';

                                try {
                                    const result = await this.$wire.findPlatesByViolator(this.violatorId.trim());

                                    if (result && result.user_data) {
                                        this.isAutoFilling = true;

                                        this.foundViolatorName = result.user_data.full_name || 'Unknown';
                                        this.foundOwnerName = result.user_data.full_name || 'Unknown';

                                        if (result.plates && result.plates.length > 0) {
                                            if (!this.licensePlate || !result.plates.includes(this.licensePlate)) {
                                                this.licensePlate = result.plates[0];
                                                this.plateStatus = 'found';
                                            }
                                            this.foundPlates = result.plates;
                                        }

                                        this.violatorStatus = 'found';

                                        setTimeout(() => {
                                            this.isAutoFilling = false;
                                            this.checkForChanges();
                                        }, 100);
                                    } else {
                                        this.violatorStatus = 'not_found';
                                        this.foundViolatorName = '';
                                    }
                                } catch (error) {
                                    console.error('Error finding plates:', error);
                                    this.violatorStatus = 'not_found';
                                    this.foundViolatorName = '';
                                }
                            },

                            async saveChanges() {
                                if (!this.hasChanges) return;

                                try {
                                    await this.$wire.updateViolation({{ $violation->id }}, this.licensePlate, this.violatorId);

                                    this.originalLicensePlate = this.licensePlate;
                                    this.originalViolatorId = this.violatorId;
                                    this.hasChanges = false;

                                    console.log('Violation updated successfully');
                                } catch (error) {
                                    console.error('Error saving changes:', error);
                                    throw error; // Re-throw to handle in approveWithSave
                                }
                            },

                            async approveWithSave(violationId) {
                                try {
                                    // First save any changes if there are any
                                    if (this.hasChanges) {
                                        await this.saveChanges();
                                    }

                                    // Then proceed with approval
                                    this.$wire.updateStatus(violationId, 'approved');

                                } catch (error) {
                                    console.error('Error during approve with save:', error);
                                    // You might want to show a user-friendly error message here
                                }
                            }
                        }
                    }
                @endforeach
            </script>



            {{-- Approved Reports --}}
        @elseif ($activeTab === 'approved')
            <table class="table table-striped custom-table">
    <thead>
        <tr>
            <th>Reporter</th>
            <th>Area</th>
            <th>Description</th>
            <th>Evidence</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($violations as $violation)
            <tr>
                <td>{{ $violation->reporter->firstname }} {{ $violation->reporter->lastname }}</td>
                <td>{{ $violation->area->name ?? 'N/A' }}</td>
                <td>{{ Str::limit($violation->description, 50) }}</td>
                <td>
                    @if($violation->evidence)
                        <a href="{{ asset('storage/' . $violation->evidence) }}" target="_blank" class="text-decoration-underline">View</a>
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    <span class="badge {{ $violation->status === 'resolved' ? 'bg-secondary' : 'bg-success' }}">
                        {{ ucfirst($violation->status) }}
                    </span>
                </td>
<td>
    <div class="d-flex flex-column gap-1">
        {{-- Dropdown --}}
        <select class="form-select form-select-sm mb-1"
                wire:model="violationsActionTaken.{{ $violation->id }}"
                @if($violation->status === 'resolved') disabled @endif>
            <option value="">Select action</option>
            <option value="Warning Issued">Warning Issued</option>
            <option value="Fine Imposed">Fine Imposed</option>
            <option value="Suspended">Suspended</option>
        </select>

        {{-- Mark as Resolved --}}
        @if($violation->status === 'resolved')
            <button class="btn btn-sm btn-secondary" disabled>✓ Resolved</button>
        @else
            <button wire:click="markResolved({{ $violation->id }})" class="btn btn-sm btn-primary">
                Mark as Resolved
            </button>
        @endif
    </div>
</td>
            </tr>
        @endforeach
    </tbody>
</table>

            {{-- Resolved Reports --}}
        @elseif ($activeTab === 'resolved')
            <table class="table table-striped custom-table">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Reporter</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Area</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Evidence</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($violations->where('status', 'resolved') as $violation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-800">
                                {{ $violation->reporter->firstname }} {{ $violation->reporter->lastname }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->area->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->description }}</td>
                            <td class="px-4 py-2 text-sm text-blue-600">
                                @if($violation->evidence)
                                    <a href="{{ asset('storage/' . $violation->evidence) }}" target="_blank"
                                        class="underline hover:text-blue-800">View</a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm font-semibold">
                                <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    Resolved
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>