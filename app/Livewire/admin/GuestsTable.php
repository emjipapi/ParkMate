<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GuestsTable extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';
    protected $pageName = 'guestsPage';

    public $search = '';
    public $guestStatus = '';
    public $perPage = 15;
    public $perPageOptions = [15, 25, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage($this->pageName);
    }

    public function updatingSearch()
    {
        $this->resetPage($this->pageName);
    }

    public function render()
    {
        // Include soft-deleted users in the query
        $query = User::query()
            ->withTrashed() // Include deleted users
            ->where(function (Builder $q) {
                $q->whereNull('student_id')
                  ->orWhere('student_id', '')
                  ->orWhere('student_id', '0');
            })
            ->where(function (Builder $q) {
                $q->whereNull('employee_id')
                  ->orWhere('employee_id', '')
                  ->orWhere('employee_id', '0');
            });

        // Search
        if ($this->search !== '') {
            $s = trim($this->search);
            $query->where(function (Builder $q) use ($s) {
                $q->whereRaw("CONCAT_WS(' ', firstname, middlename, lastname) LIKE ?", ["%{$s}%"])
                    ->orWhere('contact_number', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }

        // Status filter - show all by default, filter only if a specific status is selected
        if ($this->guestStatus !== '') {
            if ($this->guestStatus === 'active') {
                $query->whereNull('deleted_at');
            } elseif ($this->guestStatus === 'inactive') {
                $query->whereNotNull('deleted_at');
            }
        }

        // Sort active guests first (deleted_at is null), then by name
        $query->orderByRaw('deleted_at IS NULL DESC')->orderBy('firstname');

        $guests = $query->paginate($this->perPage, pageName: $this->pageName);

        return view('livewire.admin.guests-table', [
            'guests' => $guests,
        ]);
    }
}
