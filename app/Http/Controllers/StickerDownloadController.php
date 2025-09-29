<?php
// app/Http/Controllers/StickerDownloadController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StickerDownloadController extends Controller
{
    public function download($filename)
    {
        $filePath = 'downloads/' . $filename;
        
        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'File not found');
        }
        
        $fullPath = storage_path('app/public/' . $filePath);
        
        // Stream the file
        return response()->stream(function() use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
            'Content-Length' => filesize($fullPath),
        ]);
    }
}
