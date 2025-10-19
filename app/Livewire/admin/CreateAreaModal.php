<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ParkingArea;
use App\Models\CarSlot;
use App\Models\MotorcycleCount;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class CreateAreaModal extends Component
{
    public $areaName;
    public $carSlots = 0;
    public $motorcycleSlots = 0;
    public $slotPrefix;
    public $allowStudents = false;
    public $allowEmployees = false;
    public $allowGuests = false;

    protected $rules = [
        'areaName'        => 'required|string|max:255',
        'carSlots'        => 'nullable|integer|min:0',
        'motorcycleSlots' => 'nullable|integer|min:0',
        'slotPrefix'      => 'nullable|alpha|size:1',
    ];

    public function createArea()
    {
        $this->validate();

        $carSlots = (int)($this->carSlots ?? 0);
        $motorcycleSlots = (int)($this->motorcycleSlots ?? 0);

        if ($carSlots == 0 && $motorcycleSlots == 0) {
            $this->addError('carSlots', 'You must create at least one car slot or motorcycle slot.');
            $this->addError('motorcycleSlots', 'You must create at least one car slot or motorcycle slot.');
            return;
        }

        // Check that at least one user type is allowed
        if (!$this->allowStudents && !$this->allowEmployees && !$this->allowGuests) {
            $this->addError('allowStudents', 'You must allow at least one user type (Students, Employees, or Guests).');
            return;
        }

        if ($carSlots > 0 && empty($this->slotPrefix)) {
            $this->addError('slotPrefix', 'Slot prefix is required when creating car slots.');
            return;
        }

        if ($carSlots > 0 && !empty($this->slotPrefix)) {
            $existingPrefix = CarSlot::where('label', 'LIKE', strtoupper($this->slotPrefix) . '%')->exists();
            if ($existingPrefix) {
                $this->addError('slotPrefix', 'This slot prefix is already in use. Please choose a different letter.');
                return;
            }
        }

        DB::beginTransaction();
        try {
            $parkingArea = ParkingArea::create([
                'name' => $this->areaName,
                'allow_students' => $this->allowStudents,
                'allow_employees' => $this->allowEmployees,
                'allow_guests' => $this->allowGuests,
            ]);

            MotorcycleCount::create([
                'area_id'         => $parkingArea->id,
                'total_available' => $motorcycleSlots,
                'available_count' => $motorcycleSlots,
            ]);

            if ($carSlots > 0) {
                for ($i = 1; $i <= $carSlots; $i++) {
                    CarSlot::create([
                        'area_id'     => $parkingArea->id,
                        'label'       => strtoupper($this->slotPrefix) . $i,
                        'occupied'    => false,
                        'disabled'    => false,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->addError('areaName', 'Failed to create parking area: ' . $e->getMessage());
            return;
        }

        // Create activity log entry
$allowedUsers = [];
if ($this->allowStudents) $allowedUsers[] = 'Students';
if ($this->allowEmployees) $allowedUsers[] = 'Employees';
if ($this->allowGuests) $allowedUsers[] = 'Guests';

ActivityLog::create([
    'actor_type' => 'admin',
    'actor_id'   => Auth::guard('admin')->id(),
    'action'     => 'create',
    'details'    => 'Admin ' 
        . Auth::guard('admin')->user()->firstname . ' ' 
        . Auth::guard('admin')->user()->lastname 
        . ' created parking area "' . $this->areaName . '" '
        . 'allowing access to: ' . implode(', ', $allowedUsers) . '.',
]);


        $this->reset(['areaName', 'carSlots', 'motorcycleSlots', 'slotPrefix', 'allowStudents', 'allowEmployees', 'allowGuests']);
        $this->dispatch('areaCreated');
        $this->dispatch('close-create-area-modal');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Parking area created successfully'
        ]);
    }

    public function render()
    {
        return view('livewire.admin.create-area-modal');
    }
}