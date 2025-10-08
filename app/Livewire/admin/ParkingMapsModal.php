<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ParkingMap; // adjust to your model namespace

class ParkingMapsModal extends Component
{
    public $maps;

    protected $listeners = [
        'refreshMaps' => 'loadMaps',
    ];

    public function mount()
    {
        $this->loadMaps();
    }

    public function loadMaps()
    {
        // Customize ordering/limit if you want (pagination could be added)
        $this->maps = ParkingMap::orderBy('created_at', 'desc')->get();
    }

    /**
     * User clicked a thumbnail to open a map.
     * Emits a global Livewire event 'openMap' with the map id so other components can react,
     * and dispatches a browser event to close the modal.
     */
    public function selectMap($mapId)
    {
        $map = ParkingMap::find($mapId);
        if (! $map) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Map not found.']);
            return;
        }

        // emit event that other components (or parent) can listen to
        $this->dispatch('openMap', $map->id);

        // optionally you can also emit a Livewire event for XHR listeners
        $this->dispatch('close-open-map-modal');

        // If you want to refresh the list after selection:
        // $this->loadMaps();
    }

    public function render()
    {
        return view('livewire.admin.parking-maps-modal');
    }
}
