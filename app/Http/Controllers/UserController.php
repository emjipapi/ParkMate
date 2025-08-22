<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use \App\Models\ActivityLog;

class UserController extends Controller
{
public function create()
{
    $departments = User::select('department')->distinct()->pluck('department');
    $programs = User::select('program')->distinct()->pluck('program');

    return view('user-create', compact('departments', 'programs'));
}


public function store(Request $request)
{
    $data = $request->validate([
    'student_id' => 'nullable',
    'employee_id' => 'nullable',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'rfid_tag' => 'required|string|unique:users,rfid_tag|max:10',
        'firstname' => 'required|string|max:50',
        'middlename' => 'nullable|string|max:50',
        'lastname' => 'required|string|max:50',
        'program' => 'required|string|max:50',
        'department' => 'required|string|max:50',
        'license_number' => 'nullable|string|max:11',
        'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);
if (empty($request->student_id) && empty($request->employee_id)) {
    return back()->withErrors(['id' => 'Please provide either Student ID or Employee ID.'])->withInput();
}

if (!empty($request->student_id) && !empty($request->employee_id)) {
    return back()->withErrors(['id' => 'Please provide only one: Student ID or Employee ID, not both.'])->withInput();
}
    $data['password'] = Hash::make($data['password']);

    if ($request->hasFile('profile_picture')) {
        $data['profile_picture'] = $request->file('profile_picture')->store('profiles', 'public');
    }

     $user = User::create($data);

        ActivityLog::create([
        'actor_type' => 'admin',
        'actor_id'   => auth()->id(), // currently logged-in admin
        'action'     => 'create',
        'details'    => "Admin " . auth()->user()->firstname . " " . auth()->user()->lastname .
                        " created user {$user->firstname} {$user->lastname}",
        'created_at' => now(),
    ]);
    return redirect()->route('users')->with('success', 'User created successfully!');
}



}