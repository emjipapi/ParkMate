<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Str; 

class AdminsTable extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';
    protected $pageName = 'adminsPage';

    public $search = '';

        public $perPage = 15; // default
    public $perPageOptions = [15, 25, 50, 100];

    // reset page when perPage changes
public function updatedPerPage()
{
    // explicitly reset the default "page" paginator
    $this->resetPage('page');
}
    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function render()
    {
        $query = Admin::query();

        if ($this->search !== '') {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('firstname', 'like', "%$s%")
                ->orWhere('middlename', 'like', "%$s%")
                  ->orWhere('lastname', 'like', "%$s%");
            });
        }

        $admins = $query->paginate($this->perPage, ['*'], $this->pageName);

        return view('livewire.admin.admins-table', [
            'admins' => $admins,
        ]);
    }

    public function deleteSelected($ids)
    {
        if (empty($ids)) return;

        // Protect Super Admin (id 1) and current logged in admin from deletion
        $current = (int) Auth::id(); // adjust if you use a different guard
        $filtered = array_values(array_filter($ids, fn($id) => (int)$id !== 1 && (int)$id !== $current));

        if (empty($filtered)) {
            session()->flash('message', 'No valid admins selected for deletion.');
            return;
        }

        // Soft-delete
        Admin::whereIn('admin_id', $filtered)->delete();

        // OPTIONAL: Revoke credentials (recommended) — uncomment to enable
        /*
        Admin::whereIn('admin_id', $filtered)
            ->update([
                'password' => bcrypt(Str::random(60)),
                'permissions' => json_encode([]),
            ]);
        */

        $this->resetPage();
        session()->flash('message', count($filtered) . ' admin(s) deleted successfully.');
    }
}
