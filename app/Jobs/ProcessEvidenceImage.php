<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEvidenceImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $inputDisk;
    public string $inputPath;
    public string $outputDisk;
    public array  $outputPaths;
    public int    $maxWidth;
    public int    $maxHeight;
    public int    $quality;

    public function __construct(
        string $inputDisk,
        string $inputPath,
        string $outputDisk,
        array $outputPaths,
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 90
    ) {
        $this->inputDisk  = $inputDisk;
        $this->inputPath  = $inputPath;
        $this->outputDisk = $outputDisk;
        $this->outputPaths = $outputPaths;
        $this->maxWidth   = $maxWidth;
        $this->maxHeight  = $maxHeight;
        $this->quality    = $quality;
    }

    public function handle(): void
    {
        Log::info('ProcessEvidenceImage job started', [
            'inputDisk' => $this->inputDisk,
            'inputPath' => $this->inputPath,
            'outputDisk' => $this->outputDisk,
            'outputPaths' => $this->outputPaths,
        ]);

        try {
            if (! Storage::disk($this->inputDisk)->exists($this->inputPath)) {
                Log::warning('ProcessEvidenceImage: input file not found', [
                    'input' => $this->inputPath, 
                    'disk' => $this->inputDisk
                ]);
                return;
            }

            $localPath = Storage::disk($this->inputDisk)->path($this->inputPath);

            // Read image and process it in one chain
            $img = Image::read($localPath)
                // ->orientate() // Removed due to unknown method error
                ->scaleDown($this->maxWidth, $this->maxHeight);

            // Encode to JPEG
            $encoded = $img->toJpeg($this->quality);

            // Write to all requested output paths
            foreach ($this->outputPaths as $outPath) {
                Storage::disk($this->outputDisk)->put($outPath, (string) $encoded);
                Log::info('ProcessEvidenceImage: wrote output', [
                    'disk' => $this->outputDisk, 
                    'path' => $outPath
                ]);
            }

            // Clean up original
            try {
                Storage::disk($this->inputDisk)->delete($this->inputPath);
                Log::info('ProcessEvidenceImage: deleted original input', [
                    'input' => $this->inputPath
                ]);
            } catch (Throwable $ex) {
                Log::warning('ProcessEvidenceImage: failed to delete original input', [
                    'error' => $ex->getMessage()
                ]);
            }

            Log::info('ProcessEvidenceImage job finished successfully', [
                'outputPaths' => $this->outputPaths
            ]);
        } catch (Throwable $e) {
            Log::error('ProcessEvidenceImage job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}