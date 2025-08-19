<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
public function create() {
    return view('user-create');
}

public function store(Request $request) {
    $data = $request->validate([
        'student_id' => 'nullable|string|max:10',
        'employee_id' => 'nullable|string|max:10',
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

    $data['password'] = Hash::make($data['password']);

    if ($request->hasFile('profile_picture')) {
        $data['profile_picture'] = $request->file('profile_picture')->store('profiles', 'public');
    }

    User::create($data);

    return redirect()->route('users')->with('success', 'User created successfully!');
}
}