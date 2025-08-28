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
                <button wire:click="updateStatus({{ $violation->id }}, 'approved')" class="px-3 py-1 bg-green-500 text-black rounded hover:bg-green-600 text-xs">Approve</button>
                <button wire:click="updateStatus({{ $violation->id }}, 'rejected')" class="px-3 py-1 bg-red-500 text-black rounded hover:bg-red-600 text-xs">Reject</button>
                <button wire:click="updateStatus({{ $violation->id }}, 'resolved')" class="px-3 py-1 bg-blue-500 text-black rounded hover:bg-blue-600 text-xs">Resolve</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
