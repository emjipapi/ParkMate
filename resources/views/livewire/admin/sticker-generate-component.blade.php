{{-- resources/views/livewire/admin/sticker-generate-component.blade.php --}}

<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Generate Parking Stickers</h2>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Configuration Panel --}}
        <div class="space-y-4">
            {{-- Template Selection --}}
            <div>
                <label for="template-select" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Template
                </label>
                <select wire:model.live="selectedTemplateId" id="template-select" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose a template...</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- User Type Filter --}}
            <div>
                <label for="user-type" class="block text-sm font-medium text-gray-700 mb-2">
                    User Type
                </label>
                <select wire:model.live="userType" id="user-type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Users</option>
                    <option value="employee">Employees Only</option>
                    <option value="student">Students Only</option>
                </select>
            </div>

            {{-- Quantity --}}
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                    Quantity
                </label>
                <input type="number" wire:model.live="quantity" id="quantity" min="1" max="1000" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Action Buttons --}}
            <div class="flex space-x-3">
                <button wire:click="generateStickers" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-200"
                        @if(!$selectedTemplateId) disabled @endif>
                    Generate Stickers
                </button>
                
                <button wire:click="togglePreview" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md transition duration-200"
                        @if(!$selectedTemplateId) disabled @endif>
                    {{ $preview ? 'Hide Preview' : 'Show Preview' }}
                </button>
            </div>
        </div>

        {{-- Preview Panel --}}
        <div class="bg-gray-50 rounded-lg p-4">
            @if($selectedTemplate)
                <h3 class="text-lg font-medium text-gray-800 mb-3">Template: {{ $selectedTemplate->name }}</h3>
                
                <div class="bg-white rounded border p-4 text-center">
                    <img src="{{ $selectedTemplate->file_url }}" 
                         alt="{{ $selectedTemplate->name }}" 
                         class="max-w-full h-auto mx-auto rounded shadow-sm"
                         style="max-height: 300px;">
                    
                    <div class="mt-3 text-sm text-gray-600">
                        <p>Dimensions: {{ $selectedTemplate->width }} x {{ $selectedTemplate->height }}px</p>
                        <p>Aspect Ratio: {{ $selectedTemplate->aspect_ratio }}</p>
                    </div>
                </div>

                @if($preview)
                    <div class="mt-4 p-3 bg-blue-50 rounded">
                        <p class="text-sm text-blue-800">
                            Preview mode: This shows how the template will look with sample data overlaid.
                        </p>
                    </div>
                @endif
            @else
                <div class="text-center text-gray-500 py-8">
                    <p>Select a template to see preview</p>
                </div>
            @endif
        </div>
        {{-- Generation Mode Selection --}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Generation Mode</label>
    <div class="flex space-x-4">
        <label class="flex items-center">
            <input type="radio" wire:model.live="generationMode" value="quantity" class="mr-2">
            <span class="text-sm">By Quantity</span>
        </label>
        <label class="flex items-center">
            <input type="radio" wire:model.live="generationMode" value="users" class="mr-2">
            <span class="text-sm">Select Users</span>
        </label>
    </div>
</div>

@if($generationMode === 'users')
    {{-- User Selection --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Users</label>
        <div class="border border-gray-300 rounded-md max-h-40 overflow-y-auto p-2">
            @foreach($users as $user)
                <label class="flex items-center p-1 hover:bg-gray-50">
                    <input type="checkbox" wire:model.live="selectedUserIds" value="{{ $user->id }}" class="mr-2">
                    <span class="text-sm">{{ $user->name }} ({{ $user->employee_id ?? $user->student_id ?? $user->id }})</span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-1">{{ count($selectedUserIds) }} user(s) selected</p>
    </div>
@endif

{{-- Download Section --}}
@if($lastGeneratedZip)
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="font-medium text-green-800">Stickers Generated!</h4>
                <p class="text-sm text-green-600">Your stickers are ready for download.</p>
            </div>
            <button wire:click="downloadStickers" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                Download ZIP
            </button>
        </div>
    </div>
@endif

{{-- Update the generate button to show loading state --}}
<button wire:click="generateStickers" 
        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-200 disabled:opacity-50"
        @if(!$selectedTemplateId || $isGenerating) disabled @endif>
    @if($isGenerating)
        <span class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Generating...
        </span>
    @else
        Generate Stickers
    @endif
</button>
    </div>
</div>