{{-- resources/views/livewire/admin/sticker-template-manager-component.blade.php --}}

<div class="bg-white rounded-lg shadow-md p-6">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Template List --}}
        <div class="xl:col-span-1 space-y-4">
            <!-- Upload New Template -->
            <div class="mb-4 mt-4">
                <div class="text-black">
                    <h5 class="mb-0">Upload New Template</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 mt-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" wire:model="templateName" class="form-control"
                            placeholder="Enter template name">
                        @error('templateName')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Template Image</label>
                        <input type="file" wire:model="templateFile" accept="image/*" class="form-control">
                        @error('templateFile')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button wire:click="uploadNewTemplate" class="btn-add-slot btn btn-primary"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Upload Template</span>
                            <span wire:loading>Uploading...</span>
                        </button>
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-medium text-gray-800">Existing Templates</h3>

            {{-- Template List --}}
            <div class="d-flex flex-wrap gap-3 mb-5">
                @forelse($templates as $index => $template)
                <div class="card text-center shadow-sm {{ $selectedTemplateId == $template->id ? 'border-primary' : '' }}"
                    style="width: 180px; cursor: pointer; transition: all 0.2s;"
                    wire:click="selectTemplate({{ $template->id }})">

                    <div class="card-body p-2">
                        <h6 class="card-title mb-1 text-truncate">{{ $template->name }}</h6>
                        <small class="text-muted d-block mb-1">{{ $template->width }}x{{ $template->height }}px</small>
                        <span class="badge {{ $selectedTemplateId == $template->id ? 'bg-success' : 'bg-secondary' }}">
                            {{ $selectedTemplateId == $template->id ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="card-footer bg-transparent border-0 p-2">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100"
                            onclick="event.stopPropagation(); if(confirm('Are you sure you want to delete this template? This cannot be undone.')) { @this.deleteTemplate({{ $template->id }}) }"
                            wire:loading.attr="disabled" aria-label="Delete template {{ $template->name }}">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted w-100">
                    <p class="mb-0">No templates found</p>
                    <small>Upload your first template above</small>
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
                        <p class="text-sm text-gray-600">{{ $selectedTemplate->width }} x
                            {{ $selectedTemplate->height }}px ({{ $selectedTemplate->aspect_ratio }} ratio)
                        </p>
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
                            <img src="{{ $selectedTemplate->file_url }}" id="template-image-{{ $selectedTemplate->id }}"
                                alt="{{ $selectedTemplate->name }}"
                                style="display:block; width:100%; height:auto; max-height:500px;">

                            {{-- ALWAYS render overlays, but position using percent CSS --}}
                            @foreach($elementConfig as $element => $config)
                            @if(!empty($config['enabled']))
                            @php
                            $x = $config['x_percent'] ?? 10;
                            $y = $config['y_percent'] ?? 10;
                            $fontSize = max(8, $config['font_size'] ?? 16);

                            // Apply the same conversion as in StickerGeneratorService
                            $previewFontSize = round($fontSize * 0.58);
                            // Ensure minimum readable size
                            $previewFontSize = max(6, $previewFontSize);

                            $color = $config['color'] ?? '#000';
                            $align = $x <= 20 ? 'left' : ($x>= 80 ? 'right' : 'center');
                                $transform = $x <= 20 ? 'translateY(-50%)' : ($x>= 80 ? 'translateX(-100%)
                                    translateY(-50%)' : 'translate(-50%,-50%)');
                                    @endphp

                                    <div class="text-element-{{ $selectedTemplate->id }}" data-element="{{ $element }}"
                                        style="position:absolute;
                                                    left: {{ $x }}%;
                                                    top: {{ $y }}%;
                                                    transform: {{ $transform }};
                                                    font-size: {{ $previewFontSize }}px;
                                                    color: {{ $color }};

                                                    white-space: nowrap;
                                                    z-index: 10;">
                                        {{ $previewData[$element] ?? $element }}

                                    </div>
                                    @endif
                                    @endforeach


                                    {{-- dots also positioned by percent --}}
                                    @foreach($elementConfig as $element => $config)
                                    @if(!empty($config['enabled']))
                                    <div class="position-dot-{{ $selectedTemplate->id }}" data-element="{{ $element }}"
                                        style="position:absolute;
                                                                                        left: {{ $config['x_percent'] ?? 10 }}%;
                                                                                        top: {{ $config['y_percent'] ?? 10 }}%;
                                                                                        transform: translate(-50%,-50%);
                                                                                        width:10px;height:10px;
                                                                                        background:#ef4444;border:2px solid #fff;border-radius:50%;z-index:20;">
                                    </div>
                                    @endif
                                    @endforeach

                        </div>

                    </div>
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
                        <button wire:click="saveElementPositions" class="btn-add-slot btn btn-primary">
                            Save Positions
                        </button>
                    </div>

                    <div class="card-body">
                <div class="row g-4">
                    @foreach($elementConfig as $element => $config)
                    <div class="col-12 col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <div class="form-check mb-2">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       wire:model="elementConfig.{{ $element }}.enabled" 
                                       wire:click="saveElementPositions" 
                                       id="enable-{{ $element }}">
                                <label class="form-check-label small fw-semibold" for="enable-{{ $element }}">
                                    Enable this element
                                </label>
                            </div>

                            <div class="{{ empty($config['enabled']) ? 'opacity-50 pointer-events-none' : '' }}">
                                <h6 class="fw-semibold text-capitalize mb-3">
                                    {{ str_replace('_', ' ', $element) }} Settings
                                </h6>

                                <div class="row g-3">
                                    {{-- X/Y Position --}}
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">X Position (%)</label>
                                        <input type="number" 
                                               class="form-control form-control-sm" 
                                               min="0" max="100" step="0.1" 
                                               wire:model.live="elementConfig.{{ $element }}.x_percent">
                                        <small class="text-muted">
                                            @if($config['x_percent'] <= 15) Left aligned
                                            @elseif($config['x_percent'] >= 85) Right aligned
                                            @else Center aligned @endif
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Y Position (%)</label>
                                        <input type="number" 
                                               class="form-control form-control-sm" 
                                               min="0" max="100" step="0.1" 
                                               wire:model.live="elementConfig.{{ $element }}.y_percent">
                                    </div>

                                    {{-- Font Size & Color --}}
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Font Size (px)</label>
                                        <input type="number" 
                                               class="form-control form-control-sm" 
                                               min="8" max="72" step="1" 
                                               wire:model.live="elementConfig.{{ $element }}.font_size">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Text Color</label>
                                        <input type="color" 
                                               class="form-control form-control-color" 
                                               wire:model.live="elementConfig.{{ $element }}.color">
                                    </div>
                                </div>
                                {{-- Quick Position Buttons --}}
                                <div class="mt-3">
                                    <label class="form-label small d-block mb-1 fw-semibold">Quick Positions</label>
                                    <div class="row g-1">
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start" 
                                                wire:click="setQuickPosition('{{ $element }}', 'top_left')">↖ Top Left</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-center" 
                                                wire:click="setQuickPosition('{{ $element }}', 'top_center')">↑ Top Center</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-end" 
                                                wire:click="setQuickPosition('{{ $element }}', 'top_right')">↗ Top Right</button>
                                        </div>

                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start" 
                                                wire:click="setQuickPosition('{{ $element }}', 'center_left')">← Left</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-center" 
                                                wire:click="setQuickPosition('{{ $element }}', 'center')">⊙ Center</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-end" 
                                                wire:click="setQuickPosition('{{ $element }}', 'center_right')">→ Right</button>
                                        </div>

                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start" 
                                                wire:click="setQuickPosition('{{ $element }}', 'bottom_left')">↙ Bottom Left</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-center" 
                                                wire:click="setQuickPosition('{{ $element }}', 'bottom_center')">↓ Bottom Center</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-end" 
                                                wire:click="setQuickPosition('{{ $element }}', 'bottom_right')">↘ Bottom Right</button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

                  
                </div>
            </div>
            @else
            <div class="text-center py-16 text-gray-500">

                <p class="mt-2">Select a template to edit</p>
            </div>
            @endif
        </div>
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