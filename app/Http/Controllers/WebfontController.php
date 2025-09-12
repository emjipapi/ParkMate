<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class WebfontController extends Controller
{
    public function add(Request $request)
    {
        // Example: run artisan webfonts:Add
        $process = new Process(['php', 'artisan', 'webfonts:Add', 'Roboto']); // You can dynamically replace 'Roboto'
        $process->setTimeout(300); // 5 minutes
        $process->run();

        if (!$process->isSuccessful()) {
            return back()->with('error', $process->getErrorOutput());
        }

        return back()->with('success', $process->getOutput());
    }
}
