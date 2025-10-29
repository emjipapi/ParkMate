{{-- resources\views\livewire\admin\violation-admin-component.blade.php --}}
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
                    Resolutions
                </a>
            </li>
        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Pending Reports --}}
        @if ($activeTab === 'pending')
            @canaccess('pending_reports')
                <livewire:admin.pending-reports-component />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- Approved Reports --}}
        @elseif ($activeTab === 'approved')
            @canaccess('approved_reports')
                <livewire:admin.approved-reports-component />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- Resolutions --}}
        @elseif ($activeTab === 'endorsement')
            @canaccess('for_endorsement')
                <livewire:admin.resolutions-component />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess
        @endif
    </div>
            @if (!$activeTab)
    <div class="alert alert-warning mt-3 text-center">
        You don't have permission to view any of these tabs.
    </div>
    @endif
</div>