<div class="container mt-4">

    {{-- Search Bar --}}
    <div class="mb-3 position-relative" style="max-width: 300px;">
        <input type="text" class="form-control" placeholder="User_ID / License_Plate Finder..."
            wire:model.live.debounce.300ms="searchTerm">

        {{-- Dynamic Dropdown --}}
        @if(!empty($searchResults))
        <ul class="list-group position-absolute" style="max-height: 200px; overflow-y: auto; z-index: 50; width: 100%;">
            @foreach($searchResults as $result)
            <li class="list-group-item list-group-item-action" wire:click="selectResult({{ $result->id }})">
                {{ $result->user_id }} — {{ $result->license_plate }}
            </li>
            @endforeach
        </ul>
        @endif
    </div>

    {{-- Tabs with horizontal scroll on mobile --}}
    <div class="tabs-container mb-4">
        <ul class="nav nav-tabs border-b flex space-x-2">
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'pending' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('pending')">
                    Pending Reports
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'approved' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('approved')">
                    Approved Reports
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'endorsement' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('endorsement')">
                    For Endorsement
                </a>
            </li>
        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Pending Reports --}}
        @if ($activeTab === 'pending')
        <livewire:admin.pending-reports-component />

        {{-- Approved Reports --}}
        @elseif ($activeTab === 'approved')
        <livewire:admin.approved-reports-component />

        {{-- Resolved Reports --}}
        @elseif ($activeTab === 'endorsement')
        <livewire:admin.for-endorsement-component />
        @endif
    </div>
</div>