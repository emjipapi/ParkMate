<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Violation;
use App\Jobs\GenerateEndorsementReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function generateAttendanceReport(Request $request)
    {
        $reportType = $request->input('reportType', 'week');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // Decide range as Carbon instances
        if ($reportType === 'week') {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        } elseif ($reportType === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        } else {
            if (! $startDate || ! $endDate) {
                abort(422, 'Custom range requires startDate and endDate');
            }
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        }

        $logs = ActivityLog::with(['user', 'area'])
            ->whereIn('action', ['entry', 'exit', 'denied_entry'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->cursor(); // use cursor for memory efficiency

        $fileName = 'Entry-Exit Report ('.$start->format('Y-m-d').' to '.$end->format('Y-m-d').').csv';

        $response = new StreamedResponse(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['Timestamp', 'User Name', 'User ID', 'EPC', 'Vehicle Type', 'Area', 'Action']);

            foreach ($logs as $log) {
                $areaName = $log->area->name ?? 'Main Gate';

                // User info
                $userName = '';
                $userId = '-';
                if ($log->actor_type === 'user' && ! empty($log->user)) {
                    $userName = trim(($log->user->lastname ?? '').', '.($log->user->firstname ?? ''));
                    $userId = $log->user->student_id ?? $log->user->employee_id ?? '-';
                } else {
                    $userName = ucfirst($log->actor_type);
                }

                $epc = '-';
                $type = '-';

                if (! empty($log->details) && preg_match('/\|\s*([A-Za-z0-9]+)\s*-\s*([A-Za-z]+)/i', $log->details, $m)) {
                    $epc = $m[1]; // e.g. 3268191180
                    $type = ucfirst($m[2]); // e.g. Motorcycle
                }

                // Action
                $actionLabel = $log->action === 'denied_entry' ? 'Denied' : ucfirst($log->action);

                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $userName,
                    $userId,
                    $epc,
                    $type,
                    $areaName,
                    $actionLabel,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        return $response;
    }
    public function endorsementReport(Request $request)
    {
        // validate dates
        $request->validate([
            'startDate' => 'required|date',
            'endDate'   => 'required|date|after_or_equal:startDate',
        ]);

        $start = Carbon::parse($request->get('startDate'))->startOfDay();
        $end   = Carbon::parse($request->get('endDate'))->endOfDay();

        $fileName = sprintf(
            'endorsement-report-%s-to-%s-%s.pdf',
            $start->format('Ymd'),
            $end->format('Ymd'),
            Str::random(8)
        );

        // Dispatch job to queue with the filename
        GenerateEndorsementReport::dispatch(
            $start->toDateString(),
            $end->toDateString(),
            Auth::guard('admin')->id(),
            $fileName
        );

        // Redirect to the download endpoint with a small delay built into the download method
        // The download method will check if the file exists and wait if needed
        return redirect()->route('reports.download-endorsement', ['file' => $fileName]);
    }public function downloadEndorsementReport($file)
{
    $path = 'reports/' . $file;
    
    // Wait up to 30 seconds for the file to be generated
    $maxAttempts = 30;
    $attempts = 0;
    
    while (!Storage::disk('private')->exists($path) && $attempts < $maxAttempts) {
        sleep(1);
        $attempts++;
    }
    
    if (!Storage::disk('private')->exists($path)) {
        abort(404, 'Report generation failed or took too long. Please try again.');
    }

    $fullPath = storage_path('app/private/' . $path);
    return response()->download($fullPath, $file);
}
}
