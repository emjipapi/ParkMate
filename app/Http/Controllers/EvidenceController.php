<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // 5MB
        ]);

        $userId = auth()->id();
        $filename = $userId . '_' . time() . '.' . $request->file('evidence')->getClientOriginalExtension();

        // Save to private disk, inside evidence folder
        $path = $request->file('evidence')->storeAs(
            'evidence',
            $filename,
            'private'
        );

        return back()->with('success', 'Evidence uploaded successfully!');
    }
}
