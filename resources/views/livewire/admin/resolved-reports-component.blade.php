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
                    @foreach ($violations->where('status', 'resolved') as $violation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-800">
                                {{ $violation->reporter->firstname }} {{ $violation->reporter->lastname }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->area->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $violation->description }}</td>
                            <td class="px-4 py-2 text-sm text-blue-600">
                                @if($violation->evidence)
                                    <a href="{{ asset('storage/' . $violation->evidence) }}" target="_blank"
                                        class="underline hover:text-blue-800">View</a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm font-semibold">
                                <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    Resolved
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>