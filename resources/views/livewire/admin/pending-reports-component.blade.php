<div>
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
                <tr class="hover:bg-gray-50">

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
                            <input type="text" 
                                wire:model.live.debounce.500ms="violationInputs.{{ $violation->id }}.license_plate"
                                placeholder="Enter license plate"
                                {{ $violation->status === 'approved' ? 'disabled' : '' }}
                                class="form-control form-control-sm"
                                style="max-width: 150px;">

                            <div class="text-xs mt-1">
                                @if(isset($violationStatuses[$violation->id]['plate_status']))
                                    @if($violationStatuses[$violation->id]['plate_status'] === 'found')
                                        <span class="text-success font-weight-medium">
                                            ✓ {{ $violationStatuses[$violation->id]['found_owner'] ?? '' }}
                                        </span>
                                    @elseif($violationStatuses[$violation->id]['plate_status'] === 'not_found')
                                        <span class="text-danger">
                                            ✗ Plate not found
                                        </span>
                                    @elseif($violationStatuses[$violation->id]['plate_status'] === 'loading')
                                        <span class="text-primary">
                                            Searching...
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Violator Input --}}
                    <td class="px-2 py-2 text-sm">
                        <div class="d-flex flex-column">
                            <input type="text" 
                                wire:model.live.debounce.500ms="violationInputs.{{ $violation->id }}.violator_id"
                                placeholder="Enter User ID"
                                {{ $violation->status === 'approved' ? 'disabled' : '' }}
                                class="form-control form-control-sm"
                                style="max-width: 150px;">

                            <div class="text-xs mt-1">
                                @if(isset($violationStatuses[$violation->id]['violator_status']))
                                    @if($violationStatuses[$violation->id]['violator_status'] === 'found')
                                        <span class="text-success font-weight-medium">
                                            ✓ {{ $violationStatuses[$violation->id]['found_violator'] ?? '' }}
                                        </span>
                                    @elseif($violationStatuses[$violation->id]['violator_status'] === 'not_found')
                                        <span class="text-danger">
                                            ✗ User not found
                                        </span>
                                    @elseif($violationStatuses[$violation->id]['violator_status'] === 'loading')
                                        <span class="text-primary">
                                            Searching...
                                        </span>
                                    @endif
                                @endif
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
    
    {{ $violations->links() }}
</div>