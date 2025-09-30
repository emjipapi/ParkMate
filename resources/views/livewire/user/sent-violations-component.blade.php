<div>
    <div class="d-flex w-100 flex-wrap justify-content-between gap-2 mb-3 align-items-center">
        <!-- LEFT: filters -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Date Range --}}
            <div class="d-flex align-items-center flex-nowrap">
                <input type="date" class="form-control form-control-sm w-auto"
                       wire:model.live="startDate"
                       onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
                <span class="mx-1">-</span>
                <input type="date" class="form-control form-control-sm w-auto"
                       wire:model.live="endDate"
                       onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
            </div>

            {{-- Sort buttons --}}
            <div class="btn-group btn-group-sm ms-2" role="group" x-data="{ sortOrder: @entangle('sortOrder') }">
                <button type="button"
                        class="btn"
                        :class="sortOrder === 'desc' ? 'btn-primary' : 'btn-outline-primary'"
                        wire:click="$set('sortOrder', 'desc')">Newest</button>

                <button type="button"
                        class="btn"
                        :class="sortOrder === 'asc' ? 'btn-primary' : 'btn-outline-primary'"
                        wire:click="$set('sortOrder', 'asc')">Oldest</button>
            </div>
        </div>

        <!-- RIGHT: per-page selector -->
        <div class="d-flex align-items-center gap-2 ms-auto">
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

    <div class="table-responsive">
        <table class="table table-striped custom-table">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-3 py-2 text-left text-sm font-semibold text-gray-700">License Plate</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Action Taken</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Filed On</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Resolved On</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Evidence</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse($violations as $violation)
                    <tr class="align-middle">
                        <td class="px-4 py-2 text-sm text-gray-800">
                            {{ Str::limit($violation->description, 120) }}
                        </td>

                        <td class="px-3 py-2 text-sm text-gray-800">
                            {{ $violation->license_plate ?? 'N/A' }}
                            @if($violation->vehicle)
                                ({{ $violation->vehicle->body_type_model ?? 'Unknown Model' }})
                            @endif
                        </td>

                        <td class="px-4 py-2 text-sm text-gray-800">
                            {{ $violation->action_taken ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-sm text-gray-800">
                            {{-- prefer submitted_at, fallback to created_at --}}
                            {{ $violation->submitted_at
                                ? $violation->submitted_at->format('M d, Y h:i A')
                                : ($violation->created_at ? $violation->created_at->format('M d, Y h:i A') : '—') }}
                        </td>

                        <td class="px-4 py-2 text-sm text-gray-800">
                            {{ $violation->resolved_at ? $violation->resolved_at->format('M d, Y h:i A') : '—' }}
                        </td>

                        <td class="px-4 py-3 text-sm">
                            @php
                                $raw = $violation->evidence;
                                if (is_array($raw)) {
                                    $evidence = $raw;
                                } elseif (is_string($raw) && $raw !== '') {
                                    $decoded = @json_decode($raw, true);
                                    $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : ['reported' => $raw];
                                } else {
                                    $evidence = [];
                                }

                                $reportedPath = $evidence['reported'] ?? null;
                                $approvedPath = $evidence['approved'] ?? null;

                                $makeUrl = function ($path) {
                                    if (! $path) return null;
                                    return preg_match('#^https?://#i', $path) ? $path : \Illuminate\Support\Facades\Storage::url($path);
                                };

                                $reportedUrl = $makeUrl($reportedPath);
                                $approvedUrl = $makeUrl($approvedPath);

                                $thumbUrl = $reportedUrl ?? $approvedUrl;
                            @endphp

                            <div class="d-flex flex-column gap-1">
                                @if($thumbUrl)
                                    <a href="{{ $thumbUrl }}" target="_blank" class="d-inline-block mb-1">
                                        <img src="{{ $thumbUrl }}" alt="evidence" style="max-width:90px; max-height:60px; object-fit:cover; border-radius:4px; border:1px solid #eee;">
                                    </a>
                                @else
                                    <span class="text-muted">No evidence</span>
                                @endif

                                <div>
                                    @if($reportedUrl)
                                        <a href="{{ $reportedUrl }}" target="_blank" class="text-decoration-underline text-primary me-2">View</a>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-2 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $violation->status === 'resolved' ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                                {{ ucfirst(str_replace('_', ' ', $violation->status)) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-3 text-center text-gray-500">Wow, such empty.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- pagination --}}
    <div class="mt-3">
        {{ $violations->links() }}
    </div>
</div>
