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

class GenerateEndorsementReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;
    protected $adminId;
    protected $fileName;

    public $tries = 3;
    public $timeout = 120; // 2 minutes for PDF generation
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

            $violations = Violation::with(['reporter', 'area', 'violator'])
                ->where('status', 'for_endorsement')
                ->whereBetween('endorsed_at', [$start, $end])
                ->orderBy('endorsed_at', 'asc')
                ->get();

            $summary = [
                'total_reports'    => $violations->count(),
                'unique_reporters' => $violations->pluck('reporter_id')->filter()->unique()->count(),
                'unique_violators' => $violations->pluck('violator_id')->filter()->unique()->count(),
            ];

            $reportType = 'For Endorsement';

            // Use the passed filename if available, otherwise generate one
            $fileName = $this->fileName ?? sprintf(
                'endorsement-report-%s-to-%s.pdf',
                $start->format('Ymd'),
                $end->format('Ymd')
            );

            $pdf = PDF::loadView('reports.endorsement', [
                'violations' => $violations,
                'summary'    => $summary,
                'reportType' => $reportType,
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
                // Reduce resource usage
                'disable-javascript' => true,
                'no-outline' => true,
            ]);

            // Generate PDF output
            $pdfOutput = $pdf->output();

            // Store in storage/app/private/reports/
            $path = 'reports/' . $fileName;
            Storage::disk('private')->put($path, $pdfOutput);

            // Force garbage collection and free memory
            unset($pdf);
            unset($pdfOutput);
            unset($violations);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

        } catch (\Exception $e) {
            \Log::error('Endorsement Report Generation Failed:', [
                'error' => $e->getMessage(),
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'admin_id' => $this->adminId,
            ]);
            throw $e;
        }
    }
}

