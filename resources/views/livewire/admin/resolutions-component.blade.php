{{-- resources\views\livewire\admin\for-endorsement-component.blade.php --}}
<div>
    <style>
        .accordion-button:focus {
            box-shadow: none;
            outline: none;
        }
        
        .accordion-button:active {
            box-shadow: none;
        }
        
        .accordion-button:focus,
        .accordion-button:hover {
            background-color: transparent;
            color: inherit;
        }
        
        .accordion-button::after {
            filter: none !important;
        }
    </style>

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

    <!-- Generate Report Section in Accordion -->
    <div class="accordion mb-4" id="reportAccordion" wire:ignore.self>
        <div class="accordion-item" wire:ignore.self>
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#reportCollapse" aria-expanded="false" aria-controls="reportCollapse">
                    <i class="bi bi-file-earmark-pdf me-2"></i> Generate For Endorsement Report
                </button>
            </h2>
            <div id="reportCollapse" class="accordion-collapse collapse" data-bs-parent="#reportAccordion" wire:ignore.self>
                <div class="accordion-body p-4">
                    <form wire:submit.prevent="generateEndorsementReport" class="mb-4">
            <!-- Report Type Row -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="endorsementReportType" class="form-label">Report Type</label>
                    <select id="endorsementReportType" class="form-select" wire:model.live="endorsementReportType" required>
                        <option value="day">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="range">Custom Range</option>
                    </select>
                </div>
            </div>

            <!-- Date Range Row (only show when custom range) -->
            @if($endorsementReportType === 'range')
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="endorsementReportStartDate" class="form-label">Start Date</label>
                    <input type="date" id="endorsementReportStartDate" class="form-control"
                        wire:model="endorsementReportStartDate" onfocus="this.showPicker();"
                        onmousedown="event.preventDefault(); this.showPicker();">
                </div>
                <div class="col-md-6">
                    <label for="endorsementReportEndDate" class="form-label">End Date</label>
                    <input type="date" id="endorsementReportEndDate" class="form-control"
                        wire:model="endorsementReportEndDate" onfocus="this.showPicker();"
                        onmousedown="event.preventDefault(); this.showPicker();">
                </div>
            </div>
            @endif

            <!-- Generate Button Row -->
            <div class="row g-3">
                <div class="col-12">
                    <button type="submit" class="btn-add-slot btn btn-primary" wire:loading.attr="disabled" wire:target="generateEndorsementReport">
                        <span wire:loading.remove wire:target="generateEndorsementReport">Generate Report</span>
                        <span wire:loading wire:target="generateEndorsementReport">Generating...</span>
                    </button>
                </div>
            </div>
            @if($isGeneratingReport)
                <div class="mt-2 text-sm text-muted">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Generating report… this runs in background. This page will generate a download link.<br>
                    ⚠️ Please do not refresh or close this page until generation is complete.
                </div>
            @endif
        </form>

        <!-- Polling area (poll only while generating) -->
        <div @if($isGeneratingReport) wire:poll.3s="checkReportStatus" @endif>
            @if($lastGeneratedReportFileName && !$isGeneratingReport)
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-3" style="max-width: 400px;">
                    <div class="flex items-center justify-between" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 class="font-medium text-green-800" style="font-weight: 500; color: #166534; margin: 0 0 0.25rem 0;">Report Generated!</h4>
                            <p class="text-sm text-green-600" style="font-size: 0.875rem; color: #16a34a; margin: 0;">Your endorsement report is ready for download.</p>
                        </div>
                        <a href="{{ $this->downloadUrl }}" class="btn-add-slot btn btn-success" style="white-space: nowrap; margin-left: 1rem;">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                    </div>
                </div>
            @endif
        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <!-- Responsive: Desktop table (hidden on xs) -->
    <div class="table-responsive d-none d-sm-block hidden sm:block">
        <table class="table table-striped custom-table">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Reporter</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">User Type</th>
                    <th class="px-3 py-2 text-left text-sm font-semibold text-gray-700">Date</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Area</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">License Plate</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Violator</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Evidence</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($violations as $violation)
                <tr class="hover:bg-gray-50">
                    {{-- Reporter --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        <div class="font-medium">
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

                    <!-- Date -->
                    <td class="px-3 py-2 text-sm text-gray-700">
                        @if($violation->created_at)
                        @php
                        $tooltip = "";
                        if ($violation->submitted_at) {
                        $tooltip .= "Submitted on: " . $violation->submitted_at->toDayDateTimeString();
                        }
                        if ($violation->approved_at) {
                        $tooltip .= ($tooltip ? "\n" : "") . "Approved on: " .
                        $violation->approved_at->toDayDateTimeString();
                        }
                        if ($violation->endorsed_at) {
                        $tooltip .= ($tooltip ? "\n" : "") . "Endorsed on: " .
                        $violation->endorsed_at->toDayDateTimeString();
                        }
                        if (!$tooltip) {
                        $tooltip = "No additional timestamps";
                        }
                        @endphp

                        <span title="{{ $tooltip }}" class="cursor-pointer">
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
                        @if($violation->area)
                            {{ $violation->area->name }}
                        @elseif($violation->custom_area)
                            {{ $violation->custom_area }}
                        @else
                            N/A
                        @endif
                    </td>

                    {{-- License Plate --}}
                    <td class="px-4 py-2 text-sm">
                        @php
                        $plate = $violation->license_plate ?? null;
                        if (!$plate && $violation->violator && isset($violation->violator->vehicles)) {
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

                    {{-- Violator --}}
                    <td class="px-4 py-2 text-sm">
                        @if($violation->violator)
                        {{ trim($violation->violator->firstname . ' ' . $violation->violator->lastname) }}
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>

                    {{-- Description --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        <div class="max-w-xs truncate" title="{{ $violation->description }}">
                            {{ Str::limit($violation->description, 80) }}
                        </div>
                    </td>

                    {{-- Evidence --}}
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

                        $makeUrl = function ($path) {
                        return $path ? (preg_match('#^https?://#i', $path) ? $path :
                        \Illuminate\Support\Facades\Storage::url($path)) : null;
                        };

                        $reportedUrl = $makeUrl($evidence['reported'] ?? null);
                        $approvedUrl = $makeUrl($evidence['approved'] ?? null);
                        @endphp

                        <div class="d-flex flex-column gap-1">
                            @if($reportedUrl)
                            <a href="{{ $reportedUrl }}" target="_blank"
                                class="text-decoration-underline text-primary">View Reported Evidence</a>
                            @else
                            <span class="text-muted">Reported N/A</span>
                            @endif

                            @if($approvedUrl)
                            <a href="{{ $approvedUrl }}" target="_blank"
                                class="text-decoration-underline text-primary">View Approval Evidence</a>
                            @else
                            <span class="text-muted">Approval N/A</span>
                            @endif
                        </div>
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
                        'first_violation' => 'bg-info text-white',
                        'second_violation' => 'bg-warning text-dark',
                        'third_violation' => 'bg-danger text-white',
                        ];
                        $badgeClass = $statusMap[$status] ?? 'bg-secondary text-white';
                        @endphp

                        @if($status === 'third_violation')
                            <span class="badge rounded-pill {{ $badgeClass }}" style="display: flex; flex-direction: column; align-items: center; padding: 0.5rem;">
                                <div>Third violation</div>
                                <div style="font-size: 0.8em; margin-top: 0.25rem;">For endorsement</div>
                            </span>
                        @else
                            <span class="badge rounded-pill {{ $badgeClass }}" title="Status: {{ $statusText }}"
                                aria-label="Status: {{ $statusText }}">
                                {{ $statusText }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-500 py-6">
    <div class="text-center py-8">
        <div class="text-gray-500 text-lg mb-2">There are currently no reports to be display.</div>
    </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Mobile: Card list (visible only on xs) -->
    <div class="mobile-cards d-block d-sm-none">
        @forelse ($violations as $violation)
        <article class="bg-white border rounded p-3 shadow-sm mb-3">
            <header class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="small text-muted">Reporter</div>
                    <div class="fw-medium small text-dark">
                        {{ $violation->reporter ? $violation->reporter->getKey() : 'N/A' }}
                    </div>
                    <div class="small text-muted">
                        {{ $violation->reporter->firstname ?? '' }}
                        {{ $violation->reporter->lastname ?? '' }}
                    </div>
                </div>


                {{-- status badge --}}
                @php
                $status = $violation->status ?? 'unknown';
                $statusText = ucfirst(str_replace('_', ' ', $status));
                $statusMap = [
                'pending' => 'bg-warning text-dark',
                'rejected' => 'bg-danger text-white',
                'approved' => 'bg-success text-white',
                'for_endorsement' => 'bg-primary text-white',
                'resolved' => 'bg-success text-white',
                'first_violation' => 'bg-info text-white',
                'second_violation' => 'bg-warning text-dark',
                'third_violation' => 'bg-danger text-white',
                ];
                $badgeClass = $statusMap[$status] ?? 'bg-secondary text-white';
                @endphp
                <div class="ms-auto">
                    @if($status === 'third_violation')
                        <span class="badge rounded-pill {{ $badgeClass }}" style="display: flex; flex-direction: column; align-items: center; padding: 0.5rem;">
                            <div>Third violation</div>
                            <div style="font-size: 0.8em; margin-top: 0.25rem;">For endorsement</div>
                        </span>
                    @else
                        <span class="badge rounded-pill {{ $badgeClass }}" title="Status: {{ $statusText }}">
                            {{ $statusText }}
                        </span>
                    @endif
                </div>
            </header>

            <div>
        <div class="mt-3 small text-muted">Reporter Type:</div>
        @php
            $reporter = $violation->reporter;
            $type = 'Unknown';
            $badgeClass = 'bg-secondary text-white';

            if ($reporter) {
                // Check if it's an Admin model
                if ($reporter instanceof \App\Models\Admin) {
                    $type = 'Admin';
                    $badgeClass = 'bg-info text-white';
                }

                // Check if it's a User model
                elseif ($reporter instanceof \App\Models\User) {
                    if (!is_null($reporter->student_id)) {
                        $type = 'Student';
                        $badgeClass = 'bg-primary text-white';
                    } elseif (!is_null($reporter->employee_id)) {
                        $type = 'Employee';
                        $badgeClass = 'bg-success text-white';
                    }
                }
            }
        @endphp

        <span class="badge rounded-pill {{ $badgeClass }}">{{ $type }}</span>
    </div>

            <div class="mt-3 small text-muted">Date</div>
            <div class="small text-dark">
                @if($violation->created_at)
                @php
                $tooltip = "";
                if ($violation->submitted_at) {
                $tooltip .= "Submitted on: " . $violation->submitted_at->toDayDateTimeString();
                }
                if ($violation->approved_at) {
                $tooltip .= ($tooltip ? "\n" : "") . "Approved on: " . $violation->approved_at->toDayDateTimeString();
                }
                if ($violation->endorsed_at) {
                $tooltip .= ($tooltip ? "\n" : "") . "Endorsed on: " . $violation->endorsed_at->toDayDateTimeString();
                }
                if (!$tooltip) {
                $tooltip = "No additional timestamps";
                }
                @endphp

                <span title="{{ $tooltip }}" style="cursor: pointer;">
                    {{ $violation->created_at->format('M j, Y H:i') }}
                </span>
                <div class="small text-muted">
                    ({{ $violation->created_at->diffForHumans() }})
                </div>
                @else
                <span class="text-muted">N/A</span>
                @endif
            </div>

            <div class="mt-2 row g-3 small">
                <div class="col-6">
                    <div class="small text-muted">Area</div>
                    <div class="text-dark">
                        @if($violation->area)
                            {{ $violation->area->name }}
                        @elseif($violation->custom_area)
                            {{ $violation->custom_area }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                <div class="col-6">
                    <div class="small text-muted">License Plate</div>
                    @php
                    $plate = $violation->license_plate ?? null;
                    if (!$plate && $violation->violator && isset($violation->violator->vehicles)) {
                    $firstVehicle = $violation->violator->vehicles->first() ?? null;
                    $plate = $firstVehicle ? $firstVehicle->license_plate : null;
                    }
                    @endphp
                    <div class="text-dark">
                        {{ $plate ?? 'N/A' }}
                    </div>
                </div>

                <div class="col-6">
                    <div class="small text-muted">Violator</div>
                    <div class="text-dark">
                        @if($violation->violator)
                        {{ trim($violation->violator->firstname . ' ' . $violation->violator->lastname) }}
                        @else
                        N/A
                        @endif
                    </div>
                </div>

                <div class="col-6">
                    <div class="small text-muted">Description</div>
                    <div class="text-dark text-truncate" title="{{ $violation->description }}">
                        {{ Str::limit($violation->description, 80) }}
                    </div>
                </div>
            </div>

            {{-- Evidence section --}}
            <div class="mt-3 small">
                @php
                $raw = $violation->evidence;
                if (is_array($raw)) {
                $evidence = $raw;
                } elseif (is_string($raw) && $raw !== '') {
                $decoded = @json_decode($raw, true);
                $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : ['reported' =>
                $raw];
                } else {
                $evidence = [];
                }

                $makeUrl = function ($path) {
                return $path ? (preg_match('#^https?://#i', $path) ? $path :
                \Illuminate\Support\Facades\Storage::url($path)) : null;
                };

                $reportedUrl = $makeUrl($evidence['reported'] ?? null);
                $approvedUrl = $makeUrl($evidence['approved'] ?? null);
                @endphp

                <div class="small text-muted">Evidence</div>
                <div class="mt-1 d-flex flex-column" style="gap: 0.25rem;">
                    @if($reportedUrl)
                    <a href="{{ $reportedUrl }}" target="_blank"
                        class="text-decoration-underline text-primary small">View Reported Evidence</a>
                    @else
                    <span class="text-muted small">Reported N/A</span>
                    @endif

                    @if($approvedUrl)
                    <a href="{{ $approvedUrl }}" target="_blank"
                        class="text-decoration-underline text-primary small">View Approval Evidence</a>
                    @else
                    <span class="text-muted small">Approval N/A</span>
                    @endif
                </div>
            </div>
        </article>
        @empty
    <div class="text-center py-8">
        <div class="text-gray-500 text-lg mb-2">No approved violations found</div>
    </div>
        @endforelse
    </div>



    {{ $violations->links() }}
</div>