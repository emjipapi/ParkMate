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
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'admins' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('admins')">
                    Admins
                </a>
            </li>
        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Pending Reports --}}
        @if ($activeTab === 'users')
            <livewire:admin.users-table />

            {{-- Approved Reports --}}
        @elseif ($activeTab === 'admins')
            <livewire:admin.admins-table />

        @endif
    </div>
</div>