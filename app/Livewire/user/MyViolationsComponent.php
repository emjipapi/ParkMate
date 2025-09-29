<?php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Violation;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MyViolationsComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // filters / UI
    public $startDate = null;
    public $endDate = null;
    public $sortOrder = 'desc'; // 'desc' | 'asc'

    public $perPage = 15;
    public $perPageOptions = [15, 25, 50, 100];
    public $pageName = 'page';

    // reset page when perPage changes
    public function updatedPerPage()
    {
        $this->resetPage($this->pageName);
    }

    // helper to reset page on filter changes (optional but nicer UX)
    public function updatedStartDate()
    {
        $this->resetPage($this->pageName);
    }
    public function updatedEndDate()
    {
        $this->resetPage($this->pageName);
    }
    public function updatedSortOrder()
    {
        $this->resetPage($this->pageName);
    }

    public function render()
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'User not authenticated');
        }

        $violationsQuery = Violation::with('vehicle')
            ->where('violator_id', $user->getKey());

        // date range filters on submitted_at
        $violationsQuery->when($this->startDate, function (Builder $q) {
            $q->where('submitted_at', '>=', Carbon::parse($this->startDate)->startOfDay());
        });

        $violationsQuery->when($this->endDate, function (Builder $q) {
            $q->where('submitted_at', '<=', Carbon::parse($this->endDate)->endOfDay());
        });

        // ordering (submitted_at). If submitted_at is null we still order by created_at as a fallback in UI,
        // but ordering is done on submitted_at for expected behaviour.
        $violations = $violationsQuery
            ->orderBy('submitted_at', $this->sortOrder === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage, ['*'], $this->pageName);

        return view('livewire.user.my-violations-component', [
            'violations' => $violations,
        ]);
    }
}
