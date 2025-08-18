<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class UsersTable extends Component
{
    use WithPagination;

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
                $q->where('firstname', 'like', "%$s%")
                    ->orWhere('middlename', 'like', "%$s%")
                    ->orWhere('lastname', 'like', "%$s%")
                    ->orWhere('student_id', 'like', "%$s%")   // for students
                    ->orWhere('employee_id', 'like', "%$s%"); // for employees
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
            $programsQuery->where('department', $this->filterDepartment); // cascade programs by department (optional)
        }
        $programs = $programsQuery->select('program')->distinct()->orderBy('program')->pluck('program');

        return view('livewire.users-table', [
            'users' => $users,
            'departments' => $departments,
            'programs' => $programs,
        ]);
    }
}

