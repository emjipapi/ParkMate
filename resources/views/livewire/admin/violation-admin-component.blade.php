<div class="container mt-4">
    {{-- Tabs --}}
    <ul class="nav nav-tabs border-b mb-4 flex space-x-2">
        <li>
            <a class="nav-link px-4 py-2 cursor-pointer {{ $activeTab === 'pending' ? 'active font-semibold border-b-2 border-blue-500 text-blue-600' : 'text-gray-600' }}"
                wire:click="setActiveTab('pending')">
                Pending Reports
            </a>
        </li>
        <li>
            <a class="nav-link px-4 py-2 cursor-pointer {{ $activeTab === 'approved' ? 'active font-semibold border-b-2 border-blue-500 text-blue-600' : 'text-gray-600' }}"
                wire:click="setActiveTab('approved')">
                Approved Reports
            </a>
        </li>
        <li>
            <a class="nav-link px-4 py-2 cursor-pointer {{ $activeTab === 'resolved' ? 'active font-semibold border-b-2 border-blue-500 text-blue-600' : 'text-gray-600' }}"
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
                <tr class="hover:bg-gray-50">
                    {{-- Reporter ID & Name --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        #{{ $violation->reporter->id ?? 'N/A' }} - 
                        {{ $violation->reporter->firstname ?? '' }} {{ $violation->reporter->lastname ?? '' }}
                    </td>

                    {{-- Area --}}
                    <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->area->name ?? 'N/A' }}</td>

                    {{-- License Plate Dropdown/Input --}}
                    <td class="px-4 py-2 text-sm">
                        <div class="relative"
                            x-data="{
                                open: false,
                                search: @entangle('violationSearch').defer ?? '{{ $violation->license_plate ?? '' }}',
                                selectedPlate: '{{ $violation->license_plate ?? '' }}',
                                selectedOwner: '{{ $violation->violator_name ?? 'Unknown' }}',
                                selectedUserId: '{{ $violation->violator_id ?? '' }}',
                                vehicles: @js($vehicles ?? []),
                                filteredVehicles: [],
                                init() {
                                    this.filteredVehicles = this.vehicles;
                                    this.$watch('search', () => this.filterVehicles());
                                },
                                filterVehicles() {
                                    if (this.search.length === 0) {
                                        this.filteredVehicles = this.vehicles;
                                        return;
                                    }
                                    this.filteredVehicles = this.vehicles.filter(vehicle =>
                                        vehicle.license_plate.toLowerCase().includes(this.search.toLowerCase()) ||
                                        (vehicle.owner_name && vehicle.owner_name.toLowerCase().includes(this.search.toLowerCase()))
                                    );
                                },
                                selectVehicle(vehicle) {
                                    this.selectedPlate = vehicle.license_plate;
                                    this.selectedOwner = vehicle.owner_name || 'Unknown';
                                    this.selectedUserId = vehicle.user_id || '';
                                    this.search = vehicle.license_plate;
                                    this.open = false;

                                    // Update violation with selected vehicle
                                    $wire.updateVehicle({{ $violation->id }}, vehicle.license_plate, vehicle.user_id);
                                }
                            }"
                            x-id="['vehicleData']">
                            
                            <input type="text"
                                x-model="search"
                                @focus="open = true"
                                @click="open = true"
                                @blur="setTimeout(() => open = false, 200)"
                                placeholder="Type or select license plate..."
                                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">

                            {{-- Dropdown List --}}
                            <div x-show="open" x-transition
                                class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                <template x-for="vehicle in filteredVehicles" :key="vehicle.id">
                                    <div @click="selectVehicle(vehicle)"
                                        class="px-3 py-2 text-xs cursor-pointer hover:bg-gray-100 border-b border-gray-100">
                                        <div class="font-medium" x-text="vehicle.license_plate"></div>
                                        <div class="text-gray-500" x-text="vehicle.owner_name || 'Unknown'"></div>
                                    </div>
                                </template>

                                <div x-show="filteredVehicles.length === 0"
                                    class="px-3 py-2 text-xs text-gray-500">
                                    No vehicles found
                                </div>
                            </div>

                            {{-- Display current selection --}}
                            <div class="text-xs text-gray-600 mt-1" x-show="selectedPlate">
                                <span x-text="selectedPlate"></span> - <span x-text="selectedOwner"></span>
                            </div>
                        </div>
                    </td>

                    {{-- Violator Dropdown/Input --}}
                    <td class="px-4 py-2 text-sm">
                        <div class="relative"
                            x-data="{
                                open: false,
                                search: '{{ $violation->violator_id ?? '' }}',
                                selectedUser: '{{ $violation->violator_name ?? '' }}',
                                selectedUserId: '{{ $violation->violator_id ?? '' }}',
                                users: @js($users ?? []),
                                vehicles: @js($vehicles ?? []),
                                filteredUsers: [],
                                init() {
                                    this.filteredUsers = this.users;
                                    this.$watch('search', () => this.filterUsers());
                                },
                                filterUsers() {
                                    if (this.search.length === 0) {
                                        this.filteredUsers = this.users;
                                        return;
                                    }
                                    this.filteredUsers = this.users.filter(user =>
                                        user.id.toString().includes(this.search) ||
                                        (user.firstname + ' ' + user.lastname).toLowerCase().includes(this.search.toLowerCase()) ||
                                        (user.license_plates && user.license_plates.some(plate =>
                                            plate.toLowerCase().includes(this.search.toLowerCase())
                                        ))
                                    );
                                },
                                selectUser(user) {
                                    this.selectedUser = user.firstname + ' ' + user.lastname;
                                    this.selectedUserId = user.id;
                                    this.search = user.id.toString();
                                    this.open = false;

                                    // Update violation with selected user
                                    $wire.updateViolator({{ $violation->id }}, user.id);
                                }
                            }">
                            
                            <input type="text"
                                x-model="search"
                                @focus="open = true"
                                @click="open = true"
                                @blur="setTimeout(() => open = false, 200)"
                                placeholder="Type User ID or name..."
                                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">

                            {{-- Dropdown List --}}
                            <div x-show="open" x-transition
                                class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                <template x-for="user in filteredUsers" :key="user.id">
                                    <div @click="selectUser(user)"
                                        class="px-3 py-2 text-xs cursor-pointer hover:bg-gray-100 border-b border-gray-100">
                                        <div class="font-medium">
                                            #<span x-text="user.id"></span> - <span x-text="user.firstname + ' ' + user.lastname"></span>
                                        </div>
                                        <div class="text-gray-500" x-show="user.license_plates && user.license_plates.length > 0">
                                            Plates:
                                            <span x-text="user.license_plates ? user.license_plates.join(', ') : 'None'"></span>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="filteredUsers.length === 0"
                                    class="px-3 py-2 text-xs text-gray-500">
                                    No users found
                                </div>
                            </div>

                            <div class="text-xs text-gray-600 mt-1" x-show="selectedUser">
                                <span x-text="selectedUser"></span>
                            </div>
                        </div>
                    </td>

                    {{-- Description --}}
                    <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->description }}</td>

                    {{-- Evidence --}}
                    <td class="px-4 py-2 text-sm text-blue-600">
                        @if ($violation->evidence)
                            <a href="{{ asset('storage/' . $violation->evidence) }}" target="_blank"
                                class="underline hover:text-blue-800">View</a>
                        @else
                            N/A
                        @endif
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-2 text-sm font-semibold">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'resolved' => 'bg-blue-100 text-blue-800',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$violation->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($violation->status) }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-2 space-x-2">
                        @if ($violation->status === 'approved')
                            <button class="px-3 py-1 bg-green-600 text-white font-semibold rounded text-xs cursor-default">
                                ✓ Approved
                            </button>
                            <button wire:click="updateStatus({{ $violation->id }}, 'rejected')"
                                class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">
                                Reject
                            </button>
                        @elseif ($violation->status === 'rejected')
                            <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                                class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs">
                                Approve
                            </button>
                            <button class="px-3 py-1 bg-red-600 text-white font-semibold rounded text-xs cursor-default">
                                ✓ Rejected
                            </button>
                        @else
                            <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                                class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs">
                                Approve
                            </button>
                            <button wire:click="updateStatus({{ $violation->id }}, 'rejected')"
                                class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">
                                Reject
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


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