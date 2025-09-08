<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\ViolationWeeklyReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendViolationReminders extends Command
{
    protected $signature = 'violations:send-reminders';
    protected $description = 'Send weekly reminder emails to users with 3+ approved violations';

    public function handle()
    {
        $usersWithViolations = DB::table('violations')
            ->select('violator_id', DB::raw('COUNT(*) as violation_count'))
            ->where('status', 'approved')
            ->groupBy('violator_id')
            ->having('violation_count', '>=', 3)
            ->get();

        $this->info("Found {$usersWithViolations->count()} users with 3+ violations");

        foreach ($usersWithViolations as $violationData) {
            $user = User::find($violationData->violator_id);
            
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new ViolationWeeklyReminder($user));
                    
                    $this->info("Sent reminder to: {$user->firstname} {$user->lastname} ({$user->email})");
                    Log::info("Sent weekly violation reminder", [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'violation_count' => $violationData->violation_count
                    ]);
                    
                } catch (\Exception $e) {
                    $this->error("Failed to send email to {$user->email}: " . $e->getMessage());
                    Log::error("Failed to send weekly reminder", [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->info('Violation reminders completed');
    }
}