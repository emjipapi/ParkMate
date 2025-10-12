{{-- resources\views\livewire\admin\users-component.blade.php --}}
<div class="container mt-4">

    {{-- Tabs with horizontal scroll on mobile --}}
    <div class="tabs-container mb-4">
        <ul class="nav nav-tabs border-b flex space-x-2">
            @canaccess("users_table")
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'users' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('users')">
                    Users
                </a>
            </li>
            @endcanaccess
            @canaccess("vehicles_table")
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'vehicles' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('vehicles')">
                    Vehicles
                </a>
            </li>
            @endcanaccess
            @canaccess("admins_table")
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'admins' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('admins')">
                    Admins
                </a>
            </li>
            @endcanaccess
        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Users Tab --}}
        @if ($activeTab === 'users')
        <livewire:admin.users-table />

        {{-- Vehicles Tab --}}
        @elseif ($activeTab === 'vehicles')
        <livewire:admin.vehicles-table />

        {{-- Admins Tab --}}
        @elseif ($activeTab === 'admins')
        <livewire:admin.admins-table />

        @endif
    </div>
    @if (!$activeTab)
    <div class="alert alert-warning mt-3 text-center">
        You don't have permission to view any of these tabs.
    </div>
@endif
</div>