{{-- resources\views\livewire\admin\sticker-generator-component.blade.php --}}
<div class="container mt-4">
    {{-- Tabs with horizontal scroll on mobile --}}
    <div class="tabs-container mb-4">
        <ul class="nav nav-tabs border-b flex space-x-2">
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'generate' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('generate')">
                    Generate Stickers
                </a>
            </li>

            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'manage' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('manage')">
                    Manage Templates
                </a>
            </li>

        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Pending Reports --}}
        @if ($activeTab === 'generate')
            @canaccess('generate_sticker')
                <livewire:admin.sticker-generate-component />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- Approved Reports --}}
        @elseif ($activeTab === 'manage')
            @canaccess('manage_sticker')
            <livewire:admin.sticker-template-manager-component />
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
