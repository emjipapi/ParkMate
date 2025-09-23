<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class UsersTable extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';

    // search + filters
    public $search = '';
    public $filterDepartment = '';
    public $filterProgram = '';

    // mappings like in UserFormCreate
    public $allPrograms = [];     // ['Dept A' => ['Prog 1','Prog 2'], ...]
    public $programToDept = [];   // ['Prog 1' => 'Dept A', ...]
    public $departments = [];     // ['Dept A', 'Dept B', ...]
    public $programs = [];        // currently visible programs
    public $perPage = 15; // default
    public $perPageOptions = [15, 25, 50, 100];

    // reset page when perPage changes
public function updatedPerPage()
{
    // explicitly reset the default "page" paginator
    $this->resetPage('page');
}
    public function mount()
    {
        // load static list from config exactly like UserFormCreate
        $this->allPrograms = config('programs', []);

        // normalize & sort per dept and build reverse map
        foreach ($this->allPrograms as $dept => $plist) {
            sort($plist);
            $this->allPrograms[$dept] = $plist;
            foreach ($plist as $p) {
                $this->programToDept[$p] = $dept;
            }
        }

        $this->departments = array_keys($this->allPrograms);
        sort($this->departments);

        // initial programs = flattened list (all)
        $this->programs = collect($this->allPrograms)->flatten()->sort()->values()->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // call this from blade with wire:change or rely on Livewire update naming
    public function onDepartmentChanged($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            $this->programs = collect($this->allPrograms)->flatten()->sort()->values()->toArray();
            $this->filterProgram = '';
            $this->filterDepartment = '';
            $this->resetPage();
            return;
        }

        $newPrograms = $this->allPrograms[$value] ?? [];
        sort($newPrograms);
        $this->programs = $newPrograms;
        $this->filterDepartment = $value;

        if (!in_array($this->filterProgram, $newPrograms, true)) {
            $this->filterProgram = '';
        }

        $this->resetPage();
    }

    public function onProgramChanged($value)
    {
        $value = trim((string)$value);

        if ($value === '') {
            $this->filterProgram = '';
            $this->filterDepartment = '';
            $this->programs = collect($this->allPrograms)->flatten()->sort()->values()->toArray();
            $this->resetPage();
            return;
        }

        $dept = $this->programToDept[$value] ?? null;

        if ($dept) {
            $this->programs = $this->allPrograms[$dept];
            $this->filterDepartment = $dept;
            $this->filterProgram = $value;
        } else {
            $this->filterProgram = '';
            $this->filterDepartment = '';
        }

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

        $users = $query->paginate($this->perPage);

        return view('livewire.admin.users-table', [
            'users' => $users,
            'departments' => $this->departments,
            'programs' => $this->programs,
        ]);
    }

    public function deleteSelected($ids)
    {
        if (empty($ids)) return;
        User::whereIn('id', $ids)->delete();
        $this->resetPage();
        session()->flash('message', count($ids) . ' user(s) deleted successfully.');
    }
}
