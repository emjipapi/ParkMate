<div>
    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped custom-table">
            <thead class="table-light">
                <tr>
                    <th class="px-4 py-3 text-start text-sm fw-semibold text-muted">Reporter</th>
                    <th class="px-4 py-3 text-start text-sm fw-semibold text-muted">Area</th>
                    <th class="px-4 py-3 text-start text-sm fw-semibold text-muted">Description</th>
                    <th class="px-4 py-3 text-start text-sm fw-semibold text-muted">Evidence</th>
                    <th class="px-4 py-3 text-start text-sm fw-semibold text-muted">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($violations as $violation)
                    <tr class="table-hover-row">
                        <td class="px-4 py-3 text-sm">
                            {{ $violation->reporter->firstname }} {{ $violation->reporter->lastname }}
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $violation->area->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm">{{ Str::limit($violation->description, 100) }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($violation->evidence)
                                <a href="{{ asset('storage/' . $violation->evidence) }}" target="_blank"
                                    class="text-decoration-underline text-primary">View</a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="badge bg-success rounded-pill">
                                For Endorsement
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-inbox fs-1 mb-2 text-muted"></i>
                                <h6>No Resolved Reports</h6>
                                <p class="mb-0">There are currently no resolved violation reports to display.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $violations->links() }}
</div>

