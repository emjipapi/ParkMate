{{-- resources\views\livewire\admin\activity-log-component.blade.php --}}
<div class="container mt-4">

{{-- Tabs with horizontal scroll on mobile --}}
    <div class="tabs-container mb-4">
        <ul class="nav nav-tabs border-b flex space-x-2">
            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'system' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('system')">
                    System Logs
                </a>
            </li>

            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'entry/exit' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('entry/exit')">
                    Entry/Exit Logs
                </a>
            </li>

            <li class="flex-shrink-0">
                <a class="nav-link px-4 py-2 cursor-pointer no-underline text-black whitespace-nowrap {{ $activeTab === 'unknown' ? 'active font-semibold border-b-2 border-blue-500 text-black' : 'text-gray-600' }}"
                    wire:click="setActiveTab('unknown')">
                    Unknown Tags
                </a>
            </li>
        </ul>
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Pending Reports --}}
        @if ($activeTab === 'system')
            @canaccess('system_logs')
                <livewire:admin.activity-log-system-component />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- Approved Reports --}}
        @elseif ($activeTab === 'entry/exit')
            @canaccess('entry_exit_logs')
                <livewire:admin.activity-log-entry-exit-component />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess

        {{-- For Endorsement --}}
        @elseif ($activeTab === 'unknown')
            @canaccess('unknown_tags')
                <livewire:admin.unknown-tags-component />
            @else
                <div class="alert alert-danger text-center mt-3">
                    You don't have permission to view this tab.
                </div>
            @endcanaccess
        @endif
    </div>
</div>

<script>
    window.addEventListener('clear-query-string', () => {
        const cleanUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
    });
</script>

