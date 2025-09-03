<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;

class ViolationAdminComponent extends Component
{
    public $violations;
    public $activeTab = 'pending';

    public function mount()
    {
        $this->loadViolations();
    }

    public function loadViolations()
    {
        // Load all violations with their relationships
        $this->violations = Violation::with(['reporter', 'area'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

public function updateStatus($violationId, $newStatus)
{
    $violation = Violation::find($violationId);

    if ($violation) {
        if (!isset($violation->original_status)) {
            $violation->original_status = $violation->status;
        }

        $violation->status = $newStatus;
        $violation->save();
    }

    // Reload violations after update
    $this->violations = Violation::with(['reporter', 'area'])->get();
}


    public function render()
    {
        return view('livewire.admin.violation-admin-component', [
            'violations' => $this->violations
        ]);
    }
}