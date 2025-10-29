{{-- resources\views\livewire\admin\users-component.blade.php --}}
<div class="container mt-4">

    {{-- Tabs with horizontal scroll on mobile --}}
    <div class="tabs-container mb-4">
        <ul class="nav nav-tabs border-b flex space-x-2">
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'users' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('users')">
                    Users
                </a>
            </li>

            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'vehicles' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('vehicles')">
                    Vehicles
                </a>
            </li>

            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'admins' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('admins')">
                    Admins
                </a>
            </li>

            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'guests' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('guests')">
                    Guests
                </a>
            </li>
        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Users Tab --}}
        @if ($activeTab === 'users')
            @canaccess('users_table')
                <livewire:admin.users-table />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- Vehicles Tab --}}
        @elseif ($activeTab === 'vehicles')
            @canaccess('vehicles_table')
                <livewire:admin.vehicles-table />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- Admins Tab --}}
        @elseif ($activeTab === 'admins')
            @canaccess('admins_table')
                <livewire:admin.admins-table />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- Guests Tab --}}
        @elseif ($activeTab === 'guests')
            @canaccess('guests_table')
                <livewire:admin.guests-table />
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
