<?php

namespace App\Livewire\Admin;

use App\Models\ParkingArea;
use App\Models\ParkingMap;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class MapTemplateManagerComponent extends Component
{
    use WithFileUploads;

    public $selectedMapId;

    public $mapFile;

    public $mapName;

    public $isEditing = false;

    // Area positioning properties
    public $areaConfig = [];

    public $showPreview = false;

    public $previewImageDimensions = ['width' => 0, 'height' => 0];

    // Available parking areas from database
    public $availableParkingAreas = [];

    public ?int $defaultMapId = null;

    protected $listeners = [
        'setPreviewDimensions' => 'setPreviewDimensions',
    ];

    protected $rules = [
        'mapFile' => 'nullable|image|max:10240',
        'mapName' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->loadAvailableParkingAreas();

        $firstMap = ParkingMap::first();
        if ($firstMap) {
            $this->selectedMapId = $firstMap->id;
            $this->loadAreaConfig();
        }
        $this->defaultMapId = ParkingMap::where('is_default', true)->value('id');
    }

    public function loadAvailableParkingAreas()
    {
        $this->availableParkingAreas = ParkingArea::orderBy('name')->get();
    }

    public function selectMap($mapId)
    {
        $this->selectedMapId = $mapId;
        $this->isEditing = false;
        $this->loadAreaConfig();
        $this->showPreview = true;
        $this->updatePreviewDimensions();

        // Set only one active map
        ParkingMap::query()->update(['status' => 'inactive']);
        ParkingMap::where('id', $mapId)->update(['status' => 'active']);
    }

    public function loadAreaConfig()
    {
        $map = ParkingMap::find($this->selectedMapId);
        if ($map && $map->area_config) {
            $this->areaConfig = $map->area_config;
        } else {
            // Start with one default area if none exists
            if (empty($this->areaConfig)) {
                $this->areaConfig = $this->getDefaultAreaConfig();
            }
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
        $map = ParkingMap::find($this->selectedMapId);
        if ($map) {
            $this->mapName = $map->name;
            $this->isEditing = true;
            $this->showPreview = true;
        }
    }

    public function togglePreview()
    {
        $this->showPreview = ! $this->showPreview;
        if ($this->showPreview) {
            $this->updatePreviewDimensions();
        }
    }

    public function saveAreaPositions()
    {
        $map = ParkingMap::find($this->selectedMapId);
        if ($map) {
            $map->update(['area_config' => $this->areaConfig]);
            session()->flash('success', 'Parking area positions saved successfully!');
        }
    }

    public function addParkingArea()
    {
        $newKey = 'area_'.uniqid();
        $this->areaConfig[$newKey] = [
            'x_percent' => 50,
            'y_percent' => 50,
            'parking_area_id' => null,
            'label' => 'New Area',
            'enabled' => true,
            'marker_size' => 24,
            'marker_color' => '#3b82f6',
            'show_label_letter' => true,
        ];
    }

    public function removeParkingArea($key)
    {
        unset($this->areaConfig[$key]);
        $this->saveAreaPositions();
    }

    public function setQuickPosition($areaKey, $preset)
    {
        $presets = [
            'top_left' => ['x_percent' => 15, 'y_percent' => 15],
            'top_right' => ['x_percent' => 85, 'y_percent' => 15],
            'top_center' => ['x_percent' => 50, 'y_percent' => 15],
            'center_left' => ['x_percent' => 15, 'y_percent' => 50],
            'center' => ['x_percent' => 50, 'y_percent' => 50],
            'center_right' => ['x_percent' => 85, 'y_percent' => 50],
            'bottom_left' => ['x_percent' => 15, 'y_percent' => 85],
            'bottom_center' => ['x_percent' => 50, 'y_percent' => 85],
            'bottom_right' => ['x_percent' => 85, 'y_percent' => 85],
        ];

        if (isset($presets[$preset]) && isset($this->areaConfig[$areaKey])) {
            $this->areaConfig[$areaKey]['x_percent'] = $presets[$preset]['x_percent'];
            $this->areaConfig[$areaKey]['y_percent'] = $presets[$preset]['y_percent'];
        }
    }

    public function uploadNewMap()
    {
        $this->validate();

        if ($this->mapFile) {
            $image = Image::read($this->mapFile->getRealPath());
            $width = $image->width();
            $height = $image->height();
            $aspectRatio = round($width / $height, 4);

            $filename = 'parking_map_'.time().'.'.$this->mapFile->getClientOriginalExtension();
            $path = 'parking-maps/'.$filename;

            // Ensure directory exists
            $mapDir = storage_path('app/public/parking-maps');
            if (! file_exists($mapDir)) {
                mkdir($mapDir, 0755, true);
            }

            $image->save(storage_path('app/public/'.$path));

            $map = ParkingMap::create([
                'name' => $this->mapName,
                'file_path' => $path,
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => $aspectRatio,
                'status' => 'active',
                'area_config' => $this->getDefaultAreaConfig(),
            ]);

            // Select the newly created map
            // $this->selectedMapId = $map->id;
            // $this->loadAreaConfig();
            // $this->showPreview = true;

            $this->reset(['mapFile', 'mapName']);
            session()->flash('success', 'Parking map uploaded successfully!');

        }
    }

    public function updateMap()
    {
        $this->validate(['mapName' => 'required|string|max:255']);

        $map = ParkingMap::find($this->selectedMapId);
        if ($map) {
            $map->update([
                'name' => $this->mapName,
                'area_config' => $this->areaConfig,
            ]);
            $this->isEditing = false;
            session()->flash('success', 'Parking map updated successfully!');
        }
    }

    public function deleteMap($mapId)
    {
        $map = ParkingMap::find($mapId);

        if ($map) {
            Storage::disk('public')->delete($map->file_path);
            $map->delete();

            if ($this->selectedMapId == $mapId) {
                $firstMap = ParkingMap::first();
                $this->selectedMapId = $firstMap?->id;
                if ($firstMap) {
                    $this->loadAreaConfig();
                } else {
                    $this->areaConfig = [];
                }
            }

            session()->flash('success', 'Parking map deleted successfully!');
        }
    }

    private function getDefaultAreaConfig()
    {
        return [
            'area_1' => [
                'x_percent' => 50,
                'y_percent' => 50,
                'parking_area_id' => null,
                'label' => 'Parking Area 1',
                'enabled' => true,
                'marker_size' => 24,
                'show_label_letter' => true,
                'label_position' => 'right',
                'label_opacity' => 0.78,
            ],
        ];
    }

    public function setDefaultMapToggle(int $mapId)
    {
        $map = ParkingMap::find($mapId);
        if (! $map) {
            session()->flash('error', 'Map not found.');

            return;
        }

        // current status of clicked map
        $currentlyDefault = (bool) $map->is_default;

        \DB::transaction(function () use ($mapId) {
            ParkingMap::query()->update(['is_default' => false]);
            ParkingMap::where('id', $mapId)->update(['is_default' => true]);
        });
        $this->defaultMapId = $mapId;
        session()->flash('success', 'Default map updated.');

        // Refresh map list & selectedMap so the UI updates immediately
        $this->loadAreaConfig();        // reload area_config for selected map if needed
    }

    public function render()
    {
        $maps = ParkingMap::orderBy('created_at', 'asc')->get();
        $selectedMap = $this->selectedMapId
            ? ParkingMap::find($this->selectedMapId)
            : null;

        return view('livewire.admin.map-template-manager-component', [
            'maps' => $maps,
            'selectedMap' => $selectedMap,
        ]);
    }
}
