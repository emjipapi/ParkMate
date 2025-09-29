<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\Violation;
use Illuminate\Support\Facades\Auth;

class MyViolationsComponent extends Component
{

    public function render()
    {
            $user = auth()->user();
if (! $user) {
    abort(403, 'User not authenticated');
}
    $violations = Violation::with('vehicle') // 👈 eager load here
        ->where('violator_id', auth()->id())
        ->orderBy('submitted_at', 'desc')
        ->get();

        return view('livewire.user.my-violations-component', [
            'violations' => $violations
        ]);
    }
}
