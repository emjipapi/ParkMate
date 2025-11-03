<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin;
use App\Models\ActivityLog;
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
    $this->resetPage($this->pageName);
}
    public function updatingSearch()
    {
        $this->resetPage($this->pageName);
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
        $current = (int) Auth::guard('admin')->id();
        $filtered = array_values(array_filter($ids, fn($id) => (int)$id !== 1 && (int)$id !== $current));

        if (empty($filtered)) {
            session()->flash('message', 'No valid admins selected for deletion.');
            return;
        }

        // Get admin details before deletion for logging
        $adminsToDelete = Admin::whereIn('admin_id', $filtered)->get();
        $adminDetails = $adminsToDelete->map(function($admin) {
            return $admin->firstname . ' ' . $admin->lastname . ' (ID: ' . $admin->admin_id . ')';
        })->implode(', ');

        // Soft-delete
        Admin::whereIn('admin_id', $filtered)->delete();

        // Log the activity
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => Auth::guard('admin')->id(),
            'action'     => 'delete',
            'details'    => 'Admin ' 
                . Auth::guard('admin')->user()->firstname . ' ' 
                . Auth::guard('admin')->user()->lastname 
                . ' deleted ' . count($filtered) . ' admin(s): ' . $adminDetails,
        ]);

        // OPTIONAL: Revoke credentials (recommended) — uncomment to enable
        /*
        Admin::whereIn('admin_id', $filtered)
            ->update([
                'password' => bcrypt(Str::random(60)),
                'permissions' => json_encode([]),
            ]);
        */

        $this->resetPage($this->pageName);
        session()->flash('message', count($filtered) . ' admin(s) deleted successfully.');
    }
}
