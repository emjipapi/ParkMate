<div>
    <!-- Generate For Endorsement Report Button -->
<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#endorsementReportModal">
    Generate For Endorsement Report
</button>

<!-- Report Modal -->
<div class="modal fade" id="endorsementReportModal" tabindex="-1" aria-labelledby="endorsementReportModalLabel"
     aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-custom-width">
        <div class="modal-content" x-data="{ type: @entangle('endorsementReportType') }">
            <div class="modal-header">
                <h5 class="modal-title" id="endorsementReportModalLabel">Generate For Endorsement Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form wire:submit.prevent="generateEndorsementReport">
                    <!-- Report Type -->
                    <div class="mb-3">
                        <label for="endorsementReportType" class="form-label">Report Type</label>
                        <select id="endorsementReportType" class="form-select" wire:model="endorsementReportType" required>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="range">Custom Range</option>
                        </select>
                    </div>

                    <!-- Custom Date Range -->
                    <div class="row g-2 mt-2" x-show="type === 'range'" x-cloak>
                        <div class="col-md-6">
                            <label for="endorsementReportStartDate" class="form-label">Start Date</label>
                            <input type="date" id="endorsementReportStartDate" class="form-control"
                                   wire:model="endorsementReportStartDate"
                                   onfocus="this.showPicker();" 
                                   onmousedown="event.preventDefault(); this.showPicker();">
                        </div>
                        <div class="col-md-6">
                            <label for="endorsementReportEndDate" class="form-label">End Date</label>
                            <input type="date" id="endorsementReportEndDate" class="form-control"
                                   wire:model="endorsementReportEndDate"
                                   onfocus="this.showPicker();" 
                                   onmousedown="event.preventDefault(); this.showPicker();">
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        
                        @php
                            // disable when using custom range but dates are missing
                            $disabled = ($endorsementReportType === 'range' && 
                                        (empty($endorsementReportStartDate) || empty($endorsementReportEndDate)));
                        @endphp

<button type="submit"
        class="btn btn-success"
        @if($endorsementReportType === 'range' && (empty($endorsementReportStartDate) || empty($endorsementReportEndDate)))
            disabled title="Please select start and end date for custom range"
        @endif
        wire:loading.attr="disabled"
        wire:target="generateEndorsementReport">
    <span wire:loading.remove wire:target="generateEndorsementReport">Generate</span>
    <span wire:loading wire:target="generateEndorsementReport">Generating...</span>
</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

    <div class="table-responsive">
        <table class="table table-striped custom-table">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Reporter</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Area</th>
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
                        {{ $violation->reporter->firstname ?? '' }} {{ $violation->reporter->lastname ?? '' }}
                    </td>

                    {{-- Area --}}
                    <td class="px-4 py-2 text-sm text-gray-800">
                        {{ $violation->area->name ?? 'N/A' }}
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

                        // Normalize to array (support casted array, JSON string, or plain string)
                        if (is_array($raw)) {
                        $evidence = $raw;
                        } elseif (is_string($raw) && $raw !== '') {
                        $decoded = @json_decode($raw, true);
                        $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                        ? $decoded
                        : ['reported' => $raw];
                        } else {
                        $evidence = [];
                        }

                        // Helper to build URL
                        $makeUrl = function ($path) {
                        return $path
                        ? (preg_match('#^https?://#i', $path)
                        ? $path
                        : \Illuminate\Support\Facades\Storage::url($path))
                        : null;
                        };

                        $reportedUrl = $makeUrl($evidence['reported'] ?? null);
                        $approvedUrl = $makeUrl($evidence['approved'] ?? null);
                        @endphp

                        <div class="d-flex flex-column gap-1">
                            @if($reportedUrl)
                            <a href="{{ $reportedUrl }}" target="_blank" class="text-decoration-underline text-primary">
                                View Reported Evidence
                            </a>
                            @else
                            <span class="text-muted">Reported N/A</span>
                            @endif

                            @if($approvedUrl)
                            <a href="{{ $approvedUrl }}" target="_blank" class="text-decoration-underline text-primary">
                                View Approval Evidence
                            </a>
                            @else
                            <span class="text-muted">Approval N/A</span>
                            @endif
                        </div>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-2 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            For Endorsement
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-500 py-6">
                        <div class="flex flex-col items-center">
                            <i class="bi bi-inbox text-3xl mb-2 text-gray-400"></i>
                            <h6 class="font-medium">No Reports</h6>
                            <p class="text-sm text-gray-400">There are currently no approved reports to display.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $violations->links() }}
</div>