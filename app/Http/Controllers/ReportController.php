<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use App\Models\User;
use App\Models\Admin;
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

    $violations = Violation::with(['reporter', 'area', 'violator'])
        ->where('status', 'for_endorsement')
        ->whereBetween('endorsed_at', [$start, $end])
        ->orderBy('endorsed_at', 'asc')
        ->get();

    $summary = [
        'total_reports'     => $violations->count(),
        'unique_reporters'  => $violations->pluck('reporter_id')->filter()->unique()->count(),
        'unique_violators'  => $violations->pluck('violator_id')->filter()->unique()->count(),
    ];

    $reportType = 'For Endorsement';

    $fileName = sprintf(
        'endorsement-report-%s-to-%s.pdf',
        $start->format('Ymd'),
        $end->format('Ymd')
    );

    try {
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
        ]);

        return $pdf->download($fileName);
        
    } catch (\Exception $e) {
        \Log::error('PDF generation failed:', [
            'error' => $e->getMessage(),
            'violations_count' => $violations->count(),
            'date_range' => $start->toDateString() . ' to ' . $end->toDateString()
        ]);
        
        return response()->json([
            'error' => 'PDF generation failed. Please check the logs for details.',
            'debug_info' => [
                'violations_found' => $violations->count(),
                'date_range' => $start->toDateString() . ' to ' . $end->toDateString()
            ]
        ], 500);
    }
}
}
