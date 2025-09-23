<div>
        <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center gap-3 mb-3">
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
    <!-- Flash Messages -->
    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Table -->
    <div class="table-responsive">
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
                @forelse($violations as $violation)
                <tr>
                    <td>{{ $violation->reporter->firstname }} {{ $violation->reporter->lastname }}</td>
                    <td>{{ $violation->area->name ?? 'N/A' }}</td>
                    <td>{{ Str::limit($violation->description, 50) }}</td>
                    <td class="px-4 py-3 text-sm">
                        @php
                        $raw = $violation->evidence;

                        // normalize to array (support casted array, JSON string, or plain string)
                        if (is_array($raw)) {
                        $evidence = $raw;
                        } elseif (is_string($raw) && $raw !== '') {
                        $decoded = @json_decode($raw, true);
                        $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded :
                        ['reported' => $raw];
                        } else {
                        $evidence = [];
                        }

                        // pick reported first, then approved
                        $path = $evidence['reported'] ?? $evidence['approved'] ?? null;

                        // convert to URL (handles full URLs or storage paths)
                        if ($path) {
                        $url = preg_match('#^https?://#i', $path) ? $path :
                        \Illuminate\Support\Facades\Storage::url($path);
                        } else {
                        $url = null;
                        }
                        @endphp

                        @if($url)
                        <a href="{{ $url }}" target="_blank" class="text-decoration-underline text-primary">View</a>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $violation->status === 'resolved' ? 'bg-secondary' : 'bg-success' }}">
                            {{ ucfirst($violation->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <div style="max-width: 100%;">
                                <input type="file" wire:model="proofs.{{ $violation->id }}"
                                    class="form-control form-control-sm" accept="image/*" />
                                @error('proofs.' . $violation->id)
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            {{-- Dropdown --}}
                            <select class="form-select form-select-sm mb-1"
                                wire:model="violationsActionTaken.{{ $violation->id }}" @if($violation->status ===
                                'resolved') disabled @endif>
                                <option value="">Select action</option>
                                <option value="Warning">Warning Issued</option>
                                <option value="6 Months Penalty">6 Months Penalty</option>
                                <option value="Access Denied">Access Denied</option>
                            </select>

                            {{-- Mark as Resolved --}}
                            @if($violation->status === 'resolved')
                            <button class="btn btn-sm btn-secondary" disabled>✓ Resolved</button>
                            @else
                            <button wire:click="markForEndorsement({{ $violation->id }})" class="btn btn-sm btn-primary">
                                Mark as For Endorsement
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No approved violations found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $violations->links() }}
</div>