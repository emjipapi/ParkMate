{{-- resources/views/livewire/admin/sticker-generator-component.blade.php --}}
<div class="container mx-auto p-6">
    {{-- Tabs with horizontal scroll on mobile --}}
    <div class="tabs-container mb-4">
        <ul class="nav nav-tabs border-b flex space-x-2">
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'generate' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('generate')" wire:click="saveElementPositions">
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
        {{-- Generate Stickers Tab --}}
        @if ($activeTab === 'generate')
            <livewire:admin.sticker-generate-component />

            {{-- Manage Templates Tab --}}
        @elseif ($activeTab === 'manage')
            <livewire:admin.sticker-template-manager-component />
        @endif
    </div>
</div>