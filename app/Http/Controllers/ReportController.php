<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            if (!$startDate || !$endDate) {
                abort(422, 'Custom range requires startDate and endDate');
            }
            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();
        }

        $logs = ActivityLog::with(['user', 'area'])
            ->whereIn('action', ['entry', 'exit', 'denied_entry'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->cursor(); // use cursor for memory efficiency

        $fileName = 'Entry-Exit Report (' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d') . ').csv';

        $response = new StreamedResponse(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['Timestamp', 'User Name', 'User ID', 'EPC', 'Area', 'Action']);

            foreach ($logs as $log) {
                $areaName = $log->area->name ?? 'Main Gate';

                // User info
                $userName = '';
                $userId = '-';
                if ($log->actor_type === 'user' && !empty($log->user)) {
                    $userName = trim(($log->user->lastname ?? '') . ', ' . ($log->user->firstname ?? ''));
                    $userId = $log->user->student_id ?? $log->user->employee_id ?? '-';
                } else {
                    $userName = ucfirst($log->actor_type);
                }

                // EPC: use column if available, else extract from details
$epc = $log->epc ?? null;

if (!$epc && !empty($log->details) && preg_match('/epc[:=]?\s*([A-Za-z0-9\-]+)/i', $log->details, $m)) {
    $epc = $m[1];
}

if (!$epc) {
    $epc = '-';
}

                // Action
                $actionLabel = $log->action === 'denied_entry' ? 'Denied' : ucfirst($log->action);

                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $userName,
                    $userId,
                    $epc,
                    $areaName,
                    $actionLabel,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
