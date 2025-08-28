<div wire:poll.100ms="pollEpc">
    <h1 class="text-2xl font-bold mb-4">Scanned EPC Tags:</h1>
    <div class="mb-4 text-blue-600">
    Latest EPC: <br>
    {{ $latestEpc ?? 'None yet' }}

</div>
    <div class="bg-gray-100 p-4 rounded shadow max-h-96 overflow-y-auto">
    <br>
        @forelse ($namedEpcs as $line)
            <div class="text-green-600">{{ $line }}</div>
        @empty
            <p class="text-gray-500">No tags scanned yet.</p>
        @endforelse
    </div>
</div>