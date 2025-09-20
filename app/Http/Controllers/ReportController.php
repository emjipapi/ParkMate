<?php
namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function generateAttendanceReport(Request $request)
    {
        $reportType = $request->input('reportType', 'week');
        $startDate = $request->input('startDate');
        $endDate   = $request->input('endDate');

        // Decide range as Carbon instances
        if ($reportType === 'week') {
            $start = Carbon::now()->startOfWeek();
            $end   = Carbon::now()->endOfWeek();
        } elseif ($reportType === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
        } else {
            // custom range must be provided
            if (!$startDate || !$endDate) {
                abort(422, 'Custom range requires startDate and endDate');
            }
            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();
        }

        // Fetch logs (include denied_entry if you want denials too)
        $logs = ActivityLog::with(['user', 'area'])
            ->whereIn('action', ['entry', 'exit', 'denied_entry'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->get();

        // Summary (robust)
        $summary = [
            'entries'      => $logs->where('action', 'entry')->count(),
            'exits'        => $logs->where('action', 'exit')->count(),
            'denied'       => $logs->where('action', 'denied_entry')->count(),
            'unique_users' => $logs->where('actor_type', 'user')->unique('actor_id')->count(),
        ];

        $fileName = 'Entry-Exit Report (' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d') . ').pdf';

        $pdf = Pdf::loadView('reports.attendance', [
            'logs'       => $logs,
            'summary'    => $summary,
            'reportType' => ucfirst($reportType),
            'startDate'  => $start->format('Y-m-d'),
            'endDate'    => $end->format('Y-m-d'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }
}
