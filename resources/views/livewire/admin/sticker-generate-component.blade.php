{{-- resources/views/livewire/admin/sticker-generate-component.blade.php --}}
<div class="bg-white rounded-lg shadow-md p-6">
    {{-- Flash Messages --}}
    {{-- @if (session()->has('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif --}}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Configuration Panel --}}
        <div class="space-y-4">
            {{-- Template Selection --}}
            <div class="mb-3" style="max-width: 250px;">
                <label for="template-select" class="form-label">Select Template</label>
                <select wire:model.live="selectedTemplateId" id="template-select" class="form-select">
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">
                            {{ $template->name }} ({{ $template->width }}x{{ $template->height }}px)
                        </option>
                    @endforeach
                </select>
            </div>



            {{-- User Type Filter
            <div class="mb">
                <label for="user-type" class="block text-sm font-medium text-gray-700 mb-2">
                    User Type
                </label>
                <select wire:model.live="userType" id="user-type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Users</option>
                    <option value="employee">Employees Only</option>
                    <option value="student">Students Only</option>
                </select>
            </div> --}}

            {{-- Number Range --}}
            <div class="mb-3" style="max-width: 450px;">
                <label for="number-range" class="form-label">
                    Sticker Numbers
                </label>
                <input type="text" wire:model.live="numberRange" id="number-range" placeholder="e.g. 1,2,5-10,20"
                    class="form-control @error('numberRange') is-invalid @enderror">

                <div class="form-text">
                    Enter comma-separated numbers or ranges (e.g. <code>1,2,5-10,20</code>).
                    <br>
                    <strong>Maximum: 4999 stickers per generation</strong>
                </div>

                @error('numberRange')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>



            <!-- Generate button -->
            <div class="mb-3">
                <button wire:click="generateStickers" wire:loading.attr="disabled" wire:target="generateStickers"
                    class="btn-add-slot btn btn-primary" @if(!$selectedTemplateId) disabled @elseif($isGenerating)
                    disabled @endif>
                    <span wire:loading.remove wire:target="generateStickers">Generate Stickers</span>
                    <span wire:loading wire:target="generateStickers">Queuing...</span>
                </button>
                <button wire:click="togglePreview" class="btn-add-slot btn btn-primary" @if(!$selectedTemplateId)
                disabled @endif>
                    {{ $preview ? 'Hide Preview' : 'Show Preview' }}
                </button>
                @if($isGenerating)
                    <div class="mt-2 text-sm text-muted">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Generating stickers… this runs in background. This page will generate a download link.<br>
                        ⚠️ Please do not refresh or close this page until generation is complete.
                    </div>
                @endif
            </div>

            <!-- Polling area (poll only while a generationKey exists or isGenerating) -->
            <div @if($generationKey) wire:poll.3s="checkGenerationStatus" @endif>

                @if($lastGeneratedZip)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-3" style="max-width: 400px;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-green-800">Stickers Generated!</h4>
                                <p class="text-sm text-green-600">Your stickers are ready for download.</p>
                            </div>
                            {{-- Change from wire:click to regular href --}}
                            <a href="{{ $this->downloadUrl }}" class="btn-add-slot btn btn-primary">
                                Download ZIP
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Preview Panel - MATCHED TO TEMPLATE MANAGER ACCURACY --}}
        <div class="bg-white rounded border p-4 text-center">
            <div style="position: relative; display: inline-block; max-width: 100%;">
                @if($selectedTemplate)
                    <div id="template-wrapper-{{ $selectedTemplate->id }}"
                        style="position: relative; display: inline-block; max-width:100%; border: 2px solid #d1d5db; border-radius: .375rem; overflow: visible; padding: 0;">

                        <!-- Background image -->
                        <img src="{{ $selectedTemplate->file_url }}" alt="Preview Template"
                            style="display:block; width:100%; height:auto; max-height:500px;">

                        {{-- element config parsing --}}
                        @php
                            $elements = [];
                            $raw = $selectedTemplate->element_config ?? ($selectedTemplate->elementConfig ?? null);
                            if (is_array($raw)) {
                                $elements = $raw;
                            } elseif ($raw) {
                                $elements = json_decode($raw, true) ?: [];
                            }

                            // Match your elementConfig keys
                            $previewData = [
                                'int' => '123456', // sample sticker number
                                'user_id' => 'user_id',
                                'name' => 'name',
                                'department' => 'department',
                                'expiry' => 'expiry'
                            ];
                        @endphp

                        {{-- MATCHED positioning logic from template manager --}}
                        @if($preview)
                            @foreach($elements as $elementKey => $cfg)
                                @if(!empty($cfg['enabled']))
                                    @php
                                        // Use exact same logic as template manager
                                        $x = $cfg['x_percent'] ?? 10;
                                        $y = $cfg['y_percent'] ?? 10;
                                        $fontSize = max(8, $cfg['font_size'] ?? 16);

                                        // MATCH the exact conversion from template manager
                                        $previewFontSize = round($fontSize * 0.58);
                                        // Ensure minimum readable size - same as template manager
                                        $previewFontSize = max(6, $previewFontSize);

                                        $color = $cfg['color'] ?? '#000';

                                        // EXACT same alignment and transform logic as template manager
                                        $align = $x <= 20 ? 'left' : ($x >= 80 ? 'right' : 'center');
                                        $transform = $x <= 20 ? 'translateY(-50%)' : ($x >= 80 ? 'translateX(-100%) translateY(-50%)' :
                                            'translate(-50%,-50%)');

                                        $text = $previewData[$elementKey] ?? ($cfg['sample_text'] ?? $elementKey);
                                    @endphp

                                    <div style="
                                                                            position: absolute;
                                                                            left: {{ $x }}%;
                                                                            top: {{ $y }}%;
                                                                            transform: {{ $transform }};
                                                                            font-size: {{ $previewFontSize }}px;
                                                                            color: {{ $color }};
                                                                            white-space: nowrap;
                                                                            z-index: 10;
                                                                        ">
                                        {{ $text }}
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                @else
                    <p class="text-gray-500 italic">No template selected or available. Please upload one.</p>
                @endif
            </div>

            @if($selectedTemplate)
                <div class="mt-3 text-sm text-gray-600">
                    <p>Dimensions: {{ $selectedTemplate->width }} x {{ $selectedTemplate->height }}px</p>
                    <p>Aspect Ratio: {{ $selectedTemplate->aspect_ratio }}</p>
                </div>
            @else
                <div class="mt-3 text-sm text-gray-500 italic">
                    No template selected.
                </div>
            @endif
        </div>



        {{-- Generation Mode Selection --}}
        {{-- <div>
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
                <form method="POST" action="{{ route('webfonts.add') }}">
                    @csrf
                    <button type="submit">Add Font</button>
                </form>
            </div>
        </div>
        @if($generationMode === 'users')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Users</label>
            <div class="border border-gray-300 rounded-md max-h-40 overflow-y-auto p-2">
                @foreach($users as $user)
                <label class="flex items-center p-1 hover:bg-gray-50">
                    <input type="checkbox" wire:model.live="selectedUserIds" value="{{ $user->id }}" class="mr-2">
                    <span class="text-sm">{{ $user->name }}
                        ({{ $user->employee_id ?? $user->student_id ?? $user->id }})</span>
                </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ count($selectedUserIds) }} user(s) selected</p>
        </div>
        @endif --}}
    </div>
</div>