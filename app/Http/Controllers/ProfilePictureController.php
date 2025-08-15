<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ProfilePictureController extends Controller
{
    public function show($filename)
    {
        $path = storage_path('app/profile_pics/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path); // or response()->download($path);
    }
}
