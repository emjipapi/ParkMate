{{-- resources/views/livewire/admin/sticker-template-manager-component.blade.php  --}}

<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manage Sticker Templates</h2>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Template List --}}
        <div class="xl:col-span-1 space-y-4">
            <h3 class="text-lg font-medium text-gray-800">Existing Templates</h3>
            
            {{-- Upload New Template --}}
            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                <h4 class="font-medium text-gray-700">Upload New Template</h4>
                
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Template Name</label>
                    <input type="text" wire:model="templateName" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Enter template name">
                    @error('templateName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Template Image</label>
                    <input type="file" wire:model="templateFile" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @error('templateFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <button wire:click="uploadNewTemplate" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md text-sm transition duration-200"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>Upload Template</span>
                    <span wire:loading>Uploading...</span>
                </button>
            </div>

            {{-- Template List --}}
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($templates as $template)
                    <div class="border rounded-lg p-3 cursor-pointer transition-colors {{ $selectedTemplateId == $template->id ? 'bg-blue-50 border-blue-300' : 'hover:bg-gray-50' }}"
                         wire:click="selectTemplate({{ $template->id }})">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h5 class="font-medium text-gray-800 text-sm">{{ $template->name }}</h5>
                                <p class="text-xs text-gray-500">{{ $template->width }}x{{ $template->height }}px</p>
                                <span class="inline-block px-2 py-1 text-xs rounded {{ $template->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($template->status) }}
                                </span>
                            </div>
                            <button wire:click.stop="deleteTemplate({{ $template->id }})" 
                                    class="text-red-500 hover:text-red-700 p-1"
                                    onclick="return confirm('Are you sure you want to delete this template?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <p>No templates found</p>
                        <p class="text-sm">Upload your first template above</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Template Editor --}}
        <div class="xl:col-span-2">
            @if($selectedTemplate)
                <div class="space-y-4">
                    {{-- Template Info & Actions --}}
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">{{ $selectedTemplate->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $selectedTemplate->width }} x {{ $selectedTemplate->height }}px ({{ $selectedTemplate->aspect_ratio }} ratio)</p>
                        </div>
                        <div class="flex space-x-2">
                            @if($isEditing)
                                <button wire:click="updateTemplate" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                                    Save Changes
                                </button>
                                <button wire:click="$set('isEditing', false)" 
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm">
                                    Cancel
                                </button>
                            @else
                                <button wire:click="startEditing" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                                    Edit Template
                                </button>
                            @endif
                        </div>
                    </div>

{{-- WORKING Template Preview (always rendered on page load) --}}
<div class="bg-gray-50 rounded-lg p-4">
    <div class="flex justify-between items-center mb-3">
        <h4 class="font-medium text-gray-700">Template Preview</h4>
        {{-- preview toggle removed for debugging --}}
    </div>

    <div class="flex justify-center">
        {{-- Percent-based overlays — simple & robust --}}
<div id="template-wrapper-{{ $selectedTemplate->id }}"
     style="position: relative; display: inline-block; border: 2px solid #d1d5db; border-radius: .375rem; overflow: visible; padding: 0;">
    <img src="{{ $selectedTemplate->file_url }}"
         id="template-image-{{ $selectedTemplate->id }}"
         alt="{{ $selectedTemplate->name }}"
         style="display:block; width:100%; height:auto; max-height:500px;">

    {{-- ALWAYS render overlays, but position using percent CSS --}}
    @foreach($elementConfig as $element => $config)
        @php
            $x = $config['x_percent'] ?? 10;
            $y = $config['y_percent'] ?? 10;
            $fontSize = max(8, $config['font_size'] ?? 16);
            $color = $config['color'] ?? '#000';
            $align = $x <= 20 ? 'left' : ($x >= 80 ? 'right' : 'center');
            // transform for alignment: left => translateY(-50%), center => translate(-50%,-50%), right => translateX(-100%) translateY(-50%)
            $transform = $x <= 20 ? 'translateY(-50%)' : ($x >= 80 ? 'translateX(-100%) translateY(-50%)' : 'translate(-50%,-50%)');
        @endphp

        <div class="text-element-{{ $selectedTemplate->id }}"
             data-element="{{ $element }}"
             style="position:absolute;
                    left: {{ $x }}%;
                    top: {{ $y }}%;
                    transform: {{ $transform }};
                    transform-origin: center center;
                    font-size: {{ $fontSize }}px;
                    color: {{ $color }};
                    font-weight: 700;
                    white-space: nowrap;
                    pointer-events: none;
                    user-select: none;
                    z-index: 10;">
            {{ $previewData[$element] ?? strtoupper(str_replace('_',' ',$element)) }}
        </div>
    @endforeach

    {{-- dots also positioned by percent --}}
    @foreach($elementConfig as $element => $config)
        <div class="position-dot-{{ $selectedTemplate->id }}"
             data-element="{{ $element }}"
             style="position:absolute;
                    left: {{ $config['x_percent'] ?? 10 }}%;
                    top: {{ $config['y_percent'] ?? 10 }}%;
                    transform: translate(-50%,-50%);
                    width:10px;height:10px;
                    background:#ef4444;border:2px solid #fff;border-radius:50%;z-index:20;"></div>
    @endforeach
</div>

    </div>

    {{-- Positioning script (single robust module) --}}
    <script>
    (function () {
        // avoid redeclaring on multiple Livewire patches
        if (!window.__stickerTemplateInit) window.__stickerTemplateInit = { inited: true };
        let __stickerTimer = null;

        function initAllImages() {
            document.querySelectorAll('img[id^="template-image-"]').forEach(img => {
                const idMatch = img.id.match(/^template-image-(.+)$/);
                if (!idMatch) return;
                const templateId = idMatch[1];
                attachHandlers(img, templateId);
            });
        }

        function attachHandlers(img, templateId) {
            if (img.dataset.stickerInit === '1') {
                // still run once to update positions
                positionElements(img, templateId);
                return;
            }
            img.dataset.stickerInit = '1';

            img.addEventListener('load', () => positionElements(img, templateId));
            if (img.complete) setTimeout(() => positionElements(img, templateId), 30);
        }

        function positionElements(img, templateId) {
            if (!img || !document.body.contains(img)) return;

            // natural dims might not be ready immediately
            if (!img.naturalWidth || !img.naturalHeight) {
                setTimeout(() => positionElements(img, templateId), 60);
                return;
            }

            const container = img.parentElement;
            if (!container) return;

            const imgRect = img.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const imgWidth = img.offsetWidth;
            const imgHeight = img.offsetHeight;

            const offsetX = imgRect.left - containerRect.left;
            const offsetY = imgRect.top - containerRect.top;

            // Emit intrinsic dims to Livewire (component will receive them)
            if (window.Livewire && typeof Livewire.emit === 'function') {
                Livewire.emit('setPreviewDimensions', img.naturalWidth, img.naturalHeight);
            }

            // Position text overlays
            document.querySelectorAll('.text-element-' + templateId).forEach(el => {
                const xPercent = parseFloat(el.dataset.x) || 0;
                const yPercent = parseFloat(el.dataset.y) || 0;

                const x = offsetX + (xPercent / 100) * imgWidth;
                const y = offsetY + (yPercent / 100) * imgHeight;

                el.style.left = Math.round(x) + 'px';
                el.style.top = Math.round(y) + 'px';

                if (xPercent <= 20) {
                    el.style.transform = 'translateY(-50%)';
                    el.style.transformOrigin = 'left center';
                } else if (xPercent >= 80) {
                    el.style.transform = 'translateX(-100%) translateY(-50%)';
                    el.style.transformOrigin = 'right center';
                } else {
                    el.style.transform = 'translateX(-50%) translateY(-50%)';
                    el.style.transformOrigin = 'center center';
                }
            });

            // Position dots
            document.querySelectorAll('.position-dot-' + templateId).forEach(el => {
                const xPercent = parseFloat(el.dataset.x) || 0;
                const yPercent = parseFloat(el.dataset.y) || 0;

                const x = offsetX + (xPercent / 100) * imgWidth;
                const y = offsetY + (yPercent / 100) * imgHeight;

                el.style.left = Math.round(x) + 'px';
                el.style.top = Math.round(y) + 'px';
                el.style.transform = 'translateX(-50%) translateY(-50%)';
            });
        }

        // Initial run
        document.addEventListener('DOMContentLoaded', () => setTimeout(initAllImages, 30));

        // Re-run after Livewire patches
        document.addEventListener('livewire:update', () => {
            clearTimeout(__stickerTimer);
            __stickerTimer = setTimeout(initAllImages, 80);
        });
        document.addEventListener('livewire:navigated', () => {
            clearTimeout(__stickerTimer);
            __stickerTimer = setTimeout(initAllImages, 80);
        });

        // Window resize -> reposition
        window.addEventListener('resize', () => {
            clearTimeout(__stickerTimer);
            __stickerTimer = setTimeout(initAllImages, 140);
        });
    })();
    </script>

    @if($isEditing)
        <div class="mt-3 text-xs text-gray-600 text-center space-y-1">
            <p><strong>Positioning Guide:</strong> Red dots show exact text positions</p>
            <p><strong>Text Alignment:</strong> Left (0-20%), Center (21-79%), Right (80-100%)</p>
        </div>
    @endif
</div>


                    {{-- Element Configuration --}}
                    <div class="bg-white border rounded-lg p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-medium text-gray-700">Text Elements Configuration</h4>
                            <button wire:click="saveElementPositions" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                                Save Positions
                            </button>
                        </div>
                        
                        {{-- Preview Sample Data --}}
                        <div class="mb-4 p-3 bg-gray-50 rounded">
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Sample Preview Data</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="text" wire:model.live="previewData.user_id" placeholder="User ID" 
                                       class="px-2 py-1 border rounded text-sm">
                                <input type="text" wire:model.live="previewData.name" placeholder="Full Name" 
                                       class="px-2 py-1 border rounded text-sm">
                                <input type="text" wire:model.live="previewData.department" placeholder="Department" 
                                       class="px-2 py-1 border rounded text-sm">
                                <input type="text" wire:model.live="previewData.expiry" placeholder="Expiry Date" 
                                       class="px-2 py-1 border rounded text-sm">
                            </div>
                        </div>

                        {{-- Position and Style Controls --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach($elementConfig as $element => $config)
                                <div class="border rounded p-3">
                                    <h5 class="font-medium text-gray-700 mb-2 capitalize">
                                        {{ str_replace('_', ' ', $element) }} Settings
                                    </h5>
                                    
                                    <div class="space-y-3">
                                        {{-- Position --}}
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">X Position (%)</label>
                                                <input type="number" 
                                                       wire:model.live="elementConfig.{{ $element }}.x_percent" 
                                                       min="0" max="100" step="0.1"
                                                       class="w-full px-2 py-1 border rounded text-sm focus:ring-2 focus:ring-blue-500">
                                                <small class="text-gray-500 text-xs">
                                                    @if($config['x_percent'] <= 15) Left aligned
                                                    @elseif($config['x_percent'] >= 85) Right aligned
                                                    @else Center aligned @endif
                                                </small>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Y Position (%)</label>
                                                <input type="number" 
                                                       wire:model.live="elementConfig.{{ $element }}.y_percent" 
                                                       min="0" max="100" step="0.1"
                                                       class="w-full px-2 py-1 border rounded text-sm focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                        
                                        {{-- Font Size and Color --}}
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Font Size (px)</label>
                                                <input type="number" 
                                                       wire:model.live="elementConfig.{{ $element }}.font_size" 
                                                       min="8" max="72" step="1"
                                                       class="w-full px-2 py-1 border rounded text-sm focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Text Color</label>
                                                <input type="color" 
                                                       wire:model.live="elementConfig.{{ $element }}.color"
                                                       class="w-full h-8 border rounded cursor-pointer">
                                            </div>
                                        </div>

                                        {{-- Quick Position Buttons --}}
                                        <div class="mt-2">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Quick Positions</label>
                                            <div class="grid grid-cols-3 gap-1 text-xs">
                                                <button wire:click="setQuickPosition('{{ $element }}', 'top_left')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-left" type="button">
                                                    ↖ Top Left
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'top_center')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-center" type="button">
                                                    ↑ Top Center
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'top_right')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-right" type="button">
                                                    ↗ Top Right
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'center_left')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-left" type="button">
                                                    ← Left
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'center')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-center" type="button">
                                                    ⊙ Center
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'center_right')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-right" type="button">
                                                    → Right
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'bottom_left')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-left" type="button">
                                                    ↙ Bottom Left
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'bottom_center')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-center" type="button">
                                                    ↓ Bottom Center
                                                </button>
                                                <button wire:click="setQuickPosition('{{ $element }}', 'bottom_right')" 
                                                        class="px-2 py-1 bg-white border rounded hover:bg-blue-50 text-right" type="button">
                                                    ↘ Bottom Right
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Global Layout Presets --}}
                        <div class="mt-6 p-3 bg-blue-50 rounded">
                            <h5 class="text-sm font-medium text-gray-700 mb-3">Layout Presets</h5>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button wire:click="setQuickPosition('user_id', 'top_left'); setQuickPosition('name', 'center_left'); setQuickPosition('department', 'center_left'); setQuickPosition('expiry', 'bottom_left')" 
                                        class="text-xs px-3 py-2 bg-white border rounded hover:bg-gray-100 text-left" type="button">
                                    📄 Vertical Left<br>
                                    <small class="text-gray-500">All left aligned</small>
                                </button>
                                <button wire:click="setQuickPosition('user_id', 'top_right'); setQuickPosition('name', 'center_right'); setQuickPosition('department', 'center_right'); setQuickPosition('expiry', 'bottom_right')" 
                                        class="text-xs px-3 py-2 bg-white border rounded hover:bg-gray-100 text-left" type="button">
                                    📄 Vertical Right<br>
                                    <small class="text-gray-500">All right aligned</small>
                                </button>
                                <button wire:click="setQuickPosition('user_id', 'top_left'); setQuickPosition('name', 'top_right'); setQuickPosition('department', 'bottom_left'); setQuickPosition('expiry', 'bottom_right')" 
                                        class="text-xs px-3 py-2 bg-white border rounded hover:bg-gray-100 text-left" type="button">
                                    ⊞ Four Corners<br>
                                    <small class="text-gray-500">Distributed layout</small>
                                </button>
                                <button wire:click="setQuickPosition('user_id', 'top_center'); setQuickPosition('name', 'center'); setQuickPosition('department', 'center'); setQuickPosition('expiry', 'bottom_center')" 
                                        class="text-xs px-3 py-2 bg-white border rounded hover:bg-gray-100 text-left" type="button">
                                    ⊙ Centered<br>
                                    <small class="text-gray-500">All center aligned</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-16 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="mt-2">Select a template to edit</p>
                </div>
            @endif
        </div>
    </div>
</div>