<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GuestRegistration;
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
        // Query guest registrations with eager loaded relationships
        $query = GuestRegistration::query()
            ->withTrashed()
            ->with(['user' => function ($q) {
                $q->withTrashed()
                  ->whereNull('student_id')
                  ->orWhere('student_id', '')
                  ->orWhere('student_id', '0');
            }, 'guestPass']);

        // Search by guest name, contact number, or license plate
        if ($this->search !== '') {
            $s = trim($this->search);
            $query->where(function (Builder $q) use ($s) {
                $q->whereHas('user', function ($userQ) use ($s) {
                    $userQ->whereRaw("CONCAT_WS(' ', firstname, middlename, lastname) LIKE ?", ["%{$s}%"])
                          ->orWhere('contact_number', 'like', "%{$s}%")
                          ->orWhere('address', 'like', "%{$s}%");
                })
                ->orWhere('license_plate', 'like', "%{$s}%")
                ->orWhere('reason', 'like', "%{$s}%");
            });
        }

        // Status filter
        if ($this->guestStatus !== '') {
            if ($this->guestStatus === 'active') {
                $query->whereHas('guestPass', function ($q) {
                    $q->where('status', 'in_use');
                });
            } elseif ($this->guestStatus === 'inactive') {
                $query->whereHas('guestPass', function ($q) {
                    $q->where('status', '!=', 'in_use');
                });
            }
        }

        // Sort - active guests first, then by creation date (most recent first)
        $query->orderByRaw("(SELECT status FROM guest_passes WHERE guest_passes.id = guest_registrations.guest_pass_id) = 'in_use' DESC")
              ->latest('created_at');

        $guests = $query->paginate($this->perPage, pageName: $this->pageName);

        return view('livewire.admin.guests-table', [
            'guests' => $guests,
        ]);
    }
}
