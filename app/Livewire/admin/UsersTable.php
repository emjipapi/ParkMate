<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class UsersTable extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterDepartment = '';
    public $filterProgram = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatedFilterDepartment()
    {
        $this->resetPage(); /* optional: $this->filterProgram = ''; */
    }
    public function updatedFilterProgram()
    {
        $this->resetPage();
    }

public function render()
{
    $query = User::query();

    if ($this->search !== '') {
        $s = $this->search;
        $query->where(function ($q) use ($s) {
            $q->whereRaw("CONCAT_WS(' ', firstname, middlename, lastname) LIKE ?", ["%$s%"])
                ->orWhere('student_id', 'like', "%$s%")
                ->orWhere('employee_id', 'like', "%$s%")
                ->orWhere('year_section', 'like', "%$s%")
                ->orWhere('address', 'like', "%$s%")
                ->orWhere('contact_number', 'like', "%$s%")
                ->orWhere('license_number', 'like', "%$s%")
                ->orWhere('expiration_date', 'like', "%$s%");
        });
    }

    if ($this->filterDepartment !== '') {
        $query->where('department', $this->filterDepartment);
    }

    if ($this->filterProgram !== '') {
        $query->where('program', $this->filterProgram);
    }

    $users = $query->paginate(10);

    // dropdown data
    $departments = User::select('department')->distinct()->orderBy('department')->pluck('department');
    $programsQuery = User::query();
    if ($this->filterDepartment !== '') {
        $programsQuery->where('department', $this->filterDepartment);
    }
    $programs = $programsQuery->select('program')->distinct()->orderBy('program')->pluck('program');

    return view('livewire.admin.users-table', [
        'users' => $users,
        'departments' => $departments,
        'programs' => $programs,
    ]);
}

    public function deleteSelected($ids)
{
    if (empty($ids)) {
        return; // nothing to delete
    }

    // Delete the users
    User::whereIn('id', $ids)->delete();

    // Optional: reset pagination if current page is now empty
    $this->resetPage();

    // Flash success message (optional)
    session()->flash('message', count($ids) . ' user(s) deleted successfully.');
}
}