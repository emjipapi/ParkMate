{{-- resources\views\livewire\admin\pending-reports-component.blade.php --}}
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
                <option value="admin">Admins</option>
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

    <div class="table-responsive d-none d-sm-block hidden sm:block">
        <table class="table table-striped custom-table">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Reporter ID & Name</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">User Type</th>
                    <th class="px-3 py-2 text-left text-sm font-semibold text-gray-700">Date</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Area</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">License Plate</th>
                    {{-- <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Violator</th> --}}
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Evidence</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($violations as $violation)
                <tr wire:key="violation-{{ $violation->id }}" class="hover:bg-gray-50">

                    {{-- Reporter ID & Name --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        <div class="font-medium">
                            {{-- # --}}
                            {{ $violation->reporter ? $violation->reporter->getKey() : 'N/A' }}
                        </div>
                        <div class="text-gray-600">{{ $violation->reporter->firstname ?? '' }}
                            {{ $violation->reporter->lastname ?? '' }}
                        </div>
                    </td>
                    {{-- Reporter Type --}}
                    <td>
                        @php
                        $reporter = $violation->reporter;
                        $type = 'Unknown';
                        $badgeClass = 'bg-secondary text-white';

                        if ($reporter instanceof \App\Models\User) {
                        if (!empty($reporter->student_id)) {
                        $type = 'Student';
                        $badgeClass = 'bg-primary text-white'; // blue
                        } elseif (!empty($reporter->employee_id)) {
                        $type = 'Employee';
                        $badgeClass = 'bg-success text-white'; // green
                        }
                        } elseif ($reporter instanceof \App\Models\Admin) {
                        $type = 'Admin';
                        $badgeClass = 'bg-info text-white'; // purple
                        }
                        @endphp

                        <span class="badge rounded-pill {{ $badgeClass }}">{{ $type }}</span>
                    </td>


                    <!-- DATE cell (insert right after your Reporter cell) -->
                    <td class="px-3 py-2 text-sm text-gray-700" class="cursor-pointer">
                        @if($violation->created_at)
                        <span
                            title="Submitted on: {{ $violation->submitted_at ? $violation->submitted_at->toDayDateTimeString() : 'No submission date' }}">
                            {{ $violation->created_at->format('M j, Y H:i') }}
                        </span>
                        <div class="text-xs text-muted">
                            ({{ $violation->created_at->diffForHumans() }})
                        </div>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>

                    {{-- Area --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        {{ $violation->area->name ?? 'N/A' }}
                    </td>

                    {{-- License Plate Input --}}
                    <td class="px-2 py-2 text-sm">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center">
                                {{-- LEFT: small status icon (check / cross / loading) --}}
                                <div class="text-xs me-2 d-flex align-items-center justify-content-center"
                                    style="width:22px;">
                                    @if(isset($violationStatuses[$violation->id]['plate_status']))
                                    @if($violationStatuses[$violation->id]['plate_status'] === 'found')
                                    <span class="text-success font-weight-medium" aria-hidden="true">✓</span>
                                    @elseif($violationStatuses[$violation->id]['plate_status'] === 'not_found')
                                    <span class="text-danger" aria-hidden="true">✗</span>
                                    @elseif($violationStatuses[$violation->id]['plate_status'] === 'loading')
                                    <span class="text-primary" aria-hidden="true">⏳</span>
                                    @endif
                                    @endif
                                </div>

                                {{-- RIGHT: input --}}
                                <input type="text"
                                    wire:model.live.debounce.50ms="violationInputs.{{ $violation->id }}.license_plate"
                                    placeholder="Enter license plate" {{ $violation->status === 'approved' ? 'disabled'
                                : '' }}
                                class="form-control form-control-sm"
                                style="max-width: 150px;">
                            </div>

                            {{-- status text below the input (owner / not found / searching) --}}
                            <div class="text-xs mt-1" style="min-height:1.1em;">
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
                    {{-- <td class="px-2 py-2 text-sm">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center">
                                LEFT: small status icon (check / cross / loading)
                                <div class="text-xs me-2 d-flex align-items-center justify-content-center"
                                    style="width:22px;">
                                    @if(isset($violationStatuses[$violation->id]['violator_status']))
                                    @if($violationStatuses[$violation->id]['violator_status'] === 'found')
                                    <span class="text-success font-weight-medium" aria-hidden="true">✓</span>
                                    @elseif($violationStatuses[$violation->id]['violator_status'] === 'not_found')
                                    <span class="text-danger" aria-hidden="true">✗</span>
                                    @elseif($violationStatuses[$violation->id]['violator_status'] === 'loading')
                                    <span class="text-primary" aria-hidden="true">⏳</span>
                                    @endif
                                    @endif
                                </div>

                                RIGHT: input
                                <input type="text"
                                    wire:model.live.debounce.500ms="violationInputs.{{ $violation->id }}.violator_id"
                                    placeholder="Enter User ID" {{ $violation->status === 'approved' ? 'disabled' : ''
                                }}
                                class="form-control form-control-sm"
                                style="max-width: 150px;">
                            </div>

                            status text below the input (user name / not found / searching)
                            <div class="text-xs mt-1" style="min-height:1.1em;">
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
                    </td> --}}


                    {{-- Description --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        <div class="max-w-xs">
                            <div class="truncate" title="{{ $violation->description }}">
                                {{ Str::limit($violation->description, 50) }}
                            </div>
                        </div>
                    </td>

                    {{-- Evidence --}}
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


                    {{-- Status --}}
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


                    {{-- Actions --}}
                    <td class="px-4 py-2 align-middle">
                        <div class="d-flex flex-column gap-2 action-buttons">
                            @if ($violation->status === 'pending')
                            {{-- Approve Button Group --}}
                            <div class="btn-group w-100" role="group">
                                <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                                    class="btn btn-sm btn-success"
                                    @if(!isset($violationStatuses[$violation->id]['plate_status']) ||
                                    $violationStatuses[$violation->id]['plate_status'] !== 'found')
                                    disabled
                                    title="Enter a valid license plate to approve"
                                    @endif>
                                    Approve
                                </button>
                                <button class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    @if(!isset($violationStatuses[$violation->id]['plate_status']) ||
                                    $violationStatuses[$violation->id]['plate_status'] !== 'found')
                                    disabled
                                    title="Enter a valid license plate to approve"
                                    @endif>
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"
                                            wire:click.prevent="approveWithMessage({{ $violation->id }})">
                                            Approve with Message
                                        </a></li>
                                </ul>
                            </div>

                            {{-- Reject Button Group --}}
                            <div class="btn-group w-100" role="group">
                                <button wire:click="updateStatus({{ $violation->id }}, 'rejected')"
                                    class="btn btn-sm btn-danger">
                                    Reject
                                </button>
                                <button class="btn btn-sm btn-danger dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"
                                            wire:click.prevent="rejectWithMessage({{ $violation->id }})">
                                            Reject with Message
                                        </a></li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- Mobile: Card list (visible only on xs) -->
    <div class="mobile-cards d-block d-sm-none">
        @forelse ($violations as $violation)
        <article wire:key="violation-card-{{ $violation->id }}" class="bg-white border rounded p-3 shadow-sm mb-3">
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
                        <div class="fw-medium">{{ $violation->reporter ? $violation->reporter->getKey() : 'N/A' }}</div>
                        <div class="small text-muted">
                            {{ $violation->reporter->firstname ?? '' }} {{ $violation->reporter->lastname ?? '' }}
                        </div>
                    </div>
                </div>
                <!-- User Type -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Reporter Type:</div>
                    <div class="text-dark">
                        <div class="fw-medium"> @php
                            $reporter = $violation->reporter;
                            $type = 'Unknown';
                            $badgeClass = 'bg-secondary text-white';

                            if ($reporter instanceof \App\Models\User) {
                            if (!empty($reporter->student_id)) {
                            $type = 'Student';
                            $badgeClass = 'bg-primary text-white'; // blue
                            } elseif (!empty($reporter->employee_id)) {
                            $type = 'Employee';
                            $badgeClass = 'bg-success text-white'; // green
                            }
                            } elseif ($reporter instanceof \App\Models\Admin) {
                            $type = 'Admin';
                            $badgeClass = 'bg-info text-white'; // purple
                            }
                            @endphp

                            <span class="badge rounded-pill {{ $badgeClass }}">{{ $type }}</span>
                        </div>
                    </div>
                </div>

                <!-- Date -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Date:</div>
                    <div class="text-dark">
                        @if($violation->created_at)
                        <span
                            title="Submitted on: {{ $violation->submitted_at ? $violation->submitted_at->toDayDateTimeString() : 'No submission date' }}">
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
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            {{-- Status icon --}}
                            <div class="small me-2 d-flex align-items-center justify-content-center"
                                style="width:22px;">
                                @if(isset($violationStatuses[$violation->id]['plate_status']))
                                @if($violationStatuses[$violation->id]['plate_status'] === 'found')
                                <span class="text-success fw-medium" aria-hidden="true">✓</span>
                                @elseif($violationStatuses[$violation->id]['plate_status'] === 'not_found')
                                <span class="text-danger" aria-hidden="true">✗</span>
                                @elseif($violationStatuses[$violation->id]['plate_status'] === 'loading')
                                <span class="text-primary" aria-hidden="true">⏳</span>
                                @endif
                                @endif
                            </div>

                            {{-- Input --}}
                            <input type="text"
                                wire:model.live.debounce.50ms="violationInputs.{{ $violation->id }}.license_plate"
                                placeholder="Enter plate" {{ $violation->status === 'approved' ? 'disabled' : '' }}
                            class="form-control form-control-sm">
                        </div>

                        {{-- Status text --}}
                        <div class="small" style="min-height:1.1em;">
                            @if(isset($violationStatuses[$violation->id]['plate_status']))
                            @if($violationStatuses[$violation->id]['plate_status'] === 'found')
                            <span class="text-success fw-medium">
                                ✓ {{ $violationStatuses[$violation->id]['found_owner'] ?? '' }}
                            </span>
                            @elseif($violationStatuses[$violation->id]['plate_status'] === 'not_found')
                            <span class="text-danger">✗ Plate not found</span>
                            @elseif($violationStatuses[$violation->id]['plate_status'] === 'loading')
                            <span class="text-primary">Searching...</span>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="d-flex mb-2">
                    <div class="fw-bold text-muted" style="min-width: 110px; flex-shrink: 0;">Description:</div>
                    <div class="text-dark text-break" title="{{ $violation->description }}">
                        {{ Str::limit($violation->description, 50) }}
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
                        <a href="{{ $url }}" target="_blank" class="text-decoration-underline text-primary small">View
                            Evidence</a>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions (mobile) -->
            @if ($violation->status === 'pending')
            <footer class="mt-3 pt-3 border-top">
                <div class="d-flex flex-column" style="gap: 0.5rem;">
                    {{-- Approve Button Group --}}
                    <div class="btn-group w-100" role="group">
                        <button wire:click="updateStatus({{ $violation->id }}, 'approved')"
                            class="btn btn-sm btn-success"
                            @if(!isset($violationStatuses[$violation->id]['plate_status']) ||
                            $violationStatuses[$violation->id]['plate_status'] !== 'found')
                            disabled
                            title="Enter a valid license plate to approve"
                            @endif>
                            Approve
                        </button>
                        <button class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            @if(!isset($violationStatuses[$violation->id]['plate_status']) ||
                            $violationStatuses[$violation->id]['plate_status'] !== 'found')
                            disabled
                            title="Enter a valid license plate to approve"
                            @endif>
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"
                                    wire:click.prevent="approveWithMessage({{ $violation->id }})">
                                    Approve with Message
                                </a></li>
                        </ul>
                    </div>

                    {{-- Reject Button Group --}}
                    <div class="btn-group w-100" role="group">
                        <button wire:click="updateStatus({{ $violation->id }}, 'rejected')"
                            class="btn btn-sm btn-danger">
                            Reject
                        </button>
                        <button class="btn btn-sm btn-danger dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"
                                    wire:click.prevent="rejectWithMessage({{ $violation->id }})">
                                    Reject with Message
                                </a></li>
                        </ul>
                    </div>
                </div>
            </footer>
            @endif
        </article>
        @empty

        @endforelse
    </div>
    {{-- Approve with Message Modal --}}
    <div wire:ignore.self class="modal fade" id="approveMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sending to Reporter — Approve</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Choose message</label>
                        <select class="form-select" wire:model.live="selectedApproveMessage">
                            <option value="">-- Select a message --</option>

                            {{-- your canned messages --}}
                            @foreach($approveMessages as $key => $text)
                            <option value="{{ $key }}">{{ $text }}</option>
                            @endforeach

                            {{-- "Other" option that shows an input (same pattern you used before) --}}
                            <option value="other">Other</option>
                        </select>
                        @error('selectedApproveMessage') <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- show input only when "Other" is selected (same pattern as your Description block) --}}
                    @if($selectedApproveMessage === 'other')
                    <div class="mb-3">
                        <label class="form-label">Custom message</label>
                        <input type="text" wire:model.live="approveCustomMessage" placeholder="Enter details"
                            class="form-control mt-1 mt-md-2" required />
                        @error('approveCustomMessage') <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <div class="small text-muted">Message will be saved in the log and used as action taken.</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" wire:click="sendApproveMessage" class="btn btn-primary btn-sm"
                        @if($selectedApproveMessage==='' || ($selectedApproveMessage==='other' &&
                        trim($approveCustomMessage)==='' )) disabled @endif>
                        Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject with Message Modal --}}
    <div wire:ignore.self class="modal fade" id="rejectMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sending to Reporter — Reject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Choose message</label>
                        <select class="form-select" wire:model.live="selectedRejectMessage">
                            <option value="">-- Select a message --</option>

                            {{-- your canned reject messages --}}
                            @foreach($rejectMessages as $key => $text)
                            <option value="{{ $key }}">{{ $text }}</option>
                            @endforeach

                            {{-- "Other" option that shows an input just like your Description block --}}
                            <option value="other">Other</option>
                        </select>
                        @error('selectedRejectMessage') <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($selectedRejectMessage === 'other')
                    <div class="mb-3">
                        <label class="form-label">Custom message</label>
                        <input type="text" wire:model.live="rejectCustomMessage" placeholder="Enter details"
                            class="form-control mt-1 mt-md-2" required />
                        @error('rejectCustomMessage') <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <div class="small text-muted">Message will be saved in the log and used as action taken.</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" wire:click="sendRejectMessage" class="btn btn-danger btn-sm"
                        @if($selectedRejectMessage==='' || ($selectedRejectMessage==='other' &&
                        trim($rejectCustomMessage)==='' )) disabled @endif>
                        Send
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- Empty state --}}
    @if($violations->isEmpty())
    <div class="text-center py-8">
        <div class="text-gray-500 text-lg mb-2">No pending violations found</div>
        <div class="text-gray-400 text-sm">Violations will appear here once reported</div>
    </div>
    @endif

    {{ $violations->links() }}
</div>
<script>
    function setupModalListeners() {
        // Remove old listeners to prevent stacking
        const oldHandler = window._pendingReportsModalHandler;
        if (oldHandler) {
            window.removeEventListener('open-approve-modal', oldHandler.openApprove);
            window.removeEventListener('open-reject-modal', oldHandler.openReject);
            window.removeEventListener('close-approve-modal', oldHandler.closeApprove);
            window.removeEventListener('close-reject-modal', oldHandler.closeReject);
        }

        // Define new handlers
        const openApprove = () => {
            const el = document.getElementById('approveMessageModal');
            if (el) new bootstrap.Modal(el).show();
        };

        const openReject = () => {
            const el = document.getElementById('rejectMessageModal');
            if (el) new bootstrap.Modal(el).show();
        };

        const closeApprove = () => {
            const el = document.getElementById('approveMessageModal');
            if (el) {
                const m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                m.hide();
            }
        };

        const closeReject = () => {
            const el = document.getElementById('rejectMessageModal');
            if (el) {
                const m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                m.hide();
            }
        };

        // Attach new listeners
        window.addEventListener('open-approve-modal', openApprove);
        window.addEventListener('open-reject-modal', openReject);
        window.addEventListener('close-approve-modal', closeApprove);
        window.addEventListener('close-reject-modal', closeReject);

        // Store handlers for cleanup next time
        window._pendingReportsModalHandler = {
            openApprove,
            openReject,
            closeApprove,
            closeReject
        };
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', setupModalListeners);
    
    // Reinitialize after wire:navigate
    document.addEventListener('livewire:navigated', setupModalListeners);
</script>