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
                    @foreach($violations as $violation)
                        <tr>
                            <td>{{ $violation->reporter->firstname }} {{ $violation->reporter->lastname }}</td>
                            <td>{{ $violation->area->name ?? 'N/A' }}</td>
                            <td>{{ Str::limit($violation->description, 50) }}</td>
                            <td>
                                @if($violation->evidence)
                                    <a href="{{ asset('storage/' . $violation->evidence) }}" target="_blank"
                                        class="text-decoration-underline">View</a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $violation->status === 'resolved' ? 'bg-secondary' : 'bg-success' }}">
                                    {{ ucfirst($violation->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    {{-- Dropdown --}}
                                    <select class="form-select form-select-sm mb-1"
                                        wire:model="violationsActionTaken.{{ $violation->id }}"
                                        @if($violation->status === 'resolved') disabled @endif>
                                        <option value="">Select action</option>
                                        <option value="Warning Issued">Warning Issued</option>
                                        <option value="Fine Imposed">Fine Imposed</option>
                                        <option value="Suspended">Suspended</option>
                                    </select>

                                    {{-- Mark as Resolved --}}
                                    @if($violation->status === 'resolved')
                                        <button class="btn btn-sm btn-secondary" disabled>✓ Resolved</button>
                                    @else
                                        <button wire:click="markResolved({{ $violation->id }})" class="btn btn-sm btn-primary">
                                            Mark as Resolved
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>