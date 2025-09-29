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
                <tr>
                    <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->description }}</td>
                    <td class="px-3 py-2 text-sm text-gray-800">
    {{ $violation->license_plate ?? 'N/A' }}
    @if($violation->vehicle)
        ({{ $violation->vehicle->body_type_model ?? 'Unknown Model' }})
    @endif
</td>

                    <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->action_taken ?? '—' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-800">
                        {{ $violation->submitted_at ? $violation->submitted_at->format('M d, Y h:i A') : '—' }}
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

                            // thumbnail candidate: prefer reported then approved
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
                        <span class="badge bg-{{ $violation->status === 'resolved' ? 'success' : 'warning' }}">
                            {{ ucfirst(str_replace('_', ' ', $violation->status)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-3 text-center text-gray-500">Wow, such empty.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
