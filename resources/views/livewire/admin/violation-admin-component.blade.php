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
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Reporter</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Area</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Evidence</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($violations as $violation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-800">
                                {{ $violation->reporter->firstname }} {{ $violation->reporter->lastname }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->area->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->description }}</td>
                            <td class="px-4 py-2 text-sm text-blue-600">
                                @if($violation->evidence)
                                    <a href="{{ asset('storage/'.$violation->evidence) }}" target="_blank" class="underline hover:text-blue-800">View</a>
                                @else
                                    N/A
                                @endif
                            </td>
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
                            <td class="px-4 py-2 space-x-2">
                                {{-- Show different button states based on current status --}}
                                @if($violation->status === 'approved')
                                    <button class="px-3 py-1 bg-green-600 text-white font-semibold rounded text-xs cursor-default">
                                        ✓ Approved
                                    </button>
                                    <button wire:click="updateStatus({{ $violation->id }}, 'rejected')" 
                                            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">
                                        Reject
                                    </button>
                                @elseif($violation->status === 'rejected')
                                    <button wire:click="updateStatus({{ $violation->id }}, 'approved')" 
                                            class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs">
                                        Approve
                                    </button>
                                    <button class="px-3 py-1 bg-red-600 text-white font-semibold rounded text-xs cursor-default">
                                        ✓ Rejected
                                    </button>
                                @else
                                    {{-- Default pending state --}}
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
                                    <a href="{{ asset('storage/'.$violation->evidence) }}" target="_blank" class="underline hover:text-blue-800">View</a>
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
                                    <a href="{{ asset('storage/'.$violation->evidence) }}" target="_blank" class="underline hover:text-blue-800">View</a>
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