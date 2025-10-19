<?php
// app/Livewire/Admin/StickerTemplateManagerComponent.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\StickerTemplate;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class StickerTemplateManagerComponent extends Component
{
    use WithFileUploads;

    public $selectedTemplateId;
    public $templateFile;
    public $templateName;
    public $isEditing = false;
    
    // Element positioning properties with better default spacing
    public $elementConfig = [
    'int' => [
        'x_percent' => 10,
        'y_percent' => 15,
        'font_size' => 18,
        'color' => '#000000',
        'enabled' => true
    ],
];

    // add inside class StickerTemplateManagerComponent
protected $listeners = [
    'setPreviewDimensions' => 'setPreviewDimensions'
];

    
    // Preview sample data
public $previewData = [
    'int' => '123456',
];

    
    public $showPreview = false;
    public $previewImageDimensions = ['width' => 0, 'height' => 0];

    protected $rules = [
        'templateFile' => 'nullable|image|max:10240', // 10 MB max
        'templateName' => 'required|string|max:255',
    ];

    public function mount()
    {
        $firstTemplate = StickerTemplate::first();
        if ($firstTemplate) {
            $this->selectedTemplateId = $firstTemplate->id;
            $this->loadElementConfig();
        }
    }

public function selectTemplate($templateId)
{
    // Update state for UI
    $this->selectedTemplateId = $templateId;
    $this->isEditing = false;
    $this->loadElementConfig();
    $this->showPreview = true; // Show preview when selecting template
    $this->updatePreviewDimensions();

    // Ensure only one active template
    StickerTemplate::query()->update(['status' => 'inactive']);
    StickerTemplate::where('id', $templateId)->update(['status' => 'active']);
}


public function loadElementConfig()
{
    $template = StickerTemplate::find($this->selectedTemplateId);
    if ($template && $template->element_config) {
        $defaultConfig = $this->getDefaultElementConfig();
        $this->elementConfig = array_merge($defaultConfig, $template->element_config);
    }
}

    public function updatePreviewDimensions()
    {
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
            $this->showPreview = true; // Show preview when editing
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
                    ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => Auth::guard('admin')->id(),
            'area_id'    => null, // set if relevant
            'action'     => 'update',
            'details'    => 'Admin ' 
                . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
                . ' updated element positions for sticker template "' . $template->name . '".',
            'created_at' => now(),
        ]);
            session()->flash('success', 'Element positions saved successfully!');
        }
    }

    // Enhanced quick position presets with better spacing
    public function setQuickPosition($element, $preset)
    {
        $presets = [
            'top_left' => ['x_percent' => 8, 'y_percent' => 12],
            'top_right' => ['x_percent' => 92, 'y_percent' => 12],
            'top_center' => ['x_percent' => 50, 'y_percent' => 12],
            'center_left' => ['x_percent' => 8, 'y_percent' => 50],
            'center' => ['x_percent' => 50, 'y_percent' => 50],
            'center_right' => ['x_percent' => 92, 'y_percent' => 50],
            'bottom_left' => ['x_percent' => 8, 'y_percent' => 88],
            'bottom_center' => ['x_percent' => 50, 'y_percent' => 88],
            'bottom_right' => ['x_percent' => 92, 'y_percent' => 88],
        ];

        if (isset($presets[$preset]) && isset($this->elementConfig[$element])) {
            $this->elementConfig[$element]['x_percent'] = $presets[$preset]['x_percent'];
            $this->elementConfig[$element]['y_percent'] = $presets[$preset]['y_percent'];
        }
    }

    // Improved layout presets with proper spacing to avoid cramping
    // public function applyLayoutPreset($preset)
    // {
    //     $layouts = [
    //         'vertical_left' => [
    //             'user_id' => ['x_percent' => 8, 'y_percent' => 15],
    //             'name' => ['x_percent' => 8, 'y_percent' => 35], 
    //             'department' => ['x_percent' => 8, 'y_percent' => 55],
    //             'expiry' => ['x_percent' => 8, 'y_percent' => 75]
    //         ],
    //         'vertical_right' => [
    //             'user_id' => ['x_percent' => 92, 'y_percent' => 15],
    //             'name' => ['x_percent' => 92, 'y_percent' => 35],
    //             'department' => ['x_percent' => 92, 'y_percent' => 55],
    //             'expiry' => ['x_percent' => 92, 'y_percent' => 75]
    //         ],
    //         'centered' => [
    //             'user_id' => ['x_percent' => 50, 'y_percent' => 20],
    //             'name' => ['x_percent' => 50, 'y_percent' => 40],
    //             'department' => ['x_percent' => 50, 'y_percent' => 60],
    //             'expiry' => ['x_percent' => 50, 'y_percent' => 80]
    //         ],
    //         'four_corners' => [
    //             'user_id' => ['x_percent' => 15, 'y_percent' => 15],
    //             'name' => ['x_percent' => 85, 'y_percent' => 15],
    //             'department' => ['x_percent' => 15, 'y_percent' => 85],
    //             'expiry' => ['x_percent' => 85, 'y_percent' => 85]
    //         ],
    //         'horizontal_top' => [
    //             'user_id' => ['x_percent' => 25, 'y_percent' => 12],
    //             'name' => ['x_percent' => 75, 'y_percent' => 12],
    //             'department' => ['x_percent' => 25, 'y_percent' => 88],
    //             'expiry' => ['x_percent' => 75, 'y_percent' => 88]
    //         ],
    //         'stacked_center' => [
    //             'user_id' => ['x_percent' => 50, 'y_percent' => 18],
    //             'name' => ['x_percent' => 50, 'y_percent' => 34],
    //             'department' => ['x_percent' => 50, 'y_percent' => 66],
    //             'expiry' => ['x_percent' => 50, 'y_percent' => 82]
    //         ]
    //     ];

    //     if (isset($layouts[$preset])) {
    //         foreach ($layouts[$preset] as $element => $position) {
    //             if (isset($this->elementConfig[$element])) {
    //                 $this->elementConfig[$element]['x_percent'] = $position['x_percent'];
    //                 $this->elementConfig[$element]['y_percent'] = $position['y_percent'];
    //             }
    //         }
    //     }
    // }

    // Method to distribute elements evenly with proper spacing
    public function distributeVertically()
    {
        $elements = array_keys($this->elementConfig);
        $count = count($elements);
        
        if ($count <= 1) return;
        
        // Use more space with proper margins
        $topMargin = 15;
        $bottomMargin = 15;
        $availableSpace = 100 - $topMargin - $bottomMargin;
        $spacing = $availableSpace / ($count - 1);
        
        foreach ($elements as $index => $element) {
            $this->elementConfig[$element]['y_percent'] = $topMargin + ($spacing * $index);
        }
    }

    public function distributeHorizontally()
    {
        $elements = array_keys($this->elementConfig);
        $count = count($elements);
        
        if ($count <= 1) return;
        
        // Use more space with proper margins
        $leftMargin = 15;
        $rightMargin = 15;
        $availableSpace = 100 - $leftMargin - $rightMargin;
        $spacing = $availableSpace / ($count - 1);
        
        foreach ($elements as $index => $element) {
            $this->elementConfig[$element]['x_percent'] = $leftMargin + ($spacing * $index);
        }
    }

    public function uploadNewTemplate()
    {
        $this->validate();

        if ($this->templateFile) {
            $image = Image::read($this->templateFile->getRealPath());
            $width = $image->width();
            $height = $image->height();
            $aspectRatio = round($width / $height, 4);

            $filename = 'template_' . time() . '.' . $this->templateFile->getClientOriginalExtension();
            $path = 'sticker-templates/' . $filename;

            // Ensure directory exists
            $templateDir = storage_path('app/public/sticker-templates');
            if (!file_exists($templateDir)) {
                mkdir($templateDir, 0755, true);
            }

            $image->save(storage_path('app/public/' . $path));

            $template = StickerTemplate::create([
                'name' => $this->templateName,
                'file_path' => $path,
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => $aspectRatio,
                'status' => 'active',
                'element_config' => $this->getDefaultElementConfig()
            ]);
                    ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => Auth::guard('admin')->id(),
            'area_id'    => null, // set if relevant
            'action'     => 'create',
            'details'    => 'Admin ' 
                . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
                . ' uploaded and created a new sticker template: "' . $template->name . '".',
            'created_at' => now(),
        ]);

            // Select the newly created template
            $this->selectedTemplateId = $template->id;
            $this->loadElementConfig();
            $this->showPreview = true;

            $this->reset(['templateFile', 'templateName']);
            session()->flash('success', 'Template uploaded successfully!');
        }
    }

    public function updateTemplate()
    {
        $this->validate(['templateName' => 'required|string|max:255']);

        $template = StickerTemplate::find($this->selectedTemplateId);
        if ($template) {
            $template->update([
                'name' => $this->templateName,
                'element_config' => $this->elementConfig
            ]);
            $this->isEditing = false;
            session()->flash('success', 'Template updated successfully!');
        }
    }

    public function deleteTemplate($templateId)
    {
        $template = StickerTemplate::find($templateId);
        if ($template) {
            $templateName = $template->name;
            Storage::disk('public')->delete($template->file_path);
            $template->delete();
                    // Log activity
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => Auth::guard('admin')->id(),
            'area_id'    => null, // set if relevant
            'action'     => 'delete',
            'details'    => 'Admin ' 
                . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
                . ' deleted sticker template "' . $templateName . '".',
            'created_at' => now(),
        ]);
            
            
            if ($this->selectedTemplateId == $templateId) {
                $firstTemplate = StickerTemplate::first();
                $this->selectedTemplateId = $firstTemplate?->id;
                if ($firstTemplate) {
                    $this->loadElementConfig();
                } else {
                    $this->elementConfig = $this->getDefaultElementConfig();
                }
            }
            
            session()->flash('success', 'Template deleted successfully!');
        }
    }

private function getDefaultElementConfig()
{
    return [
        'int' => [
            'x_percent' => 10,
            'y_percent' => 15,
            'font_size' => 18,
            'color' => '#000000',
            'enabled' => true
        ],
    ];
}


public function render()
{
    $templates = StickerTemplate::orderBy('created_at', 'asc')->get(); // oldest first
    $selectedTemplate = $this->selectedTemplateId 
        ? StickerTemplate::find($this->selectedTemplateId) 
        : null;

    return view('livewire.admin.sticker-template-manager-component', [
        'templates' => $templates,
        'selectedTemplate' => $selectedTemplate
    ]);
}

}