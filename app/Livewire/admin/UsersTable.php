<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class UsersTable extends Component
{
    use WithPagination;
    protected string $paginationTheme = 'bootstrap';

    // search + filters
    public $search = '';
    public $userType = '';
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
        $s = trim($this->search);
        $query->where(function (Builder $q) use ($s) {
            $q->whereRaw("CONCAT_WS(' ', firstname, middlename, lastname) LIKE ?", ["%{$s}%"])
                ->orWhere('student_id', 'like', "%{$s}%")
                ->orWhere('employee_id', 'like', "%{$s}%")
                ->orWhere('year_section', 'like', "%{$s}%")
                ->orWhere('address', 'like', "%{$s}%")
                ->orWhere('contact_number', 'like', "%{$s}%")
                ->orWhere('license_number', 'like', "%{$s}%")
                ->orWhere('expiration_date', 'like', "%{$s}%");
        });
    }

    if ($this->filterDepartment !== '') {
        $query->where('department', $this->filterDepartment);
    }

    if ($this->filterProgram !== '') {
        $query->where('program', $this->filterProgram);
    }

    // USER TYPE filter
    $query->when($this->userType === 'student', fn (Builder $q) =>
        $q->whereNotNull('student_id')
          ->where('student_id', '<>', '')
          ->where('student_id', '<>', '0')
    );

    $query->when($this->userType === 'employee', fn (Builder $q) =>
        $q->whereNotNull('employee_id')
          ->where('employee_id', '<>', '')
          ->where('employee_id', '<>', '0')
          ->where(function (Builder $q2) {
              // ensure not also a student (optional, mirrors your earlier logic)
              $q2->whereNull('student_id')->orWhere('student_id', '');
          })
    );

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
public function generateReport()
{
    try {
        // Get all users with their vehicles (excluding soft deleted users)
        $users = User::with(['vehicles' => function($query) {
                $query->orderBy('id');
            }])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        // Prepare CSV data
        $csvData = [];
        
        // CSV Headers - following your specified order
        $headers = [
            'ID',                    // auto-incremented row number
            'User ID',              // employee_id/student_id
            'Serial No.',           // vehicle serial number (from vehicles table)
            'Last Name',
            'First Name', 
            'Middle Name',
            'Created At',
            'Department',
            'Program',
            'Year & Section',
            'Address',
            'Contact No.',
            'License No.',
            'Expiration Date',
            'Email',
            // Vehicle fields
            'Vehicle Type',
            'Body Type/Model',
            'OR Number',
            'CR Number',
            'RFID Tag',
            'License Plate',
            'Vehicle Created At'
        ];
        
        $csvData[] = $headers;
        
        $rowId = 1; // auto-incremented ID
        
        foreach ($users as $user) {
            // Determine User ID (prefer employee_id, fallback to student_id)
            $userId = $user->employee_id ?: $user->student_id;
            
            // Each user has vehicles - create one row per vehicle
            foreach ($user->vehicles as $vehicle) {
                $csvData[] = [
                    $user->id,
                    $userId,
                    $vehicle->serial_number,
                    $user->lastname,
                    $user->firstname,
                    $user->middlename,
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '',
                    $user->department,
                    $user->program,
                    $user->year_section,
                    $user->address,
                    $user->contact_number,
                    $user->license_number,
                    $user->expiration_date,
                    $user->email,
                    // Vehicle data
                    $vehicle->type,
                    $vehicle->body_type_model,
                    $vehicle->or_number,
                    $vehicle->cr_number,
                    $vehicle->rfid_tag,
                    $vehicle->license_plate,
                    $vehicle->created_at ? $vehicle->created_at->format('Y-m-d H:i:s') : ''
                ];
            }
        }

        // Generate CSV content
        $filename = 'users_vehicles_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $handle = fopen('php://temp', 'r+');
        
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);
                ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => Auth::guard('admin')->id(),
            'area_id'    => null,
            'action'     => 'generate',
            'details'    => 'Admin ' 
                . Auth::guard('admin')->user()->firstname . ' ' 
                . Auth::guard('admin')->user()->lastname 
                . ' exported users & vehicles data (' . $filename . ').',
            'created_at' => now(),
        ]);

        // Return CSV download
        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

    } catch (\Exception $e) {
        session()->flash('error', 'Failed to generate report: ' . $e->getMessage());
        return;
    }
}
}
