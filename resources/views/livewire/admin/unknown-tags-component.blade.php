{{-- resources/views/livewire/admin/unknown-tags-component.blade.php --}}
<div class="p-3">
    <div class="row g-3 mb-3">
        {{-- Search Box --}}
        <div class="col-md-3">
            <label for="search" class="form-label fw-bold">Search</label>
            <input type="text" id="search" class="form-control form-control-sm" placeholder="Search by tag..."
                wire:model.live.debounce.300ms="search">
        </div>

        {{-- Date Range --}}
        <div class="col-md-4">
            <label for="startDate" class="form-label fw-bold">Date Range</label>
            <div class="input-group input-group-sm">
                <input type="date" id="startDate" class="form-control" wire:model.live="startDate"
                    onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
                <span class="input-group-text">-</span>
                <input type="date" class="form-control" wire:model.live="endDate" onfocus="this.showPicker();"
                    onmousedown="event.preventDefault(); this.showPicker();">
            </div>
        </div>
                {{-- Refresh Button --}}
        <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"
                wire:click="$refresh">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Refresh</span>
            </button>
        </div>

        {{-- Per Page --}}
        <div class="col-md-2 d-flex align-items-end">
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
