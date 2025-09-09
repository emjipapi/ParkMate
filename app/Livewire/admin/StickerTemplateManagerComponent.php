<?php
// app/Livewire/Admin/StickerTemplateManagerComponent.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\StickerTemplate;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;

class StickerTemplateManagerComponent extends Component
{
    use WithFileUploads;

    public $selectedTemplateId;
    public $templateFile;
    public $templateName;
    public $isEditing = false;
    
    // Element positioning properties with live preview
    public $elementConfig = [
        'user_id' => ['x_percent' => 10, 'y_percent' => 15, 'font_size' => 18, 'color' => '#000000'],
        'name' => ['x_percent' => 10, 'y_percent' => 30, 'font_size' => 16, 'color' => '#000000'],
        'department' => ['x_percent' => 10, 'y_percent' => 50, 'font_size' => 14, 'color' => '#666666'],
        'expiry' => ['x_percent' => 10, 'y_percent' => 70, 'font_size' => 12, 'color' => '#999999'],
    ];
    
    // Preview sample data
    public $previewData = [
        'user_id' => 'EMP001',
        'name' => 'John Doe',
        'department' => 'IT Department',
        'expiry' => '2025-12-31'
    ];
    
    public $showPreview = false;
    public $previewImageDimensions = ['width' => 0, 'height' => 0]; // Track actual preview dimensions

    protected $rules = [
        'templateFile' => 'nullable|image|max:2048', // 2MB Max
        'templateName' => 'required|string|max:255',
    ];

    public function mount()
    {
        $firstTemplate = StickerTemplate::first();
        $this->selectedTemplateId = $firstTemplate?->id;
        $this->loadElementConfig();
    }

    public function selectTemplate($templateId)
    {
        $this->selectedTemplateId = $templateId;
        $this->isEditing = false;
        $this->loadElementConfig();
        $this->updatePreviewDimensions();
    }

    public function loadElementConfig()
    {
        $template = StickerTemplate::find($this->selectedTemplateId);
        if ($template && $template->element_config) {
            $this->elementConfig = array_merge($this->elementConfig, $template->element_config);
        }
    }

    public function updatePreviewDimensions()
    {
        // This will be called from JavaScript to update the actual preview image dimensions
        $this->dispatch('updatePreviewDimensions');
    }

    public function setPreviewDimensions($width, $height)
    {
        $this->previewImageDimensions = ['width' => $width, 'height' => $height];
    }

    public function startEditing()
    {
        $template = StickerTemplate::find($this->selectedTemplateId);
        if ($template) {
            $this->templateName = $template->name;
            $this->isEditing = true;
        }
    }

    public function togglePreview()
    {
        $this->showPreview = !$this->showPreview;
        if ($this->showPreview) {
            $this->updatePreviewDimensions();
        }
    }

    public function saveElementPositions()
    {
        $template = StickerTemplate::find($this->selectedTemplateId);
        if ($template) {
            $template->update(['element_config' => $this->elementConfig]);
            session()->flash('success', 'Element positions saved successfully!');
        }
    }

    // Quick position presets with better alignment
    public function setQuickPosition($element, $preset)
    {
        $presets = [
            'top_left' => ['x_percent' => 5, 'y_percent' => 10],
            'top_right' => ['x_percent' => 95, 'y_percent' => 10],
            'top_center' => ['x_percent' => 50, 'y_percent' => 10],
            'center_left' => ['x_percent' => 5, 'y_percent' => 50],
            'center' => ['x_percent' => 50, 'y_percent' => 50],
            'center_right' => ['x_percent' => 95, 'y_percent' => 50],
            'bottom_left' => ['x_percent' => 5, 'y_percent' => 90],
            'bottom_center' => ['x_percent' => 50, 'y_percent' => 90],
            'bottom_right' => ['x_percent' => 95, 'y_percent' => 90],
        ];

        if (isset($presets[$preset])) {
            $this->elementConfig[$element]['x_percent'] = $presets[$preset]['x_percent'];
            $this->elementConfig[$element]['y_percent'] = $presets[$preset]['y_percent'];
        }
    }

    public function uploadNewTemplate()
    {
        $this->validate();

        if ($this->templateFile) {
            // Process the uploaded image
            $image = Image::read($this->templateFile->getRealPath());
            $width = $image->width();
            $height = $image->height();
            $aspectRatio = round($width / $height, 4);

            // Generate filename
            $filename = 'template_' . time() . '.' . $this->templateFile->getClientOriginalExtension();
            $path = 'sticker-templates/' . $filename;

            // Save the file
            $image->save(storage_path('app/public/' . $path));

            // Create database record
            StickerTemplate::create([
                'name' => $this->templateName,
                'file_path' => $path,
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => $aspectRatio,
                'status' => 'active'
            ]);

            // Reset form
            $this->reset(['templateFile', 'templateName']);
            session()->flash('success', 'Template uploaded successfully!');
        }
    }

    public function updateTemplate()
    {
        $this->validate(['templateName' => 'required|string|max:255']);

        $template = StickerTemplate::find($this->selectedTemplateId);
        if ($template) {
            $template->update(['name' => $this->templateName]);
            $this->isEditing = false;
            session()->flash('success', 'Template updated successfully!');
        }
    }

    public function deleteTemplate($templateId)
    {
        $template = StickerTemplate::find($templateId);
        if ($template) {
            // Delete the file
            Storage::disk('public')->delete($template->file_path);
            
            // Delete the record
            $template->delete();
            
            // Reset selection if deleted template was selected
            if ($this->selectedTemplateId == $templateId) {
                $this->selectedTemplateId = StickerTemplate::first()?->id;
                $this->loadElementConfig();
            }
            
            session()->flash('success', 'Template deleted successfully!');
        }
    }

    public function render()
    {
        $templates = StickerTemplate::orderBy('created_at', 'desc')->get();
        $selectedTemplate = $this->selectedTemplateId 
            ? StickerTemplate::find($this->selectedTemplateId) 
            : null;

        return view('livewire.admin.sticker-template-manager-component', [
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate
        ]);
    }
}