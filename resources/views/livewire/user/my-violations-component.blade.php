{{-- resources\views\livewire\user\my-violations-component.blade.php --}}
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

    <!-- Desktop table (hidden on mobile) -->
    <div class="table-responsive d-none d-sm-block">
        <table class="table table-striped custom-table">
            <thead class="bg-light">
                <tr>
                    <th class="px-3 py-2 text-center small fw-semibold text-dark">Description</th>
                    <th class="px-3 py-2 text-center small fw-semibold text-dark">License Plate</th>
                    <th class="px-3 py-2 text-center small fw-semibold text-dark">Action Taken</th>
                    <th class="px-3 py-2 text-center small fw-semibold text-dark">Filed On</th>
                    <th class="px-3 py-2 text-center small fw-semibold text-dark">Resolved On</th>
                    <th class="px-3 py-2 text-center small fw-semibold text-dark">Evidence</th>
                    <th class="px-3 py-2 text-center small fw-semibold text-dark">Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse($violations as $violation)
                    <tr class="align-middle">
                        <td class="px-3 py-2 small text-dark">
                            {{ Str::limit($violation->description, 120) }}
                        </td>

                        <td class="px-3 py-2 small text-dark">
                            {{ $violation->license_plate ?? 'N/A' }}
                            @if($violation->vehicle)
                                ({{ $violation->vehicle->body_type_model ?? 'Unknown Model' }})
                            @endif
                        </td>

                        <td class="px-3 py-2 small text-dark">
                            {{ $violation->action_taken ?? '—' }}
                        </td>

                        <td class="px-3 py-2 small text-dark">
                            {{ $violation->submitted_at ? $violation->submitted_at->format('M d, Y h:i A') : ($violation->created_at ? $violation->created_at->format('M d, Y h:i A') : '—') }}
                        </td>

                        <td class="px-3 py-2 small text-dark">
                            {{ $violation->resolved_at ? $violation->resolved_at->format('M d, Y h:i A') : '—' }}
                        </td>

                        <td class="px-3 py-3 small">
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

                            <div class="d-flex flex-column" style="gap: 0.25rem;">
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

                        <td class="px-3 py-2 small">
                            @php
                                $status = $violation->status ?? 'unknown';
                                $statusText = ucfirst(str_replace('_', ' ', $status));

                                $statusMap = [
                                    'pending'         => 'bg-warning text-dark',
                                    'rejected'        => 'bg-danger text-white',
                                    'approved'        => 'bg-success text-white',
                                    'for_endorsement' => 'bg-primary text-white',
                                    'resolved'        => 'bg-success text-white',
                                ];

                                $badgeClass = $statusMap[$status] ?? 'bg-secondary text-white';
                            @endphp

                            <span class="badge rounded-pill {{ $badgeClass }}" title="Status: {{ $statusText }}" aria-label="Status: {{ $statusText }}">
                                {{ $statusText }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-3 text-center text-muted">Wow, such empty.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile cards (visible only on mobile) -->
    <div class="mobile-cards d-block d-sm-none">
        @forelse($violations as $violation)
        <article class="bg-white border rounded p-3 shadow-sm mb-3">
            <!-- Status Badge -->
            <div class="mb-3 d-flex justify-content-end">
                @php
                    $status = $violation->status ?? 'unknown';
                    $statusText = ucfirst(str_replace('_', ' ', $status));
                    $statusMap = [
                        'pending'         => 'bg-warning text-dark',
                        'rejected'        => 'bg-danger text-white',
                        'approved'        => 'bg-success text-white',
                        'for_endorsement' => 'bg-primary text-white',
                        'resolved'        => 'bg-success text-white',
                    ];
                    $badgeClass = $statusMap[$status] ?? 'bg-secondary text-white';
                @endphp
                <span class="badge rounded-pill {{ $badgeClass }}" title="Status: {{ $statusText }}">{{ $statusText }}</span>
            </div>

            <!-- 2-Column Details -->
            <div class="small">
                <!-- Description -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Description:</div>
                    <div class="text-dark text-break">
                        {{ Str::limit($violation->description, 120) }}
                    </div>
                </div>

                <!-- License Plate -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">License Plate:</div>
                    <div class="text-dark">
                        {{ $violation->license_plate ?? 'N/A' }}
                        @if($violation->vehicle)
                            <div class="small text-muted">({{ $violation->vehicle->body_type_model ?? 'Unknown Model' }})</div>
                        @endif
                    </div>
                </div>

                <!-- Action Taken -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Action Taken:</div>
                    <div class="text-dark">{{ $violation->action_taken ?? '—' }}</div>
                </div>

                <!-- Filed On -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Filed On:</div>
                    <div class="text-dark">
                        {{ $violation->submitted_at ? $violation->submitted_at->format('M d, Y h:i A') : ($violation->created_at ? $violation->created_at->format('M d, Y h:i A') : '—') }}
                    </div>
                </div>

                <!-- Resolved On -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Resolved On:</div>
                    <div class="text-dark">
                        {{ $violation->resolved_at ? $violation->resolved_at->format('M d, Y h:i A') : '—' }}
                    </div>
                </div>

                <!-- Evidence -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Evidence:</div>
                    <div class="text-dark">
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

                        @if($thumbUrl)
                            <a href="{{ $thumbUrl }}" target="_blank" class="d-inline-block mb-2">
                                <img src="{{ $thumbUrl }}" alt="evidence" style="max-width:120px; max-height:80px; object-fit:cover; border-radius:4px; border:1px solid #eee;">
                            </a>
                            <div>
                                <a href="{{ $reportedUrl }}" target="_blank" class="text-decoration-underline text-primary small">View Full Size</a>
                            </div>
                        @else
                            <span class="text-muted">No evidence</span>
                        @endif
                    </div>
                </div>
            </div>
        </article>
        @empty
        <div class="text-center py-4">
            <i class="bi bi-inbox fs-1 mb-2 text-muted"></i>
            <h6 class="fw-medium">Wow, such empty.</h6>
            <p class="small text-muted">Resolved violations will appear here.</p>
        </div>
        @endforelse
    </div>

    {{-- pagination --}}
    <div class="mt-3">
        {{ $violations->links() }}
    </div>
</div>
