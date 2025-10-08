{{-- resources/views/livewire/admin/unknown-tags-component.blade.php --}}
<div class="p-3">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 align-items-center">
            <input type="text" class="form-control" placeholder="Search by tag..."
                wire:model.live.debounce.300ms="search" style="min-width: 240px;">

            <div class="d-flex align-items-center gap-1">
                <label class="mb-0 small">Date</label>
                <input type="date" class="form-control form-control-sm" wire:model.live="startDate"
                    onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
                <span class="mx-1">-</span>
                <input type="date" class="form-control form-control-sm" wire:model.live="endDate"
                    onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
            </div>
<button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" wire:click="$refresh">
    <i class="bi bi-arrow-clockwise"></i>
    <span>Refresh</span>
</button>

        </div>
        
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

    <div class="table-responsive" wire:poll.2s>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>RFID Tag</th>
                    <th>Area</th>
                    <th>Seen At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($unknownTags as $tag)
                    <tr>
                        <td>{{ $tag->id }}</td>
                        <td><code>{{ $tag->rfid_tag }}</code></td>
                        <td>{{ $tag->area->name ?? $tag->area_id ?? '—' }}</td>
                        <td>{{ $tag->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No unknown tags found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $unknownTags->links() }}
    </div>
</div>
