{{-- resources\views\livewire\user\violation-user-component.blade.php --}}
<div class="container mt-4">

    {{-- Tabs --}}
    <div class="tabs-container mb-4">
        <ul class="nav nav-tabs border-b flex space-x-2">
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'my_violations' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('my_violations')">
                    My Violations
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'sent_reports' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('sent_reports')">
                    Sent Reports
                </a>
            </li>
        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        @if ($activeTab === 'my_violations')
            <livewire:user.my-violations-component />
        @elseif ($activeTab === 'sent_reports')
            <livewire:user.sent-violations-component />
        @endif
    </div>
</div>