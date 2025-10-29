<?php

namespace App\Jobs;

use App\Models\Violation;
use App\Models\Admin;
use Carbon\Carbon;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Str;

class GenerateEndorsementReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;
    protected $adminId;
    protected $fileName;

    public $tries = 3;
    public $timeout = 300; // 5 minutes for PDF generation with zipping
    public $maxExceptions = 3;
    public $backoff = [5, 10]; // Backoff strategy

    public function __construct($startDate, $endDate, $adminId, $fileName = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->adminId = $adminId;
        $this->fileName = $fileName;
        
        // Lower priority - this job should not starve other jobs
        $this->onQueue('default')->delay(0);
    }

    public function handle()
    {
        try {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();

            // Find violators with at least one third_violation endorsed in the date range
            $thirdViolationViolators = Violation::where('status', 'third_violation')
                ->whereBetween('endorsed_at', [$start, $end])
                ->pluck('violator_id')
                ->unique()
                ->filter()
                ->toArray();

            \Log::info('Third Violation Report: Found violators', [
                'violators' => $thirdViolationViolators,
                'count' => count($thirdViolationViolators),
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);

            if (empty($thirdViolationViolators)) {
                \Log::info('No third violation violators found for endorsement report', [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ]);
                return;
            }

            // Create temp directory for PDFs
            $tempDir = storage_path('app/temp/endorsement-' . Str::random(8));
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zipFileName = $this->fileName ?? sprintf(
                'third-violation-endorsements-%s-to-%s.zip',
                $start->format('Ymd'),
                $end->format('Ymd')
            );

            $pdfCount = 0;

            // Generate one PDF per violator
            foreach ($thirdViolationViolators as $violatorId) {
                \Log::info('Processing violator for PDF', ['violator_id' => $violatorId]);
                
                // Get all violations (first, second, third) for this violator
                $violations = Violation::with(['reporter', 'area', 'violator'])
                    ->where('violator_id', $violatorId)
                    ->whereIn('status', ['first_violation', 'second_violation', 'third_violation'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                \Log::info('Violations found for violator', [
                    'violator_id' => $violatorId,
                    'count' => $violations->count(),
                ]);

                if ($violations->isEmpty()) {
                    continue;
                }

                // Get violator name for filename
                $violator = $violations->first()->violator;
                $violatorName = $violator ? str_replace(' ', '_', strtolower($violator->firstname . '_' . $violator->lastname)) : 'unknown';
                $pdfFileName = $violatorName . '_' . $violatorId . '.pdf';

                $summary = [
                    'total_reports'    => $violations->count(),
                    'unique_reporters' => $violations->pluck('reporter_id')->filter()->unique()->count(),
                ];

                // Mark violation numbers (1st, 2nd, 3rd)
                $violations = $violations->map(function ($v, $index) {
                    $v->violation_number = $index + 1;
                    return $v;
                });

                $pdf = PDF::loadView('reports.endorsement-per-violator', [
                    'violations' => $violations,
                    'summary'    => $summary,
                    'violator'   => $violator,
                    'startDate'  => $start->toDateString(),
                    'endDate'    => $end->toDateString(),
                ])
                ->setPaper('a4')
                ->setOptions([
                    'margin-top' => 15,
                    'margin-bottom' => 15,
                    'margin-left' => 10,
                    'margin-right' => 10,
                    'enable-local-file-access' => true,
                    'no-stop-slow-scripts' => true,
                    'disable-smart-shrinking' => true,
                    'load-error-handling' => 'ignore',
                    'load-media-error-handling' => 'ignore',
                    'disable-javascript' => true,
                    'no-outline' => true,
                ]);

                // Save PDF to temp directory
                $pdfPath = $tempDir . '/' . $pdfFileName;
                $pdf->save($pdfPath);

                \Log::info('PDF created', ['file' => $pdfFileName, 'path' => $pdfPath]);

                $pdfCount++;

                // Free memory
                unset($pdf);
                unset($violations);
                gc_collect_cycles();
            }

            \Log::info('All PDFs created', ['total_pdfs' => $pdfCount, 'temp_dir' => $tempDir]);

            // Create zip file
            $zip = new \ZipArchive();
            $zipPath = storage_path('app/private/reports/' . $zipFileName);
            
            if (!is_dir(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                // Add all PDFs to zip
                $files = scandir($tempDir);
                $filesAdded = 0;
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                        $zip->addFile($tempDir . '/' . $file, $file);
                        $filesAdded++;
                    }
                }
                $zip->close();
                
                \Log::info('Zip created', ['zip_file' => $zipFileName, 'files_in_zip' => $filesAdded]);
            }

            // Clean up temp directory
            $this->deleteDirectory($tempDir);

            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

        } catch (\Exception $e) {
            \Log::error('Third Violation Endorsement Report Generation Failed:', [
                'error' => $e->getMessage(),
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'admin_id' => $this->adminId,
            ]);
            throw $e;
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $this->deleteDirectory($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }
}


