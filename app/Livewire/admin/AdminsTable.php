<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin;

class AdminsTable extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';

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

        $admins = $query->paginate($this->perPage);

        return view('livewire.admin.admins-table', [
            'admins' => $admins,
        ]);
    }

    public function deleteSelected($ids)
    {
        if (empty($ids)) return;

        Admin::whereIn('admin_id', $ids)->delete();
        $this->resetPage();
        session()->flash('message', count($ids) . ' admin(s) deleted successfully.');
    }
}
