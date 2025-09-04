<div class="container mt-4">
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
    <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
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
                <tr class="hover:bg-gray-50"
                    x-data="violationRow_{{ $violation->id }}()"
                    x-init="init()">
                    
                    {{-- Reporter ID & Name --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        <div class="font-medium">#{{ $violation->reporter->id ?? 'N/A' }}</div>
                        <div class="text-gray-600">{{ $violation->reporter->firstname ?? '' }} {{ $violation->reporter->lastname ?? '' }}</div>
                    </td>

                    {{-- Area --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        {{ $violation->area->name ?? 'N/A' }}
                    </td>

                    {{-- License Plate Input --}}
                    <td class="px-4 py-2 text-sm">
                        <div class="space-y-1">
                            <input type="text"
                                   x-model="licensePlate"
                                   @blur="findViolatorByPlate()"
                                   placeholder="Enter license plate"
                                   class="w-full px-2 py-1 text-xs border rounded focus:outline-none focus:ring-1 transition-colors"
                                   :class="plateStatus === 'found' ? 'border-green-500 focus:ring-green-500' : 
                                          plateStatus === 'not_found' ? 'border-red-300 focus:ring-red-300' : 
                                          'border-gray-300 focus:ring-blue-500'">
                            
                            {{-- Status indicator --}}
                            <div class="text-xs" x-show="plateStatus">
                                <span x-show="plateStatus === 'found'" class="text-green-600 font-medium">
                                    ✓ <span x-text="foundOwnerName"></span>
                                </span>
                                <span x-show="plateStatus === 'not_found'" class="text-red-500">
                                    ✗ Plate not found
                                </span>
                                <span x-show="plateStatus === 'loading'" class="text-blue-500">
                                    Searching...
                                </span>
                            </div>
                        </div>
                    </td>

                    {{-- Violator Input --}}
                    <td class="px-4 py-2 text-sm">
                        <div class="space-y-1">
                            <input type="text"
                                   x-model="violatorId"
                                   @blur="findPlateByViolator()"
                                   placeholder="Enter User ID"
                                   class="w-full px-2 py-1 text-xs border rounded focus:outline-none focus:ring-1 transition-colors"
                                   :class="violatorStatus === 'found' ? 'border-green-500 focus:ring-green-500' : 
                                          violatorStatus === 'not_found' ? 'border-red-300 focus:ring-red-300' : 
                                          'border-gray-300 focus:ring-blue-500'">
                            
                            {{-- Status indicator --}}
                            <div class="text-xs" x-show="violatorStatus">
                                <span x-show="violatorStatus === 'found'" class="text-green-600 font-medium">
                                    ✓ <span x-text="foundViolatorName"></span>
                                </span>
                                <span x-show="violatorStatus === 'not_found'" class="text-red-500">
                                    ✗ User not found
                                </span>
                                <span x-show="violatorStatus === 'loading'" class="text-blue-500">
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
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$violation->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($violation->status) }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-2">
                        <div class="flex space-x-1">
                            @if ($violation->status === 'approved')
                                <button class="px-2 py-1 bg-green-600 text-white font-medium rounded text-xs cursor-default">
                                    ✓ Approved
                                </button>
                                <button wire:click="updateStatus({{ $violation->id }}, 'rejected')"
                                        class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs transition-colors">
                                    Reject
                                </button>
                            @elseif ($violation->status === 'rejected')
                                <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                                        class="px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs transition-colors">
                                    Approve
                                </button>
                                <button class="px-2 py-1 bg-red-600 text-white font-medium rounded text-xs cursor-default">
                                    ✓ Rejected
                                </button>
                            @else
                                <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                                        class="px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs transition-colors">
                                    Approve
                                </button>
                                <button wire:click="updateStatus({{ $violation->id }}, 'rejected')"
                                        class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs transition-colors">
                                    Reject
                                </button>
                            @endif
                            
                            {{-- Save button (shows when changes are made) --}}
                            <button x-show="hasChanges"
                                    @click="saveChanges()"
                                    class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs transition-colors">
                                Save
                            </button>
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
// Fixed script - replace $wire with this.$wire
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
                // Use this.$wire instead of $wire
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
                // Use this.$wire instead of $wire
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
                // Use this.$wire instead of $wire
                await this.$wire.updateViolation({{ $violation->id }}, this.licensePlate, this.violatorId);
                
                this.originalLicensePlate = this.licensePlate;
                this.originalViolatorId = this.violatorId;
                this.hasChanges = false;
                
                console.log('Violation updated successfully');
            } catch (error) {
                console.error('Error saving changes:', error);
            }
        }
    }
}
@endforeach
</script>
<script>

// Clock function (keep your existing clock code)
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
}

setInterval(updateClock, 1000);
updateClock();
</script>


            {{-- Approved Reports --}}
        @elseif ($activeTab === 'approved')
            <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Reporter</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Area</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Evidence</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($violations->where('status', 'approved') as $violation)
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
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                    Approved
                                </span>
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                @if($violation->status === 'resolved')
                                    <button class="px-3 py-1 bg-blue-600 text-white font-semibold rounded text-xs cursor-default">
                                        ✓ Resolved
                                    </button>
                                @else
                                    <button wire:click="updateStatus({{ $violation->id }}, 'resolved')"
                                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">
                                        Mark as Resolved
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Resolved Reports --}}
        @elseif ($activeTab === 'resolved')
            <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
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