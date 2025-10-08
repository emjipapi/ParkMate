{{-- resources\views\livewire\admin\approved-reports-component.blade.php --}}
<div>
    <div class="d-flex w-100 flex-wrap justify-content-between gap-2 mb-3 align-items-center">

        <!-- LEFT: filters -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Search --}}
            <div class="input-group input-group-sm w-auto">
                <input type="search" class="form-control form-control-sm"
                    placeholder="Search plate, reporter, violator, description..."
                    wire:model.live.debounce.500ms="search">
            </div>

            {{-- Reporter Type Filter --}}
            <select class="form-select form-select-sm w-auto" wire:model.live="reporterType">
                <option value="">All Reporters</option>
                <option value="student">Students</option>
                <option value="employee">Employees</option>
            </select>

            {{-- Date Range --}}
            <div class="d-flex align-items-center flex-nowrap">
                <input type="date" class="form-control form-control-sm w-auto" wire:model.live="startDate"
                    onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
                <span class="mx-1">-</span>
                <input type="date" class="form-control form-control-sm w-auto" wire:model.live="endDate"
                    onfocus="this.showPicker();" onmousedown="event.preventDefault(); this.showPicker();">
            </div>

            {{-- Sort buttons --}}
            <div class="btn-group btn-group-sm ms-2" role="group" x-data="{ sortOrder: @entangle('sortOrder') }">
                <button type="button" class="btn" :class="sortOrder === 'desc' ? 'btn-primary' : 'btn-outline-primary'"
                    wire:click="$set('sortOrder', 'desc')">Newest</button>
                <button type="button" class="btn" :class="sortOrder === 'asc' ? 'btn-primary' : 'btn-outline-primary'"
                    wire:click="$set('sortOrder', 'asc')">Oldest</button>
            </div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="$refresh">
            <i class="bi bi-arrow-clockwise"></i>
            Refresh
        </button>
        <!-- RIGHT: per-page + pagination (anchored to far right) -->
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
    <!-- Flash Messages -->
    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Desktop table (visible on sm and up) -->
    <div class="table-responsive d-none d-sm-block hidden sm:block">
        <table class="table table-striped custom-table">
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th class="px-3 py-2 text-left text-sm font-semibold text-gray-700">Date</th>
                    <th>Area</th>
                    <th>License Plate</th>
                    <th>Violator</th>
                    <th>Description</th>
                    <th>Evidence</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($violations as $violation)
                <tr>
                    <td class="px-4 py-2 text-sm text-gray-800">
                        <div class="font-medium">
                            {{ $violation->reporter ? $violation->reporter->getKey() : 'N/A' }}
                        </div>
                        <div class="text-gray-600">
                            {{ $violation->reporter->firstname ?? '' }}
                            {{ $violation->reporter->lastname ?? '' }}
                        </div>
                    </td>

                    <!-- Date -->
                    <td class="px-3 py-2 text-sm text-gray-700">
                        @if($violation->created_at)
                        <span
                            title="Submitted on: {{ $violation->created_at->toDayDateTimeString() }}@if($violation->updated_at && $violation->updated_at != $violation->created_at)&#10;Approved on: {{ $violation->updated_at->toDayDateTimeString() }}@endif">
                            {{ $violation->created_at->format('M j, Y H:i') }}
                        </span>
                        <div class="text-xs text-muted">( {{ $violation->created_at->diffForHumans() }} )</div>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>

                    <td>{{ $violation->area->name ?? 'N/A' }}</td>

                    <!-- License Plate -->
                    <td class="px-3 py-2 text-sm">
                        @php
                        $plate = $violation->license_plate ?? null;
                        if (! $plate && $violation->violator) {
                        $firstVehicle = $violation->violator->vehicles->first() ?? null;
                        $plate = $firstVehicle ? $firstVehicle->license_plate : null;
                        }
                        @endphp

                        @if($plate)
                        <div class="font-medium">{{ $plate }}</div>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>

                    <!-- Violator -->
                    <td class="px-3 py-2 text-sm">
                        @if($violation->violator)
                        {{ trim($violation->violator->firstname . ' ' . $violation->violator->lastname) }}
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>

                    <td>{{ Str::limit($violation->description, 50) }}</td>

                    <!-- Evidence -->
                    <td class="px-4 py-3 text-sm">
                        @php
                        $raw = $violation->evidence;
                        if (is_array($raw)) {
                        $evidence = $raw;
                        } elseif (is_string($raw) && $raw !== '') {
                        $decoded = @json_decode($raw, true);
                        $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded :
                        ['reported' => $raw];
                        } else {
                        $evidence = [];
                        }
                        $path = $evidence['reported'] ?? $evidence['approved'] ?? null;
                        $url = $path ? (preg_match('#^https?://#i', $path) ? $path :
                        \Illuminate\Support\Facades\Storage::url($path)) : null;
                        @endphp

                        @if($url)
                        <a href="{{ $url }}" target="_blank" class="text-decoration-underline text-primary">View</a>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>

                    <!-- Status -->
                    <td class="px-4 py-2 text-sm">
                        @php
                        $status = $violation->status ?? 'unknown';
                        $statusText = ucfirst(str_replace('_', ' ', $status));
                        $statusMap = [
                        'pending' => 'bg-warning text-dark',
                        'rejected' => 'bg-danger text-white',
                        'approved' => 'bg-success text-white',
                        'for_endorsement' => 'bg-primary text-white',
                        'resolved' => 'bg-success text-white',
                        ];
                        $badgeClass = $statusMap[$status] ?? 'bg-secondary text-white';
                        @endphp

                        <span class="badge rounded-pill {{ $badgeClass }}" title="Status: {{ $statusText }}"
                            aria-label="Status: {{ $statusText }}">
                            {{ $statusText }}
                        </span>
                    </td>

                    <!-- Actions (desktop) -->
                    <td>
                        <div class="d-flex flex-column gap-1" style="max-width: 220px;">
                            <div>
                                <input type="file" wire:model="proofs.{{ $violation->id }}"
                                    class="form-control form-control-sm" accept="image/*" />
                                <div wire:loading wire:target="proofs.{{ $violation->id }}" class="mt-2 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    Uploading image…
                                </div>
                                @error('proofs.' . $violation->id) <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <select class="form-select form-select-sm mb-1"
                                wire:model="violationsActionTaken.{{ $violation->id }}" @if($violation->status ===
                                'resolved') disabled @endif>
                                <option value="">Select action</option>
                                <option value="Warning">Warning Issued</option>
                                <option value="6 Months Penalty">6 Months Penalty</option>
                                <option value="Access Denied">Access Denied</option>
                            </select>
                            @error('violationsActionTaken.' . $violation->id) <span class="text-danger small">{{
                                $message }}</span> @enderror

                            @if($violation->status === 'resolved')
                            <button class="btn btn-sm btn-secondary" disabled>✓ Resolved</button>
                            @else
                            <button wire:click="markForEndorsement({{ $violation->id }})" wire:loading.attr="disabled"
                                class="btn btn-sm btn-primary" wire:target="markForEndorsement({{ $violation->id }})">
                                Mark as For Endorsement
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No approved violations found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile cards (visible on xs only) -->
    <div class="mobile-cards d-block d-sm-none">
        @forelse($violations as $violation)
        <article class="bg-white border rounded p-3 shadow-sm mb-3">
            <!-- Status Badge -->
            <div class="mb-3 d-flex justify-content-end">
                @php
                $status = $violation->status ?? 'unknown';
                $statusText = ucfirst(str_replace('_', ' ', $status));
                $statusMap = [
                'pending' => 'bg-warning text-dark',
                'rejected' => 'bg-danger text-white',
                'approved' => 'bg-success text-white',
                'for_endorsement' => 'bg-primary text-white',
                'resolved' => 'bg-success text-white',
                ];
                $badgeClass = $statusMap[$status] ?? 'bg-secondary text-white';
                @endphp
                <span class="badge rounded-pill {{ $badgeClass }}" title="Status: {{ $statusText }}">{{ $statusText
                    }}</span>
            </div>

            <!-- 2-Column Details -->
            <div class="small">
                <!-- Reporter -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Reporter:</div>
                    <div class="text-dark">
                        <div>{{ $violation->reporter ? $violation->reporter->getKey() : 'N/A' }}</div>
                        <div class="small text-muted">
                            {{ $violation->reporter->firstname ?? '' }} {{ $violation->reporter->lastname ?? '' }}
                        </div>
                    </div>
                </div>

                <!-- Date -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Date:</div>
                    <div class="text-dark">
                        @if($violation->created_at)
                        <span
                            title="Submitted on: {{ $violation->created_at->toDayDateTimeString() }}@if($violation->updated_at && $violation->updated_at != $violation->created_at)&#10;Approved on: {{ $violation->updated_at->toDayDateTimeString() }}@endif">
                            {{ $violation->created_at->format('M j, Y H:i') }}
                        </span>
                        <div class="small text-muted">({{ $violation->created_at->diffForHumans() }})</div>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- Area -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Area:</div>
                    <div class="text-dark">{{ $violation->area->name ?? 'N/A' }}</div>
                </div>

                <!-- License Plate -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">License Plate:</div>
                    <div class="text-dark">
                        @php
                        $plate = $violation->license_plate ?? null;
                        if (! $plate && $violation->violator) {
                        $firstVehicle = $violation->violator->vehicles->first() ?? null;
                        $plate = $firstVehicle ? $firstVehicle->license_plate : null;
                        }
                        @endphp
                        {{ $plate ?? 'N/A' }}
                    </div>
                </div>

                <!-- Violator -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Violator:</div>
                    <div class="text-dark">
                        @if($violation->violator) {{ trim($violation->violator->firstname . ' ' .
                        $violation->violator->lastname) }} @else N/A @endif
                    </div>
                </div>

                <!-- Description -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Description:</div>
                    <div class="text-dark text-break" title="{{ $violation->description }}">{{
                        Str::limit($violation->description, 80) }}</div>
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
                        $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded :
                        ['reported' => $raw];
                        } else {
                        $evidence = [];
                        }
                        $reportedUrl = $evidence['reported'] ?? null;
                        $approvedUrl = $evidence['approved'] ?? null;
                        $makeUrl = function ($path) {
                        return $path ? (preg_match('#^https?://#i', $path) ? $path :
                        \Illuminate\Support\Facades\Storage::url($path)) : null;
                        };
                        $reportedUrl = $makeUrl($reportedUrl);
                        $approvedUrl = $makeUrl($approvedUrl);
                        @endphp
                        <div class="d-flex flex-column gap-1">
                            @if($reportedUrl)
                            <a href="{{ $reportedUrl }}" target="_blank"
                                class="text-decoration-underline text-primary small">View Reported</a>
                            @else
                            <span class="text-muted small">Reported N/A</span>
                            @endif

                            @if($approvedUrl)
                            <a href="{{ $approvedUrl }}" target="_blank"
                                class="text-decoration-underline text-primary small">View Approval</a>
                            @else
                            <span class="text-muted small">Approval N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions (mobile) -->
            <footer class="mt-3 pt-3 border-top">
                <div class="d-flex flex-column gap-2">
                    <div>
                        <input type="file" wire:model="proofs.{{ $violation->id }}" class="form-control form-control-sm"
                            accept="image/*" />
                        <div wire:loading wire:target="proofs.{{ $violation->id }}" class="mt-2 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            Uploading image…
                        </div>
                        @error('proofs.' . $violation->id) <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <select class="form-select form-select-sm"
                            wire:model="violationsActionTaken.{{ $violation->id }}" @if($violation->status ===
                            'resolved') disabled @endif>
                            <option value="">Select action</option>
                            <option value="Warning">Warning Issued</option>
                            <option value="6 Months Penalty">6 Months Penalty</option>
                            <option value="Access Denied">Access Denied</option>
                        </select>
                        @error('violationsActionTaken.' . $violation->id) <span class="text-danger small">{{ $message
                            }}</span> @enderror
                    </div>

                    <div>
                        @if($violation->status === 'resolved')
                        <button class="btn btn-sm btn-secondary w-100" disabled>✓ Resolved</button>
                        @else
                        <button wire:click="markForEndorsement({{ $violation->id }})" wire:loading.attr="disabled"
                            class="btn btn-sm btn-primary w-100" wire:target="markForEndorsement({{ $violation->id }})">
                            Mark as For Endorsement
                        </button>
                        @endif
                    </div>
                </div>
            </footer>
        </article>
        @empty
        <div class="text-center py-4">
            <i class="bi bi-inbox fs-1 mb-2 text-muted"></i>
            <h6 class="fw-medium">No approved violations found.</h6>
            <p class="small text-muted">There are currently no reports to display.</p>
        </div>
        @endforelse
    </div>

    {{ $violations->links() }}
</div>