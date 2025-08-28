<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Violation;

class ViolationAdminComponent extends Component
{
    public $violations;

    public function mount() {
        $this->violations = Violation::latest()->get();
    }

    public function updateStatus($id, $status)
    {
        $violation = Violation::find($id);
        if ($violation) {
            $violation->status = $status;
            $violation->save();
        }
        $this->violations = Violation::latest()->get();
    }

    public function render()
    {
        return view('livewire.violation-admin-component', [
            'violations' => $this->violations
        ]);
    }
}
