<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

class VehiclesTable extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 15;
    public $perPageOptions = [15, 25, 50, 100];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage('page');
    }

    public function render()
    {
        $query = Vehicle::with('user');

        if ($this->search !== '') {
            $s = trim($this->search);
            $query->where(function (Builder $q) use ($s) {
                $q->where('serial_number', 'like', "%{$s}%")
                    ->orWhere('rfid_tag', 'like', "%{$s}%")
                    ->orWhere('type', 'like', "%{$s}%")
                    ->orWhere('body_type_model', 'like', "%{$s}%")
                    ->orWhere('or_number', 'like', "%{$s}%")
                    ->orWhere('cr_number', 'like', "%{$s}%")
                    ->orWhere('license_plate', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($uq) =>
                        $uq->where('firstname', 'like', "%{$s}%")
                           ->orWhere('lastname', 'like', "%{$s}%")
                           ->orWhere('student_id', 'like', "%{$s}%")
                           ->orWhere('employee_id', 'like', "%{$s}%")
                    );
            });
        }

        $vehicles = $query->paginate($this->perPage);

        return view('livewire.admin.vehicles-table', [
            'vehicles' => $vehicles,
        ]);
    }
}
